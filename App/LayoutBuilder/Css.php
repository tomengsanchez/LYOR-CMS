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
                        self::collectInnerRowCss($base, $tablet, $mobile, $mod);
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
     * Nested columns/modules on an inner_row (one level).
     *
     * @param list<string> $base
     * @param list<string> $tablet
     * @param list<string> $mobile
     * @param array<string, mixed> $mod
     */
    private static function collectInnerRowCss(array &$base, array &$tablet, array &$mobile, array $mod): void
    {
        if (($mod['type'] ?? '') !== 'inner_row') {
            return;
        }
        $columns = is_array($mod['columns'] ?? null) ? $mod['columns'] : [];
        foreach ($columns as $col) {
            if (!is_array($col)) {
                continue;
            }
            self::collectNodeCss($base, $tablet, $mobile, (string) ($col['id'] ?? ''), $col, 'settings');
            $modules = is_array($col['modules'] ?? null) ? $col['modules'] : [];
            foreach ($modules as $innerMod) {
                if (!is_array($innerMod)) {
                    continue;
                }
                self::collectNodeCss($base, $tablet, $mobile, (string) ($innerMod['id'] ?? ''), $innerMod, 'design');
            }
        }
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
        if ($kind === 'design') {
            $alignChild = self::cssTextAlignChildRule($id, is_array($node[$kind] ?? null) ? $node[$kind] : [], false);
            if ($alignChild !== '') {
                $base[] = $alignChild;
            }
        }
        $srcBase = is_array($node[$kind] ?? null) ? $node[$kind] : [];
        $t = is_array($node[$kind . '_tablet'] ?? null) ? $node[$kind . '_tablet'] : [];
        $tDecls = $kind === 'design' ? self::styleFromDesign($t, true) : self::styleFromSettings($t, true, $srcBase);
        $rule = self::cssRule($id, $tDecls);
        if ($rule !== '') {
            $tablet[] = $rule;
        }
        if ($kind === 'design') {
            $alignChild = self::cssTextAlignChildRule($id, $t, true);
            if ($alignChild !== '') {
                $tablet[] = $alignChild;
            }
        }
        $m = is_array($node[$kind . '_mobile'] ?? null) ? $node[$kind . '_mobile'] : [];
        $mDecls = $kind === 'design' ? self::styleFromDesign($m, true) : self::styleFromSettings($m, true, $srcBase);
        $rule = self::cssRule($id, $mDecls);
        if ($rule !== '') {
            $mobile[] = $rule;
        }
        if ($kind === 'design') {
            $alignChild = self::cssTextAlignChildRule($id, $m, true);
            if ($alignChild !== '') {
                $mobile[] = $alignChild;
            }
        }
        if ($kind !== 'design') {
            self::collectSectionExtraCss($base, $tablet, $mobile, $id, $node);
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
        $hoverAlign = self::cssTextAlignHoverChildRule($id, $hover);
        if ($hoverAlign !== '') {
            $base[] = $hoverAlign;
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

    /**
     * Inner wrappers (e.g. .cms-mod-blurb) must not keep a hardcoded text-align over Design.
     *
     * @param array<string, mixed> $bag
     */
    private static function cssTextAlignChildRule(string $id, array $bag, bool $override): string
    {
        $cls = self::elementCssClass($id);
        if ($cls === '') {
            return '';
        }
        $align = (string) ($bag['text_align'] ?? '');
        if (in_array($align, ['left', 'center', 'right', 'justify'], true)) {
            return '.' . $cls . '>*{text-align:' . $align . '}';
        }
        if ($override && array_key_exists('text_align', $bag)) {
            return '.' . $cls . '>*{text-align:unset}';
        }
        return '';
    }

    /** @param array<string, mixed> $hover */
    private static function cssTextAlignHoverChildRule(string $id, array $hover): string
    {
        $cls = self::elementCssClass($id);
        $align = (string) ($hover['text_align'] ?? '');
        if ($cls === '' || !in_array($align, ['left', 'center', 'right', 'justify'], true)) {
            return '';
        }
        return '.' . $cls . ':hover>*{text-align:' . $align . '}';
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

    /**
     * Shape fill/height + video overlay on a dedicated background layer (section overflow stays visible).
     *
     * @param list<string> $base
     * @param list<string> $tablet
     * @param list<string> $mobile
     * @param array<string, mixed> $node
     */
    private static function collectSectionExtraCss(array &$base, array &$tablet, array &$mobile, string $id, array $node): void
    {
        $cls = self::elementCssClass($id);
        if ($cls === '') {
            return;
        }
        $site = is_array($node['settings'] ?? null) ? $node['settings'] : [];
        $tBag = is_array($node['settings_tablet'] ?? null) ? $node['settings_tablet'] : [];
        $mBag = is_array($node['settings_mobile'] ?? null) ? $node['settings_mobile'] : [];
        foreach (['top', 'bottom'] as $side) {
            $rule = self::shapeSideCss($cls, $side, $site, false);
            if ($rule !== '') {
                $base[] = $rule;
            }
            $rule = self::shapeSideCss($cls, $side, $tBag, true);
            if ($rule !== '') {
                $tablet[] = $rule;
            }
            $rule = self::shapeSideCss($cls, $side, $mBag, true);
            if ($rule !== '') {
                $mobile[] = $rule;
            }
        }
        if (self::parseVideoUrl((string) ($site['bg_video_url'] ?? '')) === null) {
            return;
        }
        $rule = self::videoBgOverlayCss($cls, $site, $site, false);
        if ($rule !== '') {
            $base[] = $rule;
        }
        $rule = self::videoBgOverlayCss($cls, $tBag, $site, true);
        if ($rule !== '') {
            $tablet[] = $rule;
        }
        $rule = self::videoBgOverlayCss($cls, $mBag, $site, true);
        if ($rule !== '') {
            $mobile[] = $rule;
        }
    }

    /**
     * @param array<string, mixed> $bag
     */
    private static function shapeSideCss(string $cls, string $side, array $bag, bool $override): string
    {
        $colorName = 'shape_' . $side . '_color';
        $hName = 'shape_' . $side . '_height';
        $color = self::colorCssValue((string) ($bag[$colorName] ?? ''));
        $hKey = self::safeShapeHeightKey((string) ($bag[$hName] ?? ''));
        $h = $hKey !== '' ? self::SHAPE_HEIGHTS[$hKey] : '';
        $touchesC = array_key_exists($colorName, $bag);
        $touchesH = array_key_exists($hName, $bag);
        if ($override && !$touchesC && !$touchesH) {
            return '';
        }
        $sel = '.' . $cls . '>.cms-shape--' . $side;
        $css = '';
        $parts = [];
        if ($color !== '') {
            $parts[] = 'color:' . $color;
        } elseif ($override && $touchesC) {
            $parts[] = 'color:unset';
        }
        if ($parts !== []) {
            $css .= $sel . '{' . implode(';', $parts) . '}';
        }
        if ($h !== '') {
            $css .= $sel . ' svg{height:' . $h . '}';
        } elseif ($override && $touchesH) {
            $css .= $sel . ' svg{height:unset}';
        }
        return $css;
    }

    /**
     * @param array<string, mixed> $bag
     * @param array<string, mixed> $base
     */
    private static function videoBgOverlayCss(string $cls, array $bag, array $base, bool $override): string
    {
        $touches = array_key_exists('bg_overlay', $bag) || array_key_exists('bg_overlay_opacity', $bag);
        if ($override && !$touches) {
            return '';
        }
        $overlayColor = array_key_exists('bg_overlay', $bag) || !$override
            ? (string) ($bag['bg_overlay'] ?? '')
            : (string) ($base['bg_overlay'] ?? '');
        $overlayOp = array_key_exists('bg_overlay_opacity', $bag) || !$override
            ? self::safeOpacity($bag['bg_overlay_opacity'] ?? '')
            : self::safeOpacity($base['bg_overlay_opacity'] ?? '');
        $overlay = self::overlayCss($overlayColor, $overlayOp);
        $sel = '.' . $cls . '>.cms-layout-section-bg::after';
        if ($overlay === '') {
            return ($override && $touches) ? $sel . '{content:none;background:unset}' : '';
        }
        return $sel . '{content:"";position:absolute;inset:0;background:' . $overlay . ';pointer-events:none}';
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
        self::appendPositionDecls($parts, $settings, $override);
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
        $fontKey = self::safeFontFamilyKey((string) ($design['font_family'] ?? ''));
        $fontCss = $fontKey !== '' ? self::FONT_FAMILIES[$fontKey] : '';
        self::pushDecl($parts, 'font-family', $fontCss, $override, array_key_exists('font_family', $design));
        $trackKey = self::safeLetterSpacingKey((string) ($design['letter_spacing'] ?? ''));
        $trackCss = $trackKey !== '' ? self::LETTER_SPACINGS[$trackKey] : '';
        self::pushDecl($parts, 'letter-spacing', $trackCss, $override, array_key_exists('letter_spacing', $design));
        self::pushDecl($parts, 'text-transform', self::safeTextTransform((string) ($design['text_transform'] ?? '')), $override, array_key_exists('text_transform', $design));
        self::appendPositionDecls($parts, $design, $override);
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

    /**
     * relative / sticky + allowlisted z-index. Never interpolates user CSS.
     *
     * @param list<string> $parts
     * @param array<string, mixed> $bag
     */
    private static function appendPositionDecls(array &$parts, array $bag, bool $override): void
    {
        $pos = self::safePosition((string) ($bag['position'] ?? ''));
        $z = self::safeZIndex((string) ($bag['z_index'] ?? ''));
        $topKey = self::safeStickyOffsetKey((string) ($bag['sticky_offset'] ?? ''));
        $touchesPos = array_key_exists('position', $bag) || array_key_exists('sticky_offset', $bag);
        if ($pos === 'sticky') {
            $parts[] = 'position:sticky';
            $top = $topKey !== '' ? self::STICKY_OFFSETS[$topKey] : '0';
            $parts[] = 'top:' . $top;
        } elseif ($pos === 'relative') {
            $parts[] = 'position:relative';
        } elseif ($z !== '' && $pos === '') {
            $parts[] = 'position:relative';
        } elseif ($override && array_key_exists('position', $bag) && $pos === '') {
            $parts[] = 'position:unset';
            $parts[] = 'top:unset';
        } elseif ($override && $touchesPos && $pos !== 'sticky') {
            $parts[] = 'top:unset';
        }
        self::pushDecl($parts, 'z-index', $z, $override, array_key_exists('z_index', $bag));
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
