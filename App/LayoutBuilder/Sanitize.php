<?php
namespace App\LayoutBuilder;

/**
 * LayoutBuilder implementation: Sanitize.
 */
trait Sanitize
{
    private static function id(mixed $existing): string
    {
        $existing = is_string($existing) ? trim($existing) : '';
        if ($existing !== '' && preg_match('/^[a-zA-Z0-9_\-]{4,40}$/', $existing)) {
            return $existing;
        }
        try {
            return 'el_' . bin2hex(random_bytes(6));
        } catch (\Throwable $e) {
            return 'el_' . uniqid();
        }
    }

    private static function safeUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '' || $url === '#') {
            return $url === '#' ? '#' : '';
        }
        if (str_starts_with($url, '/') || str_starts_with($url, '#')) {
            return mb_substr($url, 0, 500);
        }
        if (preg_match('#^https?://#i', $url) && filter_var($url, FILTER_VALIDATE_URL)) {
            return mb_substr($url, 0, 500);
        }
        return '';
    }

    /**
     * YouTube / Vimeo / HTTPS (or same-origin) media file. Embed src is built here — never pass the raw URL to iframe.
     *
     * @return array{kind: string, id: string, src: string}|null
     */
    private static function parseVideoUrl(string $url): ?array
    {
        $url = self::safeUrl($url);
        if ($url === '' || $url === '#' || preg_match('/[<>"\']/', $url)) {
            return null;
        }
        if (str_starts_with($url, '/') && !str_starts_with($url, '//')) {
            $path = (string) (parse_url($url, PHP_URL_PATH) ?: $url);
            if (preg_match('/\.(mp4|webm|ogg)$/i', $path)) {
                return ['kind' => 'file', 'id' => '', 'src' => $url];
            }
            return null;
        }
        $parts = parse_url($url);
        if (!is_array($parts) || empty($parts['host']) || empty($parts['scheme'])) {
            return null;
        }
        if (strtolower((string) $parts['scheme']) !== 'https' && strtolower((string) $parts['scheme']) !== 'http') {
            return null;
        }
        $host = strtolower((string) $parts['host']);
        if (str_starts_with($host, 'www.')) {
            $host = substr($host, 4);
        }
        $path = (string) ($parts['path'] ?? '');

        if (in_array($host, ['youtube.com', 'm.youtube.com', 'youtube-nocookie.com', 'youtu.be'], true)) {
            $id = '';
            if ($host === 'youtu.be') {
                $id = explode('/', trim($path, '/'))[0] ?? '';
            } else {
                parse_str((string) ($parts['query'] ?? ''), $q);
                if (!empty($q['v'])) {
                    $id = (string) $q['v'];
                } elseif (preg_match('#^/(embed|shorts|live)/([A-Za-z0-9_-]{11})#', $path, $m)) {
                    $id = $m[2];
                }
            }
            if (!preg_match('/^[A-Za-z0-9_-]{11}$/', $id)) {
                return null;
            }
            return ['kind' => 'youtube', 'id' => $id, 'src' => 'https://www.youtube-nocookie.com/embed/' . $id];
        }

        if (in_array($host, ['vimeo.com', 'player.vimeo.com'], true)) {
            if ($host === 'player.vimeo.com') {
                if (!preg_match('#^/video/(\d{6,12})$#', $path, $m)) {
                    return null;
                }
                $id = $m[1];
            } elseif (!preg_match('#^/(\d{6,12})(?:/|$)#', $path, $m)) {
                return null;
            } else {
                $id = $m[1];
            }
            return ['kind' => 'vimeo', 'id' => $id, 'src' => 'https://player.vimeo.com/video/' . $id];
        }

        if (strtolower((string) $parts['scheme']) !== 'https') {
            return null;
        }
        if (preg_match('/\.(mp4|webm|ogg)$/i', $path)) {
            return ['kind' => 'file', 'id' => '', 'src' => $url];
        }
        return null;
    }

    /** Background-image URLs: no quotes/parentheses that could break out of url("…"). */
    private static function safeCssUrl(string $url): string
    {
        $url = self::safeUrl($url);
        if ($url === '' || $url === '#') {
            return '';
        }
        if (str_contains($url, '..') || preg_match('/[\\\\\'"()<>\\s]/', $url)) {
            return '';
        }
        return $url;
    }

    private static function safeOpacity(mixed $value): string
    {
        if ($value === '' || $value === null) {
            return '';
        }
        if (!is_numeric($value)) {
            return '';
        }
        $n = (int) $value;
        if ($n < 0) {
            $n = 0;
        }
        if ($n > 80) {
            $n = 80;
        }
        return (string) $n;
    }

    private static function safeColor(string $color): string
    {
        $color = trim($color);
        if ($color === '') {
            return '';
        }
        $token = strtolower($color);
        if (isset(self::COLOR_TOKENS[$token])) {
            return $token;
        }
        if (preg_match('/^var\(\s*(--pub-[a-z-]+)\s*\)$/i', $color, $m)) {
            $var = strtolower($m[1]);
            foreach (self::COLOR_TOKENS as $key => $cssVar) {
                if ($cssVar === $var) {
                    return $key;
                }
            }
            return '';
        }
        if (preg_match('/^#[0-9a-fA-F]{3,8}$/', $color)) {
            return $color;
        }
        return '';
    }

    /** Stored token or hex → CSS color for the stylesheet. */
    private static function colorCssValue(string $stored): string
    {
        $stored = trim($stored);
        if ($stored === '') {
            return '';
        }
        if (isset(self::COLOR_TOKENS[$stored])) {
            return 'var(' . self::COLOR_TOKENS[$stored] . ')';
        }
        if (preg_match('/^#[0-9a-fA-F]{3,8}$/', $stored)) {
            return $stored;
        }
        return '';
    }

    private static function safeSpacing(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }
        // e.g. 1rem, 16px, 1.5rem 2rem
        if (preg_match('/^(\d+(\.\d+)?(px|rem|em|%)\s*){1,4}$/', $value)) {
            return mb_substr($value, 0, 40);
        }
        return '';
    }

    private static function safeFontSize(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }
        if (preg_match('/^\d+(\.\d+)?(px|rem|em)$/', $value)) {
            return $value;
        }
        return '';
    }

    private static function safeHeight(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }
        if (preg_match('/^\d+(\.\d+)?(px|rem|em|%|vh|vw)$/', $value)) {
            return mb_substr($value, 0, 20);
        }
        return '';
    }

    private static function safeRadius(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }
        if (preg_match('/^(\d+(\.\d+)?(px|rem|em|%)\s*){1,4}$/', $value)) {
            return mb_substr($value, 0, 40);
        }
        return '';
    }

    private static function safeBorderStyle(string $value): string
    {
        $value = strtolower(trim($value));
        return in_array($value, ['solid', 'dashed', 'dotted', 'double', 'none'], true) ? $value : '';
    }

    private static function safeShadowKey(string $value): string
    {
        $value = strtolower(trim($value));
        return isset(self::BOX_SHADOWS[$value]) ? $value : '';
    }

    private static function safeFontWeight(string $value): string
    {
        $value = trim($value);
        return in_array($value, ['400', '500', '600', '700'], true) ? $value : '';
    }

    private static function safeLineHeight(string $value): string
    {
        $value = trim($value);
        if (!preg_match('/^\d(\.\d{1,2})?$/', $value)) {
            return '';
        }
        $n = (float) $value;
        if ($n < 1 || $n > 2.5) {
            return '';
        }
        return $value;
    }

    private static function safeFontFamilyKey(string $value): string
    {
        $value = strtolower(trim($value));
        return isset(self::FONT_FAMILIES[$value]) ? $value : '';
    }

    private static function safeLetterSpacingKey(string $value): string
    {
        $value = strtolower(trim($value));
        return isset(self::LETTER_SPACINGS[$value]) ? $value : '';
    }

    private static function safeTextTransform(string $value): string
    {
        $value = strtolower(trim($value));
        return in_array($value, ['none', 'uppercase', 'lowercase', 'capitalize'], true) ? $value : '';
    }

    private static function safePosition(string $value): string
    {
        $value = strtolower(trim($value));
        return in_array($value, ['relative', 'sticky'], true) ? $value : '';
    }

    private static function safeZIndex(string $value): string
    {
        $value = trim($value);
        return in_array($value, self::Z_INDEXES, true) ? $value : '';
    }

    private static function safeStickyOffsetKey(string $value): string
    {
        $value = strtolower(trim($value));
        return isset(self::STICKY_OFFSETS[$value]) ? $value : '';
    }

    private static function safeShapeKey(string $value): string
    {
        $value = strtolower(trim($value));
        return in_array($value, self::SHAPE_DIVIDERS, true) ? $value : '';
    }

    private static function safeShapeHeightKey(string $value): string
    {
        $value = strtolower(trim($value));
        return isset(self::SHAPE_HEIGHTS[$value]) ? $value : '';
    }

    /** Allow safe subset of HTML for custom HTML modules. */
    public static function sanitizeHtml(string $html): string
    {
        $html = preg_replace('#<(script|iframe|object|embed|form|input|button)[^>]*>.*?</\1>#is', '', $html) ?? '';
        $html = preg_replace('#<(script|iframe|object|embed|form|input|button)[^>]*/?>#is', '', $html) ?? '';
        $html = preg_replace('/\son\w+\s*=\s*("|\').*?\1/i', '', $html) ?? '';
        $html = preg_replace('/\s(href|src)\s*=\s*("|\')\s*javascript:[^"\']*\2/i', '', $html) ?? '';
        return $html;
    }

    /**
     * Allowlisted rich text for Text / CTA / Blurb / Accordion / Tabs bodies.
     * Unknown tags are unwrapped; scripts and event handlers cannot survive.
     */
    public static function sanitizeRichText(string $html): string
    {
        $html = trim($html);
        if ($html === '') {
            return '';
        }
        if (!preg_match('/<[a-zA-Z]/', $html)) {
            $plain = htmlspecialchars($html, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $plain = str_replace(["\r\n", "\r"], "\n", $plain);
            $paras = preg_split("/\n{2,}/", $plain) ?: [$plain];
            $out = [];
            foreach ($paras as $para) {
                $para = trim($para);
                if ($para === '') {
                    continue;
                }
                $out[] = '<p>' . str_replace("\n", '<br>', $para) . '</p>';
            }
            return implode('', $out);
        }
        $prev = libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $ok = $dom->loadHTML(
            '<?xml encoding="UTF-8"><div id="cmsrt">' . $html . '</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        libxml_clear_errors();
        libxml_use_internal_errors($prev);
        if (!$ok) {
            return htmlspecialchars(strip_tags($html), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        }
        $root = $dom->getElementById('cmsrt');
        if (!$root instanceof \DOMElement) {
            foreach ($dom->getElementsByTagName('div') as $div) {
                if ($div instanceof \DOMElement && $div->getAttribute('id') === 'cmsrt') {
                    $root = $div;
                    break;
                }
            }
        }
        if (!$root instanceof \DOMElement) {
            return '';
        }
        self::scrubRichNode($root);
        $out = '';
        foreach (iterator_to_array($root->childNodes) as $child) {
            $out .= $dom->saveHTML($child);
        }
        return trim($out);
    }

    private static function scrubRichNode(\DOMNode $node): void
    {
        $allowed = ['p' => 1, 'br' => 1, 'strong' => 1, 'em' => 1, 'u' => 1, 'ul' => 1, 'ol' => 1, 'li' => 1, 'a' => 1];
        $rename = ['b' => 'strong', 'i' => 'em'];
        $drop = [
            'script' => 1, 'iframe' => 1, 'object' => 1, 'embed' => 1, 'form' => 1,
            'style' => 1, 'noscript' => 1, 'link' => 1, 'meta' => 1, 'textarea' => 1,
            'template' => 1, 'svg' => 1,
        ];
        $kids = [];
        foreach ($node->childNodes as $child) {
            $kids[] = $child;
        }
        foreach ($kids as $child) {
            if ($child instanceof \DOMText || $child instanceof \DOMCdataSection) {
                continue;
            }
            if ($child instanceof \DOMComment) {
                $child->parentNode?->removeChild($child);
                continue;
            }
            if (!$child instanceof \DOMElement) {
                $child->parentNode?->removeChild($child);
                continue;
            }
            $tag = strtolower($child->tagName);
            if (isset($drop[$tag])) {
                $child->parentNode?->removeChild($child);
                continue;
            }
            if (isset($rename[$tag])) {
                $new = $child->ownerDocument->createElement($rename[$tag]);
                while ($child->firstChild) {
                    $new->appendChild($child->firstChild);
                }
                $child->parentNode?->replaceChild($new, $child);
                $child = $new;
                $tag = strtolower($child->tagName);
            }
            self::scrubRichNode($child);
            if (!isset($allowed[$tag])) {
                $parent = $child->parentNode;
                if ($parent) {
                    while ($child->firstChild) {
                        $parent->insertBefore($child->firstChild, $child);
                    }
                    $parent->removeChild($child);
                }
                continue;
            }
            $keepHref = '';
            if ($tag === 'a') {
                $keepHref = self::safeUrl(html_entity_decode((string) $child->getAttribute('href'), ENT_QUOTES, 'UTF-8'));
            }
            while ($child->attributes && $child->attributes->length > 0) {
                $child->removeAttributeNode($child->attributes->item(0));
            }
            if ($tag === 'a') {
                if ($keepHref !== '' && $keepHref !== '#') {
                    $child->setAttribute('href', $keepHref);
                } else {
                    $parent = $child->parentNode;
                    if ($parent) {
                        while ($child->firstChild) {
                            $parent->insertBefore($child->firstChild, $child);
                        }
                        $parent->removeChild($child);
                    }
                }
            }
        }
    }
}
