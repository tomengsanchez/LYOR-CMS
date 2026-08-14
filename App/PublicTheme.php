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
    public const PRESET_CUSTOM = 'custom';

    public const FONT_SYSTEM = 'system';
    public const FONT_SERIF = 'serif';
    public const FONT_MODERN = 'modern';
    public const FONT_MONO = 'mono';

    public const RADIUS_SM = 'sm';
    public const RADIUS_MD = 'md';
    public const RADIUS_LG = 'lg';

    public const WIDTH_INHERIT = 'inherit';
    public const WIDTH_NARROW = 'narrow';
    public const WIDTH_NORMAL = 'normal';
    public const WIDTH_WIDE = 'wide';

    public const MODE_LIGHT = 'light';
    public const MODE_DARK = 'dark';
    public const MODE_SYSTEM = 'system';

    public const HEADER_SOLID = 'solid';
    public const HEADER_ACCENT = 'accent';
    public const HEADER_MINIMAL = 'minimal';

    private const SESSION_PREVIEW = 'pub_theme_preview';

    /** @return array<string, string> */
    public static function presets(): array
    {
        return [
            self::PRESET_DEFAULT => 'Default (Blue)',
            self::PRESET_OCEAN    => 'Ocean',
            self::PRESET_FOREST   => 'Forest',
            self::PRESET_SUNSET   => 'Sunset',
            self::PRESET_VIOLET   => 'Violet',
            self::PRESET_SLATE    => 'Slate',
            self::PRESET_ROSE     => 'Rose',
            self::PRESET_CUSTOM   => 'Custom colors',
        ];
    }

    /** @return array<string, string> */
    public static function fonts(): array
    {
        return [
            self::FONT_SYSTEM => 'System UI',
            self::FONT_SERIF  => 'Serif',
            self::FONT_MODERN => 'Modern sans',
            self::FONT_MONO   => 'Monospace',
        ];
    }

    /** @return array<string, string> */
    public static function radii(): array
    {
        return [
            self::RADIUS_SM => 'Small (8px)',
            self::RADIUS_MD => 'Medium (12px)',
            self::RADIUS_LG => 'Large (16px)',
        ];
    }

    /** @return array<string, string> */
    public static function contentWidths(): array
    {
        return [
            self::WIDTH_NARROW  => 'Narrow (720px)',
            self::WIDTH_NORMAL  => 'Normal (840px)',
            self::WIDTH_WIDE    => 'Wide (960px)',
        ];
    }

    /** @return array<string, string> */
    public static function blogContentWidths(): array
    {
        return [
            self::WIDTH_INHERIT => 'Same as site default',
            self::WIDTH_NARROW  => 'Narrow (720px)',
            self::WIDTH_NORMAL  => 'Normal (840px)',
            self::WIDTH_WIDE    => 'Wide (960px)',
        ];
    }

    /** @return array<string, string> */
    public static function contentLayoutOptions(): array
    {
        return [
            '' => 'Site default (theme)',
            self::WIDTH_NARROW => 'Narrow (720px)',
            self::WIDTH_NORMAL => 'Normal (840px)',
            self::WIDTH_WIDE   => 'Wide (960px)',
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
            'content_width' => self::normalizeWidth(AppSettings::get('pub_theme_width', self::WIDTH_NARROW)),
            'blog_content_width' => self::normalizeBlogWidth(AppSettings::get('pub_theme_blog_width', self::WIDTH_INHERIT)),
            'default_color_mode' => self::normalizeColorMode(AppSettings::get('pub_theme_color_mode', self::MODE_SYSTEM)),
            'header_style' => self::normalizeHeader(AppSettings::get('pub_theme_header', self::HEADER_SOLID)),
            'show_color_toggle' => AppSettings::get('pub_theme_show_toggle', '1') === '1',
            'custom_bg' => AppSettings::normalizeAccentColor(AppSettings::get('pub_theme_custom_bg', '#f8fafc')),
            'custom_surface' => AppSettings::normalizeAccentColor(AppSettings::get('pub_theme_custom_surface', '#ffffff')),
            'custom_text' => AppSettings::normalizeAccentColor(AppSettings::get('pub_theme_custom_text', '#0f172a')),
        ];
    }

    /** @param array<string, mixed> $data */
    public static function saveConfig(array $data): void
    {
        AppSettings::set('pub_theme_preset', self::normalizePreset((string) ($data['pub_theme_preset'] ?? '')));
        AppSettings::set('public_accent_color', AppSettings::normalizeAccentColor((string) ($data['public_accent_color'] ?? '')));
        AppSettings::set('pub_theme_font', self::normalizeFont((string) ($data['pub_theme_font'] ?? '')));
        AppSettings::set('pub_theme_radius', self::normalizeRadius((string) ($data['pub_theme_radius'] ?? '')));
        AppSettings::set('pub_theme_width', self::normalizeWidth((string) ($data['pub_theme_width'] ?? '')));
        AppSettings::set('pub_theme_blog_width', self::normalizeBlogWidth((string) ($data['pub_theme_blog_width'] ?? self::WIDTH_INHERIT)));
        AppSettings::set('pub_theme_color_mode', self::normalizeColorMode((string) ($data['pub_theme_color_mode'] ?? '')));
        AppSettings::set('pub_theme_header', self::normalizeHeader((string) ($data['pub_theme_header'] ?? '')));
        AppSettings::set('pub_theme_show_toggle', !empty($data['pub_theme_show_toggle']) ? '1' : '0');
        AppSettings::set('pub_theme_custom_bg', AppSettings::normalizeAccentColor((string) ($data['pub_theme_custom_bg'] ?? '#f8fafc')));
        AppSettings::set('pub_theme_custom_surface', AppSettings::normalizeAccentColor((string) ($data['pub_theme_custom_surface'] ?? '#ffffff')));
        AppSettings::set('pub_theme_custom_text', AppSettings::normalizeAccentColor((string) ($data['pub_theme_custom_text'] ?? '#0f172a')));
        self::clearPreview();
    }

    /** @param array<string, mixed> $data */
    public static function setPreviewFromPost(array $data): void
    {
        $_SESSION[self::SESSION_PREVIEW] = [
            'preset' => self::normalizePreset((string) ($data['pub_theme_preset'] ?? '')),
            'accent_color' => AppSettings::normalizeAccentColor((string) ($data['public_accent_color'] ?? '')),
            'font' => self::normalizeFont((string) ($data['pub_theme_font'] ?? '')),
            'radius' => self::normalizeRadius((string) ($data['pub_theme_radius'] ?? '')),
            'content_width' => self::normalizeWidth((string) ($data['pub_theme_width'] ?? '')),
            'blog_content_width' => self::normalizeBlogWidth((string) ($data['pub_theme_blog_width'] ?? self::WIDTH_INHERIT)),
            'default_color_mode' => self::normalizeColorMode((string) ($data['pub_theme_color_mode'] ?? '')),
            'header_style' => self::normalizeHeader((string) ($data['pub_theme_header'] ?? '')),
            'show_color_toggle' => !empty($data['pub_theme_show_toggle']),
            'custom_bg' => AppSettings::normalizeAccentColor((string) ($data['pub_theme_custom_bg'] ?? '#f8fafc')),
            'custom_surface' => AppSettings::normalizeAccentColor((string) ($data['pub_theme_custom_surface'] ?? '#ffffff')),
            'custom_text' => AppSettings::normalizeAccentColor((string) ($data['pub_theme_custom_text'] ?? '#0f172a')),
        ];
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
        return '/?theme_preview=1';
    }

    private static function configFromPreview(): object
    {
        $stored = (array) self::loadStoredConfig();
        $preview = $_SESSION[self::SESSION_PREVIEW];
        return (object) array_merge($stored, $preview);
    }

    public static function htmlClasses(?object $config = null): string
    {
        $config = $config ?? self::getConfig();
        $parts = [
            'pub-theme-' . self::normalizePreset($config->preset ?? self::PRESET_DEFAULT),
            'pub-font-' . self::normalizeFont($config->font ?? self::FONT_SYSTEM),
            'pub-radius-' . self::normalizeRadius($config->radius ?? self::RADIUS_MD),
            'pub-header-' . self::normalizeHeader($config->header_style ?? self::HEADER_SOLID),
        ];
        return implode(' ', $parts);
    }

    public static function contentWidthClass(?object $config = null): string
    {
        $config = $config ?? self::getConfig();
        return self::layoutClassForWidth($config->content_width ?? self::WIDTH_NARROW, $config);
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
        if ($width === self::WIDTH_WIDE) {
            return 'public-main--wide';
        }
        if ($width === self::WIDTH_NORMAL) {
            return 'public-main--normal';
        }
        return 'public-main--narrow';
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
        $config = $config ?? self::getConfig();
        return !empty($config->show_color_toggle);
    }

    public static function normalizePreset(string $value): string
    {
        $value = strtolower(trim($value));
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
        return array_key_exists($value, self::contentWidths()) ? $value : self::WIDTH_NARROW;
    }

    public static function normalizeBlogWidth(string $value): string
    {
        $value = strtolower(trim($value));
        return array_key_exists($value, self::blogContentWidths()) ? $value : self::WIDTH_INHERIT;
    }

    /** @return string|null narrow|normal|wide or null for site default */
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
}
