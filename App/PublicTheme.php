<?php
namespace App;

use App\Models\AppSettings;
use Core\Auth;

/**
 * Sitewide public site theme (presets, fonts, layout, color mode defaults).
 */
class PublicTheme
{
    public const PRESET_DEFAULT = 'default';
    public const PRESET_OCEAN = 'ocean';
    public const PRESET_FOREST = 'forest';
    public const PRESET_SUNSET = 'sunset';
    public const PRESET_VIOLET = 'violet';
    public const PRESET_SLATE = 'slate';
    public const PRESET_ROSE = 'rose';
    public const PRESET_AMBER = 'amber';
    public const PRESET_INDIGO = 'indigo';
    public const PRESET_TEAL = 'teal';
    public const PRESET_CHARCOAL = 'charcoal';
    public const PRESET_CORAL = 'coral';
    public const PRESET_MINT = 'mint';
    public const PRESET_EDITORIAL = 'editorial';
    public const PRESET_CUSTOM = 'custom';

    public const FONT_SYSTEM = 'system';
    public const FONT_SERIF = 'serif';
    public const FONT_MODERN = 'modern';
    public const FONT_MONO = 'mono';
    public const FONT_READABLE = 'readable';
    public const FONT_DISPLAY = 'display';

    public const RADIUS_NONE = 'none';
    public const RADIUS_SM = 'sm';
    public const RADIUS_MD = 'md';
    public const RADIUS_LG = 'lg';
    public const RADIUS_XL = 'xl';

    public const WIDTH_INHERIT = 'inherit';
    public const WIDTH_NARROW = 'narrow';
    public const WIDTH_NORMAL = 'normal';
    public const WIDTH_WIDE = 'wide';
    public const WIDTH_FULL = 'full';

    public const MODE_LIGHT = 'light';
    public const MODE_DARK = 'dark';
    public const MODE_SYSTEM = 'system';

    public const HEADER_SOLID = 'solid';
    public const HEADER_ACCENT = 'accent';
    public const HEADER_MINIMAL = 'minimal';

    public const SIZE_SM = 'sm';
    public const SIZE_MD = 'md';
    public const SIZE_LG = 'lg';

    public const LEADING_TIGHT = 'tight';
    public const LEADING_NORMAL = 'normal';
    public const LEADING_RELAXED = 'relaxed';

    public const BTN_SOLID = 'solid';
    public const BTN_SOFT = 'soft';
    public const BTN_OUTLINE = 'outline';

    public const FOOTER_SOLID = 'solid';
    public const FOOTER_MINIMAL = 'minimal';
    public const FOOTER_ACCENT = 'accent';

    public const SHADOW_NONE = 'none';
    public const SHADOW_SOFT = 'soft';
    public const SHADOW_STRONG = 'strong';

    public const SPACE_COMPACT = 'compact';
    public const SPACE_COMFORTABLE = 'comfortable';
    public const SPACE_SPACIOUS = 'spacious';

    public const LINK_ACCENT = 'accent';
    public const LINK_UNDERLINE = 'underline';
    public const LINK_MUTED = 'muted';

    public const NAV_INLINE = 'inline';
    public const NAV_PILLS = 'pills';
    public const NAV_UNDERLINE = 'underline';

    public const BRAND_NORMAL = 'normal';
    public const BRAND_BOLD = 'bold';
    public const BRAND_BLACK = 'black';

    public const HEADING_COMPACT = 'compact';
    public const HEADING_NORMAL = 'normal';
    public const HEADING_DRAMATIC = 'dramatic';

    public const BLOG_STACKED = 'stacked';
    public const BLOG_LIST = 'list';
    public const BLOG_GRID = 'grid';
    public const BLOG_CARDS = 'cards';
    public const BLOG_MAGAZINE = 'magazine';
    public const BLOG_COMPACT = 'compact';

    public const BLOG_COLS_2 = '2';
    public const BLOG_COLS_3 = '3';
    public const BLOG_COLS_4 = '4';

    public const BLOG_RATIO_AUTO = 'auto';
    public const BLOG_RATIO_LANDSCAPE = 'landscape';
    public const BLOG_RATIO_SQUARE = 'square';
    public const BLOG_RATIO_PORTRAIT = 'portrait';

    public const IMAGE_SQUARE = 'square';
    public const IMAGE_ROUNDED = 'rounded';
    public const IMAGE_SOFT = 'soft';

    public const BORDER_NONE = 'none';
    public const BORDER_SUBTLE = 'subtle';
    public const BORDER_STRONG = 'strong';

    public const HEADER_HEIGHT_COMPACT = 'compact';
    public const HEADER_HEIGHT_COMFORTABLE = 'comfortable';
    public const HEADER_HEIGHT_TALL = 'tall';

    public const BTN_SIZE_SM = 'sm';
    public const BTN_SIZE_MD = 'md';
    public const BTN_SIZE_LG = 'lg';

    public const SIDEBAR_CARD = 'card';
    public const SIDEBAR_PLAIN = 'plain';
    public const SIDEBAR_BORDERED = 'bordered';

    public const FOOTER_ALIGN_SPLIT = 'split';
    public const FOOTER_ALIGN_CENTER = 'center';
    public const FOOTER_ALIGN_LEFT = 'left';

    public const PROSE_LEFT = 'left';
    public const PROSE_JUSTIFY = 'justify';

    public const LOGO_SM = 'sm';
    public const LOGO_MD = 'md';
    public const LOGO_LG = 'lg';

    public const TRANSITION_NONE = 'none';
    public const TRANSITION_SUBTLE = 'subtle';
    public const TRANSITION_SMOOTH = 'smooth';

    public const FOCUS_ACCENT = 'accent';
    public const FOCUS_STRONG = 'strong';
    public const FOCUS_OFF = 'off';

    public const CHROME_DEFAULT = 'default';
    public const CHROME_EDITORIAL = 'editorial';

    public const DATE_RAW = 'raw';
    public const DATE_HUMAN = 'human';

    private const SESSION_PREVIEW = 'pub_theme_preview';

    /** @return array<string, string> */
    public static function presets(): array
    {
        return [
            self::PRESET_DEFAULT => 'Default (Blue)',
            self::PRESET_EDITORIAL => 'Editorial (crimson)',
            self::PRESET_OCEAN    => 'Ocean',
            self::PRESET_FOREST   => 'Forest',
            self::PRESET_SUNSET   => 'Sunset',
            self::PRESET_VIOLET   => 'Violet',
            self::PRESET_SLATE    => 'Slate',
            self::PRESET_ROSE     => 'Rose',
            self::PRESET_AMBER    => 'Amber',
            self::PRESET_INDIGO   => 'Indigo',
            self::PRESET_TEAL     => 'Teal',
            self::PRESET_CHARCOAL => 'Charcoal',
            self::PRESET_CORAL    => 'Coral',
            self::PRESET_MINT     => 'Mint',
            self::PRESET_CUSTOM   => 'Custom colors',
        ];
    }

    /** @return array<string, string> */
    public static function chromeStyles(): array
    {
        return [
            self::CHROME_DEFAULT => 'Default (app chrome)',
            self::CHROME_EDITORIAL => 'Editorial magazine',
        ];
    }

    /** @return array<string, string> */
    public static function dateFormats(): array
    {
        return [
            self::DATE_HUMAN => 'Readable (Aug 14, 2026)',
            self::DATE_RAW => 'Database raw',
        ];
    }

    /** @return array<string, string> */
    public static function fonts(): array
    {
        return [
            self::FONT_SYSTEM   => 'System UI',
            self::FONT_SERIF    => 'Serif',
            self::FONT_MODERN   => 'Modern sans',
            self::FONT_READABLE => 'Readable (Verdana)',
            self::FONT_DISPLAY  => 'Display',
            self::FONT_MONO     => 'Monospace',
        ];
    }

    /** @return array<string, string> */
    public static function radii(): array
    {
        return [
            self::RADIUS_NONE => 'Square (0)',
            self::RADIUS_SM   => 'Small (8px)',
            self::RADIUS_MD   => 'Medium (12px)',
            self::RADIUS_LG   => 'Large (16px)',
            self::RADIUS_XL   => 'XL (24px)',
        ];
    }

    /** @return array<string, string> */
    public static function contentWidths(): array
    {
        return [
            self::WIDTH_FULL    => 'Full width (1320px)',
            self::WIDTH_WIDE    => 'Wide (960px)',
            self::WIDTH_NORMAL  => 'Normal (840px)',
            self::WIDTH_NARROW  => 'Narrow (720px)',
        ];
    }

