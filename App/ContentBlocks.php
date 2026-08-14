<?php
namespace App;

/**
 * Block-based page builder (WordPress Gutenberg-lite).
 */
class ContentBlocks
{
    /** @return array<string, string> */
    public static function types(): array
    {
        return [
            'heading'   => 'Heading',
            'paragraph' => 'Paragraph',
            'image'     => 'Image',
            'columns'   => 'Two columns',
            'cta'       => 'Call to action',
            'spacer'    => 'Spacer',
            'html'      => 'Custom HTML',
        ];
    }

    /** @return array<int, array<string, mixed>> */
    public static function parse(?string $json): array
    {
        if ($json === null || trim($json) === '') {
            return [];
        }
        $data = json_decode($json, true);
        if (!is_array($data)) {
            return [];
        }
        $out = [];
        foreach ($data as $block) {
            if (!is_array($block) || empty($block['type'])) {
                continue;
            }
            $type = (string) $block['type'];
            if (!array_key_exists($type, self::types())) {
                continue;
            }
            $out[] = [
                'type' => $type,
                'data' => is_array($block['data'] ?? null) ? $block['data'] : [],
            ];
        }
        return $out;
    }

    public static function normalizeJson(?string $json): ?string
    {
        $blocks = self::parse($json);
        if ($blocks === []) {
            return null;
        }
        return json_encode($blocks, JSON_UNESCAPED_UNICODE);
    }

    /** @param array<int, array<string, mixed>> $blocks */
    public static function render(array $blocks): string
    {
        $html = '';
        foreach ($blocks as $block) {
            $html .= self::renderBlock($block);
        }
        return $html;
    }

    /** @param array<string, mixed> $block */
    public static function renderBlock(array $block): string
    {
        $type = (string) ($block['type'] ?? '');
        $data = is_array($block['data'] ?? null) ? $block['data'] : [];

        switch ($type) {
            case 'heading':
                $level = in_array((int) ($data['level'] ?? 2), [2, 3, 4], true) ? (int) $data['level'] : 2;
                $text = htmlspecialchars((string) ($data['text'] ?? ''), ENT_QUOTES, 'UTF-8');
                return '<h' . $level . ' class="cms-block-heading">' . $text . '</h' . $level . '>';
            case 'paragraph':
                $text = nl2br(htmlspecialchars((string) ($data['text'] ?? ''), ENT_QUOTES, 'UTF-8'));
                return '<div class="cms-block-paragraph">' . $text . '</div>';
            case 'image':
                $alt = (string) ($data['alt'] ?? '');
                $mediaId = (int) ($data['media_id'] ?? 0);
                if ($mediaId < 1) {
                    $mediaId = (int) (\App\Models\Media::idFromUrl((string) ($data['url'] ?? '')) ?: 0);
                }
                if ($mediaId > 0) {
                    $img = \App\Models\Media::responsiveImg($mediaId, [
                        'alt' => $alt,
                        'class' => 'img-fluid',
                        'sizes' => '(max-width: 768px) 100vw, min(960px, 100vw)',
                        'preferred_width' => 1024,
                        'loading' => 'lazy',
                    ]);
                    if ($img !== '') {
                        return '<figure class="cms-block-image">' . $img . '</figure>';
                    }
                }
                $url = htmlspecialchars((string) ($data['url'] ?? ''), ENT_QUOTES, 'UTF-8');
                $altEsc = htmlspecialchars($alt, ENT_QUOTES, 'UTF-8');
                if ($url === '') {
                    return '';
                }
                // Prefer public share URL if admin serve URL was stored
                if (preg_match('#/serve/media/(\d+)#', (string) ($data['url'] ?? ''), $m)) {
                    $url = htmlspecialchars(\App\Models\Media::publicShareUrl((int) $m[1]), ENT_QUOTES, 'UTF-8');
                }
                return '<figure class="cms-block-image"><img src="' . $url . '" alt="' . $altEsc . '" loading="lazy" decoding="async" class="img-fluid"></figure>';
            case 'columns':
                $left = nl2br(htmlspecialchars((string) ($data['left'] ?? ''), ENT_QUOTES, 'UTF-8'));
                $right = nl2br(htmlspecialchars((string) ($data['right'] ?? ''), ENT_QUOTES, 'UTF-8'));
                return '<div class="cms-block-columns row g-3"><div class="col-md-6">' . $left . '</div><div class="col-md-6">' . $right . '</div></div>';
            case 'cta':
                $label = htmlspecialchars((string) ($data['label'] ?? 'Learn more'), ENT_QUOTES, 'UTF-8');
                $url = htmlspecialchars((string) ($data['url'] ?? '#'), ENT_QUOTES, 'UTF-8');
                $text = htmlspecialchars((string) ($data['text'] ?? ''), ENT_QUOTES, 'UTF-8');
                $btn = '<a href="' . $url . '" class="btn btn-primary cms-block-cta-btn">' . $label . '</a>';
                return '<div class="cms-block-cta">' . ($text !== '' ? '<p>' . nl2br($text) . '</p>' : '') . $btn . '</div>';
            case 'spacer':
                $size = in_array((string) ($data['size'] ?? 'md'), ['sm', 'md', 'lg'], true) ? (string) $data['size'] : 'md';
                return '<div class="cms-block-spacer cms-block-spacer--' . $size . '" aria-hidden="true"></div>';
            case 'html':
                return (string) ($data['html'] ?? '');
            default:
                return '';
        }
    }

