<?php
namespace App\LayoutBuilder;

use App\Models\Media;

/**
 * LayoutBuilder implementation: Css.
 */
trait Css
{
    /** @param array<string, mixed> $settings */
    private static function resolveBgUrl(array $settings): string
    {
        $id = (int) ($settings['bg_media_id'] ?? 0);
        if ($id > 0) {
            $url = self::safeCssUrl(Media::publicShareUrl($id));
            if ($url !== '') {
                return $url;
            }
        }
        return self::safeCssUrl((string) ($settings['bg_image'] ?? ''));
    }

    private static function overlayCss(string $storedColor, string $opacity): string
    {
        $color = self::colorCssValue($storedColor);
        if ($color === '') {
            return '';
        }
        $op = $opacity === '' ? 40 : (int) $opacity;
        if ($op < 1) {
            return '';
        }
        if ($op > 80) {
            $op = 80;
        }
        if (str_starts_with($color, '#')) {
            $rgba = self::hexToRgba($color, $op / 100);
            if ($rgba === '') {
                return '';
            }
            return 'linear-gradient(' . $rgba . ',' . $rgba . ')';
        }
        $mix = 'color-mix(in srgb,' . $color . ' ' . $op . '%,transparent)';
        return 'linear-gradient(' . $mix . ',' . $mix . ')';
    }