    /** @return array<string, string> */
    public static function blogContentWidths(): array
    {
        return [
            self::WIDTH_INHERIT => 'Same as site default',
            self::WIDTH_FULL    => 'Full width (1320px)',
            self::WIDTH_WIDE    => 'Wide (960px)',
            self::WIDTH_NORMAL  => 'Normal (840px)',
            self::WIDTH_NARROW  => 'Narrow (720px)',
        ];
    }

    /** @return array<string, string> */
    public static function contentLayoutOptions(): array
    {
        return [
            '' => 'Site default (theme)',
            self::WIDTH_FULL   => 'Full width (1320px)',
            self::WIDTH_WIDE   => 'Wide (960px)',
            self::WIDTH_NORMAL => 'Normal (840px)',
            self::WIDTH_NARROW => 'Narrow (720px)',
        ];
    }

    /** @return array<string, string> */
    public static function colorModes(): array
    {
        return [
            self::MODE_LIGHT  => 'Light',
            self::MODE_DARK   => 'Dark',
            self::MODE_SYSTEM => 'System preference',
        ];
    }

    /** @return array<string, string> */
    public static function headerStyles(): array
    {
        return [
            self::HEADER_SOLID   => 'Solid bar',
            self::HEADER_ACCENT  => 'Accent tint',
            self::HEADER_MINIMAL => 'Minimal',
        ];
    }

    /** @return array<string, string> */
    public static function fontSizes(): array
    {
        return [
            self::SIZE_SM => 'Small',
            self::SIZE_MD => 'Medium',
            self::SIZE_LG => 'Large',
        ];
    }

    /** @return array<string, string> */
    public static function lineHeights(): array
    {
        return [
            self::LEADING_TIGHT   => 'Tight',
            self::LEADING_NORMAL  => 'Normal',
            self::LEADING_RELAXED => 'Relaxed',
        ];
    }

    /** @return array<string, string> */
    public static function buttonStyles(): array
    {
        return [
            self::BTN_SOLID   => 'Solid',
            self::BTN_SOFT    => 'Soft fill',
            self::BTN_OUTLINE => 'Outline',
        ];
    }

    /** @return array<string, string> */
    public static function footerStyles(): array
    {
        return [
            self::FOOTER_SOLID   => 'Solid bar',
            self::FOOTER_MINIMAL => 'Minimal',
            self::FOOTER_ACCENT  => 'Accent tint',
        ];
    }

    /** @return array<string, string> */
    public static function cardShadows(): array
    {
        return [
            self::SHADOW_NONE   => 'None',
            self::SHADOW_SOFT   => 'Soft',
            self::SHADOW_STRONG => 'Strong',
        ];
    }

    /** @return array<string, string> */
    public static function contentSpacings(): array
    {
        return [
            self::SPACE_COMPACT     => 'Compact',
            self::SPACE_COMFORTABLE => 'Comfortable',
            self::SPACE_SPACIOUS    => 'Spacious',
        ];
    }

    /** @return array<string, string> */
    public static function linkStyles(): array
    {
        return [
            self::LINK_ACCENT    => 'Accent color',
            self::LINK_UNDERLINE => 'Always underline',
            self::LINK_MUTED     => 'Muted',
        ];
    }

    /** @return array<string, string> */
    public static function navStyles(): array
    {
        return [
            self::NAV_INLINE    => 'Inline links',
            self::NAV_PILLS     => 'Pills',
            self::NAV_UNDERLINE => 'Underline active',
        ];
    }

    /** @return array<string, string> */
    public static function brandWeights(): array
    {
        return [
            self::BRAND_NORMAL => 'Normal',
            self::BRAND_BOLD   => 'Bold',
            self::BRAND_BLACK  => 'Extra bold',
        ];
    }

    /** @return array<string, string> */
    public static function headingScales(): array
    {
        return [
            self::HEADING_COMPACT  => 'Compact',
            self::HEADING_NORMAL   => 'Normal',
            self::HEADING_DRAMATIC => 'Dramatic',
        ];
    }

    /** @return array<string, string> */
    public static function blogListStyles(): array
    {
        return [
            self::BLOG_LIST     => 'List (image + text)',
            self::BLOG_GRID     => 'Grid',
            self::BLOG_CARDS    => 'Cards',
            self::BLOG_MAGAZINE => 'Magazine (hero + grid)',
            self::BLOG_COMPACT  => 'Compact rows',
        ];
    }

    /** @return array<string, string> */
    public static function blogGridColumns(): array
    {
        return [
            self::BLOG_COLS_2 => '2 columns',
            self::BLOG_COLS_3 => '3 columns',
            self::BLOG_COLS_4 => '4 columns',
        ];
    }

    /** @return array<string, string> */
    public static function blogImageRatios(): array
    {
        return [
            self::BLOG_RATIO_AUTO      => 'Auto (image default)',
            self::BLOG_RATIO_LANDSCAPE => 'Landscape (16:10)',
            self::BLOG_RATIO_SQUARE    => 'Square (1:1)',
            self::BLOG_RATIO_PORTRAIT  => 'Portrait (3:4)',
        ];
    }

    /** @return array<string, string> */
    public static function imageStyles(): array
    {
        return [
            self::IMAGE_SQUARE  => 'Square corners',
            self::IMAGE_ROUNDED => 'Rounded',
            self::IMAGE_SOFT    => 'Soft (theme radius)',
        ];
    }

    /** @return array<string, string> */
    public static function borderStyles(): array
    {
        return [
            self::BORDER_NONE   => 'None',
            self::BORDER_SUBTLE => 'Subtle',
            self::BORDER_STRONG => 'Strong',
        ];
    }

    /** @return array<string, string> */
    public static function headerHeights(): array
    {
        return [
            self::HEADER_HEIGHT_COMPACT     => 'Compact',
            self::HEADER_HEIGHT_COMFORTABLE => 'Comfortable',
            self::HEADER_HEIGHT_TALL        => 'Tall',
        ];
    }

    /** @return array<string, string> */
    public static function buttonSizes(): array
    {
        return [
            self::BTN_SIZE_SM => 'Small',
            self::BTN_SIZE_MD => 'Medium',
            self::BTN_SIZE_LG => 'Large',
        ];
    }

    /** @return array<string, string> */
    public static function sidebarStyles(): array
    {
        return [
            self::SIDEBAR_CARD     => 'Card',
            self::SIDEBAR_PLAIN    => 'Plain',
            self::SIDEBAR_BORDERED => 'Bordered',
        ];
    }

    /** @return array<string, string> */
    public static function footerAlignments(): array
    {
        return [
            self::FOOTER_ALIGN_SPLIT  => 'Split (copyright | links)',
            self::FOOTER_ALIGN_CENTER => 'Centered',
            self::FOOTER_ALIGN_LEFT   => 'Left stacked',
        ];
    }

    /** @return array<string, string> */
    public static function proseAlignments(): array
    {
        return [
            self::PROSE_LEFT    => 'Left',
            self::PROSE_JUSTIFY => 'Justified',
        ];
    }

    /** @return array<string, string> */
    public static function logoSizes(): array
    {
        return [
            self::LOGO_SM => 'Small',
            self::LOGO_MD => 'Medium',
            self::LOGO_LG => 'Large',
        ];
    }

    /** @return array<string, string> */
    public static function transitionStyles(): array
    {
        return [
            self::TRANSITION_NONE   => 'None',
            self::TRANSITION_SUBTLE => 'Subtle',
            self::TRANSITION_SMOOTH => 'Smooth',
        ];
    }

    /** @return array<string, string> */
    public static function focusStyles(): array
    {
        return [
            self::FOCUS_ACCENT => 'Accent ring',
            self::FOCUS_STRONG => 'Strong ring',
            self::FOCUS_OFF    => 'Browser default',
        ];
    }

    public static function getConfig(): object
    {
        if (self::isPreviewActive()) {
            return self::configFromPreview();
        }
        return self::loadStoredConfig();
    }