    public static function hasBlocks(?object $entity): bool
    {
        if (!$entity || empty($entity->blocks_json)) {
            return false;
        }
        return self::parse((string) $entity->blocks_json) !== [];
    }

    public static function renderEntity(?object $entity, string $bodyFallback = ''): string
    {
        if ($entity && LayoutBuilder::hasLayout($entity)) {
            return LayoutBuilder::renderFromEntity($entity);
        }
        if ($entity && self::hasBlocks($entity)) {
            return self::render(self::parse((string) $entity->blocks_json));
        }
        return $bodyFallback;
    }

    public static function plainTextFromEntity(?object $entity): string
    {
        if ($entity && LayoutBuilder::hasLayout($entity)) {
            return LayoutBuilder::plainTextFromEntity($entity);
        }
        if ($entity && self::hasBlocks($entity)) {
            return self::plainTextFromBlocks(self::parse((string) $entity->blocks_json));
        }
        return self::stripPlainText((string) ($entity->body ?? ''));
    }

    /** @param array<int, array<string, mixed>> $blocks */
    public static function plainTextFromBlocks(array $blocks): string
    {
        $parts = [];
        foreach ($blocks as $block) {
            $type = (string) ($block['type'] ?? '');
            $data = is_array($block['data'] ?? null) ? $block['data'] : [];
            switch ($type) {
                case 'heading':
                case 'paragraph':
                    if (!empty($data['text'])) {
                        $parts[] = (string) $data['text'];
                    }
                    break;
                case 'columns':
                    if (!empty($data['left'])) {
                        $parts[] = (string) $data['left'];
                    }
                    if (!empty($data['right'])) {
                        $parts[] = (string) $data['right'];
                    }
                    break;
                case 'cta':
                    if (!empty($data['text'])) {
                        $parts[] = (string) $data['text'];
                    }
                    if (!empty($data['label'])) {
                        $parts[] = (string) $data['label'];
                    }
                    break;
                case 'html':
                    if (!empty($data['html'])) {
                        $parts[] = self::stripPlainText((string) $data['html']);
                    }
                    break;
                default:
                    break;
            }
        }
        $text = trim(implode(' ', $parts));
        return preg_replace('/\s+/u', ' ', $text) ?? '';
    }

    private static function stripPlainText(string $html): string
    {
        $text = trim(strip_tags($html));
        return preg_replace('/\s+/u', ' ', $text) ?? '';
    }
}
