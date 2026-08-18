<?php
/**
 * Build one or more CMS style-pack zips from docs/samples/cms-style-pack-{slug}/.
 *
 * Usage:
 *   php cli/build_style_pack.php                  # all known packs
 *   php cli/build_style_pack.php example
 *   php cli/build_style_pack.php play-build-sound
 *   php cli/build_style_pack.php manly
 *   php cli/build_style_pack.php pulse
 *   php cli/build_style_pack.php enterprise
 */
$root = dirname(__DIR__);

if (!class_exists('ZipArchive')) {
    fwrite(STDERR, "PHP ZipArchive extension is required (enable extension=zip).\n");
    exit(1);
}

$known = [
    'example' => [
        'dir' => 'cms-style-pack-example',
        'zip' => 'cms-style-pack-example.zip',
        'readme' => "CMS Style Pack Template (v1)\n============================\n\n1. Edit cms-theme.json + extra.css.\n2. Re-zip with files at the ARCHIVE ROOT.\n3. Import in Appearance → Customize.\n\nNot a WordPress PHP theme.\n",
    ],
    'play-build-sound' => [
        'dir' => 'cms-style-pack-play-build-sound',
        'zip' => 'cms-style-pack-play-build-sound.zip',
        'readme' => "Play · Build · Sound\n====================\n\nGaming · web development · music theme pack.\n\n1. Edit cms-theme.json + extra.css as needed.\n2. Re-zip with files at the ARCHIVE ROOT.\n3. Import in Appearance → Customize.\n\nNot a WordPress PHP theme.\n",
    ],
    'manly' => [
        'dir' => 'cms-style-pack-manly',
        'zip' => 'cms-style-pack-manly.zip',
        'readme' => "Manly\n=====\n\nOak, iron, leather theme pack (dark lodge / magazine).\n\n1. Edit cms-theme.json + extra.css as needed.\n2. Re-zip with files at the ARCHIVE ROOT.\n3. Import in Appearance → Customize.\n\nNot a WordPress PHP theme.\n",
    ],
    'pulse' => [
        'dir' => 'cms-style-pack-pulse',
        'zip' => 'cms-style-pack-pulse.zip',
        'readme' => "Pulse\n=====\n\nTeal civic / community pack. Header, After header, Homepage, and After content starter widgets (empty areas only).\n\n1. Edit cms-theme.json + extra.css as needed.\n2. Re-zip with files at the ARCHIVE ROOT.\n3. Import in Appearance → Customize.\n\nNot a WordPress PHP theme.\n",
    ],
    'enterprise' => [
        'dir' => 'cms-style-pack-enterprise',
        'zip' => 'cms-style-pack-enterprise.zip',
        'readme' => "Enterprise\n==========\n\nModern navy / slate professional pack. Header, After header, Homepage, and After content starter widgets (empty areas only).\n\n1. Edit cms-theme.json + extra.css as needed.\n2. Re-zip with files at the ARCHIVE ROOT.\n3. Import in Appearance → Customize.\n\nNot a WordPress PHP theme.\n",
    ],
];

$arg = isset($argv[1]) ? strtolower(trim((string) $argv[1])) : '';
$slugs = $arg === '' ? array_keys($known) : [$arg];

foreach ($slugs as $slug) {
    if ($slug === 'example' || $slug === 'sample' || $slug === 'default') {
        $slug = 'example';
    }
    if (!isset($known[$slug])) {
        fwrite(STDERR, "Unknown pack '{$slug}'. Known: " . implode(', ', array_keys($known)) . "\n");
        exit(1);
    }
    $meta = $known[$slug];
    $srcDir = $root . '/docs/samples/' . $meta['dir'];
    $json = $srcDir . '/cms-theme.json';
    $css = $srcDir . '/extra.css';
    if (!is_file($json)) {
        fwrite(STDERR, "Missing {$json}\n");
        exit(1);
    }

    $targets = [
        $root . '/docs/samples/' . $meta['zip'],
        $root . '/public/assets/theme-packs/' . $meta['zip'],
    ];

    foreach ($targets as $zipPath) {
        $dir = dirname($zipPath);
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            fwrite(STDERR, "Cannot create {$dir}\n");
            exit(1);
        }
        if (is_file($zipPath)) {
            @unlink($zipPath);
        }
        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            fwrite(STDERR, "Cannot write {$zipPath}\n");
            exit(1);
        }
        $zip->addFile($json, 'cms-theme.json');
        if (is_file($css)) {
            $zip->addFile($css, 'extra.css');
        }
        $zip->addFromString('README.txt', $meta['readme']);
        $zip->close();
        fwrite(STDOUT, 'Wrote ' . $zipPath . ' (' . filesize($zipPath) . " bytes)\n");
    }
}

fwrite(STDOUT, "OK\n");
