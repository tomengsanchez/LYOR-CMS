<?php
/**
 * Smoke test: admin posts list sort columns and filters.
 */
require dirname(__DIR__, 2) . '/bootstrap.php';

use App\ListConfig;
use App\ListHelper;
use App\Models\Post;

$keys = array_column(ListConfig::getColumns('posts'), 'key');
assert(in_array('published_at', $keys, true), 'posts list has published_at');
assert(in_array('created_at', $keys, true), 'posts list has created_at');
$created = ListConfig::getColumnByKey('posts', 'created_at');
assert(!empty($created['sortable']), 'created_at is sortable');

$draft = (object) [
    'id' => 1, 'title' => 'Alpha draft', 'status' => 'draft', 'published_at' => '',
    'created_at' => '2026-03-01 09:00:00', 'category_id' => null, 'author_id' => 2,
    'author_name' => 'Bee', 'is_sticky' => 0,
];
$live = (object) [
    'id' => 2, 'title' => 'Bravo live', 'status' => 'published', 'published_at' => '2026-01-15 10:00:00',
    'created_at' => '2026-01-10 08:00:00', 'category_id' => 4, 'author_id' => 1,
    'author_name' => 'Ada', 'is_sticky' => 1,
];
$scheduled = (object) [
    'id' => 3, 'title' => 'Charlie scheduled', 'status' => 'published', 'published_at' => '2099-06-01 12:00:00',
    'created_at' => '2026-02-01 11:00:00', 'category_id' => 4, 'author_id' => 1,
    'author_name' => 'Ada', 'is_sticky' => 0,
];
$rows = [$draft, $live, $scheduled];

$parsed = Post::adminListFiltersFromRequest([
    'status' => 'not-a-status',
    'category_id' => '0',
    'author_id' => '1',
    'sticky' => '1',
    'date_field' => 'created_at',
    'date_from' => '2026-02-10',
    'date_to' => '2026-02-01',
]);
assert($parsed['status'] === '', 'invalid status ignored');
assert($parsed['category_id'] === '0', 'uncategorized category');
assert($parsed['author_id'] === '1', 'author kept');
assert($parsed['sticky'] === '1', 'sticky kept');
assert($parsed['date_field'] === 'created_at', 'created date field');
assert($parsed['date_from'] === '2026-02-01' && $parsed['date_to'] === '2026-02-10', 'date range swapped');
assert($parsed['date_from'] <= $parsed['date_to'], 'from not after to');
assert(Post::adminListFiltersFromRequest(['date_from' => '2026-02-31'])['date_from'] === '', 'invalid calendar date dropped');

$scheduledOnly = Post::filterAdminList($rows, ['status' => 'scheduled']);
assert(count($scheduledOnly) === 1 && (int) $scheduledOnly[0]->id === 3, 'scheduled filter');
$publishedOnly = Post::filterAdminList($rows, ['status' => 'published']);
assert(count($publishedOnly) === 1 && (int) $publishedOnly[0]->id === 2, 'published excludes scheduled');
$draftOnly = Post::filterAdminList($rows, ['status' => 'draft']);
assert(count($draftOnly) === 1 && (int) $draftOnly[0]->id === 1, 'draft filter');
$noneCat = Post::filterAdminList($rows, ['category_id' => '0']);
assert(count($noneCat) === 1 && (int) $noneCat[0]->id === 1, 'uncategorized filter');
$cat4 = Post::filterAdminList($rows, ['category_id' => '4']);
assert(count($cat4) === 2, 'category filter');
$author1 = Post::filterAdminList($rows, ['author_id' => '1']);
assert(count($author1) === 2, 'author filter');
$pinned = Post::filterAdminList($rows, ['sticky' => '1']);
assert(count($pinned) === 1 && (int) $pinned[0]->id === 2, 'pinned filter');
$unpinned = Post::filterAdminList($rows, ['sticky' => '0']);
assert(count($unpinned) === 2, 'unpinned filter');

$byCreated = Post::filterAdminList($rows, [
    'date_field' => 'created_at',
    'date_from' => '2026-02-01',
    'date_to' => '2026-03-31',
]);
assert(count($byCreated) === 2, 'created date range');
$idsCreated = array_map(static fn ($r) => (int) $r->id, $byCreated);
assert($idsCreated === [1, 3] || $idsCreated === [3, 1], 'created range ids');
$byPublished = Post::filterAdminList($rows, [
    'date_field' => 'published_at',
    'date_from' => '2026-01-01',
    'date_to' => '2026-01-31',
]);
assert(count($byPublished) === 1 && (int) $byPublished[0]->id === 2, 'published date range skips empty and future');

$sortedCreated = ListHelper::sort($rows, 'created_at', 'desc', ['title'], 'posts');
assert((int) $sortedCreated[0]->id === 1, 'sort created_at desc even if column hidden');
$sortedPublished = ListHelper::sort($rows, 'published_at', 'desc', ['title', 'published_at'], 'posts');
assert((int) $sortedPublished[0]->id === 3, 'sort published_at desc');

$authors = Post::authorsFromRows($rows);
assert(array_key_exists(1, $authors) && array_key_exists(2, $authors), 'authors from rows');
assert(array_values($authors) === ['Ada', 'Bee'], 'authors sorted by name');

$_SESSION['list_columns']['posts'] = ['title', 'slug', 'category_name', 'status', 'published_at', 'author_name'];
unset($_GET['columns']);
$resolved = ListConfig::resolveFromRequest('posts');
assert(in_array('created_at', $resolved, true), 'legacy default columns pick up created_at');

$view = file_get_contents(dirname(__DIR__, 2) . '/App/Views/posts/index.php');
assert(str_contains($view, 'id="postListFilters"'), 'filter form in view');
assert(str_contains($view, 'list-sort-link'), 'clickable sort headers');
assert(str_contains($view, "name=\"date_field\""), 'date field filter');
$js = file_get_contents(dirname(__DIR__, 2) . '/public/assets/js/posts/index.js');
assert(is_string($js) && str_contains($js, 'data-filter-autosubmit'), 'external filter js');

echo "cms_posts_list_filter_sort_test: OK\n";
