<?php
namespace App;

use App\Models\Media;

/**
 * Divi-inspired visual layout: Section → Row → Column → Module.
 */
class LayoutBuilder
{
    public const VERSION = 1;

    /** Bootstrap column widths allowed. */
    private const WIDTHS = [12, 6, 4, 3, 8, 9];

    /** @return array<string, string> */
    public static function moduleTypes(): array
    {
        return [
            'heading' => 'Heading',
            'text' => 'Text',
            'image' => 'Image',
            'button' => 'Button',
            'cta' => 'Call to Action',
            'spacer' => 'Spacer',
            'divider' => 'Divider',
            'html' => 'Custom HTML',
            'blurb' => 'Blurb',
            'carousel' => 'Carousel',
        ];
    }

    public static function hasLayout(?object $entity): bool
    {
        if (!$entity || empty($entity->layout_json)) {
            return false;
        }
        $layout = self::parse((string) $entity->layout_json);
        return !empty($layout['sections']);
    }

    /** @return array{version: int, sections: array<int, array<string, mixed>>} */
    public static function emptyLayout(): array
    {
        return [
            'version' => self::VERSION,
            'sections' => [],
        ];
    }

    /** @return array{version: int, sections: array<int, array<string, mixed>>} */
    public static function parse(?string $json): array
    {
        if ($json === null || trim($json) === '') {
            return self::emptyLayout();
        }
        $data = json_decode($json, true);
        if (!is_array($data)) {
            return self::emptyLayout();
        }
        return self::normalize($data);
    }

    public static function normalizeJson(?string $json): ?string
    {
        $layout = self::parse($json);
        if ($layout['sections'] === []) {
            return null;
        }
        return json_encode($layout, JSON_UNESCAPED_UNICODE);
    }

    /**
     * @param array<string, mixed> $data
     * @return array{version: int, sections: array<int, array<string, mixed>>}
     */
    public static function normalize(array $data): array
    {
        $sectionsIn = is_array($data['sections'] ?? null) ? $data['sections'] : [];
        $sections = [];
        foreach ($sectionsIn as $section) {
            if (!is_array($section)) {
                continue;
            }
            $norm = self::normalizeSection($section);
            if ($norm !== null) {
                $sections[] = $norm;
            }
        }
        return [
            'version' => self::VERSION,
            'sections' => $sections,
        ];
    }

    /** @param array<string, mixed> $section */
    private static function normalizeSection(array $section): ?array
    {
        $type = (string) ($section['type'] ?? 'regular');
        if (!in_array($type, ['regular', 'fullwidth'], true)) {
            $type = 'regular';
        }
        $rowsIn = is_array($section['rows'] ?? null) ? $section['rows'] : [];
        $rows = [];
        foreach ($rowsIn as $row) {
            if (!is_array($row)) {
                continue;
            }
            $normRow = self::normalizeRow($row);
            if ($normRow !== null) {
                $rows[] = $normRow;
            }
        }
        return [
            'id' => self::id($section['id'] ?? null),
            'type' => $type,
            'settings' => self::normalizeSettings(is_array($section['settings'] ?? null) ? $section['settings'] : []),
            'rows' => $rows,
        ];
    }

    /** @param array<string, mixed> $row */
    private static function normalizeRow(array $row): ?array
    {
        $colsIn = is_array($row['columns'] ?? null) ? $row['columns'] : [];
        $columns = [];
        $widthSum = 0;
        foreach ($colsIn as $col) {
            if (!is_array($col)) {
                continue;
            }
            $normCol = self::normalizeColumn($col);
            if ($normCol !== null) {
                $columns[] = $normCol;
                $widthSum += (int) $normCol['width'];
            }
        }
        if ($columns === []) {
            $columns[] = self::normalizeColumn(['width' => 12, 'modules' => []]);
        }
        // Soft-normalize widths if they exceed 12
        if ($widthSum > 12 && count($columns) === 1) {
            $columns[0]['width'] = 12;
        }
        return [
            'id' => self::id($row['id'] ?? null),
            'settings' => self::normalizeSettings(is_array($row['settings'] ?? null) ? $row['settings'] : []),
            'columns' => $columns,
        ];
    }

