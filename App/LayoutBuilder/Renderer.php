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
        $html .= self::renderSectionMedia($settings);
        $html .= self::renderShapeDivider($settings, 'top');
        $html .= '<div class="cms-layout-section-inner' . ($type === 'fullwidth' ? ' cms-layout-section-inner--full' : ' container') . '">';
        $rows = is_array($section['rows'] ?? null) ? $section['rows'] : [];
        foreach ($rows as $row) {
            $html .= self::renderRow($row);
        }
        $html .= '</div>';
        $html .= self::renderShapeDivider($settings, 'bottom');
        $html .= '</section>';
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
        if (!empty($settings['col_reverse_mobile'])) {
            $classes[] = 'cms-layout-row--reverse-mobile';
        }
        $html = '<div class="' . implode(' ', $classes) . '">';
        $columns = is_array($row['columns'] ?? null) ? $row['columns'] : [];
        foreach ($columns as $col) {
            $html .= self::renderColumn($col);
        }
        $html .= '</div>';
        return $html;
    }

    /** @param array<string, mixed> $settings */
    private static function renderSectionMedia(array $settings): string
    {
        $url = (string) ($settings['bg_video_url'] ?? '');
        $parsed = $url !== '' ? self::parseVideoUrl($url) : null;
        if ($parsed === null) {
            return '';
        }
        $src = self::videoBackgroundSrc($parsed);
        if ($src === '') {
            return '';
        }
        $esc = htmlspecialchars($src, ENT_QUOTES, 'UTF-8');
        $html = '<div class="cms-layout-section-bg" aria-hidden="true">';
        if ($parsed['kind'] === 'file') {
            $html .= '<video autoplay muted loop playsinline preload="metadata" src="' . $esc . '"></video>';
        } else {
            $html .= '<iframe src="' . $esc . '" title="Background video" tabindex="-1" allow="autoplay; encrypted-media" referrerpolicy="strict-origin-when-cross-origin"></iframe>';
        }
        $html .= '</div>';
        return $html;
    }

    /** @param array{kind: string, id?: string, src: string} $parsed */
    private static function videoBackgroundSrc(array $parsed): string
    {
        $id = preg_replace('/[^A-Za-z0-9_-]/', '', (string) ($parsed['id'] ?? '')) ?? '';
        if (($parsed['kind'] ?? '') === 'youtube' && preg_match('/^[A-Za-z0-9_-]{11}$/', $id)) {
            return 'https://www.youtube-nocookie.com/embed/' . $id
                . '?autoplay=1&mute=1&loop=1&controls=0&playsinline=1&rel=0&playlist=' . $id;
        }
        if (($parsed['kind'] ?? '') === 'vimeo' && preg_match('/^\d{6,12}$/', $id)) {
            return 'https://player.vimeo.com/video/' . $id . '?background=1&autoplay=1&muted=1&loop=1';
        }
        if (($parsed['kind'] ?? '') === 'file') {
            return (string) ($parsed['src'] ?? '');
        }
        return '';
    }

    /** @param array<string, mixed> $settings */
    private static function renderShapeDivider(array $settings, string $side): string
    {
        if ($side !== 'top' && $side !== 'bottom') {
            return '';
        }
        $key = self::safeShapeKey((string) ($settings['shape_' . $side] ?? ''));
        if ($key === '' || !isset(self::SHAPE_SVGS[$key])) {
            return '';
        }
        $h = self::safeShapeHeightKey((string) ($settings['shape_' . $side . '_height'] ?? ''));
        if ($h === '') {
            $h = 'md';
        }
        $classes = ['cms-shape', 'cms-shape--' . $side, 'cms-shape--' . $key, 'cms-shape--' . $h];
        if (!empty($settings['shape_' . $side . '_flip'])) {
            $classes[] = 'cms-shape--flip';
        }
        return '<div class="' . implode(' ', $classes) . '" aria-hidden="true">' . self::SHAPE_SVGS[$key] . '</div>';
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
        $inner = $type === 'inner_row'
            ? self::renderInnerRow($mod)
            : self::renderModuleInner($type, $data, (string) ($mod['id'] ?? ''));
        if ($inner === '') {
            return '';
        }
        return '<div class="' . implode(' ', $classes) . '">' . $inner . '</div>';
    }

    /** @param array<string, mixed> $mod */
    private static function renderInnerRow(array $mod): string
    {
        $columns = is_array($mod['columns'] ?? null) ? $mod['columns'] : [];
        if ($columns === []) {
            return '';
        }
        $html = '<div class="cms-mod-inner-row row g-3">';
        foreach ($columns as $col) {
            if (!is_array($col)) {
                continue;
            }
            $html .= self::renderColumn($col);
        }
        $html .= '</div>';
        return $html;
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
                $html = self::sanitizeRichText((string) ($data['text'] ?? ''));
                return $html === '' ? '' : '<div class="cms-mod-text">' . $html . '</div>';
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
                $text = self::sanitizeRichText((string) ($data['text'] ?? ''));
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
                $text = self::sanitizeRichText((string) ($data['text'] ?? ''));
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
            case 'accordion':
                return self::renderAccordion($data);
            case 'tabs':
                return self::renderTabs($data, $moduleId);
            case 'icon_list':
                return self::renderIconList($data);
            case 'gallery':
                return self::renderGallery($data);
            case 'testimonial':
                return self::renderTestimonials($data);
            case 'video':
                return self::renderVideo($data);
            default:
                $widgetType = self::widgetTypeFromModule($type);
                if ($widgetType !== null) {
                    return self::renderWidgetModule($widgetType, $data);
                }
                return '';
        }
    }

    /** @param array<string, mixed> $data */
    private static function renderWidgetModule(string $widgetType, array $data): string
    {
        $title = trim((string) ($data['title'] ?? ''));
        $config = $data;
        unset($config['title'], $config['social_lines']);
        $widget = (object) [
            'widget_type' => $widgetType,
            'title' => $title,
            'config_json' => json_encode($config, JSON_UNESCAPED_UNICODE),
        ];
        return \App\Models\Widget::renderWidget($widget);
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

    /** @param array<string, mixed> $data */
    private static function renderAccordion(array $data): string
    {
        $items = is_array($data['items'] ?? null) ? $data['items'] : [];
        $firstOpen = array_key_exists('first_open', $data) ? !empty($data['first_open']) : true;
        $html = '<div class="cms-mod-accordion">';
        $i = 0;
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }
            $title = htmlspecialchars((string) ($item['title'] ?? 'Item'), ENT_QUOTES, 'UTF-8');
            $body = self::sanitizeRichText((string) ($item['body'] ?? ''));
            $open = ($i === 0 && $firstOpen) ? ' open' : '';
            $html .= '<details class="cms-mod-accordion-item"' . $open . '>';
            $html .= '<summary class="cms-mod-accordion-title">' . $title . '</summary>';
            $html .= '<div class="cms-mod-accordion-body">' . $body . '</div>';
            $html .= '</details>';
            $i++;
        }
        $html .= '</div>';
        return $i === 0 ? '' : $html;
    }

    /** @param array<string, mixed> $data */
    private static function renderTabs(array $data, string $moduleId = ''): string
    {
        $items = is_array($data['items'] ?? null) ? $data['items'] : [];
        $clean = [];
        foreach ($items as $item) {
            if (is_array($item)) {
                $clean[] = $item;
            }
        }
        if ($clean === []) {
            return '';
        }
        $sid = preg_replace('/[^a-zA-Z0-9_\-]/', '', $moduleId !== '' ? $moduleId : uniqid('t', true));
        $name = 'cms-tabs-' . $sid;
        $html = '<div class="cms-mod-tabs" id="' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '">';
        foreach ($clean as $i => $item) {
            $id = $name . '-' . $i;
            $checked = $i === 0 ? ' checked' : '';
            $title = htmlspecialchars((string) ($item['title'] ?? 'Tab'), ENT_QUOTES, 'UTF-8');
            $html .= '<input class="cms-mod-tabs-input" type="radio" name="' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8')
                . '" id="' . htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . '" value="' . $i . '"' . $checked
                . ' aria-label="' . $title . '">';
        }
        $html .= '<div class="cms-mod-tabs-nav">';
        foreach ($clean as $i => $item) {
            $id = $name . '-' . $i;
            $title = htmlspecialchars((string) ($item['title'] ?? 'Tab'), ENT_QUOTES, 'UTF-8');
            $html .= '<label class="cms-mod-tabs-label" for="' . htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . '">' . $title . '</label>';
        }
        $html .= '</div><div class="cms-mod-tabs-panels">';
        foreach ($clean as $item) {
            $body = self::sanitizeRichText((string) ($item['body'] ?? ''));
            $html .= '<div class="cms-mod-tabs-panel">' . $body . '</div>';
        }
        $html .= '</div></div>';
        return $html;
    }

    /** @param array<string, mixed> $data */
    private static function renderIconList(array $data): string
    {
        $items = is_array($data['items'] ?? null) ? $data['items'] : [];
        $html = '<ul class="cms-mod-icon-list">';
        $n = 0;
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }
            $icon = htmlspecialchars((string) ($item['icon'] ?? '•'), ENT_QUOTES, 'UTF-8');
            $text = nl2br(htmlspecialchars((string) ($item['text'] ?? ''), ENT_QUOTES, 'UTF-8'));
            $html .= '<li class="cms-mod-icon-list-item">';
            $html .= '<span class="cms-mod-icon-list-icon" aria-hidden="true">' . $icon . '</span>';
            $html .= '<span class="cms-mod-icon-list-text">' . $text . '</span>';
            $html .= '</li>';
            $n++;
        }
        $html .= '</ul>';
        return $n === 0 ? '' : $html;
    }

    /** @param array<string, mixed> $data */
    private static function renderGallery(array $data): string
    {
        $cols = (int) ($data['columns'] ?? 3);
        if (!in_array($cols, [2, 3, 4], true)) {
            $cols = 3;
        }
        $items = is_array($data['items'] ?? null) ? $data['items'] : [];
        $html = '<div class="cms-mod-gallery cms-mod-gallery--cols-' . $cols . '">';
        $n = 0;
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }
            $mediaId = (int) ($item['media_id'] ?? 0);
            $alt = (string) ($item['alt'] ?? '');
            $caption = trim((string) ($item['caption'] ?? ''));
            $link = (string) ($item['link'] ?? '');
            $img = '';
            if ($mediaId > 0) {
                $img = Media::responsiveImg($mediaId, [
                    'alt' => $alt,
                    'class' => 'cms-mod-gallery-img img-fluid',
                    'sizes' => '(max-width: 576px) 100vw, ' . (int) round(100 / $cols) . 'vw',
                    'preferred_width' => 800,
                ]);
            }
            if ($img === '') {
                $url = htmlspecialchars((string) ($item['url'] ?? ''), ENT_QUOTES, 'UTF-8');
                if ($url === '') {
                    continue;
                }
                $img = '<img src="' . $url . '" alt="' . htmlspecialchars($alt, ENT_QUOTES, 'UTF-8')
                    . '" class="cms-mod-gallery-img img-fluid" loading="lazy" decoding="async">';
            }
            if ($link !== '') {
                $img = '<a href="' . htmlspecialchars($link, ENT_QUOTES, 'UTF-8') . '" class="cms-mod-gallery-link">' . $img . '</a>';
            }
            $figure = '<figure class="cms-mod-gallery-item">' . $img;
            if ($caption !== '') {
                $figure .= '<figcaption class="cms-mod-gallery-caption">' . htmlspecialchars($caption, ENT_QUOTES, 'UTF-8') . '</figcaption>';
            }
            $figure .= '</figure>';
            $html .= $figure;
            $n++;
        }
        $html .= '</div>';
        return $n === 0 ? '' : $html;
    }

    /** @param array<string, mixed> $data */
    private static function renderTestimonials(array $data): string
    {
        $items = is_array($data['items'] ?? null) ? $data['items'] : [];
        $html = '<div class="cms-mod-testimonials">';
        $n = 0;
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }
            $quote = nl2br(htmlspecialchars((string) ($item['quote'] ?? ''), ENT_QUOTES, 'UTF-8'));
            $name = htmlspecialchars((string) ($item['name'] ?? ''), ENT_QUOTES, 'UTF-8');
            $role = htmlspecialchars((string) ($item['role'] ?? ''), ENT_QUOTES, 'UTF-8');
            $mediaId = (int) ($item['media_id'] ?? 0);
            $photo = '';
            if ($mediaId > 0) {
                $photo = Media::responsiveImg($mediaId, [
                    'alt' => (string) ($item['name'] ?? ''),
                    'class' => 'cms-mod-testimonial-photo',
                    'preferred_width' => 160,
                    'sizes' => '72px',
                ]);
            }
            if ($photo === '') {
                $url = htmlspecialchars((string) ($item['url'] ?? ''), ENT_QUOTES, 'UTF-8');
                if ($url !== '') {
                    $photo = '<img src="' . $url . '" alt="' . $name . '" class="cms-mod-testimonial-photo" loading="lazy" decoding="async">';
                }
            }
            $html .= '<blockquote class="cms-mod-testimonial">';
            if ($photo !== '') {
                $html .= $photo;
            }
            if ($quote !== '') {
                $html .= '<p class="cms-mod-testimonial-quote">' . $quote . '</p>';
            }
            if ($name !== '' || $role !== '') {
                $html .= '<footer class="cms-mod-testimonial-meta">';
                if ($name !== '') {
                    $html .= '<cite class="cms-mod-testimonial-name">' . $name . '</cite>';
                }
                if ($role !== '') {
                    $html .= '<span class="cms-mod-testimonial-role">' . $role . '</span>';
                }
                $html .= '</footer>';
            }
            $html .= '</blockquote>';
            $n++;
        }
        $html .= '</div>';
        return $n === 0 ? '' : $html;
    }

    /** @param array<string, mixed> $data */
    private static function renderVideo(array $data): string
    {
        $parsed = self::parseVideoUrl((string) ($data['url'] ?? ''));
        if ($parsed === null) {
            return '';
        }
        $src = htmlspecialchars($parsed['src'], ENT_QUOTES, 'UTF-8');
        $caption = trim((string) ($data['caption'] ?? ''));
        $title = $caption !== ''
            ? htmlspecialchars($caption, ENT_QUOTES, 'UTF-8')
            : 'Video';
        $frame = '<div class="cms-mod-video-frame">';
        if ($parsed['kind'] === 'file') {
            $frame .= '<video class="cms-mod-video-player" controls preload="metadata" src="' . $src . '" title="' . $title . '"></video>';
        } else {
            $frame .= '<iframe class="cms-mod-video-iframe" src="' . $src . '" title="' . $title . '"'
                . ' loading="lazy" allowfullscreen referrerpolicy="strict-origin-when-cross-origin"></iframe>';
        }
        $frame .= '</div>';
        $html = '<figure class="cms-mod-video">' . $frame;
        if ($caption !== '') {
            $html .= '<figcaption class="cms-mod-video-caption">' . nl2br(htmlspecialchars($caption, ENT_QUOTES, 'UTF-8')) . '</figcaption>';
        }
        $html .= '</figure>';
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
                        if (!is_array($mod)) {
                            continue;
                        }
                        foreach (self::modulePlainParts($mod) as $bit) {
                            $parts[] = $bit;
                        }
                    }
                }
            }
        }
        $text = trim(implode(' ', $parts));
        return preg_replace('/\s+/u', ' ', $text) ?? '';
    }

    /**
     * @param array<string, mixed> $mod
     * @return list<string>
     */
    private static function modulePlainParts(array $mod): array
    {
        $type = (string) ($mod['type'] ?? '');
        $data = is_array($mod['data'] ?? null) ? $mod['data'] : [];
        $parts = [];
        if ($type === 'inner_row') {
            foreach ($mod['columns'] ?? [] as $col) {
                if (!is_array($col)) {
                    continue;
                }
                foreach ($col['modules'] ?? [] as $inner) {
                    if (!is_array($inner)) {
                        continue;
                    }
                    foreach (self::modulePlainParts($inner) as $bit) {
                        $parts[] = $bit;
                    }
                }
            }
            return $parts;
        }
        switch ($type) {
            case 'heading':
                if (!empty($data['text'])) {
                    $parts[] = (string) $data['text'];
                }
                break;
            case 'text':
                if (!empty($data['text'])) {
                    $parts[] = trim(strip_tags((string) $data['text']));
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
                    $parts[] = trim(strip_tags((string) $data['text']));
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
            case 'accordion':
            case 'tabs':
                foreach (is_array($data['items'] ?? null) ? $data['items'] : [] as $item) {
                    if (!is_array($item)) {
                        continue;
                    }
                    if (!empty($item['title'])) {
                        $parts[] = (string) $item['title'];
                    }
                    if (!empty($item['body'])) {
                        $parts[] = trim(strip_tags((string) $item['body']));
                    }
                }
                break;
            case 'icon_list':
                foreach (is_array($data['items'] ?? null) ? $data['items'] : [] as $item) {
                    if (!is_array($item)) {
                        continue;
                    }
                    if (!empty($item['text'])) {
                        $parts[] = (string) $item['text'];
                    }
                }
                break;
            case 'gallery':
                foreach (is_array($data['items'] ?? null) ? $data['items'] : [] as $item) {
                    if (!is_array($item)) {
                        continue;
                    }
                    if (!empty($item['caption'])) {
                        $parts[] = (string) $item['caption'];
                    } elseif (!empty($item['alt'])) {
                        $parts[] = (string) $item['alt'];
                    }
                }
                break;
            case 'testimonial':
                foreach (is_array($data['items'] ?? null) ? $data['items'] : [] as $item) {
                    if (!is_array($item)) {
                        continue;
                    }
                    if (!empty($item['quote'])) {
                        $parts[] = (string) $item['quote'];
                    }
                    if (!empty($item['name'])) {
                        $parts[] = (string) $item['name'];
                    }
                    if (!empty($item['role'])) {
                        $parts[] = (string) $item['role'];
                    }
                }
                break;
            case 'video':
                if (!empty($data['caption'])) {
                    $parts[] = (string) $data['caption'];
                }
                break;
            default:
                break;
        }
        return $parts;
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
