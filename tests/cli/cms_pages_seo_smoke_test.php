<?php
/**
 * Smoke test: page SEO columns and PublicSeo helpers.
 */
require dirname(__DIR__, 2) . '/bootstrap.php';

use App\Models\Page;
use App\PublicSeo;
use Core\Database;

$db = Database::getInstance();
foreach (['featured_image_id', 'llm_summary', 'robots_noindex'] as $col) {
    $rows = $db->query('SHOW COLUMNS FROM cms_pages LIKE ' . $db->quote($col))->fetchAll();
    assert(count($rows) === 1, "cms_pages.{$col} should exist");
}

$page = Page::findBySlug('welcome', true);
assert($page !== null, 'welcome page exists');

$ctx = PublicSeo::pageContext($page, null, true);
assert(!empty($ctx['share']['title']), 'page share title');
assert(!empty($ctx['json_url']), 'json url');
assert(isset($ctx['json_ld']['@type']), 'json-ld type');

$doc = PublicSeo::jsonDocument($page, null, true);
assert($doc['type'] === 'page', 'json document type');
assert(isset($doc['content_text']), 'json content_text');

echo "cms_pages_seo_smoke_test: OK\n";