    /** @param array<string, mixed> $col */
    private static function normalizeColumn(array $col): ?array
    {
        $width = (int) ($col['width'] ?? 12);
        if (!in_array($width, self::WIDTHS, true)) {
            $width = 12;
        }
        $modsIn = is_array($col['modules'] ?? null) ? $col['modules'] : [];
        $modules = [];
        $types = self::moduleTypes();
        foreach ($modsIn as $mod) {
            if (!is_array($mod)) {
                continue;
            }
            $type = (string) ($mod['type'] ?? '');
            if (!isset($types[$type])) {
                continue;
            }
            $modules[] = [
                'id' => self::id($mod['id'] ?? null),
                'type' => $type,
                'data' => self::normalizeModuleData($type, is_array($mod['data'] ?? null) ? $mod['data'] : []),
                'design' => self::normalizeDesign(is_array($mod['design'] ?? null) ? $mod['design'] : []),
                'advanced' => self::normalizeAdvanced(is_array($mod['advanced'] ?? null) ? $mod['advanced'] : []),
            ];
        }
        return [
            'id' => self::id($col['id'] ?? null),
            'width' => $width,
            'settings' => self::normalizeSettings(is_array($col['settings'] ?? null) ? $col['settings'] : []),
            'modules' => $modules,
        ];
    }

    /** @param array<string, mixed> $data */
    private static function normalizeModuleData(string $type, array $data): array
    {
        switch ($type) {
            case 'heading':
                return [
                    'text' => mb_substr(trim((string) ($data['text'] ?? '')), 0, 500),
                    'level' => in_array((int) ($data['level'] ?? 2), [1, 2, 3, 4, 5, 6], true) ? (int) $data['level'] : 2,
                ];
            case 'text':
                return [
                    'text' => mb_substr(trim((string) ($data['text'] ?? '')), 0, 20000),
                ];
            case 'image':
                $mediaId = (int) ($data['media_id'] ?? 0);
                return [
                    'media_id' => $mediaId > 0 ? $mediaId : null,
                    'url' => self::safeUrl((string) ($data['url'] ?? '')),
                    'alt' => mb_substr(trim((string) ($data['alt'] ?? '')), 0, 255),
                    'caption' => mb_substr(trim((string) ($data['caption'] ?? '')), 0, 1000),
                    'link' => self::safeUrl((string) ($data['link'] ?? '')),
                ];
            case 'button':
                return [
                    'label' => mb_substr(trim((string) ($data['label'] ?? 'Learn more')), 0, 120),
                    'url' => self::safeUrl((string) ($data['url'] ?? '#')) ?: '#',
                    'style' => in_array((string) ($data['style'] ?? 'primary'), ['primary', 'secondary', 'outline'], true)
                        ? (string) $data['style'] : 'primary',
                ];
            case 'cta':
                return [
                    'title' => mb_substr(trim((string) ($data['title'] ?? '')), 0, 200),
                    'text' => mb_substr(trim((string) ($data['text'] ?? '')), 0, 2000),
                    'label' => mb_substr(trim((string) ($data['label'] ?? 'Get started')), 0, 120),
                    'url' => self::safeUrl((string) ($data['url'] ?? '#')) ?: '#',
                ];
            case 'spacer':
                return [
                    'size' => in_array((string) ($data['size'] ?? 'md'), ['sm', 'md', 'lg', 'xl'], true)
                        ? (string) $data['size'] : 'md',
                ];
            case 'divider':
                return [
                    'style' => in_array((string) ($data['style'] ?? 'solid'), ['solid', 'dashed', 'dotted'], true)
                        ? (string) $data['style'] : 'solid',
                ];
            case 'html':
                return [
                    'html' => mb_substr((string) ($data['html'] ?? ''), 0, 50000),
                ];
            case 'blurb':
                return [
                    'title' => mb_substr(trim((string) ($data['title'] ?? '')), 0, 200),
                    'text' => mb_substr(trim((string) ($data['text'] ?? '')), 0, 2000),
                    'icon' => mb_substr(trim((string) ($data['icon'] ?? '')), 0, 40),
                    'media_id' => ((int) ($data['media_id'] ?? 0)) > 0 ? (int) $data['media_id'] : null,
                    'url' => self::safeUrl((string) ($data['url'] ?? '')),
                ];
            case 'carousel':
                return self::normalizeCarouselData($data);
            default:
                return [];
        }
    }

