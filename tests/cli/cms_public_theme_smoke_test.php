<?php
/**
 * Smoke test: public site theme config (presets, normalization, save/load).
 */
require dirname(__DIR__, 2) . '/bootstrap.php';

use App\PublicTheme;
use App\Models\AppSettings;

$original = PublicTheme::loadStoredConfig();

assert(PublicTheme::normalizePreset('ocean') === PublicTheme::PRESET_OCEAN, 'ocean preset');
assert(PublicTheme::normalizePreset('coral') === PublicTheme::PRESET_CORAL, 'coral preset');
assert(PublicTheme::normalizeButtonSize('lg') === PublicTheme::BTN_SIZE_LG, 'button size');
assert(PublicTheme::normalizeFooterAlign('center') === PublicTheme::FOOTER_ALIGN_CENTER, 'footer align');
assert(PublicTheme::normalizeFocusStyle('strong') === PublicTheme::FOCUS_STRONG, 'focus');

PublicTheme::saveConfig([
    'pub_theme_preset' => 'forest',
    'public_accent_color' => '#059669',
    'pub_theme_font' => 'modern',
    'pub_theme_radius' => 'lg',
    'pub_theme_width' => 'normal',
    'pub_theme_blog_width' => 'wide',
    'pub_theme_color_mode' => 'light',
    'pub_theme_header' => 'accent',
    'pub_theme_custom_bg' => '#f0fdf4',
    'pub_theme_custom_surface' => '#ffffff',
    'pub_theme_custom_text' => '#0f172a',
    'pub_theme_font_size' => 'lg',
    'pub_theme_line_height' => 'relaxed',
    'pub_theme_button_style' => 'soft',
    'pub_theme_footer_style' => 'accent',
    'pub_theme_card_shadow' => 'strong',
    'pub_theme_content_spacing' => 'spacious',
    'pub_theme_link_style' => 'underline',
    'pub_theme_sticky_header' => '0',
    'pub_theme_show_admin_link' => '0',
    'pub_theme_nav_style' => 'pills',
    'pub_theme_blog_list_style' => 'cards',
    'pub_theme_blog_grid_columns' => '4',
    'pub_theme_blog_image_ratio' => 'square',
    'pub_theme_blog_show_excerpt' => '0',
    'pub_theme_blog_view_switcher' => '1',
    'pub_theme_heading_scale' => 'dramatic',
    'pub_theme_show_breadcrumbs' => '0',
    'pub_theme_nav_uppercase' => '1',
    'pub_theme_button_size' => 'lg',
    'pub_theme_footer_align' => 'center',
    'pub_theme_show_blog_search' => '0',
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
assert($loaded->font_size === PublicTheme::SIZE_LG, 'saved font size');
assert($loaded->line_height === PublicTheme::LEADING_RELAXED, 'saved line height');
assert($loaded->button_style === PublicTheme::BTN_SOFT, 'saved button');
assert($loaded->footer_style === PublicTheme::FOOTER_ACCENT, 'saved footer');
assert($loaded->card_shadow === PublicTheme::SHADOW_STRONG, 'saved shadow');
assert($loaded->content_spacing === PublicTheme::SPACE_SPACIOUS, 'saved spacing');
assert($loaded->link_style === PublicTheme::LINK_UNDERLINE, 'saved link');
assert($loaded->sticky_header === false, 'sticky off');
assert($loaded->show_admin_link === false, 'admin link off');
assert($loaded->nav_style === PublicTheme::NAV_PILLS, 'nav pills saved');
assert($loaded->blog_list_style === PublicTheme::BLOG_CARDS, 'blog cards saved');
assert($loaded->blog_grid_columns === PublicTheme::BLOG_COLS_4, 'blog cols saved');
assert($loaded->blog_image_ratio === PublicTheme::BLOG_RATIO_SQUARE, 'blog ratio saved');
assert($loaded->blog_show_excerpt === false, 'blog excerpt off');
assert($loaded->blog_view_switcher === true, 'blog switcher on');
assert(PublicTheme::normalizeBlogListStyle('stacked') === PublicTheme::BLOG_LIST, 'stacked maps to list');
assert($loaded->heading_scale === PublicTheme::HEADING_DRAMATIC, 'heading saved');
assert($loaded->show_breadcrumbs === false, 'crumbs off');
assert($loaded->nav_uppercase === true, 'nav upper');
assert($loaded->button_size === PublicTheme::BTN_SIZE_LG, 'btn size');
assert($loaded->footer_align === PublicTheme::FOOTER_ALIGN_CENTER, 'footer center');
assert($loaded->show_blog_search === false, 'blog search off');
assert($loaded->show_color_toggle === false, 'public toggle disabled');

$classes = PublicTheme::htmlClasses($loaded);
assert(strpos($classes, 'pub-theme-forest') !== false, 'html classes include preset');
assert(strpos($classes, 'pub-size-lg') !== false, 'html classes include size');
assert(strpos($classes, 'pub-btn-soft') !== false, 'html classes include button');
assert(strpos($classes, 'pub-header-static') !== false, 'static header class');
assert(strpos($classes, 'pub-nav-pills') !== false, 'nav pills class');
assert(strpos($classes, 'pub-blog-cards') !== false, 'blog cards class');
assert(strpos($classes, 'pub-blogcols-4') !== false, 'blog cols class');
assert(strpos($classes, 'pub-blogratio-square') !== false, 'blog ratio class');
assert(strpos($classes, 'pub-blogexcerpt-off') !== false, 'blog excerpt class');
assert(strpos($classes, 'pub-blogswitch-on') !== false, 'blog switcher class');
assert(strpos($classes, 'pub-crumbs-off') !== false, 'crumbs off class');
assert(strpos($classes, 'pub-btnsz-lg') !== false, 'btn size class');
assert(strpos($classes, 'pub-blogsearch-off') !== false, 'blog search class');

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
    'pub_theme_custom_bg' => '#fef3c7',
    'pub_theme_custom_surface' => '#fffbeb',
    'pub_theme_custom_text' => '#78350f',
    'pub_theme_sticky_header' => '1',
    'pub_theme_show_admin_link' => '1',
]);

$custom = PublicTheme::getConfig();
assert($custom->show_color_toggle === false, 'toggle stays off');
$customStyle = PublicTheme::inlineStyle($custom);
assert(strpos($customStyle, '--pub-bg: #fef3c7') !== false, 'custom bg in style');

PublicTheme::saveConfig(PublicTheme::configToPostFields($original));

$branding = AppSettings::getBrandingConfig();
assert(preg_match('/^#[0-9a-f]{6}$/', $branding->public_accent_color), 'branding accent still valid');

echo "cms_public_theme_smoke_test: OK\n";
