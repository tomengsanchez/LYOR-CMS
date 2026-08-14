<?php
/**
 * Smoke test: WordPress-like features (reading, menus, tags, page hierarchy).
 */
require dirname(__DIR__, 2) . '/bootstrap.php';

use App\ReadingSettings;
use App\Models\NavMenu;
use App\Models\Page;
use App\Models\Tag;
use App\Models\Post;
use Core\Database;

$db = Database::getInstance();

foreach (['cms_tags', 'cms_post_tags', 'cms_menus', 'cms_menu_items'] as $table) {
    $db->query('SELECT 1 FROM ' . $table . ' LIMIT 1');
}

$cols = $db->query("SHOW COLUMNS FROM cms_pages LIKE 'parent_id'")->fetchAll();
assert(count($cols) === 1, 'cms_pages.parent_id exists');

$reading = ReadingSettings::get();
assert(isset($reading->show_on_front), 'reading settings');
assert($reading->posts_per_page >= 1, 'posts per page');

ReadingSettings::save([
    'reading_show_on_front' => ReadingSettings::FRONT_POSTS,
    'reading_page_on_front' => 0,
    'reading_posts_per_page' => 5,
]);
$loaded = ReadingSettings::get();
assert($loaded->show_on_front === ReadingSettings::FRONT_POSTS, 'reading save');
assert($loaded->posts_per_page === 5, 'posts per page save');

ReadingSettings::save([
    'reading_show_on_front' => ReadingSettings::FRONT_PAGE,
    'reading_page_on_front' => 0,
    'reading_posts_per_page' => 10,
]);

$items = NavMenu::itemsForLocation(NavMenu::LOCATION_PRIMARY);
assert(count($items) >= 2, 'primary menu items');

$tagId = Tag::findOrCreateByName('SmokeTestTag');
assert($tagId > 0, 'tag created');
Tag::syncPostTags(0, 'ignored'); // no-op on 0

assert(Page::normalizeParentId(null) === null, 'parent null');
assert(Tag::parseNames('a, b, a')[0] === 'a', 'tag parse');

echo "cms_wp_features_smoke_test: OK\n";
