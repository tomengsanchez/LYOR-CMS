<?php
/**
 * Smoke test: responsive media sizes (WordPress-style).
 */
require dirname(__DIR__, 2) . '/bootstrap.php';

use App\MediaImageSizes;
use App\Models\Media;
use Core\Database;

$db = Database::getInstance();
$table = $db->query("SHOW TABLES LIKE 'cms_media_sizes'")->fetchAll();
assert(count($table) === 1, 'cms_media_sizes table exists');

$sizes = MediaImageSizes::registered();
assert(isset($sizes['thumbnail'], $sizes['medium'], $sizes['large']), 'core sizes registered');

$dir = dirname(__DIR__, 2) . '/public/uploads/media';
if (!is_dir($dir)) {
    mkdir($dir, 0755, true);
}

$filename = 'test-responsive-' . bin2hex(random_bytes(4)) . '.png';
$path = $dir . '/' . $filename;
$img = imagecreatetruecolor(1200, 800);
$bg = imagecolorallocate($img, 40, 120, 200);
imagefilledrectangle($img, 0, 0, 1199, 799, $bg);
imagepng($img, $path);
imagedestroy($img);

$stmt = $db->prepare('
    INSERT INTO cms_media (filename, original_name, file_path, mime_type, file_size, width, height, alt_text, uploaded_by)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, NULL)
');
$stmt->execute([
    $filename,
    'responsive-test.png',
    'media/' . $filename,
    'image/png',
    (int) filesize($path),
    1200,
    800,
    'test',
]);
$id = (int) $db->lastInsertId();

$created = MediaImageSizes::generateForMedia($id);
assert($created >= 3, 'generated multiple sizes, got ' . $created);

$rows = MediaImageSizes::listForMedia($id);
assert(count($rows) === $created, 'DB rows match generated count');

$thumb = MediaImageSizes::findSize($id, 'thumbnail');
assert($thumb !== null, 'thumbnail row exists');
assert((int) $thumb->width === 150 && (int) $thumb->height === 150, 'thumbnail is 150×150 crop');
assert(is_file(MediaImageSizes::absolutePathForSize($thumb)), 'thumbnail file on disk');

$medium = MediaImageSizes::findSize($id, 'medium');
assert($medium !== null, 'medium row exists');
assert((int) $medium->width === 300, 'medium width 300');

$media = Media::find($id);
assert($media !== null, 'media find');
$srcset = MediaImageSizes::srcsetAttribute($media, true);
assert(str_contains($srcset, ' 300w'), 'srcset has 300w');
assert(str_contains($srcset, '/share/media/' . $id), 'srcset uses share URLs');

$html = Media::responsiveImg($id, ['alt' => 'demo', 'preferred_width' => 640]);
assert(str_contains($html, 'srcset='), 'responsive img has srcset');
assert(str_contains($html, 'sizes='), 'responsive img has sizes');

$resolved = Media::resolveServePath($media, 'medium');
assert($resolved !== null && is_file($resolved['path']), 'resolveServePath medium');
$full = Media::resolveServePath($media, 'full');
assert($full !== null && $full['path'] === Media::absolutePath($media), 'full is original');

MediaImageSizes::deleteForMedia($id);
assert(MediaImageSizes::listForMedia($id) === [], 'sizes deleted from DB');
assert(!is_file(MediaImageSizes::absolutePathForSize($thumb)), 'thumbnail file removed');

@unlink($path);
$db->prepare('DELETE FROM cms_media WHERE id = ?')->execute([$id]);

echo "cms_media_responsive_smoke_test: OK\n";
