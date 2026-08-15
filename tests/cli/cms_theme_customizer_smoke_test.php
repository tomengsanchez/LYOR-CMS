<?php
/**
 * Smoke: theme Customizer URL helpers and bridge config.
 */
require dirname(__DIR__, 2) . '/bootstrap.php';

use App\PublicTheme;

$url = PublicTheme::previewUrlFor('/blog');
assert(str_contains($url, '/blog'), 'blog path');
assert(str_contains($url, 'theme_preview=1'), 'preview flag');

$frame = PublicTheme::customizerFrameUrl('/');
assert(str_contains($frame, 'theme_preview=1'), 'frame preview');
assert(str_contains($frame, 'customize_frame=1'), 'frame flag');

$fields = PublicTheme::configToPostFields(PublicTheme::loadStoredConfig());
assert(isset($fields['pub_theme_preset'], $fields['public_accent_color']), 'post fields');

$bridge = PublicTheme::configForBridge(PublicTheme::loadStoredConfig());
assert(isset($bridge['html_classes'], $bridge['inline_style'], $bridge['content_width_class']), 'bridge keys');
assert(isset($bridge['button_style'], $bridge['font_size'], $bridge['sticky_header']), 'extended bridge keys');
assert(isset($bridge['nav_style'], $bridge['blog_list_style'], $bridge['heading_scale']), 'round2 bridge keys');
assert(isset($bridge['button_size'], $bridge['footer_align'], $bridge['show_blog_search']), 'round3 bridge keys');
assert(str_contains($bridge['html_classes'], 'pub-theme-'), 'html classes');
assert(str_contains($bridge['html_classes'], 'pub-size-') || str_contains($bridge['html_classes'], 'pub-btn-'), 'extended html classes');

$js = file_get_contents(dirname(__DIR__, 2) . '/public/assets/js/customize/customizer.js');
assert(str_contains($js, 'cms-theme-preview'), 'customizer postMessage type');
$bridgeJs = file_get_contents(dirname(__DIR__, 2) . '/public/assets/js/public/theme-preview-bridge.js');
assert(str_contains($bridgeJs, 'cms-theme-preview'), 'bridge listener');

$index = file_get_contents(dirname(__DIR__, 2) . '/public/index.php');
assert(str_contains($index, "get('/admin/customize'"), 'customize route');
assert(str_contains($index, 'CustomizeController@publish'), 'publish route');
assert(str_contains($index, 'import-style-pack'), 'style pack import route');

echo "cms_theme_customizer_smoke_test: OK\n";