    public static function loadStoredConfig(): object
    {
        $preset = self::normalizePreset(AppSettings::get('pub_theme_preset', self::PRESET_DEFAULT));
        $accent = AppSettings::normalizeAccentColor(AppSettings::get('public_accent_color', '#2563eb'));

        return (object) [
            'preset' => $preset,
            'accent_color' => $accent,
            'font' => self::normalizeFont(AppSettings::get('pub_theme_font', self::FONT_SYSTEM)),
            'radius' => self::normalizeRadius(AppSettings::get('pub_theme_radius', self::RADIUS_MD)),
            'content_width' => self::normalizeWidth(AppSettings::get('pub_theme_width', self::WIDTH_FULL)),
            'blog_content_width' => self::normalizeBlogWidth(AppSettings::get('pub_theme_blog_width', self::WIDTH_INHERIT)),
            'default_color_mode' => self::normalizeColorMode(AppSettings::get('pub_theme_color_mode', self::MODE_SYSTEM)),
            'header_style' => self::normalizeHeader(AppSettings::get('pub_theme_header', self::HEADER_SOLID)),
            'show_color_toggle' => false,
            'custom_bg' => AppSettings::normalizeAccentColor(AppSettings::get('pub_theme_custom_bg', '#f8fafc')),
            'custom_surface' => AppSettings::normalizeAccentColor(AppSettings::get('pub_theme_custom_surface', '#ffffff')),
            'custom_text' => AppSettings::normalizeAccentColor(AppSettings::get('pub_theme_custom_text', '#0f172a')),
            'font_size' => self::normalizeFontSize(AppSettings::get('pub_theme_font_size', self::SIZE_MD)),
            'line_height' => self::normalizeLineHeight(AppSettings::get('pub_theme_line_height', self::LEADING_NORMAL)),
            'button_style' => self::normalizeButtonStyle(AppSettings::get('pub_theme_button_style', self::BTN_SOLID)),
            'footer_style' => self::normalizeFooterStyle(AppSettings::get('pub_theme_footer_style', self::FOOTER_SOLID)),
            'card_shadow' => self::normalizeCardShadow(AppSettings::get('pub_theme_card_shadow', self::SHADOW_SOFT)),
            'content_spacing' => self::normalizeContentSpacing(AppSettings::get('pub_theme_content_spacing', self::SPACE_COMFORTABLE)),
            'link_style' => self::normalizeLinkStyle(AppSettings::get('pub_theme_link_style', self::LINK_ACCENT)),
            'sticky_header' => AppSettings::get('pub_theme_sticky_header', '1') === '1',
            'show_admin_link' => AppSettings::get('pub_theme_show_admin_link', '0') === '1',
            'nav_style' => self::normalizeNavStyle(AppSettings::get('pub_theme_nav_style', self::NAV_INLINE)),
            'brand_weight' => self::normalizeBrandWeight(AppSettings::get('pub_theme_brand_weight', self::BRAND_BOLD)),
            'heading_scale' => self::normalizeHeadingScale(AppSettings::get('pub_theme_heading_scale', self::HEADING_NORMAL)),
            'blog_list_style' => self::normalizeBlogListStyle(AppSettings::get('pub_theme_blog_list_style', self::BLOG_LIST)),
            'blog_grid_columns' => self::normalizeBlogGridColumns(AppSettings::get('pub_theme_blog_grid_columns', self::BLOG_COLS_3)),
            'blog_image_ratio' => self::normalizeBlogImageRatio(AppSettings::get('pub_theme_blog_image_ratio', self::BLOG_RATIO_LANDSCAPE)),
            'blog_show_excerpt' => AppSettings::get('pub_theme_blog_show_excerpt', '1') === '1',
            'blog_show_read_more' => AppSettings::get('pub_theme_blog_show_read_more', '1') === '1',
            'blog_show_category' => AppSettings::get('pub_theme_blog_show_category', '1') === '1',
            'blog_view_switcher' => AppSettings::get('pub_theme_blog_view_switcher', '1') === '1',
            'image_style' => self::normalizeImageStyle(AppSettings::get('pub_theme_image_style', self::IMAGE_SOFT)),
            'border_style' => self::normalizeBorderStyle(AppSettings::get('pub_theme_border_style', self::BORDER_SUBTLE)),
            'header_height' => self::normalizeHeaderHeight(AppSettings::get('pub_theme_header_height', self::HEADER_HEIGHT_COMFORTABLE)),
            'show_site_title' => AppSettings::get('pub_theme_show_site_title', '1') === '1',
            'show_breadcrumbs' => AppSettings::get('pub_theme_show_breadcrumbs', '1') === '1',
            'nav_uppercase' => AppSettings::get('pub_theme_nav_uppercase', '0') === '1',
            'button_size' => self::normalizeButtonSize(AppSettings::get('pub_theme_button_size', self::BTN_SIZE_MD)),
            'sidebar_style' => self::normalizeSidebarStyle(AppSettings::get('pub_theme_sidebar_style', self::SIDEBAR_CARD)),
            'footer_align' => self::normalizeFooterAlign(AppSettings::get('pub_theme_footer_align', self::FOOTER_ALIGN_SPLIT)),
            'prose_align' => self::normalizeProseAlign(AppSettings::get('pub_theme_prose_align', self::PROSE_LEFT)),
            'logo_size' => self::normalizeLogoSize(AppSettings::get('pub_theme_logo_size', self::LOGO_MD)),
            'transition_style' => self::normalizeTransitionStyle(AppSettings::get('pub_theme_transition', self::TRANSITION_SUBTLE)),
            'focus_style' => self::normalizeFocusStyle(AppSettings::get('pub_theme_focus', self::FOCUS_ACCENT)),
            'show_blog_search' => AppSettings::get('pub_theme_show_blog_search', '1') === '1',
            'show_post_dates' => AppSettings::get('pub_theme_show_post_dates', '1') === '1',
            'show_list_featured' => AppSettings::get('pub_theme_show_list_featured', '1') === '1',
            'chrome' => self::normalizeChrome(AppSettings::get('pub_theme_chrome', self::CHROME_DEFAULT)),
            'blog_kicker' => self::normalizeBlogKicker(AppSettings::get('pub_theme_blog_kicker', '')),
            'date_format' => self::normalizeDateFormat(AppSettings::get('pub_theme_date_format', self::DATE_HUMAN)),
            'show_site_tagline' => AppSettings::get('pub_theme_show_site_tagline', '0') === '1',
        ];
    }

    /** @param array<string, mixed> $data */
    public static function saveConfig(array $data): void
    {
        $parsed = self::parsePostData($data);
        AppSettings::set('pub_theme_preset', $parsed['preset']);
        AppSettings::set('public_accent_color', $parsed['accent_color']);
        AppSettings::set('pub_theme_font', $parsed['font']);
        AppSettings::set('pub_theme_radius', $parsed['radius']);
        AppSettings::set('pub_theme_width', $parsed['content_width']);
        AppSettings::set('pub_theme_blog_width', $parsed['blog_content_width']);
        AppSettings::set('pub_theme_color_mode', $parsed['default_color_mode']);
        AppSettings::set('pub_theme_header', $parsed['header_style']);
        AppSettings::set('pub_theme_show_toggle', '0');
        AppSettings::set('pub_theme_custom_bg', $parsed['custom_bg']);
        AppSettings::set('pub_theme_custom_surface', $parsed['custom_surface']);
        AppSettings::set('pub_theme_custom_text', $parsed['custom_text']);
        AppSettings::set('pub_theme_font_size', $parsed['font_size']);
        AppSettings::set('pub_theme_line_height', $parsed['line_height']);
        AppSettings::set('pub_theme_button_style', $parsed['button_style']);
        AppSettings::set('pub_theme_footer_style', $parsed['footer_style']);
        AppSettings::set('pub_theme_card_shadow', $parsed['card_shadow']);
        AppSettings::set('pub_theme_content_spacing', $parsed['content_spacing']);
        AppSettings::set('pub_theme_link_style', $parsed['link_style']);
        AppSettings::set('pub_theme_sticky_header', !empty($parsed['sticky_header']) ? '1' : '0');
        AppSettings::set('pub_theme_show_admin_link', !empty($parsed['show_admin_link']) ? '1' : '0');
        AppSettings::set('pub_theme_nav_style', $parsed['nav_style']);
        AppSettings::set('pub_theme_brand_weight', $parsed['brand_weight']);
        AppSettings::set('pub_theme_heading_scale', $parsed['heading_scale']);
        AppSettings::set('pub_theme_blog_list_style', $parsed['blog_list_style']);
        AppSettings::set('pub_theme_blog_grid_columns', $parsed['blog_grid_columns']);
        AppSettings::set('pub_theme_blog_image_ratio', $parsed['blog_image_ratio']);
        AppSettings::set('pub_theme_blog_show_excerpt', !empty($parsed['blog_show_excerpt']) ? '1' : '0');
        AppSettings::set('pub_theme_blog_show_read_more', !empty($parsed['blog_show_read_more']) ? '1' : '0');
        AppSettings::set('pub_theme_blog_show_category', !empty($parsed['blog_show_category']) ? '1' : '0');
        AppSettings::set('pub_theme_blog_view_switcher', !empty($parsed['blog_view_switcher']) ? '1' : '0');
        AppSettings::set('pub_theme_image_style', $parsed['image_style']);
        AppSettings::set('pub_theme_border_style', $parsed['border_style']);
        AppSettings::set('pub_theme_header_height', $parsed['header_height']);
        AppSettings::set('pub_theme_show_site_title', !empty($parsed['show_site_title']) ? '1' : '0');
        AppSettings::set('pub_theme_show_breadcrumbs', !empty($parsed['show_breadcrumbs']) ? '1' : '0');
        AppSettings::set('pub_theme_nav_uppercase', !empty($parsed['nav_uppercase']) ? '1' : '0');
        AppSettings::set('pub_theme_button_size', $parsed['button_size']);
        AppSettings::set('pub_theme_sidebar_style', $parsed['sidebar_style']);
        AppSettings::set('pub_theme_footer_align', $parsed['footer_align']);
        AppSettings::set('pub_theme_prose_align', $parsed['prose_align']);
        AppSettings::set('pub_theme_logo_size', $parsed['logo_size']);
        AppSettings::set('pub_theme_transition', $parsed['transition_style']);
        AppSettings::set('pub_theme_focus', $parsed['focus_style']);
        AppSettings::set('pub_theme_show_blog_search', !empty($parsed['show_blog_search']) ? '1' : '0');
        AppSettings::set('pub_theme_show_post_dates', !empty($parsed['show_post_dates']) ? '1' : '0');
        AppSettings::set('pub_theme_show_list_featured', !empty($parsed['show_list_featured']) ? '1' : '0');
        AppSettings::set('pub_theme_chrome', $parsed['chrome']);
        AppSettings::set('pub_theme_blog_kicker', $parsed['blog_kicker']);
        AppSettings::set('pub_theme_date_format', $parsed['date_format']);
        AppSettings::set('pub_theme_show_site_tagline', !empty($parsed['show_site_tagline']) ? '1' : '0');
        self::clearPreview();
    }

