<?php
namespace App;

/**
 * Table of contents from h2/h3 in already-rendered public HTML.
 */
class PublicToc
{
    /**
     * @return array{html: string, items: list<array{id: string, text: string, level: int}>}
     */
    public static function enhance(string $html): array
    {
        $items = [];
        $used = [];
        $enhanced = preg_replace_callback(
            '/<h([23])(\s[^>]*)?>(.*?)<\/h\1>/is',
            static function (array $m) use (&$items, &$used): string {
                $level = (int) $m[1];
                $attrs = $m[2] ?? '';
                $inner = $m[3];
                $text = trim(html_entity_decode(strip_tags($inner), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                $text = preg_replace('/\s+/u', ' ', $text) ?? $text;
                if ($text === '') {
                    return $m[0];
                }
                $id = '';
                if (preg_match('/\sid=(["\'])([^"\']+)\1/i', $attrs, $idMatch)) {
                    $id = self::safeId((string) $idMatch[2]);
                }
                if ($id === '') {
                    $id = self::uniqueId($text, $used);
                    $attrs .= ' id="' . htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . '"';
                } else {
                    $used[$id] = true;
                }
                $items[] = [
                    'id' => $id,
                    'text' => mb_substr($text, 0, 120),
                    'level' => $level,
                ];
                return '<h' . $level . $attrs . '>' . $inner . '</h' . $level . '>';
            },
            $html
        );
        if (!is_string($enhanced)) {
            return ['html' => $html, 'items' => []];
        }
        if (count($items) < 2) {
            return ['html' => $html, 'items' => []];
        }
        return ['html' => $enhanced, 'items' => $items];
    }

    public static function renderNav(array $items): string
    {
        if (count($items) < 2) {
            return '';
        }
        $out = '<nav class="public-toc" aria-label="On this page"><h2 class="public-toc-title">On this page</h2><ol class="public-toc-list">';
        foreach ($items as $item) {
            $level = (int) ($item['level'] ?? 2);
            $cls = $level === 3 ? ' class="public-toc-h3"' : '';
            $out .= '<li' . $cls . '><a href="#' . htmlspecialchars((string) $item['id'], ENT_QUOTES, 'UTF-8') . '">'
                . htmlspecialchars((string) $item['text'], ENT_QUOTES, 'UTF-8') . '</a></li>';
        }
        $out .= '</ol></nav>';
        return $out;
    }

    private static function uniqueId(string $text, array &$used): string
    {
        $base = self::safeId(CmsSlug::from($text, 'section'));
        $id = $base;
        $n = 2;
        while (isset($used[$id])) {
            $id = $base . '-' . $n;
            $n++;
        }
        $used[$id] = true;
        return $id;
    }

    private static function safeId(string $id): string
    {
        $id = strtolower(trim($id));
        $id = preg_replace('/[^a-z0-9_-]+/', '-', $id) ?? '';
        $id = trim($id, '-');
        if ($id === '' || !preg_match('/^[a-z]/', $id)) {
            $id = 'section-' . ($id !== '' ? $id : 'h');
        }
        return substr($id, 0, 80);
    }
}
