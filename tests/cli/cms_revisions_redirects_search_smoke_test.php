<?php
/**
 * Smoke: content revisions, redirects normalize, content search query shape.
 */
require dirname(__DIR__, 2) . '/bootstrap.php';

use App\ContentRevision;
use App\ContentSearch;
use App\Models\Page;
use App\Models\Redirect;
use Core\Database;

$db = Database::getInstance();

assert($db->query("SHOW TABLES LIKE 'cms_content_revisions'")->fetchAll() !== [], 'cms_content_revisions exists');
assert($db->query("SHOW TABLES LIKE 'cms_redirects'")->fetchAll() !== [], 'cms_redirects exists');

assert(Redirect::normalizePath('old') === '/old', 'normalize relative');
assert(Redirect::normalizePath('/old/') === '/old', 'strip trailing slash');
assert(Redirect::isProtectedSource('/admin/pages') === true, 'protect admin');
assert(Redirect::isProtectedSource('/blog/hello') === false, 'allow blog');

$snap = ContentRevision::snapshotFromPage((object) [
    'id' => 1,
    'title' => 'T',
    'slug' => 't',
    'body' => '<p>x</p>',
    'blocks_json' => null,
    'layout_json' => null,
    'status' => 'draft',
    'meta_title' => '',
    'meta_description' => '',
    'featured_image_id' => null,
    'llm_summary' => '',
    'robots_noindex' => 0,
    'content_layout' => '',
    'parent_id' => null,
]);
assert($snap['title'] === 'T', 'page snapshot title');

$empty = ContentSearch::query('a');
assert($empty['pages'] === [] && $empty['q'] === 'a', 'short query empty');

$page = Page::findBySlug('welcome') ?: Page::findBySlug('home');
if ($page) {
    $before = ContentRevision::listFor('page', (int) $page->id);
    $ok = Page::update((int) $page->id, [
        'title' => $page->title,
        'slug' => $page->slug,
        'body' => $page->body,
        'blocks_json' => $page->blocks_json,
        'status' => $page->status,
        'meta_title' => $page->meta_title ?? '',
        'meta_description' => $page->meta_description ?? '',
        'featured_image_id' => $page->featured_image_id ?? null,
        'llm_summary' => $page->llm_summary ?? '',
        'robots_noindex' => !empty($page->robots_noindex),
        'content_layout' => $page->content_layout ?? '',
        'parent_id' => $page->parent_id ?? null,
    ]);
    assert($ok === true, 'page update ok');
    $after = ContentRevision::listFor('page', (int) $page->id);
    assert(count($after) >= count($before) + 1, 'revision recorded on update');
}

$from = '/smoke-old-' . bin2hex(random_bytes(3));
$redirId = Redirect::create([
    'from_path' => $from,
    'to_url' => '/smoke-new',
    'status_code' => 301,
    'is_active' => 1,
    'note' => 'smoke',
]);
assert($redirId > 0, 'redirect create');
assert(Redirect::findActiveByPath($from) !== null, 'redirect findable');
assert(Redirect::delete($redirId) === true, 'redirect delete');

echo "cms_revisions_redirects_search_smoke_test: OK\n";