    /** @param array<string, mixed> $data */
    public static function setPreviewFromPost(array $data): void
    {
        $_SESSION[self::SESSION_PREVIEW] = self::parsePostData($data);
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public static function parsePostData(array $data): array
    {
        $parsed = [
            'preset' => self::normalizePreset((string) ($data['pub_theme_preset'] ?? '')),
            'accent_color' => AppSettings::normalizeAccentColor((string) ($data['public_accent_color'] ?? '')),
            'font' => self::normalizeFont((string) ($data['pub_theme_font'] ?? '')),
            'radius' => self::normalizeRadius((string) ($data['pub_theme_radius'] ?? '')),
            'content_width' => self::normalizeWidth((string) ($data['pub_theme_width'] ?? '')),
            'blog_content_width' => self::normalizeBlogWidth((string) ($data['pub_theme_blog_width'] ?? self::WIDTH_INHERIT)),
            'default_color_mode' => self::normalizeColorMode((string) ($data['pub_theme_color_mode'] ?? '')),
            'header_style' => self::normalizeHeader((string) ($data['pub_theme_header'] ?? '')),
            'show_color_toggle' => false,
            'custom_bg' => AppSettings::normalizeAccentColor((string) ($data['pub_theme_custom_bg'] ?? '#f8fafc')),
            'custom_surface' => AppSettings::normalizeAccentColor((string) ($data['pub_theme_custom_surface'] ?? '#ffffff')),
            'custom_text' => AppSettings::normalizeAccentColor((string) ($data['pub_theme_custom_text'] ?? '#0f172a')),
            'font_size' => self::normalizeFontSize((string) ($data['pub_theme_font_size'] ?? self::SIZE_MD)),
            'line_height' => self::normalizeLineHeight((string) ($data['pub_theme_line_height'] ?? self::LEADING_NORMAL)),
            'button_style' => self::normalizeButtonStyle((string) ($data['pub_theme_button_style'] ?? self::BTN_SOLID)),
            'footer_style' => self::normalizeFooterStyle((string) ($data['pub_theme_footer_style'] ?? self::FOOTER_SOLID)),
            'card_shadow' => self::normalizeCardShadow((string) ($data['pub_theme_card_shadow'] ?? self::SHADOW_SOFT)),
            'content_spacing' => self::normalizeContentSpacing((string) ($data['pub_theme_content_spacing'] ?? self::SPACE_COMFORTABLE)),
            'link_style' => self::normalizeLinkStyle((string) ($data['pub_theme_link_style'] ?? self::LINK_ACCENT)),
            'sticky_header' => self::boolFromPost($data, 'pub_theme_sticky_header', true),
            'show_admin_link' => self::boolFromPost($data, 'pub_theme_show_admin_link', false),
            'nav_style' => self::normalizeNavStyle((string) ($data['pub_theme_nav_style'] ?? self::NAV_INLINE)),
            'brand_weight' => self::normalizeBrandWeight((string) ($data['pub_theme_brand_weight'] ?? self::BRAND_BOLD)),
            'heading_scale' => self::normalizeHeadingScale((string) ($data['pub_theme_heading_scale'] ?? self::HEADING_NORMAL)),
            'blog_list_style' => self::normalizeBlogListStyle((string) ($data['pub_theme_blog_list_style'] ?? self::BLOG_LIST)),
            'blog_grid_columns' => self::normalizeBlogGridColumns((string) ($data['pub_theme_blog_grid_columns'] ?? self::BLOG_COLS_3)),
            'blog_image_ratio' => self::normalizeBlogImageRatio((string) ($data['pub_theme_blog_image_ratio'] ?? self::BLOG_RATIO_LANDSCAPE)),
            'blog_show_excerpt' => self::boolFromPost($data, 'pub_theme_blog_show_excerpt', true),
            'blog_show_read_more' => self::boolFromPost($data, 'pub_theme_blog_show_read_more', true),
            'blog_show_category' => self::boolFromPost($data, 'pub_theme_blog_show_category', true),
            'blog_view_switcher' => self::boolFromPost($data, 'pub_theme_blog_view_switcher', true),
            'image_style' => self::normalizeImageStyle((string) ($data['pub_theme_image_style'] ?? self::IMAGE_SOFT)),
            'border_style' => self::normalizeBorderStyle((string) ($data['pub_theme_border_style'] ?? self::BORDER_SUBTLE)),
            'header_height' => self::normalizeHeaderHeight((string) ($data['pub_theme_header_height'] ?? self::HEADER_HEIGHT_COMFORTABLE)),
            'show_site_title' => self::boolFromPost($data, 'pub_theme_show_site_title', true),
            'show_breadcrumbs' => self::boolFromPost($data, 'pub_theme_show_breadcrumbs', true),
            'nav_uppercase' => self::boolFromPost($data, 'pub_theme_nav_uppercase', false),
            'button_size' => self::normalizeButtonSize((string) ($data['pub_theme_button_size'] ?? self::BTN_SIZE_MD)),
            'sidebar_style' => self::normalizeSidebarStyle((string) ($data['pub_theme_sidebar_style'] ?? self::SIDEBAR_CARD)),
            'footer_align' => self::normalizeFooterAlign((string) ($data['pub_theme_footer_align'] ?? self::FOOTER_ALIGN_SPLIT)),
            'prose_align' => self::normalizeProseAlign((string) ($data['pub_theme_prose_align'] ?? self::PROSE_LEFT)),
            'logo_size' => self::normalizeLogoSize((string) ($data['pub_theme_logo_size'] ?? self::LOGO_MD)),
            'transition_style' => self::normalizeTransitionStyle((string) ($data['pub_theme_transition'] ?? self::TRANSITION_SUBTLE)),
            'focus_style' => self::normalizeFocusStyle((string) ($data['pub_theme_focus'] ?? self::FOCUS_ACCENT)),
            'show_blog_search' => self::boolFromPost($data, 'pub_theme_show_blog_search', true),
            'show_post_dates' => self::boolFromPost($data, 'pub_theme_show_post_dates', true),
            'show_list_featured' => self::boolFromPost($data, 'pub_theme_show_list_featured', true),
            'chrome' => self::normalizeChrome((string) ($data['pub_theme_chrome'] ?? self::CHROME_DEFAULT)),
            'blog_kicker' => self::normalizeBlogKicker((string) ($data['pub_theme_blog_kicker'] ?? '')),
            'date_format' => self::normalizeDateFormat((string) ($data['pub_theme_date_format'] ?? self::DATE_HUMAN)),
            'show_site_tagline' => self::boolFromPost($data, 'pub_theme_show_site_tagline', false),
        ];

        if (
            self::boolFromPost($data, 'pub_theme_apply_editorial_pack', false)
            || self::boolFromPost($data, 'pub_theme_apply_goodmen_pack', false)
        ) {
            $parsed = array_merge($parsed, self::editorialStylePack());
        }

        return $parsed;
    }

    /**
     * Opinionated magazine/editorial defaults (reusable style pack).
     * @return array<string, mixed>
     */
    public static function editorialStylePack(): array
    {
        return [
            'preset' => self::PRESET_EDITORIAL,
            'accent_color' => '#c8102e',
            'font' => self::FONT_SYSTEM,
            'radius' => self::RADIUS_NONE,
            'content_width' => self::WIDTH_FULL,
            'blog_content_width' => self::WIDTH_FULL,
            'default_color_mode' => self::MODE_LIGHT,
            'header_style' => self::HEADER_SOLID,
            'button_style' => self::BTN_OUTLINE,
            'footer_style' => self::FOOTER_MINIMAL,
            'card_shadow' => self::SHADOW_NONE,
            'content_spacing' => self::SPACE_COMFORTABLE,
            'link_style' => self::LINK_UNDERLINE,
            'sticky_header' => true,
            'show_admin_link' => false,
            'nav_style' => self::NAV_INLINE,
            'brand_weight' => self::BRAND_BLACK,
            'heading_scale' => self::HEADING_DRAMATIC,
            'blog_list_style' => self::BLOG_MAGAZINE,
            'blog_grid_columns' => self::BLOG_COLS_3,
            'blog_image_ratio' => self::BLOG_RATIO_LANDSCAPE,
            'blog_show_excerpt' => true,
            'blog_show_read_more' => false,
            'blog_show_category' => true,
            'blog_view_switcher' => false,
            'image_style' => self::IMAGE_SQUARE,
            'border_style' => self::BORDER_SUBTLE,
            'header_height' => self::HEADER_HEIGHT_TALL,
            'show_site_title' => true,
            'show_breadcrumbs' => true,
            'nav_uppercase' => true,
            'button_size' => self::BTN_SIZE_SM,
            'sidebar_style' => self::SIDEBAR_PLAIN,
            'footer_align' => self::FOOTER_ALIGN_CENTER,
            'prose_align' => self::PROSE_LEFT,
            'logo_size' => self::LOGO_LG,
            'transition_style' => self::TRANSITION_SUBTLE,
            'focus_style' => self::FOCUS_ACCENT,
            'show_blog_search' => false,
            'show_post_dates' => true,
            'show_list_featured' => true,
            'chrome' => self::CHROME_EDITORIAL,
            'blog_kicker' => "HERE'S WHAT'S NEW",
            'date_format' => self::DATE_HUMAN,
            'show_site_tagline' => true,
            'font_size' => self::SIZE_MD,
            'line_height' => self::LEADING_NORMAL,
        ];
    }

    /** @param array<string, mixed> $data */
    private static function boolFromPost(array $data, string $key, bool $default): bool
    {
        if (!array_key_exists($key, $data)) {
            return $default;
        }
        $v = $data[$key];
        if (is_bool($v)) {
            return $v;
        }
        return !empty($v) && (string) $v !== '0';
    }

    public static function clearPreview(): void
    {
        unset($_SESSION[self::SESSION_PREVIEW]);
    }

    public static function isPreviewActive(): bool
    {
        if (!self::previewAllowed() || empty($_SESSION[self::SESSION_PREVIEW]) || !is_array($_SESSION[self::SESSION_PREVIEW])) {
            return false;
        }
        return isset($_GET['theme_preview']) && (string) $_GET['theme_preview'] === '1';
    }

    public static function previewAllowed(): bool
    {
        return Auth::check() && Auth::isAdmin();
    }

    public static function previewUrl(): string
    {
        return self::previewUrlFor('/');
    }

    public static function previewUrlFor(string $path = '/'): string
    {
        $path = trim($path);
        if ($path === '' || $path[0] !== '/') {
            $path = '/' . ltrim($path, '/');
        }
        $parts = parse_url($path);
        $pathOnly = $parts['path'] ?? '/';
        $query = [];
        if (!empty($parts['query'])) {
            parse_str($parts['query'], $query);
        }
        $query['theme_preview'] = '1';
        return $pathOnly . '?' . http_build_query($query);
    }

    public static function customizerFrameUrl(string $path = '/'): string
    {
        $url = self::previewUrlFor($path);
        return $url . '&customize_frame=1';
    }

    public static function isCustomizerFrame(): bool
    {
        return isset($_GET['customize_frame']) && (string) $_GET['customize_frame'] === '1'
            && self::isPreviewActive();
    }

    /** @return array<string, string> */
    public static function configToPostFields(?object $config = null): array
    {
        $config = $config ?? self::loadStoredConfig();
        return [
            'pub_theme_preset' => (string) ($config->preset ?? self::PRESET_DEFAULT),
            'public_accent_color' => (string) ($config->accent_color ?? '#2563eb'),
            'pub_theme_font' => (string) ($config->font ?? self::FONT_SYSTEM),
            'pub_theme_radius' => (string) ($config->radius ?? self::RADIUS_MD),
            'pub_theme_width' => (string) ($config->content_width ?? self::WIDTH_FULL),
            'pub_theme_blog_width' => (string) ($config->blog_content_width ?? self::WIDTH_INHERIT),
            'pub_theme_color_mode' => (string) ($config->default_color_mode ?? self::MODE_SYSTEM),
            'pub_theme_header' => (string) ($config->header_style ?? self::HEADER_SOLID),
            'pub_theme_custom_bg' => (string) ($config->custom_bg ?? '#f8fafc'),
            'pub_theme_custom_surface' => (string) ($config->custom_surface ?? '#ffffff'),
            'pub_theme_custom_text' => (string) ($config->custom_text ?? '#0f172a'),
            'pub_theme_font_size' => (string) ($config->font_size ?? self::SIZE_MD),
            'pub_theme_line_height' => (string) ($config->line_height ?? self::LEADING_NORMAL),
            'pub_theme_button_style' => (string) ($config->button_style ?? self::BTN_SOLID),
            'pub_theme_footer_style' => (string) ($config->footer_style ?? self::FOOTER_SOLID),
            'pub_theme_card_shadow' => (string) ($config->card_shadow ?? self::SHADOW_SOFT),
            'pub_theme_content_spacing' => (string) ($config->content_spacing ?? self::SPACE_COMFORTABLE),
            'pub_theme_link_style' => (string) ($config->link_style ?? self::LINK_ACCENT),
            'pub_theme_sticky_header' => !empty($config->sticky_header) ? '1' : '0',
            'pub_theme_show_admin_link' => !empty($config->show_admin_link) ? '1' : '0',
            'pub_theme_nav_style' => (string) ($config->nav_style ?? self::NAV_INLINE),
            'pub_theme_brand_weight' => (string) ($config->brand_weight ?? self::BRAND_BOLD),
            'pub_theme_heading_scale' => (string) ($config->heading_scale ?? self::HEADING_NORMAL),
            'pub_theme_blog_list_style' => (string) ($config->blog_list_style ?? self::BLOG_LIST),
            'pub_theme_blog_grid_columns' => (string) ($config->blog_grid_columns ?? self::BLOG_COLS_3),
            'pub_theme_blog_image_ratio' => (string) ($config->blog_image_ratio ?? self::BLOG_RATIO_LANDSCAPE),
            'pub_theme_blog_show_excerpt' => !empty($config->blog_show_excerpt) ? '1' : '0',
            'pub_theme_blog_show_read_more' => !empty($config->blog_show_read_more) ? '1' : '0',
            'pub_theme_blog_show_category' => !empty($config->blog_show_category) ? '1' : '0',
            'pub_theme_blog_view_switcher' => !empty($config->blog_view_switcher) ? '1' : '0',
            'pub_theme_image_style' => (string) ($config->image_style ?? self::IMAGE_SOFT),
            'pub_theme_border_style' => (string) ($config->border_style ?? self::BORDER_SUBTLE),
            'pub_theme_header_height' => (string) ($config->header_height ?? self::HEADER_HEIGHT_COMFORTABLE),
            'pub_theme_show_site_title' => !empty($config->show_site_title) ? '1' : '0',
            'pub_theme_show_breadcrumbs' => !empty($config->show_breadcrumbs) ? '1' : '0',
            'pub_theme_nav_uppercase' => !empty($config->nav_uppercase) ? '1' : '0',
            'pub_theme_button_size' => (string) ($config->button_size ?? self::BTN_SIZE_MD),
            'pub_theme_sidebar_style' => (string) ($config->sidebar_style ?? self::SIDEBAR_CARD),
            'pub_theme_footer_align' => (string) ($config->footer_align ?? self::FOOTER_ALIGN_SPLIT),
            'pub_theme_prose_align' => (string) ($config->prose_align ?? self::PROSE_LEFT),
            'pub_theme_logo_size' => (string) ($config->logo_size ?? self::LOGO_MD),
            'pub_theme_transition' => (string) ($config->transition_style ?? self::TRANSITION_SUBTLE),
            'pub_theme_focus' => (string) ($config->focus_style ?? self::FOCUS_ACCENT),
            'pub_theme_show_blog_search' => !empty($config->show_blog_search) ? '1' : '0',
            'pub_theme_show_post_dates' => !empty($config->show_post_dates) ? '1' : '0',
            'pub_theme_show_list_featured' => !empty($config->show_list_featured) ? '1' : '0',
            'pub_theme_chrome' => (string) ($config->chrome ?? self::CHROME_DEFAULT),
            'pub_theme_blog_kicker' => (string) ($config->blog_kicker ?? ''),
            'pub_theme_date_format' => (string) ($config->date_format ?? self::DATE_HUMAN),
            'pub_theme_show_site_tagline' => !empty($config->show_site_tagline) ? '1' : '0',
        ];
    }

    /** @return array<string, mixed> */
    public static function configForBridge(?object $config = null): array
    {
        $config = $config ?? self::getConfig();
        return [
            'preset' => self::normalizePreset((string) ($config->preset ?? self::PRESET_DEFAULT)),
            'accent_color' => AppSettings::normalizeAccentColor((string) ($config->accent_color ?? '#2563eb')),
            'font' => self::normalizeFont((string) ($config->font ?? self::FONT_SYSTEM)),
            'radius' => self::normalizeRadius((string) ($config->radius ?? self::RADIUS_MD)),
            'content_width' => self::normalizeWidth((string) ($config->content_width ?? self::WIDTH_FULL)),
            'blog_content_width' => self::normalizeBlogWidth((string) ($config->blog_content_width ?? self::WIDTH_INHERIT)),
            'default_color_mode' => self::normalizeColorMode((string) ($config->default_color_mode ?? self::MODE_SYSTEM)),
            'header_style' => self::normalizeHeader((string) ($config->header_style ?? self::HEADER_SOLID)),
            'custom_bg' => AppSettings::normalizeAccentColor((string) ($config->custom_bg ?? '#f8fafc')),
            'custom_surface' => AppSettings::normalizeAccentColor((string) ($config->custom_surface ?? '#ffffff')),
            'custom_text' => AppSettings::normalizeAccentColor((string) ($config->custom_text ?? '#0f172a')),
            'font_size' => self::normalizeFontSize((string) ($config->font_size ?? self::SIZE_MD)),
            'line_height' => self::normalizeLineHeight((string) ($config->line_height ?? self::LEADING_NORMAL)),
            'button_style' => self::normalizeButtonStyle((string) ($config->button_style ?? self::BTN_SOLID)),
            'footer_style' => self::normalizeFooterStyle((string) ($config->footer_style ?? self::FOOTER_SOLID)),
            'card_shadow' => self::normalizeCardShadow((string) ($config->card_shadow ?? self::SHADOW_SOFT)),
            'content_spacing' => self::normalizeContentSpacing((string) ($config->content_spacing ?? self::SPACE_COMFORTABLE)),
            'link_style' => self::normalizeLinkStyle((string) ($config->link_style ?? self::LINK_ACCENT)),
            'sticky_header' => !empty($config->sticky_header),
            'show_admin_link' => !empty($config->show_admin_link),
            'nav_style' => self::normalizeNavStyle((string) ($config->nav_style ?? self::NAV_INLINE)),
            'brand_weight' => self::normalizeBrandWeight((string) ($config->brand_weight ?? self::BRAND_BOLD)),
            'heading_scale' => self::normalizeHeadingScale((string) ($config->heading_scale ?? self::HEADING_NORMAL)),
            'blog_list_style' => self::normalizeBlogListStyle((string) ($config->blog_list_style ?? self::BLOG_LIST)),
            'blog_grid_columns' => self::normalizeBlogGridColumns((string) ($config->blog_grid_columns ?? self::BLOG_COLS_3)),
            'blog_image_ratio' => self::normalizeBlogImageRatio((string) ($config->blog_image_ratio ?? self::BLOG_RATIO_LANDSCAPE)),
            'blog_show_excerpt' => !empty($config->blog_show_excerpt),
            'blog_show_read_more' => !empty($config->blog_show_read_more),
            'blog_show_category' => !empty($config->blog_show_category),
            'blog_view_switcher' => !empty($config->blog_view_switcher),
            'image_style' => self::normalizeImageStyle((string) ($config->image_style ?? self::IMAGE_SOFT)),
            'border_style' => self::normalizeBorderStyle((string) ($config->border_style ?? self::BORDER_SUBTLE)),
            'header_height' => self::normalizeHeaderHeight((string) ($config->header_height ?? self::HEADER_HEIGHT_COMFORTABLE)),
            'show_site_title' => !empty($config->show_site_title),
            'show_breadcrumbs' => !empty($config->show_breadcrumbs),
            'nav_uppercase' => !empty($config->nav_uppercase),
            'button_size' => self::normalizeButtonSize((string) ($config->button_size ?? self::BTN_SIZE_MD)),
            'sidebar_style' => self::normalizeSidebarStyle((string) ($config->sidebar_style ?? self::SIDEBAR_CARD)),
            'footer_align' => self::normalizeFooterAlign((string) ($config->footer_align ?? self::FOOTER_ALIGN_SPLIT)),
            'prose_align' => self::normalizeProseAlign((string) ($config->prose_align ?? self::PROSE_LEFT)),
            'logo_size' => self::normalizeLogoSize((string) ($config->logo_size ?? self::LOGO_MD)),
            'transition_style' => self::normalizeTransitionStyle((string) ($config->transition_style ?? self::TRANSITION_SUBTLE)),
            'focus_style' => self::normalizeFocusStyle((string) ($config->focus_style ?? self::FOCUS_ACCENT)),
            'show_blog_search' => !empty($config->show_blog_search),
            'show_post_dates' => !empty($config->show_post_dates),
            'show_list_featured' => !empty($config->show_list_featured),
            'chrome' => self::normalizeChrome((string) ($config->chrome ?? self::CHROME_DEFAULT)),
            'blog_kicker' => self::normalizeBlogKicker((string) ($config->blog_kicker ?? '')),
            'date_format' => self::normalizeDateFormat((string) ($config->date_format ?? self::DATE_HUMAN)),
            'show_site_tagline' => !empty($config->show_site_tagline),
            'html_classes' => self::htmlClasses($config),
            'inline_style' => self::inlineStyle($config),
            'content_width_class' => self::contentWidthClass($config),
            'blog_content_width_class' => self::blogContentWidthClass($config),
        ];
    }

    public static function configFromSessionPreview(): ?object
    {
        if (empty($_SESSION[self::SESSION_PREVIEW]) || !is_array($_SESSION[self::SESSION_PREVIEW])) {
            return null;
        }
        $stored = (array) self::loadStoredConfig();
        return (object) array_merge($stored, $_SESSION[self::SESSION_PREVIEW]);
    }

    private static function configFromPreview(): object
    {
        return self::configFromSessionPreview() ?? self::loadStoredConfig();
    }

    public static function htmlClasses(?object $config = null): string
    {
        $config = $config ?? self::getConfig();
        $parts = [
            'pub-theme-' . self::normalizePreset($config->preset ?? self::PRESET_DEFAULT),
            'pub-font-' . self::normalizeFont($config->font ?? self::FONT_SYSTEM),
            'pub-radius-' . self::normalizeRadius($config->radius ?? self::RADIUS_MD),
            'pub-header-' . self::normalizeHeader($config->header_style ?? self::HEADER_SOLID),
            'pub-size-' . self::normalizeFontSize($config->font_size ?? self::SIZE_MD),
            'pub-leading-' . self::normalizeLineHeight($config->line_height ?? self::LEADING_NORMAL),
            'pub-btn-' . self::normalizeButtonStyle($config->button_style ?? self::BTN_SOLID),
            'pub-footer-' . self::normalizeFooterStyle($config->footer_style ?? self::FOOTER_SOLID),
            'pub-shadow-' . self::normalizeCardShadow($config->card_shadow ?? self::SHADOW_SOFT),
            'pub-space-' . self::normalizeContentSpacing($config->content_spacing ?? self::SPACE_COMFORTABLE),
            'pub-link-' . self::normalizeLinkStyle($config->link_style ?? self::LINK_ACCENT),
            'pub-nav-' . self::normalizeNavStyle($config->nav_style ?? self::NAV_INLINE),
            'pub-brand-' . self::normalizeBrandWeight($config->brand_weight ?? self::BRAND_BOLD),
            'pub-heading-' . self::normalizeHeadingScale($config->heading_scale ?? self::HEADING_NORMAL),
            'pub-blog-' . self::normalizeBlogListStyle($config->blog_list_style ?? self::BLOG_LIST),
            'pub-blogcols-' . self::normalizeBlogGridColumns($config->blog_grid_columns ?? self::BLOG_COLS_3),
            'pub-blogratio-' . self::normalizeBlogImageRatio($config->blog_image_ratio ?? self::BLOG_RATIO_LANDSCAPE),
            'pub-img-' . self::normalizeImageStyle($config->image_style ?? self::IMAGE_SOFT),
            'pub-border-' . self::normalizeBorderStyle($config->border_style ?? self::BORDER_SUBTLE),
            'pub-hh-' . self::normalizeHeaderHeight($config->header_height ?? self::HEADER_HEIGHT_COMFORTABLE),
            'pub-btnsz-' . self::normalizeButtonSize($config->button_size ?? self::BTN_SIZE_MD),
            'pub-sidebar-' . self::normalizeSidebarStyle($config->sidebar_style ?? self::SIDEBAR_CARD),
            'pub-footalign-' . self::normalizeFooterAlign($config->footer_align ?? self::FOOTER_ALIGN_SPLIT),
            'pub-prose-' . self::normalizeProseAlign($config->prose_align ?? self::PROSE_LEFT),
            'pub-logo-' . self::normalizeLogoSize($config->logo_size ?? self::LOGO_MD),
            'pub-motion-' . self::normalizeTransitionStyle($config->transition_style ?? self::TRANSITION_SUBTLE),
            'pub-focus-' . self::normalizeFocusStyle($config->focus_style ?? self::FOCUS_ACCENT),
            !empty($config->sticky_header) ? 'pub-header-sticky' : 'pub-header-static',
            !empty($config->show_admin_link) ? 'pub-admin-link-on' : 'pub-admin-link-off',
            !empty($config->show_site_title) ? 'pub-title-on' : 'pub-title-off',
            !empty($config->show_breadcrumbs) ? 'pub-crumbs-on' : 'pub-crumbs-off',
            !empty($config->nav_uppercase) ? 'pub-nav-upper' : 'pub-nav-normal-case',
            !empty($config->show_blog_search) ? 'pub-blogsearch-on' : 'pub-blogsearch-off',
            !empty($config->show_post_dates) ? 'pub-dates-on' : 'pub-dates-off',
            !empty($config->show_list_featured) ? 'pub-listfeat-on' : 'pub-listfeat-off',
            !empty($config->blog_show_excerpt) ? 'pub-blogexcerpt-on' : 'pub-blogexcerpt-off',
            !empty($config->blog_show_read_more) ? 'pub-blogmore-on' : 'pub-blogmore-off',
            !empty($config->blog_show_category) ? 'pub-blogcat-on' : 'pub-blogcat-off',
            !empty($config->blog_view_switcher) ? 'pub-blogswitch-on' : 'pub-blogswitch-off',
            'pub-chrome-' . self::normalizeChrome($config->chrome ?? self::CHROME_DEFAULT),
            'pub-date-' . self::normalizeDateFormat($config->date_format ?? self::DATE_HUMAN),
            !empty($config->show_site_tagline) ? 'pub-tagline-on' : 'pub-tagline-off',
        ];
        return implode(' ', $parts);
    }

    public static function contentWidthClass(?object $config = null): string
    {
        $config = $config ?? self::getConfig();
        return self::layoutClassForWidth($config->content_width ?? self::WIDTH_FULL, $config);
    }

    public static function blogContentWidthClass(?object $config = null): string
    {
        $config = $config ?? self::getConfig();
        $blog = self::normalizeBlogWidth($config->blog_content_width ?? self::WIDTH_INHERIT);
        if ($blog === self::WIDTH_INHERIT) {
            return self::contentWidthClass($config);
        }
        return self::layoutClassForWidth($blog, $config);
    }

    public static function layoutClassForWidth(string $width, ?object $config = null): string
    {
        $width = self::normalizeWidth($width);
        if ($width === self::WIDTH_NARROW) {
            return 'public-main--narrow';
        }
        if ($width === self::WIDTH_NORMAL) {
            return 'public-main--normal';
        }
        if ($width === self::WIDTH_WIDE) {
            return 'public-main--wide';
        }
        return 'public-main--full';
    }

    public static function resolveContentLayout(?string $layout, ?object $config = null): string
    {
        $layout = self::normalizeContentLayout($layout);
        if ($layout === null) {
            return self::contentWidthClass($config);
        }
        return self::layoutClassForWidth($layout, $config);
    }

    public static function inlineStyle(?object $config = null): string
    {
        $config = $config ?? self::getConfig();
        $vars = ['--pub-accent: ' . AppSettings::normalizeAccentColor($config->accent_color ?? '#2563eb')];
        if (self::normalizePreset($config->preset ?? '') === self::PRESET_CUSTOM) {
            $vars[] = '--pub-bg: ' . AppSettings::normalizeAccentColor($config->custom_bg ?? '#f8fafc');
            $vars[] = '--pub-surface: ' . AppSettings::normalizeAccentColor($config->custom_surface ?? '#ffffff');
            $vars[] = '--pub-text: ' . AppSettings::normalizeAccentColor($config->custom_text ?? '#0f172a');
        }
        return implode('; ', $vars) . ';';
    }

    public static function defaultColorMode(?object $config = null): string
    {
        $config = $config ?? self::getConfig();
        return self::normalizeColorMode($config->default_color_mode ?? self::MODE_SYSTEM);
    }

    public static function showColorToggle(?object $config = null): bool
    {
        return false;
    }

    public static function showAdminLink(?object $config = null): bool
    {
        $config = $config ?? self::getConfig();
        return !empty($config->show_admin_link);
    }

    public static function showSiteTitle(?object $config = null): bool
    {
        $config = $config ?? self::getConfig();
        return !empty($config->show_site_title);
    }

    public static function showBreadcrumbs(?object $config = null): bool
    {
        $config = $config ?? self::getConfig();
        return !empty($config->show_breadcrumbs);
    }

    public static function showBlogSearch(?object $config = null): bool
    {
        $config = $config ?? self::getConfig();
        return !empty($config->show_blog_search);
    }

    public static function showPostDates(?object $config = null): bool
    {
        $config = $config ?? self::getConfig();
        return !empty($config->show_post_dates);
    }

    public static function showListFeatured(?object $config = null): bool
    {
        $config = $config ?? self::getConfig();
        return !empty($config->show_list_featured);
    }

    public static function blogListStyle(?object $config = null): string
    {
        $config = $config ?? self::getConfig();
        return self::normalizeBlogListStyle((string) ($config->blog_list_style ?? self::BLOG_LIST));
    }

    public static function showBlogExcerpt(?object $config = null): bool
    {
        $config = $config ?? self::getConfig();
        return !empty($config->blog_show_excerpt);
    }

    public static function showBlogReadMore(?object $config = null): bool
    {
        $config = $config ?? self::getConfig();
        return !empty($config->blog_show_read_more);
    }

    public static function showBlogCategory(?object $config = null): bool
    {
        $config = $config ?? self::getConfig();
        return !empty($config->blog_show_category);
    }

    public static function showBlogViewSwitcher(?object $config = null): bool
    {
        $config = $config ?? self::getConfig();
        return !empty($config->blog_view_switcher);
    }

    public static function normalizePreset(string $value): string
    {
        $value = strtolower(trim($value));
        // Legacy alias from removed Good Men branding.
        if ($value === 'goodmen') {
            $value = self::PRESET_EDITORIAL;
        }
        return array_key_exists($value, self::presets()) ? $value : self::PRESET_DEFAULT;
    }

    public static function normalizeFont(string $value): string
    {
        $value = strtolower(trim($value));
        return array_key_exists($value, self::fonts()) ? $value : self::FONT_SYSTEM;
    }

    public static function normalizeRadius(string $value): string
    {
        $value = strtolower(trim($value));
        return array_key_exists($value, self::radii()) ? $value : self::RADIUS_MD;
    }

    public static function normalizeWidth(string $value): string
    {
        $value = strtolower(trim($value));
        return array_key_exists($value, self::contentWidths()) ? $value : self::WIDTH_FULL;
    }

    public static function normalizeBlogWidth(string $value): string
    {
        $value = strtolower(trim($value));
        return array_key_exists($value, self::blogContentWidths()) ? $value : self::WIDTH_INHERIT;
    }

    /** @return string|null narrow|normal|wide|full or null for site default */
    public static function normalizeContentLayout(?string $value): ?string
    {
        $value = strtolower(trim((string) $value));
        if ($value === '' || $value === 'default' || $value === 'inherit') {
            return null;
        }
        if (!array_key_exists($value, self::contentWidths())) {
            return null;
        }
        return $value;
    }

    public static function normalizeColorMode(string $value): string
    {
        $value = strtolower(trim($value));
        return array_key_exists($value, self::colorModes()) ? $value : self::MODE_SYSTEM;
    }

    public static function normalizeHeader(string $value): string
    {
        $value = strtolower(trim($value));
        return array_key_exists($value, self::headerStyles()) ? $value : self::HEADER_SOLID;
    }

    public static function normalizeFontSize(string $value): string
    {
        $value = strtolower(trim($value));
        return array_key_exists($value, self::fontSizes()) ? $value : self::SIZE_MD;
    }

    public static function normalizeLineHeight(string $value): string
    {
        $value = strtolower(trim($value));
        return array_key_exists($value, self::lineHeights()) ? $value : self::LEADING_NORMAL;
    }

    public static function normalizeButtonStyle(string $value): string
    {
        $value = strtolower(trim($value));
        return array_key_exists($value, self::buttonStyles()) ? $value : self::BTN_SOLID;
    }

    public static function normalizeFooterStyle(string $value): string
    {
        $value = strtolower(trim($value));
        return array_key_exists($value, self::footerStyles()) ? $value : self::FOOTER_SOLID;
    }

    public static function normalizeCardShadow(string $value): string
    {
        $value = strtolower(trim($value));
        return array_key_exists($value, self::cardShadows()) ? $value : self::SHADOW_SOFT;
    }

    public static function normalizeContentSpacing(string $value): string
    {
        $value = strtolower(trim($value));
        return array_key_exists($value, self::contentSpacings()) ? $value : self::SPACE_COMFORTABLE;
    }

    public static function normalizeLinkStyle(string $value): string
    {
        $value = strtolower(trim($value));
        return array_key_exists($value, self::linkStyles()) ? $value : self::LINK_ACCENT;
    }

    public static function normalizeNavStyle(string $value): string
    {
        $value = strtolower(trim($value));
        return array_key_exists($value, self::navStyles()) ? $value : self::NAV_INLINE;
    }

    public static function normalizeBrandWeight(string $value): string
    {
        $value = strtolower(trim($value));
        return array_key_exists($value, self::brandWeights()) ? $value : self::BRAND_BOLD;
    }

    public static function normalizeHeadingScale(string $value): string
    {
        $value = strtolower(trim($value));
        return array_key_exists($value, self::headingScales()) ? $value : self::HEADING_NORMAL;
    }

    public static function normalizeBlogListStyle(string $value): string
    {
        $value = strtolower(trim($value));
        if ($value === self::BLOG_STACKED) {
            return self::BLOG_LIST;
        }
        return array_key_exists($value, self::blogListStyles()) ? $value : self::BLOG_LIST;
    }

    public static function normalizeBlogGridColumns(string $value): string
    {
        $value = strtolower(trim($value));
        return array_key_exists($value, self::blogGridColumns()) ? $value : self::BLOG_COLS_3;
    }

    public static function normalizeBlogImageRatio(string $value): string
    {
        $value = strtolower(trim($value));
        return array_key_exists($value, self::blogImageRatios()) ? $value : self::BLOG_RATIO_LANDSCAPE;
    }

    public static function normalizeImageStyle(string $value): string
    {
        $value = strtolower(trim($value));
        return array_key_exists($value, self::imageStyles()) ? $value : self::IMAGE_SOFT;
    }

    public static function normalizeBorderStyle(string $value): string
    {
        $value = strtolower(trim($value));
        return array_key_exists($value, self::borderStyles()) ? $value : self::BORDER_SUBTLE;
    }

    public static function normalizeHeaderHeight(string $value): string
    {
        $value = strtolower(trim($value));
        return array_key_exists($value, self::headerHeights()) ? $value : self::HEADER_HEIGHT_COMFORTABLE;
    }

    public static function normalizeButtonSize(string $value): string
    {
        $value = strtolower(trim($value));
        return array_key_exists($value, self::buttonSizes()) ? $value : self::BTN_SIZE_MD;
    }

    public static function normalizeSidebarStyle(string $value): string
    {
        $value = strtolower(trim($value));
        return array_key_exists($value, self::sidebarStyles()) ? $value : self::SIDEBAR_CARD;
    }

    public static function normalizeFooterAlign(string $value): string
    {
        $value = strtolower(trim($value));
        return array_key_exists($value, self::footerAlignments()) ? $value : self::FOOTER_ALIGN_SPLIT;
    }

    public static function normalizeProseAlign(string $value): string
    {
        $value = strtolower(trim($value));
        return array_key_exists($value, self::proseAlignments()) ? $value : self::PROSE_LEFT;
    }

    public static function normalizeLogoSize(string $value): string
    {
        $value = strtolower(trim($value));
        return array_key_exists($value, self::logoSizes()) ? $value : self::LOGO_MD;
    }

    public static function normalizeTransitionStyle(string $value): string
    {
        $value = strtolower(trim($value));
        return array_key_exists($value, self::transitionStyles()) ? $value : self::TRANSITION_SUBTLE;
    }

    public static function normalizeFocusStyle(string $value): string
    {
        $value = strtolower(trim($value));
        return array_key_exists($value, self::focusStyles()) ? $value : self::FOCUS_ACCENT;
    }

    public static function normalizeChrome(string $value): string
    {
        $value = strtolower(trim($value));
        return array_key_exists($value, self::chromeStyles()) ? $value : self::CHROME_DEFAULT;
    }

    public static function normalizeDateFormat(string $value): string
    {
        $value = strtolower(trim($value));
        return array_key_exists($value, self::dateFormats()) ? $value : self::DATE_HUMAN;
    }

    public static function normalizeBlogKicker(string $value): string
    {
        $value = trim(preg_replace('/\s+/u', ' ', $value) ?? '');
        if (mb_strlen($value) > 80) {
            $value = mb_substr($value, 0, 80);
        }
        return $value;
    }

    public static function isEditorialChrome(?object $config = null): bool
    {
        $config = $config ?? self::getConfig();
        return self::normalizeChrome((string) ($config->chrome ?? '')) === self::CHROME_EDITORIAL;
    }

    public static function blogKicker(?object $config = null): string
    {
        $config = $config ?? self::getConfig();
        return self::normalizeBlogKicker((string) ($config->blog_kicker ?? ''));
    }

    public static function showSiteTagline(?object $config = null): bool
    {
        $config = $config ?? self::getConfig();
        return !empty($config->show_site_tagline);
    }

    /** Format a stored datetime for public lists/articles. */
    public static function formatPublicDate(?string $value, ?object $config = null): string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }
        $config = $config ?? self::getConfig();
        if (self::normalizeDateFormat((string) ($config->date_format ?? '')) === self::DATE_RAW) {
            return $value;
        }
        $ts = strtotime($value);
        if ($ts === false) {
            return $value;
        }
        return date('F j, Y', $ts);
    }
}
