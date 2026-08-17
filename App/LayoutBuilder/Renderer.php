<?php
namespace App\LayoutBuilder;

use App\Models\Media;

/**
 * LayoutBuilder implementation: Renderer.
 */
trait Renderer
{
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
        $css = self::compileStylesheet($layout);
        $html = '';
        if ($css !== '') {
            $html .= '<style class="cms-layout-css">' . $css . '</style>';
        }
        $html .= '<div class="cms-layout">';
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
        $elClass = self::elementCssClass((string) ($section['id'] ?? ''));
        if ($elClass !== '') {
            $classes[] = $elClass;
        }
        if (!empty($settings['css_class'])) {
            $classes[] = htmlspecialchars((string) $settings['css_class'], ENT_QUOTES, 'UTF-8');
        }
        $html = '<section class="' . implode(' ', $classes) . '">';
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
        $elClass = self::elementCssClass((string) ($row['id'] ?? ''));
        if ($elClass !== '') {
            $classes[] = $elClass;
        }
        if (!empty($settings['css_class'])) {
            $classes[] = htmlspecialchars((string) $settings['css_class'], ENT_QUOTES, 'UTF-8');
        }
        $html = '<div class="' . implode(' ', $classes) . '">';
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
        $elClass = self::elementCssClass((string) ($col['id'] ?? ''));
        if ($elClass !== '') {
            $classes[] = $elClass;
        }
        $valign = (string) ($settings['valign'] ?? '');
        if (in_array($valign, ['center', 'bottom'], true)) {
            $classes[] = 'cms-layout-column--valign-' . $valign;
        }
        if (!empty($settings['css_class'])) {
            $classes[] = htmlspecialchars((string) $settings['css_class'], ENT_QUOTES, 'UTF-8');
        }
        $html = '<div class="' . implode(' ', $classes) . '">';
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
        $elClass = self::elementCssClass((string) ($mod['id'] ?? ''));
        if ($elClass !== '') {
            $classes[] = $elClass;
        }
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
        $inner = self::renderModuleInner($type, $data, (string) ($mod['id'] ?? ''));
        if ($inner === '') {
            return '';
        }
        return '<div class="' . implode(' ', $classes) . '">' . $inner . '</div>';
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
                $btnClass = self::buttonClass((string) ($data['style'] ?? 'primary'));
                return '<a href="' . $url . '" class="' . $btnClass . ' cms-mod-button"' . self::linkTargetAttrs(!empty($data['new_tab'])) . '>' . $label . '</a>';
            case 'cta':
                $title = htmlspecialchars((string) ($data['title'] ?? ''), ENT_QUOTES, 'UTF-8');
                $text = nl2br(htmlspecialchars((string) ($data['text'] ?? ''), ENT_QUOTES, 'UTF-8'));
                $label = htmlspecialchars((string) ($data['label'] ?? 'Get started'), ENT_QUOTES, 'UTF-8');
                $url = htmlspecialchars((string) ($data['url'] ?? '#'), ENT_QUOTES, 'UTF-8');
                $btnClass = self::buttonClass((string) ($data['style'] ?? 'primary'));
                $html = '<div class="cms-mod-cta">';
                if ($title !== '') {
                    $html .= '<h3 class="cms-mod-cta-title">' . $title . '</h3>';
                }
                if ($text !== '') {
                    $html .= '<div class="cms-mod-cta-text">' . $text . '</div>';
                }
                $html .= '<a href="' . $url . '" class="' . $btnClass . ' cms-mod-cta-btn"' . self::linkTargetAttrs(!empty($data['new_tab'])) . '>' . $label . '</a></div>';
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

    private static function buttonClass(string $style): string
    {
        return match (self::normalizeButtonStyle($style)) {
            'secondary' => 'btn btn-secondary',
            'outline' => 'btn btn-outline-primary',
            default => 'btn btn-primary',
        };
    }

    private static function linkTargetAttrs(bool $newTab): string
    {
        return $newTab ? ' target="_blank" rel="noopener noreferrer"' : '';
    }
}
