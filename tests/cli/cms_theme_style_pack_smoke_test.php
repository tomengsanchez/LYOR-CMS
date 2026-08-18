<?php
/**
 * Smoke: ThemeStylePack CMS zip import, CSS sanitize, export, clear.
 */
require dirname(__DIR__, 2) . '/bootstrap.php';

use App\Models\AppSettings;
use App\PublicTheme;
use App\ThemeStylePack;

assert(class_exists(\ZipArchive::class), 'ZipArchive required');

$css = ThemeStylePack::sanitizeCss("@import url('http://evil'); body{color:red} expression(alert(1)) behavior:url(x) javascript:void(0) -moz-binding:x");
assert(!str_contains(strtolower($css), '@import url'), 'import stripped');
assert(!str_contains(strtolower($css), 'expression('), 'expression stripped');
assert(!str_contains(strtolower($css), 'javascript:'), 'javascript stripped');

$beforePreset = AppSettings::get('pub_theme_preset', 'default');
$beforeAccent = AppSettings::get('public_accent_color', '#2563eb');

$tmpDir = sys_get_temp_dir() . '/cms_style_pack_smoke_' . bin2hex(random_bytes(4));
mkdir($tmpDir, 0755, true);
$json = [
    'format' => 'cms-style-pack',
    'version' => 1,
    'name' => 'Smoke Pack',
    'settings' => [
        'pub_theme_preset' => 'coral',
        'public_accent_color' => '#ea580c',
        'pub_theme_blog_kicker' => 'SMOKE KICKER',
    ],
    'extra_css' => 'extra.css',
];
file_put_contents($tmpDir . '/cms-theme.json', json_encode($json));
file_put_contents($tmpDir . '/extra.css', ".public-site{outline:1px solid #ea580c}\n@import url('bad');\n");

$zipPath = $tmpDir . '/pack.zip';
$zip = new ZipArchive();
assert($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true, 'create zip');
$zip->addFile($tmpDir . '/cms-theme.json', 'cms-theme.json');
$zip->addFile($tmpDir . '/extra.css', 'extra.css');
$zip->close();

$result = ThemeStylePack::importFromPath($zipPath);
assert(!empty($result['ok']), 'import ok: ' . ($result['message'] ?? ''));
assert(ThemeStylePack::packName() === 'Smoke Pack', 'pack name');
assert(ThemeStylePack::packSource() === 'cms', 'pack source');
assert(AppSettings::get('pub_theme_preset') === 'coral', 'preset applied');
assert(str_contains(AppSettings::get('public_accent_color', ''), 'ea580c') || AppSettings::get('public_accent_color') === '#ea580c', 'accent applied');
$cssUrl = ThemeStylePack::activeCssPublicUrl();
assert($cssUrl !== null && str_contains($cssUrl, 'theme-packs/library/') && str_contains($cssUrl, 'extra.css'), 'css url');
$cssRel = str_replace('/public/uploads/', '', $cssUrl);
$cssFile = dirname(__DIR__, 2) . '/public/uploads/' . ltrim($cssRel, '/');
assert(is_file($cssFile), 'css on disk');
$diskCss = (string) file_get_contents($cssFile);
assert(!str_contains(strtolower($diskCss), '@import url'), 'disk css sanitized');
assert(ThemeStylePack::listLibrary() !== [], 'library populated');
assert(ThemeStylePack::activePackId() !== '', 'active pack id');

$bin = ThemeStylePack::exportCurrentZipBinary();
assert(strlen($bin) > 50, 'export binary');