    /** @param array<string, mixed> $data */
    private static function normalizeCarouselData(array $data): array
    {
        $interval = (int) ($data['interval_ms'] ?? 5000);
        if ($interval < 2000) {
            $interval = 2000;
        }
        if ($interval > 30000) {
            $interval = 30000;
        }
        $rawSlides = is_array($data['slides'] ?? null) ? $data['slides'] : [];
        $slides = [];
        foreach ($rawSlides as $slide) {
            if (!is_array($slide)) {
                continue;
            }
            $mediaId = (int) ($slide['media_id'] ?? 0);
            $url = self::safeUrl((string) ($slide['url'] ?? ''));
            $alt = mb_substr(trim((string) ($slide['alt'] ?? '')), 0, 255);
            $caption = mb_substr(trim((string) ($slide['caption'] ?? '')), 0, 500);
            $link = self::safeUrl((string) ($slide['link'] ?? ''));
            if ($mediaId <= 0 && $url === '' && $caption === '') {
                continue;
            }
            $slides[] = [
                'media_id' => $mediaId > 0 ? $mediaId : null,
                'url' => $url,
                'alt' => $alt,
                'caption' => $caption,
                'link' => $link,
            ];
            if (count($slides) >= 12) {
                break;
            }
        }
        if ($slides === []) {
            $slides[] = [
                'media_id' => null,
                'url' => '',
                'alt' => '',
                'caption' => '',
                'link' => '',
            ];
        }
        return [
            'autoplay' => !empty($data['autoplay']),
            'interval_ms' => $interval,
            'show_arrows' => array_key_exists('show_arrows', $data) ? !empty($data['show_arrows']) : true,
            'show_dots' => array_key_exists('show_dots', $data) ? !empty($data['show_dots']) : true,
            'slides' => $slides,
        ];
    }

    /** @param array<string, mixed> $design */
    private static function normalizeDesign(array $design): array
    {
        return [
            'text_align' => in_array((string) ($design['text_align'] ?? ''), ['left', 'center', 'right'], true)
                ? (string) $design['text_align'] : '',
            'text_color' => self::safeColor((string) ($design['text_color'] ?? '')),
            'bg_color' => self::safeColor((string) ($design['bg_color'] ?? '')),
            'padding' => self::safeSpacing((string) ($design['padding'] ?? '')),
            'margin' => self::safeSpacing((string) ($design['margin'] ?? '')),
            'font_size' => self::safeFontSize((string) ($design['font_size'] ?? '')),
        ];
    }

    /** @param array<string, mixed> $adv */
    private static function normalizeAdvanced(array $adv): array
    {
        $class = preg_replace('/[^a-zA-Z0-9_\-\s]/', '', (string) ($adv['css_class'] ?? '')) ?? '';
        $class = trim(preg_replace('/\s+/', ' ', $class) ?? '');
        return [
            'css_class' => mb_substr($class, 0, 120),
            'hide_mobile' => !empty($adv['hide_mobile']),
            'hide_desktop' => !empty($adv['hide_desktop']),
        ];
    }

    /** @param array<string, mixed> $settings */
    private static function normalizeSettings(array $settings): array
    {
        return [
            'bg_color' => self::safeColor((string) ($settings['bg_color'] ?? '')),
            'padding' => self::safeSpacing((string) ($settings['padding'] ?? '')),
            'css_class' => mb_substr(preg_replace('/[^a-zA-Z0-9_\-\s]/', '', (string) ($settings['css_class'] ?? '')) ?? '', 0, 120),
        ];
    }

