<?php
/**
 * Smoke test: media picker payload + category quick-create data shape.
 */
require dirname(__DIR__, 2) . '/bootstrap.php';

use App\Models\Category;
use App\Models\Media;
use Core\Database;

$db = Database::getInstance();

$item = Media::toPickerItem((object) [
    'id' => 42,
    'original_name' => 'hero.png',
    'alt_text' => 'Hero',
    'mime_type' => 'image/png',
    'width' => 1200,
    'height' => 630,
]);
assert($item['id'] === 42, 'picker id');
assert($item['url'] === '/serve/media/42', 'picker admin url');
assert($item['share_url'] !== '', 'picker share url');
assert(str_contains($item['preview'], '/medium'), 'picker preview size');

$list = Media::listImagesForPicker();
assert(is_array($list), 'listImagesForPicker returns array');

$name = 'Quick Cat ' . bin2hex(random_bytes(3));
$id = Category::create(['name' => $name, 'slug' => '', 'description' => '']);
assert($id > 0, 'category create');
$cat = Category::find($id);
assert($cat && $cat->name === $name, 'category name');
$db->prepare('DELETE FROM cms_categories WHERE id = ?')->execute([$id]);

echo "cms_inline_media_picker_smoke_test: OK\n";