// WP-style partial
$wpDir = $tmpDir . '/wp';
mkdir($wpDir);
file_put_contents($wpDir . '/style.css', "/*\nTheme Name: Smoke WP Theme\nDescription: Test\n*/\nbody{}\n");
file_put_contents($wpDir . '/theme.json', json_encode([
    'settings' => [
        'color' => [
            'palette' => [
                ['slug' => 'primary', 'color' => '#0ea5e9', 'name' => 'Primary'],
                ['slug' => 'background', 'color' => '#f8fafc', 'name' => 'Background'],
                ['slug' => 'text', 'color' => '#0f172a', 'name' => 'Text'],
            ],
        ],
    ],
]));
file_put_contents($wpDir . '/functions.php', '<?php // ignored');
$wpZip = $tmpDir . '/wp-theme.zip';
$zip = new ZipArchive();
assert($zip->open($wpZip, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true, 'wp zip');
$zip->addFile($wpDir . '/style.css', 'style.css');
$zip->addFile($wpDir . '/theme.json', 'theme.json');
$zip->addFile($wpDir . '/functions.php', 'functions.php');
$zip->close();
$wpResult = ThemeStylePack::importFromPath($wpZip);
assert(!empty($wpResult['ok']), 'wp import: ' . ($wpResult['message'] ?? ''));
assert(ThemeStylePack::packSource() === 'wordpress', 'wp source');
assert(str_contains(strtolower(ThemeStylePack::packName()), 'smoke wp'), 'wp name');

ThemeStylePack::clearActivePack();
assert(ThemeStylePack::packName() === '', 'cleared name');
assert(ThemeStylePack::activeCssPublicUrl() === null, 'cleared css');
assert(ThemeStylePack::listLibrary() !== [], 'library kept after clear');

$libBefore = ThemeStylePack::listLibrary();
$react = ThemeStylePack::activatePack($libBefore[0]['id']);
assert(!empty($react['ok']), 'reactivate from library: ' . ($react['message'] ?? ''));
assert(ThemeStylePack::activePackId() === $libBefore[0]['id'], 'active id');

ThemeStylePack::clearActivePack();

// Restore prior theme basics so smoke does not leave coral permanently if that matters
PublicTheme::saveConfig(array_merge(PublicTheme::configToPostFields(), [
    'pub_theme_preset' => $beforePreset,
    'public_accent_color' => $beforeAccent,
]));

// Cleanup temp
foreach ([$zipPath, $wpZip, $tmpDir . '/cms-theme.json', $tmpDir . '/extra.css', $wpDir . '/style.css', $wpDir . '/theme.json', $wpDir . '/functions.php'] as $f) {
    @unlink($f);
}
@rmdir($wpDir);
@rmdir($tmpDir);

$index = file_get_contents(dirname(__DIR__, 2) . '/public/index.php');
assert(str_contains($index, 'import-style-pack'), 'import route');
assert(str_contains($index, 'export-style-pack'), 'export route');
assert(str_contains($index, 'activate-style-pack'), 'activate route');
assert(str_contains($index, 'install-bundled-style-pack'), 'install bundled route');
assert(isset(ThemeStylePack::bundledPackLabels()['manly']), 'manly bundled label');
assert(isset(ThemeStylePack::bundledPackLabels()['pulse']), 'pulse bundled label');
assert(isset(ThemeStylePack::bundledPackLabels()['enterprise']), 'enterprise bundled label');

// Ensure default developer template zip exists and imports.
$build = dirname(__DIR__, 2) . '/cli/build_sample_style_pack.php';
passthru('php ' . escapeshellarg($build), $buildCode);
assert($buildCode === 0, 'build sample zip');
$sample = \App\ThemeStylePack::samplePackAbsolutePath();
assert($sample !== null && is_file($sample), 'sample zip path');
$sampleImport = ThemeStylePack::importFromPath($sample);
assert(!empty($sampleImport['ok']), 'sample zip imports: ' . ($sampleImport['message'] ?? ''));
assert(ThemeStylePack::packSource() === 'cms', 'sample is cms pack');
ThemeStylePack::clearActivePack();

$manly = ThemeStylePack::samplePackAbsolutePath('manly');
assert($manly !== null && is_file($manly), 'manly zip path');
$manlyImport = ThemeStylePack::importFromPath($manly);
assert(!empty($manlyImport['ok']), 'manly zip imports: ' . ($manlyImport['message'] ?? ''));
assert(stripos(ThemeStylePack::packName(), 'Manly') !== false, 'manly pack name');
ThemeStylePack::clearActivePack();
PublicTheme::saveConfig(array_merge(PublicTheme::configToPostFields(), [
    'pub_theme_preset' => $beforePreset,
    'public_accent_color' => $beforeAccent,
]));

$pulseAreas = [
    \App\Models\Widget::AREA_HEADER,
    \App\Models\Widget::AREA_AFTER_HEADER,
    \App\Models\Widget::AREA_HOME,
    \App\Models\Widget::AREA_AFTER_CONTENT,
];
$pulseWasEmpty = [];
foreach ($pulseAreas as $area) {
    $pulseWasEmpty[$area] = \App\Models\Widget::forArea($area, false) === [];
}
$pulse = ThemeStylePack::samplePackAbsolutePath('pulse');
assert($pulse !== null && is_file($pulse), 'pulse zip path');
$pulseImport = ThemeStylePack::importFromPath($pulse);
assert(!empty($pulseImport['ok']), 'pulse zip imports: ' . ($pulseImport['message'] ?? ''));
assert(stripos(ThemeStylePack::packName(), 'Pulse') !== false, 'pulse pack name');
if (!empty($pulseWasEmpty[\App\Models\Widget::AREA_HEADER])) {
    assert(\App\Models\Widget::areaHasWidgets(\App\Models\Widget::AREA_HEADER), 'pulse filled empty header');
}
foreach ($pulseWasEmpty as $area => $wasEmpty) {
    if ($wasEmpty) {
        \App\Models\Widget::saveAreaWidgets($area, []);
    }
}
ThemeStylePack::clearActivePack();
PublicTheme::saveConfig(array_merge(PublicTheme::configToPostFields(), [
    'pub_theme_preset' => $beforePreset,
    'public_accent_color' => $beforeAccent,
]));

$enterprise = ThemeStylePack::samplePackAbsolutePath('enterprise');
assert($enterprise !== null && is_file($enterprise), 'enterprise zip path');
$enterpriseImport = ThemeStylePack::importFromPath($enterprise);
assert(!empty($enterpriseImport['ok']), 'enterprise zip imports: ' . ($enterpriseImport['message'] ?? ''));
assert(stripos(ThemeStylePack::packName(), 'Enterprise') !== false, 'enterprise pack name');
assert(AppSettings::get('pub_theme_font') === 'modern', 'enterprise modern font');
ThemeStylePack::clearActivePack();
PublicTheme::saveConfig(array_merge(PublicTheme::configToPostFields(), [
    'pub_theme_preset' => $beforePreset,
    'public_accent_color' => $beforeAccent,
]));

echo "cms_theme_style_pack_smoke_test: OK\n";