    public static function renderFromEntity(?object $entity): string
    {
        if (!$entity || empty($entity->layout_json)) {
            return '';
        }
        return self::render(self::parse((string) $entity->layout_json));
    }

    /** @param array{version?: int, sections?: array<int, array<string, mixed>>} $layout */
    public static function render(array $layout): string
    {
        $sections = is_array($layout['sections'] ?? null) ? $layout['sections'] : [];
        if ($sections === []) {
            return '';
        }
        $html = '<div class="cms-layout">';
        foreach ($sections as $section) {
            $html .= self::renderSection($section);
        }
        $html .= '</div>';
        return $html;
    }

    /** @param array<string, mixed> $section */
    private static function renderSection(array $section): string
    {
        $type = (string) ($section['type'] ?? 'regular');
        $settings = is_array($section['settings'] ?? null) ? $section['settings'] : [];
        $classes = ['cms-layout-section', 'cms-layout-section--' . $type];
        if (!empty($settings['css_class'])) {
            $classes[] = htmlspecialchars((string) $settings['css_class'], ENT_QUOTES, 'UTF-8');
        }
        $style = self::styleFromSettings($settings);
        $html = '<section class="' . implode(' ', $classes) . '"' . ($style !== '' ? ' style="' . $style . '"' : '') . '>';
        $html .= '<div class="cms-layout-section-inner' . ($type === 'fullwidth' ? ' cms-layout-section-inner--full' : ' container') . '">';
        $rows = is_array($section['rows'] ?? null) ? $section['rows'] : [];
        foreach ($rows as $row) {
            $html .= self::renderRow($row);
        }
        $html .= '</div></section>';
        return $html;
    }

    /** @param array<string, mixed> $row */
    private static function renderRow(array $row): string
    {
        $settings = is_array($row['settings'] ?? null) ? $row['settings'] : [];
        $classes = ['cms-layout-row', 'row', 'g-3'];
        if (!empty($settings['css_class'])) {
            $classes[] = htmlspecialchars((string) $settings['css_class'], ENT_QUOTES, 'UTF-8');
        }
        $style = self::styleFromSettings($settings);
        $html = '<div class="' . implode(' ', $classes) . '"' . ($style !== '' ? ' style="' . $style . '"' : '') . '>';
        $columns = is_array($row['columns'] ?? null) ? $row['columns'] : [];
        foreach ($columns as $col) {
            $html .= self::renderColumn($col);
        }
        $html .= '</div>';
        return $html;
    }

    /** @param array<string, mixed> $col */
    private static function renderColumn(array $col): string
    {
        $width = (int) ($col['width'] ?? 12);
        if (!in_array($width, self::WIDTHS, true)) {
            $width = 12;
        }
        $settings = is_array($col['settings'] ?? null) ? $col['settings'] : [];
        $classes = ['cms-layout-column', 'col-md-' . $width];
        if (!empty($settings['css_class'])) {
            $classes[] = htmlspecialchars((string) $settings['css_class'], ENT_QUOTES, 'UTF-8');
        }
        $style = self::styleFromSettings($settings);
        $html = '<div class="' . implode(' ', $classes) . '"' . ($style !== '' ? ' style="' . $style . '"' : '') . '>';
        $modules = is_array($col['modules'] ?? null) ? $col['modules'] : [];
        foreach ($modules as $mod) {
            $html .= self::renderModule($mod);
        }
        $html .= '</div>';
        return $html;
    }

    /** @param array<string, mixed> $mod */
    private static function renderModule(array $mod): string
    {
        $type = (string) ($mod['type'] ?? '');
        $data = is_array($mod['data'] ?? null) ? $mod['data'] : [];
        $design = is_array($mod['design'] ?? null) ? $mod['design'] : [];
        $advanced = is_array($mod['advanced'] ?? null) ? $mod['advanced'] : [];

        $classes = ['cms-layout-module', 'cms-mod-' . preg_replace('/[^a-z0-9\-]/', '', $type)];
        if (!empty($advanced['css_class'])) {
            $classes[] = htmlspecialchars((string) $advanced['css_class'], ENT_QUOTES, 'UTF-8');
        }
        if (!empty($advanced['hide_mobile'])) {
            $classes[] = 'd-none';
            $classes[] = 'd-md-block';
        }
        if (!empty($advanced['hide_desktop'])) {
            $classes[] = 'd-md-none';
        }
        $style = self::styleFromDesign($design);
        $inner = self::renderModuleInner($type, $data, (string) ($mod['id'] ?? ''));
        if ($inner === '') {
            return '';
        }
        return '<div class="' . implode(' ', $classes) . '"' . ($style !== '' ? ' style="' . $style . '"' : '') . '>' . $inner . '</div>';
    }

