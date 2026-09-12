<?php
namespace App;

/**
 * Sanitize imported post/page HTML and rewrite internal hrefs to this site's base_url.
 */
final class ImportHtml
{
    /**
     * @param array<string, string> $pathMap lowercase path (with leading /) => dest site path (`/blog/slug`)
     */
    public static function cleanAndRewrite(string $html, array $pathMap, ?string $baseUrl = null): string
    {
        $html = trim($html);
        if ($html === '') {
            return '';
        }
        $prev = libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $ok = $dom->loadHTML(
            '<?xml encoding="UTF-8"><div id="cmsimp">' . $html . '</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NONET
        );
        libxml_clear_errors();
        libxml_use_internal_errors($prev);
        if (!$ok) {
            return htmlspecialchars(strip_tags($html), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        }
        $root = $dom->getElementById('cmsimp');
        if (!$root instanceof \DOMElement) {
            foreach ($dom->getElementsByTagName('div') as $div) {
                if ($div instanceof \DOMElement && $div->getAttribute('id') === 'cmsimp') {
                    $root = $div;
                    break;
                }
            }
        }
        if (!$root instanceof \DOMElement) {
            return '';
        }
        self::scrub($root);
        self::rewriteAnchors($root, $pathMap, $baseUrl);
        $out = '';
        foreach (iterator_to_array($root->childNodes) as $child) {
            $out .= $dom->saveHTML($child);
        }

        return trim($out);
    }

    public static function isGoogleSearchUrl(string $href): bool
    {
        $href = trim($href);
        if ($href === '') {
            return false;
        }
        $parts = parse_url($href);
        $host = strtolower((string) ($parts['host'] ?? ''));
        $path = strtolower((string) ($parts['path'] ?? ''));
        if ($host === '' || $path === '') {
            return false;
        }
        $host = preg_replace('/^www\./', '', $host) ?? $host;

        return ($host === 'google.com' || str_ends_with($host, '.google.com'))
            && ($path === '/search' || str_starts_with($path, '/search'));
    }

    /**
     * Normalize an href to a lookup path, stripping this site's base_url prefix when present.
     */
    public static function lookupPath(string $href, ?string $baseUrl = null): string
    {
        $href = html_entity_decode(trim($href), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        if ($href === '' || str_starts_with($href, '#') || preg_match('#^(mailto|tel|javascript):#i', $href)) {
            return '';
        }
        $parts = parse_url($href);
        if ($parts === false) {
            return '';
        }
        $path = (string) ($parts['path'] ?? '');
        if ($path === '') {
            return '';
        }
        $path = rawurldecode($path);
        $path = '/' . ltrim($path, '/');
        $prefix = SiteUrl::pathPrefix($baseUrl);
        if ($prefix !== '' && ($path === $prefix || str_starts_with($path, $prefix . '/'))) {
            $path = substr($path, strlen($prefix)) ?: '/';
        }
        $path = strtolower($path);
        if (str_ends_with($path, '.html')) {
            $path = substr($path, 0, -5);
        }

        return rtrim($path, '/') ?: '/';
    }

    private static function scrub(\DOMNode $node): void
    {
        $allowed = [
            'p' => 1, 'br' => 1, 'strong' => 1, 'em' => 1, 'u' => 1, 'ul' => 1, 'ol' => 1, 'li' => 1,
            'a' => 1, 'h2' => 1, 'h3' => 1, 'h4' => 1, 'h5' => 1, 'h6' => 1, 'blockquote' => 1, 'hr' => 1,
            'img' => 1,
        ];
        $rename = ['b' => 'strong', 'i' => 'em'];
        $drop = [
            'script' => 1, 'iframe' => 1, 'object' => 1, 'embed' => 1, 'form' => 1,
            'style' => 1, 'noscript' => 1, 'link' => 1, 'meta' => 1, 'textarea' => 1,
            'template' => 1, 'svg' => 1, 'input' => 1, 'button' => 1,
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
                $new = $child->ownerDocument?->createElement($rename[$tag]);
                if ($new instanceof \DOMElement) {
                    while ($child->firstChild) {
                        $new->appendChild($child->firstChild);
                    }
                    $child->parentNode?->replaceChild($new, $child);
                    $child = $new;
                    $tag = $rename[$tag];
                }
            }
            if ($tag === 'a' && self::isGoogleSearchUrl($child->getAttribute('href'))) {
                self::unwrap($child);
                continue;
            }
            if (!isset($allowed[$tag])) {
                self::scrub($child);
                self::unwrap($child);
                continue;
            }
            self::stripAttrs($child, $tag);
            self::scrub($child);
        }
    }

    private static function unwrap(\DOMElement $el): void
    {
        $parent = $el->parentNode;
        if (!$parent) {
            return;
        }
        while ($el->firstChild) {
            $parent->insertBefore($el->firstChild, $el);
        }
        $parent->removeChild($el);
    }

    private static function stripAttrs(\DOMElement $el, string $tag): void
    {
        $keep = [];
        if ($tag === 'a') {
            $keep = ['href' => true, 'title' => true];
        } elseif ($tag === 'img') {
            $keep = ['src' => true, 'alt' => true, 'width' => true, 'height' => true];
        }
        $names = [];
        foreach ($el->attributes ?? [] as $attr) {
            $names[] = $attr->name;
        }
        foreach ($names as $name) {
            $ln = strtolower($name);
            if (!isset($keep[$ln])) {
                $el->removeAttribute($name);
                continue;
            }
            $val = trim($el->getAttribute($name));
            if ($ln === 'href' || $ln === 'src') {
                if ($val === '' || preg_match('#^\s*javascript:#i', $val) || str_starts_with(strtolower($val), 'data:')) {
                    $el->removeAttribute($name);
                }
            }
        }
        if ($tag === 'img') {
            $src = trim($el->getAttribute('src'));
            if ($src === '' || (!preg_match('#^https?://#i', $src) && !str_starts_with($src, '/'))) {
                $el->parentNode?->removeChild($el);
            }
        }
    }

    /**
     * @param array<string, string> $pathMap
     */
    private static function rewriteAnchors(\DOMElement $root, array $pathMap, ?string $baseUrl): void
    {
        $anchors = [];
        foreach ($root->getElementsByTagName('a') as $a) {
            if ($a instanceof \DOMElement) {
                $anchors[] = $a;
            }
        }
        foreach ($anchors as $a) {
            $href = trim($a->getAttribute('href'));
            if ($href === '') {
                continue;
            }
            $lookup = self::lookupPath($href, $baseUrl);
            if ($lookup === '' || $lookup === '/') {
                continue;
            }
            $dest = $pathMap[$lookup] ?? $pathMap[$lookup . '.html'] ?? null;
            if (!is_string($dest) || $dest === '') {
                continue;
            }
            $a->setAttribute('href', SiteUrl::href($dest, $baseUrl));
        }
    }
}