    private static function hexToRgba(string $hex, float $alpha): string
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3 && ctype_xdigit($hex)) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        if (strlen($hex) < 6 || !ctype_xdigit(substr($hex, 0, 6))) {
            return '';
        }
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        $a = max(0, min(1, $alpha));
        $aStr = rtrim(rtrim(sprintf('%.2f', $a), '0'), '.');
        if ($aStr === '') {
            $aStr = '0';
        }
        return 'rgba(' . $r . ',' . $g . ',' . $b . ',' . $aStr . ')';
    }

    /**
     * @param list<string> $parts
     * @param array<string, mixed> $settings
     * @param array<string, mixed> $base
     */
    private static function appendBackgroundDecls(array &$parts, array $settings, bool $override, array $base): void
    {
        $bgKeys = ['bg_media_id', 'bg_image', 'bg_overlay', 'bg_overlay_opacity'];
        $touches = false;
        foreach ($bgKeys as $k) {
            if (array_key_exists($k, $settings)) {
                $touches = true;
                break;
            }
        }
        if ($override && !$touches) {
            return;
        }
        $imgSrc = ($override && $base !== []) ? $base : $settings;
        $url = self::resolveBgUrl($imgSrc);
        $overlayColor = array_key_exists('bg_overlay', $settings) || !$override
            ? (string) ($settings['bg_overlay'] ?? '')
            : (string) ($base['bg_overlay'] ?? '');
        $overlayOp = array_key_exists('bg_overlay_opacity', $settings) || !$override
            ? self::safeOpacity($settings['bg_overlay_opacity'] ?? '')
            : self::safeOpacity($base['bg_overlay_opacity'] ?? '');
        if ($override && ($overlayColor === '' && array_key_exists('bg_overlay', $settings))) {
            $layer = $url !== '' ? 'url("' . $url . '")' : '';
            if ($layer !== '') {
                $parts[] = 'background-image:' . $layer;
                $parts[] = 'background-size:cover';
                $parts[] = 'background-position:center';
                $parts[] = 'background-repeat:no-repeat';
            } elseif ($url === '') {
                $parts[] = 'background-image:unset';
                $parts[] = 'background-size:unset';
            }
            return;
        }
        $overlay = self::overlayCss($overlayColor, $overlayOp);
        if ($url === '' && $overlay === '') {
            if ($override && $touches) {
                $parts[] = 'background-image:unset';
            }
            return;
        }
        $image = $url !== '' ? 'url("' . $url . '")' : '';
        if ($overlay !== '' && $image !== '') {
            $parts[] = 'background-image:' . $overlay . ',' . $image;
        } elseif ($image !== '') {
            $parts[] = 'background-image:' . $image;
        } elseif ($overlay !== '') {
            $parts[] = 'background-image:' . $overlay;
        }
        if ($image !== '' || $overlay !== '') {
            $parts[] = 'background-size:cover';
            $parts[] = 'background-position:center';
            $parts[] = 'background-repeat:no-repeat';
        }
    }

    /**
     * CSS class hook for a layout node id (same 4–40 [A-Za-z0-9_-] as id()).
     * Used by the public stylesheet and the frontend editor live CSS.
     */
    public static function elementCssClass(string $id): string
    {
        $id = trim($id);
        if ($id === '' || !preg_match('/^[a-zA-Z0-9_-]{4,40}$/', $id)) {
            return '';
        }
        return 'cms-el-' . $id;
    }

    /**
     * Compile design/settings into a stylesheet. Values are re-checked with the same
     * safeColor / safeSpacing / safeHeight helpers as normalize (no raw user CSS).
     * Module design_hover compiles to :hover (same on every device).
     *
     * @param array{version?: int, sections?: array<int, array<string, mixed>>} $layout
     */
    public static function compileStylesheet(array $layout): string
    {
        $base = [];
        $tablet = [];
        $mobile = [];
        $sections = is_array($layout['sections'] ?? null) ? $layout['sections'] : [];
        foreach ($sections as $section) {
            if (!is_array($section)) {
                continue;
            }
            self::collectNodeCss($base, $tablet, $mobile, (string) ($section['id'] ?? ''), $section, 'settings');
            $rows = is_array($section['rows'] ?? null) ? $section['rows'] : [];
            foreach ($rows as $row) {
                if (!is_array($row)) {
                    continue;
                }
                self::collectNodeCss($base, $tablet, $mobile, (string) ($row['id'] ?? ''), $row, 'settings');
                $columns = is_array($row['columns'] ?? null) ? $row['columns'] : [];
                foreach ($columns as $col) {
                    if (!is_array($col)) {
                        continue;
                    }
                    self::collectNodeCss($base, $tablet, $mobile, (string) ($col['id'] ?? ''), $col, 'settings');
                    $modules = is_array($col['modules'] ?? null) ? $col['modules'] : [];
                    foreach ($modules as $mod) {
                        if (!is_array($mod)) {
                            continue;
                        }
                        self::collectNodeCss($base, $tablet, $mobile, (string) ($mod['id'] ?? ''), $mod, 'design');
                    }
                }
            }
        }
        $css = implode('', $base);
        if ($tablet !== []) {
            $css .= '@media (max-width:' . self::CSS_TABLET_MAX . '){' . implode('', $tablet) . '}';
        }
        if ($mobile !== []) {
            $css .= '@media (max-width:' . self::CSS_MOBILE_MAX . '){' . implode('', $mobile) . '}';
        }
        return $css;
    }

    /**
     * @param list<string> $base
     * @param list<string> $tablet
     * @param list<string> $mobile
     * @param array<string, mixed> $node
     */
    private static function collectNodeCss(array &$base, array &$tablet, array &$mobile, string $id, array $node, string $kind): void
    {
        $baseDecls = $kind === 'design'
            ? self::styleFromDesign(is_array($node[$kind] ?? null) ? $node[$kind] : [], false)
            : self::styleFromSettings(is_array($node[$kind] ?? null) ? $node[$kind] : [], false);
        $rule = self::cssRule($id, $baseDecls);
        if ($rule !== '') {
            $base[] = $rule;
        }
        $srcBase = is_array($node[$kind] ?? null) ? $node[$kind] : [];
        $t = is_array($node[$kind . '_tablet'] ?? null) ? $node[$kind . '_tablet'] : [];
        $tDecls = $kind === 'design' ? self::styleFromDesign($t, true) : self::styleFromSettings($t, true, $srcBase);
        $rule = self::cssRule($id, $tDecls);
        if ($rule !== '') {
            $tablet[] = $rule;
        }
        $m = is_array($node[$kind . '_mobile'] ?? null) ? $node[$kind . '_mobile'] : [];
        $mDecls = $kind === 'design' ? self::styleFromDesign($m, true) : self::styleFromSettings($m, true, $srcBase);
        $rule = self::cssRule($id, $mDecls);
        if ($rule !== '') {
            $mobile[] = $rule;
        }
        if ($kind !== 'design') {
            return;
        }
        $hover = is_array($node['design_hover'] ?? null) ? $node['design_hover'] : [];
        if ($hover === []) {
            return;
        }
        $hDecls = self::styleFromDesign($hover, false);
        $border = self::colorCssValue((string) ($hover['bg_color'] ?? ''));
        if ($border !== '') {
            $hDecls = $hDecls === '' ? 'border-color:' . $border : $hDecls . ';border-color:' . $border;
        }
        $rule = self::cssHoverRule($id, $hDecls);
        if ($rule !== '') {
            $base[] = $rule;
        }
    }

    private static function cssRule(string $id, string $decls): string
    {
        $cls = self::elementCssClass($id);
        if ($cls === '' || $decls === '') {
            return '';
        }
        return '.' . $cls . '{' . $decls . '}';
    }

    private static function cssHoverRule(string $id, string $decls): string
    {
        $cls = self::elementCssClass($id);
        if ($cls === '' || $decls === '') {
            return '';
        }
        $sel = '.' . $cls . ':hover,.' . $cls . ':hover .btn,.' . $cls . ':hover a,.public-site .' . $cls . ':hover .btn';

        return '.' . $cls . '{transition:color .15s ease,background-color .15s ease,border-color .15s ease,box-shadow .15s ease}'
            . $sel . '{' . $decls . '}';
    }

    /** @param array<string, mixed> $settings */
    private static function styleFromSettings(array $settings, bool $override = false, array $base = []): string
    {
        $parts = [];
        self::pushDecl($parts, 'background-color', self::colorCssValue((string) ($settings['bg_color'] ?? '')), $override, array_key_exists('bg_color', $settings));
        self::pushDecl($parts, 'padding', self::safeSpacing((string) ($settings['padding'] ?? '')), $override, array_key_exists('padding', $settings));
        self::pushDecl($parts, 'min-height', self::safeHeight((string) ($settings['min_height'] ?? '')), $override, array_key_exists('min_height', $settings));
        if (array_key_exists('valign', $settings) || !$override) {
            $valign = (string) ($settings['valign'] ?? '');
            if ($valign === 'center') {
                $parts[] = 'display:flex';
                $parts[] = 'flex-direction:column';
                $parts[] = 'justify-content:center';
            } elseif ($valign === 'bottom') {
                $parts[] = 'display:flex';
                $parts[] = 'flex-direction:column';
                $parts[] = 'justify-content:flex-end';
            } elseif ($override && ($valign === '' || $valign === 'top')) {
                $parts[] = 'justify-content:flex-start';
            }
        }
        self::appendBackgroundDecls($parts, $settings, $override, $base);
        self::appendChromeDecls($parts, $settings, $override);
        return implode(';', $parts);
    }

    /** @param array<string, mixed> $design */
    private static function styleFromDesign(array $design, bool $override = false): string
    {
        $parts = [];
        $align = (string) ($design['text_align'] ?? '');
        if (in_array($align, ['left', 'center', 'right', 'justify'], true)) {
            $parts[] = 'text-align:' . $align;
        } elseif ($override && array_key_exists('text_align', $design)) {
            $parts[] = 'text-align:unset';
        }
        self::pushDecl($parts, 'color', self::colorCssValue((string) ($design['text_color'] ?? '')), $override, array_key_exists('text_color', $design));
        self::pushDecl($parts, 'background-color', self::colorCssValue((string) ($design['bg_color'] ?? '')), $override, array_key_exists('bg_color', $design));
        self::pushDecl($parts, 'padding', self::safeSpacing((string) ($design['padding'] ?? '')), $override, array_key_exists('padding', $design));
        self::pushDecl($parts, 'margin', self::safeSpacing((string) ($design['margin'] ?? '')), $override, array_key_exists('margin', $design));
        self::pushDecl($parts, 'font-size', self::safeFontSize((string) ($design['font_size'] ?? '')), $override, array_key_exists('font_size', $design));
        self::pushDecl($parts, 'font-weight', self::safeFontWeight((string) ($design['font_weight'] ?? '')), $override, array_key_exists('font_weight', $design));
        self::pushDecl($parts, 'line-height', self::safeLineHeight((string) ($design['line_height'] ?? '')), $override, array_key_exists('line_height', $design));
        self::appendChromeDecls($parts, $design, $override);
        return implode(';', $parts);
    }

    /**
     * @param list<string> $parts
     * @param array<string, mixed> $bag
     */
    private static function appendChromeDecls(array &$parts, array $bag, bool $override): void
    {
        $width = self::safeSpacing((string) ($bag['border_width'] ?? ''));
        $style = self::safeBorderStyle((string) ($bag['border_style'] ?? ''));
        $color = self::colorCssValue((string) ($bag['border_color'] ?? ''));
        $touches = array_key_exists('border_width', $bag) || array_key_exists('border_style', $bag) || array_key_exists('border_color', $bag);
        if ($width !== '' || $style !== '' || $color !== '') {
            if ($width === '') {
                $width = '1px';
            }
            if ($style === '') {
                $style = 'solid';
            }
            $parts[] = 'border-width:' . $width;
            $parts[] = 'border-style:' . $style;
            if ($color !== '') {
                $parts[] = 'border-color:' . $color;
            }
        } elseif ($override && $touches) {
            $parts[] = 'border-width:unset';
            $parts[] = 'border-style:unset';
            $parts[] = 'border-color:unset';
        }
        self::pushDecl($parts, 'border-radius', self::safeRadius((string) ($bag['border_radius'] ?? '')), $override, array_key_exists('border_radius', $bag));
        $shadowKey = self::safeShadowKey((string) ($bag['box_shadow'] ?? ''));
        $shadow = $shadowKey !== '' ? self::BOX_SHADOWS[$shadowKey] : '';
        self::pushDecl($parts, 'box-shadow', $shadow, $override, array_key_exists('box_shadow', $bag));
    }

    /** @param list<string> $parts */
    private static function pushDecl(array &$parts, string $prop, string $value, bool $override, bool $present): void
    {
        if ($value !== '') {
            $parts[] = $prop . ':' . $value;
            return;
        }
        if ($override && $present) {
            $parts[] = $prop . ':unset';
        }
    }
}