    /** @param array<string, mixed> $data */
    private static function renderModuleInner(string $type, array $data, string $moduleId = ''): string
    {
        switch ($type) {
            case 'heading':
                $level = in_array((int) ($data['level'] ?? 2), [1, 2, 3, 4, 5, 6], true) ? (int) $data['level'] : 2;
                $text = htmlspecialchars((string) ($data['text'] ?? ''), ENT_QUOTES, 'UTF-8');
                return '<h' . $level . ' class="cms-mod-heading">' . $text . '</h' . $level . '>';
            case 'text':
                $text = nl2br(htmlspecialchars((string) ($data['text'] ?? ''), ENT_QUOTES, 'UTF-8'));
                return '<div class="cms-mod-text">' . $text . '</div>';
            case 'image':
                $mediaId = (int) ($data['media_id'] ?? 0);
                $alt = (string) ($data['alt'] ?? '');
                $link = (string) ($data['link'] ?? '');
                $caption = trim((string) ($data['caption'] ?? ''));
                $img = '';
                if ($mediaId > 0) {
                    $img = Media::responsiveImg($mediaId, [
                        'alt' => $alt,
                        'class' => 'img-fluid',
                        'sizes' => '(max-width: 768px) 100vw, min(960px, 100vw)',
                        'preferred_width' => 1024,
                    ]);
                }
                if ($img === '') {
                    $url = htmlspecialchars((string) ($data['url'] ?? ''), ENT_QUOTES, 'UTF-8');
                    if ($url === '') {
                        return '';
                    }
                    $img = '<img src="' . $url . '" alt="' . htmlspecialchars($alt, ENT_QUOTES, 'UTF-8') . '" class="img-fluid" loading="lazy" decoding="async">';
                }
                if ($link !== '') {
                    $img = '<a href="' . htmlspecialchars($link, ENT_QUOTES, 'UTF-8') . '" class="cms-mod-image-link">' . $img . '</a>';
                }
                $capHtml = '';
                if ($caption !== '') {
                    $capHtml = '<figcaption class="cms-mod-image-caption">' . nl2br(htmlspecialchars($caption, ENT_QUOTES, 'UTF-8')) . '</figcaption>';
                }
                return '<figure class="cms-mod-image">' . $img . $capHtml . '</figure>';
            case 'button':
                $label = htmlspecialchars((string) ($data['label'] ?? 'Learn more'), ENT_QUOTES, 'UTF-8');
                $url = htmlspecialchars((string) ($data['url'] ?? '#'), ENT_QUOTES, 'UTF-8');
                $style = (string) ($data['style'] ?? 'primary');
                $btnClass = match ($style) {
                    'secondary' => 'btn btn-secondary',
                    'outline' => 'btn btn-outline-primary',
                    default => 'btn btn-primary',
                };
                return '<a href="' . $url . '" class="' . $btnClass . ' cms-mod-button">' . $label . '</a>';
            case 'cta':
                $title = htmlspecialchars((string) ($data['title'] ?? ''), ENT_QUOTES, 'UTF-8');
                $text = nl2br(htmlspecialchars((string) ($data['text'] ?? ''), ENT_QUOTES, 'UTF-8'));
                $label = htmlspecialchars((string) ($data['label'] ?? 'Get started'), ENT_QUOTES, 'UTF-8');
                $url = htmlspecialchars((string) ($data['url'] ?? '#'), ENT_QUOTES, 'UTF-8');
                $html = '<div class="cms-mod-cta">';
                if ($title !== '') {
                    $html .= '<h3 class="cms-mod-cta-title">' . $title . '</h3>';
                }
                if ($text !== '') {
                    $html .= '<div class="cms-mod-cta-text">' . $text . '</div>';
                }
                $html .= '<a href="' . $url . '" class="btn btn-primary">' . $label . '</a></div>';
                return $html;
            case 'spacer':
                $size = (string) ($data['size'] ?? 'md');
                return '<div class="cms-mod-spacer cms-mod-spacer--' . htmlspecialchars($size, ENT_QUOTES, 'UTF-8') . '" aria-hidden="true"></div>';
            case 'divider':
                $style = (string) ($data['style'] ?? 'solid');
                return '<hr class="cms-mod-divider cms-mod-divider--' . htmlspecialchars($style, ENT_QUOTES, 'UTF-8') . '">';
            case 'html':
                return '<div class="cms-mod-html">' . self::sanitizeHtml((string) ($data['html'] ?? '')) . '</div>';
            case 'blurb':
                $title = htmlspecialchars((string) ($data['title'] ?? ''), ENT_QUOTES, 'UTF-8');
                $text = nl2br(htmlspecialchars((string) ($data['text'] ?? ''), ENT_QUOTES, 'UTF-8'));
                $icon = htmlspecialchars((string) ($data['icon'] ?? ''), ENT_QUOTES, 'UTF-8');
                $link = (string) ($data['url'] ?? '');
                $mediaId = (int) ($data['media_id'] ?? 0);
                $html = '<div class="cms-mod-blurb">';
                if ($mediaId > 0) {
                    $html .= Media::responsiveImg($mediaId, [
                        'alt' => $title,
                        'class' => 'cms-mod-blurb-img img-fluid',
                        'preferred_width' => 400,
                        'sizes' => '160px',
                    ]);
                } elseif ($icon !== '') {
                    $html .= '<div class="cms-mod-blurb-icon" aria-hidden="true">' . $icon . '</div>';
                }
                if ($title !== '') {
                    $titleHtml = '<h4 class="cms-mod-blurb-title">' . $title . '</h4>';
                    if ($link !== '') {
                        $titleHtml = '<a href="' . htmlspecialchars($link, ENT_QUOTES, 'UTF-8') . '">' . $titleHtml . '</a>';
                    }
                    $html .= $titleHtml;
                }
                if ($text !== '') {
                    $html .= '<div class="cms-mod-blurb-text">' . $text . '</div>';
                }
                $html .= '</div>';
                return $html;
            case 'carousel':
                return self::renderCarousel($data, $moduleId);
            default:
                return '';
        }
    }

