<?php
/**
 * Smoke test: CMS pages model after fresh migrations.
 */
require dirname(__DIR__, 2) . '/bootstrap.php';

use App\Models\Page;

$page = Page::findBySlug('welcome', true);
assert($page !== null, 'welcome page should exist from seed');
assert($page->status === 'published', 'welcome page should be published');
assert(Page::countsByStatus()['published'] >= 1, 'at least one published page');

echo "cms_pages_smoke_test: OK\n";
