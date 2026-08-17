<?php
namespace App\LayoutBuilder;

/**
 * LayoutBuilder implementation: Normalizer.
 */
trait Normalizer
{
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
        return self::withStyleOverrides([
            'id' => self::id($section['id'] ?? null),
            'type' => $type,
            'settings' => self::normalizeSettings(is_array($section['settings'] ?? null) ? $section['settings'] : []),
            'rows' => $rows,
        ], $section, 'settings');
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
        return self::withStyleOverrides([
            'id' => self::id($row['id'] ?? null),
            'settings' => self::normalizeSettings(is_array($row['settings'] ?? null) ? $row['settings'] : []),
            'columns' => $columns,
        ], $row, 'settings');
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
            $modOut = self::withStyleOverrides([
                'id' => self::id($mod['id'] ?? null),
                'type' => $type,
                'data' => self::normalizeModuleData($type, is_array($mod['data'] ?? null) ? $mod['data'] : []),
                'design' => self::normalizeDesign(is_array($mod['design'] ?? null) ? $mod['design'] : []),
                'advanced' => self::normalizeAdvanced(is_array($mod['advanced'] ?? null) ? $mod['advanced'] : []),
            ], $mod, 'design');
            $hover = self::normalizeDesignOverride(is_array($mod['design_hover'] ?? null) ? $mod['design_hover'] : []);
            if ($hover !== []) {
                $modOut['design_hover'] = $hover;
            }
            $modules[] = $modOut;
        }
        return self::withStyleOverrides([
            'id' => self::id($col['id'] ?? null),
            'width' => $width,
            'settings' => self::normalizeSettings(is_array($col['settings'] ?? null) ? $col['settings'] : []),
            'modules' => $modules,
        ], $col, 'settings');
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
                    'style' => self::normalizeButtonStyle((string) ($data['style'] ?? 'primary')),
                    'new_tab' => !empty($data['new_tab']),
                ];
            case 'cta':
                return [
                    'title' => mb_substr(trim((string) ($data['title'] ?? '')), 0, 200),
                    'text' => mb_substr(trim((string) ($data['text'] ?? '')), 0, 2000),
                    'label' => mb_substr(trim((string) ($data['label'] ?? 'Get started')), 0, 120),
                    'url' => self::safeUrl((string) ($data['url'] ?? '#')) ?: '#',
                    'style' => self::normalizeButtonStyle((string) ($data['style'] ?? 'primary')),
                    'new_tab' => !empty($data['new_tab']),
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
            'font_weight' => self::safeFontWeight((string) ($design['font_weight'] ?? '')),
            'line_height' => self::safeLineHeight((string) ($design['line_height'] ?? '')),
            'border_width' => self::safeSpacing((string) ($design['border_width'] ?? '')),
            'border_style' => self::safeBorderStyle((string) ($design['border_style'] ?? '')),
            'border_color' => self::safeColor((string) ($design['border_color'] ?? '')),
            'border_radius' => self::safeRadius((string) ($design['border_radius'] ?? '')),
            'box_shadow' => self::safeShadowKey((string) ($design['box_shadow'] ?? '')),
        ];
    }

    /**
     * Sparse tablet/mobile/hover design. Present empty string means unset at that breakpoint.
     *
     * @param array<string, mixed> $design
     * @return array<string, string>
     */
    private static function normalizeDesignOverride(array $design): array
    {
        $out = [];
        foreach ([
            'text_align', 'text_color', 'bg_color', 'padding', 'margin', 'font_size',
            'font_weight', 'line_height', 'border_width', 'border_style', 'border_color', 'border_radius', 'box_shadow',
        ] as $k) {
            if (!array_key_exists($k, $design)) {
                continue;
            }
            $raw = (string) $design[$k];
            if (trim($raw) === '') {
                $out[$k] = '';
                continue;
            }
            $full = self::normalizeDesign([$k => $raw]);
            $out[$k] = (string) ($full[$k] ?? '');
        }
        return $out;
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
            'min_height' => self::safeHeight((string) ($settings['min_height'] ?? '')),
            'valign' => in_array((string) ($settings['valign'] ?? ''), ['top', 'center', 'bottom'], true)
                ? (string) $settings['valign'] : '',
            'css_class' => mb_substr(preg_replace('/[^a-zA-Z0-9_\-\s]/', '', (string) ($settings['css_class'] ?? '')) ?? '', 0, 120),
            'border_width' => self::safeSpacing((string) ($settings['border_width'] ?? '')),
            'border_style' => self::safeBorderStyle((string) ($settings['border_style'] ?? '')),
            'border_color' => self::safeColor((string) ($settings['border_color'] ?? '')),
            'border_radius' => self::safeRadius((string) ($settings['border_radius'] ?? '')),
            'box_shadow' => self::safeShadowKey((string) ($settings['box_shadow'] ?? '')),
        ] + self::normalizeBackgroundSettings($settings);
    }

    /**
     * Optional background image + overlay (omitted when empty to keep JSON small).
     *
     * @param array<string, mixed> $settings
     * @return array<string, mixed>
     */
    private static function normalizeBackgroundSettings(array $settings): array
    {
        $out = [];
        $mid = (int) ($settings['bg_media_id'] ?? 0);
        if ($mid > 0) {
            $out['bg_media_id'] = $mid;
        }
        $img = self::safeCssUrl((string) ($settings['bg_image'] ?? ''));
        if ($img !== '') {
            $out['bg_image'] = $img;
        }
        $overlay = self::safeColor((string) ($settings['bg_overlay'] ?? ''));
        if ($overlay !== '') {
            $out['bg_overlay'] = $overlay;
        }
        $op = self::safeOpacity($settings['bg_overlay_opacity'] ?? '');
        if ($op !== '') {
            $out['bg_overlay_opacity'] = $op;
        }
        return $out;
    }

    /**
     * Sparse tablet/mobile settings (no css_class). Empty string means unset at that breakpoint.
     *
     * @param array<string, mixed> $settings
     * @return array<string, string>
     */
    private static function normalizeSettingsOverride(array $settings): array
    {
        $out = [];
        foreach (['bg_color', 'padding', 'min_height', 'valign', 'bg_overlay', 'bg_overlay_opacity', 'border_width', 'border_style', 'border_color', 'border_radius', 'box_shadow'] as $k) {
            if (!array_key_exists($k, $settings)) {
                continue;
            }
            $raw = (string) $settings[$k];
            if (trim($raw) === '') {
                $out[$k] = '';
                continue;
            }
            $full = self::normalizeSettings([$k => $raw]);
            $out[$k] = (string) ($full[$k] ?? '');
        }
        return $out;
    }

    /**
     * @param array<string, mixed> $out
     * @param array<string, mixed> $src
     * @return array<string, mixed>
     */
    private static function withStyleOverrides(array $out, array $src, string $kind): array
    {
        foreach (['tablet', 'mobile'] as $bp) {
            $key = $kind . '_' . $bp;
            $bag = is_array($src[$key] ?? null) ? $src[$key] : [];
            $norm = $kind === 'design'
                ? self::normalizeDesignOverride($bag)
                : self::normalizeSettingsOverride($bag);
            if ($norm !== []) {
                $out[$key] = $norm;
            }
        }
        return $out;
    }

    private static function normalizeButtonStyle(string $style): string
    {
        return in_array($style, ['primary', 'secondary', 'outline'], true) ? $style : 'primary';
    }
}