    /** @param array<string, mixed> $data */
    private static function renderCarousel(array $data, string $moduleId = ''): string
    {
        $slides = is_array($data['slides'] ?? null) ? $data['slides'] : [];
        $renderedSlides = [];
        foreach ($slides as $slide) {
            if (!is_array($slide)) {
                continue;
            }
            $mediaId = (int) ($slide['media_id'] ?? 0);
            $alt = (string) ($slide['alt'] ?? '');
            $caption = (string) ($slide['caption'] ?? '');
            $link = (string) ($slide['link'] ?? '');
            $img = '';
            if ($mediaId > 0) {
                $img = Media::responsiveImg($mediaId, [
                    'alt' => $alt,
                    'class' => 'cms-carousel-img img-fluid',
                    'sizes' => '(max-width: 768px) 100vw, min(1320px, 100vw)',
                    'preferred_width' => 1200,
                ]);
            }
            if ($img === '') {
                $url = htmlspecialchars((string) ($slide['url'] ?? ''), ENT_QUOTES, 'UTF-8');
                if ($url === '') {
                    continue;
                }
                $img = '<img src="' . $url . '" alt="' . htmlspecialchars($alt, ENT_QUOTES, 'UTF-8')
                    . '" class="cms-carousel-img img-fluid" loading="lazy" decoding="async">';
            }
            if ($link !== '') {
                $img = '<a href="' . htmlspecialchars($link, ENT_QUOTES, 'UTF-8') . '" class="cms-carousel-link">' . $img . '</a>';
            }
            $slideHtml = '<div class="cms-carousel-slide" role="group" aria-roledescription="slide">';
            $slideHtml .= $img;
            if ($caption !== '') {
                $slideHtml .= '<div class="cms-carousel-caption">' . htmlspecialchars($caption, ENT_QUOTES, 'UTF-8') . '</div>';
            }
            $slideHtml .= '</div>';
            $renderedSlides[] = $slideHtml;
        }
        if ($renderedSlides === []) {
            return '';
        }
        $id = 'cms-carousel-' . preg_replace('/[^a-zA-Z0-9_\-]/', '', $moduleId !== '' ? $moduleId : uniqid('c', true));
        $autoplay = !empty($data['autoplay']) ? '1' : '0';
        $interval = (int) ($data['interval_ms'] ?? 5000);
        $showArrows = array_key_exists('show_arrows', $data) ? !empty($data['show_arrows']) : true;
        $showDots = array_key_exists('show_dots', $data) ? !empty($data['show_dots']) : true;
        $count = count($renderedSlides);

        $html = '<div class="cms-carousel" id="' . htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . '"'
            . ' data-cms-carousel'
            . ' data-autoplay="' . $autoplay . '"'
            . ' data-interval="' . $interval . '"'
            . ' aria-roledescription="carousel"'
            . ' aria-label="Image carousel">';
        $html .= '<div class="cms-carousel-viewport"><div class="cms-carousel-track">';
        foreach ($renderedSlides as $i => $slideHtml) {
            $active = $i === 0 ? ' is-active' : '';
            $html .= preg_replace(
                '/class="cms-carousel-slide"/',
                'class="cms-carousel-slide' . $active . '" aria-hidden="' . ($i === 0 ? 'false' : 'true') . '"',
                $slideHtml,
                1
            );
        }
        $html .= '</div></div>';

        if ($showArrows && $count > 1) {
            $html .= '<button type="button" class="cms-carousel-btn cms-carousel-prev" data-carousel-prev aria-label="Previous slide">&lsaquo;</button>';
            $html .= '<button type="button" class="cms-carousel-btn cms-carousel-next" data-carousel-next aria-label="Next slide">&rsaquo;</button>';
        }
        if ($showDots && $count > 1) {
            $html .= '<div class="cms-carousel-dots" role="tablist" aria-label="Slides">';
            for ($i = 0; $i < $count; $i++) {
                $html .= '<button type="button" class="cms-carousel-dot' . ($i === 0 ? ' is-active' : '') . '"'
                    . ' data-carousel-dot="' . $i . '"'
                    . ' aria-label="Go to slide ' . ($i + 1) . '"'
                    . ($i === 0 ? ' aria-current="true"' : '')
                    . '></button>';
            }
            $html .= '</div>';
        }
        $html .= '</div>';
        return $html;
    }

