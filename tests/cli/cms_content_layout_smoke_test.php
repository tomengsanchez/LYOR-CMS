<?php
/**
 * Smoke test: content layout column + blog width + theme preview session.
 */
require dirname(__DIR__, 2) . '/bootstrap.php';

use App\PublicTheme;
use Core\Database;

$db = Database::getInstance();

foreach (['cms_pages' => 'content_layout', 'cms_posts' => 'content_layout'] as $table => $col) {
    $rows = $db->query('SHOW COLUMNS FROM ' . $table . ' LIKE ' . $db->quote($col))->fetchAll();
    assert(count($rows) === 1, "{$table}.{$col} should exist");
}

assert(PublicTheme::normalizeContentLayout('') === null, 'empty layout is default');
assert(PublicTheme::normalizeContentLayout('wide') === PublicTheme::WIDTH_WIDE, 'wide layout');
assert(PublicTheme::normalizeContentLayout('invalid') === null, 'invalid layout');

assert(PublicTheme::normalizeBlogWidth('inherit') === PublicTheme::WIDTH_INHERIT, 'blog inherit');
assert(PublicTheme::layoutClassForWidth('normal') === 'public-main--normal', 'normal class');
assert(PublicTheme::resolveContentLayout(null) === PublicTheme::contentWidthClass(), 'default resolve');

$original = PublicTheme::loadStoredConfig();
PublicTheme::saveConfig([
    'pub_theme_preset' => $original->preset,
    'public_accent_color' => $original->accent_color,
    'pub_theme_font' => $original->font,
    'pub_theme_radius' => $original->radius,
    'pub_theme_width' => PublicTheme::WIDTH_NARROW,
    'pub_theme_blog_width' => PublicTheme::WIDTH_WIDE,
    'pub_theme_color_mode' => $original->default_color_mode,
    'pub_theme_header' => $original->header_style,
    'pub_theme_show_toggle' => $original->show_color_toggle ? '1' : '',
    'pub_theme_custom_bg' => $original->custom_bg,
    'pub_theme_custom_surface' => $original->custom_surface,
    'pub_theme_custom_text' => $original->custom_text,
]);

$loaded = PublicTheme::loadStoredConfig();
assert($loaded->blog_content_width === PublicTheme::WIDTH_WIDE, 'blog width saved');
assert(PublicTheme::blogContentWidthClass($loaded) === 'public-main--wide', 'blog wide class');

PublicTheme::setPreviewFromPost([
    'pub_theme_preset' => 'ocean',
    'public_accent_color' => '#0891b2',
    'pub_theme_font' => PublicTheme::FONT_SYSTEM,
    'pub_theme_radius' => PublicTheme::RADIUS_MD,
    'pub_theme_width' => PublicTheme::WIDTH_NARROW,
    'pub_theme_blog_width' => PublicTheme::WIDTH_INHERIT,
    'pub_theme_color_mode' => PublicTheme::MODE_SYSTEM,
    'pub_theme_header' => PublicTheme::HEADER_SOLID,
    'pub_theme_show_toggle' => '1',
    'pub_theme_custom_bg' => '#f0fdfa',
    'pub_theme_custom_surface' => '#ffffff',
    'pub_theme_custom_text' => '#0f172a',
]);
assert(!PublicTheme::isPreviewActive(), 'preview inactive without query flag');
$_GET['theme_preview'] = '1';
// previewAllowed may be false in CLI without auth — test setPreview data only
$preview = $_SESSION['pub_theme_preview'] ?? null;
assert(is_array($preview), 'preview stored in session');
assert($preview['preset'] === 'ocean', 'preview preset');
unset($_GET['theme_preview']);
PublicTheme::clearPreview();

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

echo "cms_content_layout_smoke_test: OK\n";
