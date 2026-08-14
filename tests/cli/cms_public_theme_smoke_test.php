<?php
/**
 * Smoke test: public site theme config (presets, normalization, save/load).
 */
require dirname(__DIR__, 2) . '/bootstrap.php';

use App\PublicTheme;
use App\Models\AppSettings;

$original = PublicTheme::getConfig();

assert(PublicTheme::normalizePreset('ocean') === PublicTheme::PRESET_OCEAN, 'ocean preset');
assert(PublicTheme::normalizePreset('invalid') === PublicTheme::PRESET_DEFAULT, 'invalid preset fallback');
assert(PublicTheme::normalizeFont('serif') === PublicTheme::FONT_SERIF, 'serif font');
assert(PublicTheme::normalizeColorMode('dark') === PublicTheme::MODE_DARK, 'dark mode');
assert(PublicTheme::normalizeWidth('wide') === PublicTheme::WIDTH_WIDE, 'wide width');

PublicTheme::saveConfig([
    'pub_theme_preset' => 'forest',
    'public_accent_color' => '#059669',
    'pub_theme_font' => 'modern',
    'pub_theme_radius' => 'lg',
    'pub_theme_width' => 'normal',
    'pub_theme_blog_width' => 'wide',
    'pub_theme_color_mode' => 'light',
    'pub_theme_header' => 'accent',
    'pub_theme_show_toggle' => '1',
    'pub_theme_custom_bg' => '#f0fdf4',
    'pub_theme_custom_surface' => '#ffffff',
    'pub_theme_custom_text' => '#0f172a',
]);

$loaded = PublicTheme::getConfig();
assert($loaded->preset === PublicTheme::PRESET_FOREST, 'saved preset');
assert($loaded->accent_color === '#059669', 'saved accent');
assert($loaded->font === PublicTheme::FONT_MODERN, 'saved font');
assert($loaded->radius === PublicTheme::RADIUS_LG, 'saved radius');
assert($loaded->content_width === PublicTheme::WIDTH_NORMAL, 'saved width');
assert($loaded->blog_content_width === PublicTheme::WIDTH_WIDE, 'saved blog width');
assert($loaded->default_color_mode === PublicTheme::MODE_LIGHT, 'saved color mode');
assert($loaded->header_style === PublicTheme::HEADER_ACCENT, 'saved header');
assert($loaded->show_color_toggle === true, 'toggle on');

$classes = PublicTheme::htmlClasses($loaded);
assert(strpos($classes, 'pub-theme-forest') !== false, 'html classes include preset');
assert(strpos($classes, 'pub-font-modern') !== false, 'html classes include font');

$style = PublicTheme::inlineStyle($loaded);
assert(strpos($style, '#059669') !== false, 'inline style includes accent');

PublicTheme::saveConfig([
    'pub_theme_preset' => 'custom',
    'public_accent_color' => '#111827',
    'pub_theme_font' => PublicTheme::FONT_SYSTEM,
    'pub_theme_radius' => PublicTheme::RADIUS_MD,
    'pub_theme_width' => PublicTheme::WIDTH_NARROW,
    'pub_theme_blog_width' => PublicTheme::WIDTH_INHERIT,
    'pub_theme_color_mode' => PublicTheme::MODE_SYSTEM,
    'pub_theme_header' => PublicTheme::HEADER_SOLID,
    'pub_theme_show_toggle' => '',
    'pub_theme_custom_bg' => '#fef3c7',
    'pub_theme_custom_surface' => '#fffbeb',
    'pub_theme_custom_text' => '#78350f',
]);

$custom = PublicTheme::getConfig();
assert($custom->show_color_toggle === false, 'toggle off when unchecked');
$customStyle = PublicTheme::inlineStyle($custom);
assert(strpos($customStyle, '--pub-bg: #fef3c7') !== false, 'custom bg in style');

// Restore original settings
PublicTheme::saveConfig([
    'pub_theme_preset' => $original->preset,
    'public_accent_color' => $original->accent_color,
    'pub_theme_font' => $original->font,
    'pub_theme_radius' => $original->radius,
    'pub_theme_width' => $original->content_width,
    'pub_theme_blog_width' => $original->blog_content_width ?? PublicTheme::WIDTH_INHERIT,
    'pub_theme_color_mode' => $original->default_color_mode,
    'pub_theme_header' => $original->header_style,
    'pub_theme_show_toggle' => $original->show_color_toggle ? '1' : '',
    'pub_theme_custom_bg' => $original->custom_bg,
    'pub_theme_custom_surface' => $original->custom_surface,
    'pub_theme_custom_text' => $original->custom_text,
]);

$branding = AppSettings::getBrandingConfig();
assert(preg_match('/^#[0-9a-f]{6}$/', $branding->public_accent_color), 'branding accent still valid');

echo "cms_public_theme_smoke_test: OK\n";