    public static function plainTextFromEntity(?object $entity): string
    {
        if (!$entity || empty($entity->layout_json)) {
            return '';
        }
        return self::plainText(self::parse((string) $entity->layout_json));
    }

    /** @param array{sections?: array<int, array<string, mixed>>} $layout */
    public static function plainText(array $layout): string
    {
        $parts = [];
        foreach ($layout['sections'] ?? [] as $section) {
            foreach ($section['rows'] ?? [] as $row) {
                foreach ($row['columns'] ?? [] as $col) {
                    foreach ($col['modules'] ?? [] as $mod) {
                        $type = (string) ($mod['type'] ?? '');
                        $data = is_array($mod['data'] ?? null) ? $mod['data'] : [];
                        switch ($type) {
                            case 'heading':
                            case 'text':
                                if (!empty($data['text'])) {
                                    $parts[] = (string) $data['text'];
                                }
                                break;
                            case 'button':
                                if (!empty($data['label'])) {
                                    $parts[] = (string) $data['label'];
                                }
                                break;
                            case 'cta':
                            case 'blurb':
                                if (!empty($data['title'])) {
                                    $parts[] = (string) $data['title'];
                                }
                                if (!empty($data['text'])) {
                                    $parts[] = (string) $data['text'];
                                }
                                if (!empty($data['label'])) {
                                    $parts[] = (string) $data['label'];
                                }
                                break;
                            case 'html':
                                if (!empty($data['html'])) {
                                    $parts[] = trim(strip_tags((string) $data['html']));
                                }
                                break;
                            case 'carousel':
                                foreach (is_array($data['slides'] ?? null) ? $data['slides'] : [] as $slide) {
                                    if (!is_array($slide)) {
                                        continue;
                                    }
                                    if (!empty($slide['caption'])) {
                                        $parts[] = (string) $slide['caption'];
                                    } elseif (!empty($slide['alt'])) {
                                        $parts[] = (string) $slide['alt'];
                                    }
                                }
                                break;
                            default:
                                break;
                        }
                    }
                }
            }
        }
        $text = trim(implode(' ', $parts));
        return preg_replace('/\s+/u', ' ', $text) ?? '';
    }

