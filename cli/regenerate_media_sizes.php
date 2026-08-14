#!/usr/bin/env php
<?php
/**
 * Regenerate WordPress-style intermediate sizes for existing media.
 *
 * Usage:
 *   php cli/regenerate_media_sizes.php
 *   php cli/regenerate_media_sizes.php --id=12
 */
$isCli = php_sapi_name() === 'cli';
if (!$isCli) {
    die('This script must be run from the command line.');
}

require_once dirname(__DIR__) . '/bootstrap.php';

use App\MediaImageSizes;
use App\Models\Media;
use Core\Database;

$onlyId = null;
foreach ($argv as $arg) {
    if (str_starts_with($arg, '--id=')) {
        $onlyId = (int) substr($arg, 5);
    }
}

$db = Database::getInstance();
if ($onlyId) {
    $stmt = $db->prepare("
        SELECT id FROM cms_media
        WHERE id = ? AND deleted_at IS NULL
          AND mime_type IN ('image/jpeg', 'image/png', 'image/webp')
    ");
    $stmt->execute([$onlyId]);
    $ids = array_map('intval', $stmt->fetchAll(\PDO::FETCH_COLUMN));
} else {
    $ids = array_map('intval', $db->query("
        SELECT id FROM cms_media
        WHERE deleted_at IS NULL
          AND mime_type IN ('image/jpeg', 'image/png', 'image/webp')
        ORDER BY id
    ")->fetchAll(\PDO::FETCH_COLUMN));
}

if ($ids === []) {
    echo "No resizable images found.\n";
    exit(0);
}

$totalVariants = 0;
foreach ($ids as $id) {
    $n = MediaImageSizes::generateForMedia($id);
    $media = Media::find($id);
    $label = $media ? $media->original_name : ('#' . $id);
    echo "Media #{$id} ({$label}): {$n} size(s)\n";
    $totalVariants += $n;
}

echo 'Done. Regenerated ' . count($ids) . ' image(s), ' . $totalVariants . " variant file(s).\n";