    /** Default starter layout for empty editor. */
    public static function starterLayout(): array
    {
        return [
            'version' => self::VERSION,
            'sections' => [
                [
                    'id' => self::id(null),
                    'type' => 'regular',
                    'settings' => [],
                    'rows' => [
                        [
                            'id' => self::id(null),
                            'settings' => [],
                            'columns' => [
                                [
                                    'id' => self::id(null),
                                    'width' => 12,
                                    'settings' => [],
                                    'modules' => [
                                        [
                                            'id' => self::id(null),
                                            'type' => 'heading',
                                            'data' => ['text' => 'Welcome', 'level' => 1],
                                            'design' => ['text_align' => 'center'],
                                            'advanced' => [],
                                        ],
                                        [
                                            'id' => self::id(null),
                                            'type' => 'text',
                                            'data' => ['text' => 'Edit this layout with the visual builder. Add sections, rows, columns, and modules.'],
                                            'design' => ['text_align' => 'center'],
                                            'advanced' => [],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

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

    private static function safeColor(string $color): string
    {
        $color = trim($color);
        if ($color === '') {
            return '';
        }
        if (preg_match('/^#[0-9a-fA-F]{3,8}$/', $color)) {
            return $color;
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

    /** @param array<string, mixed> $settings */
    private static function styleFromSettings(array $settings): string
    {
        $parts = [];
        if (!empty($settings['bg_color'])) {
            $parts[] = 'background-color:' . htmlspecialchars((string) $settings['bg_color'], ENT_QUOTES, 'UTF-8');
        }
        if (!empty($settings['padding'])) {
            $parts[] = 'padding:' . htmlspecialchars((string) $settings['padding'], ENT_QUOTES, 'UTF-8');
        }
        return implode(';', $parts);
    }

    /** @param array<string, mixed> $design */
    private static function styleFromDesign(array $design): string
    {
        $parts = [];
        if (!empty($design['text_align'])) {
            $parts[] = 'text-align:' . htmlspecialchars((string) $design['text_align'], ENT_QUOTES, 'UTF-8');
        }
        if (!empty($design['text_color'])) {
            $parts[] = 'color:' . htmlspecialchars((string) $design['text_color'], ENT_QUOTES, 'UTF-8');
        }
        if (!empty($design['bg_color'])) {
            $parts[] = 'background-color:' . htmlspecialchars((string) $design['bg_color'], ENT_QUOTES, 'UTF-8');
        }
        if (!empty($design['padding'])) {
            $parts[] = 'padding:' . htmlspecialchars((string) $design['padding'], ENT_QUOTES, 'UTF-8');
        }
        if (!empty($design['margin'])) {
            $parts[] = 'margin:' . htmlspecialchars((string) $design['margin'], ENT_QUOTES, 'UTF-8');
        }
        if (!empty($design['font_size'])) {
            $parts[] = 'font-size:' . htmlspecialchars((string) $design['font_size'], ENT_QUOTES, 'UTF-8');
        }
        return implode(';', $parts);
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
}
