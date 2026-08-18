<?php
/**
 * Smoke test: CMS pages model after fresh migrations.
 */
require dirname(__DIR__, 2) . '/bootstrap.php';

use App\ContentPassword;
use App\Models\Page;

$page = Page::findBySlug('welcome', true);
assert($page !== null, 'welcome page should exist from seed');
assert($page->status === 'published', 'welcome page should be published');
assert(Page::countsByStatus()['published'] >= 1, 'at least one published page');

$copyId = Page::duplicate((int) $page->id);
assert($copyId > 0, 'page duplicate returns id');
$copy = Page::find($copyId);
assert($copy !== null, 'duplicate page exists');
assert($copy->status === 'draft', 'duplicate is draft');
assert(str_starts_with((string) $copy->title, 'Copy of '), 'duplicate title prefixed');
assert((string) $copy->slug !== (string) $page->slug, 'duplicate slug unique');

$n = Page::bulkSetStatus([$copyId], 'published');
assert($n >= 1, 'bulk publish copy');
$copy = Page::find($copyId);
assert($copy !== null && $copy->status === 'published', 'copy published via bulk');
assert(Page::bulkSetStatus([$copyId], 'draft') >= 1, 'bulk draft copy');
$copy = Page::find($copyId);
assert($copy !== null && $copy->status === 'draft', 'copy drafted via bulk');
$protected = \App\ContentBulk::protectedPageIds();
assert(in_array((int) $page->id, $protected, true), 'welcome/front page is protected');
$skipped = \App\ContentBulk::withoutIds([(int) $page->id, $copyId], $protected);
assert(!in_array((int) $page->id, $skipped, true), 'protected id stripped');
assert(Page::bulkSoftDelete($skipped) >= 1, 'bulk delete copy only');
assert(Page::find($copyId) === null, 'bulk-deleted copy gone');

$col = \Core\Database::getInstance()->query("SHOW COLUMNS FROM cms_pages LIKE 'password_hash'")->fetchAll();
assert(count($col) === 1, 'pages password_hash column');
$secret = 'unique_pw_body_' . uniqid('', true);
$pwId = Page::create([
    'title' => 'Smoke password page ' . uniqid('', true),
    'body' => '<p>' . $secret . '</p>',
    'status' => 'published',
    'content_password' => 'secret99',
]);
assert($pwId > 0, 'password page created');
$pwPage = Page::find($pwId);
assert($pwPage !== null && \App\ContentPassword::has($pwPage), 'page has password hash');
assert(!str_contains(json_encode($pwPage), 'secret99'), 'plaintext password not stored');
assert(\App\ContentPassword::verify($pwPage, 'secret99'), 'password_verify matches');
assert(\App\ContentPassword::isLocked('page', $pwPage), 'visitor sees lock');
$found = Page::searchPublished($secret, 20);
foreach ($found as $row) {
    assert((int) $row->id !== $pwId, 'protected page body excluded from public search');
}
$stub = \App\PublicSeo::jsonDocument($pwPage);
assert(!empty($stub['protected']), 'locked json is stub');
assert(!isset($stub['content_text']), 'locked json has no body');
$dupPw = Page::duplicate($pwId);
$dupRow = Page::find($dupPw);
assert($dupRow !== null && (string) $dupRow->password_hash === (string) $pwPage->password_hash, 'duplicate copies hash');
$apiRow = \App\ContentPassword::withoutHash($pwPage);
assert(!isset($apiRow->password_hash), 'api helper strips hash');
assert(!empty($apiRow->password_protected), 'api helper flags protected');
ContentPassword::persistHash('cms_pages', $pwId, null);
$opened = Page::find($pwId);
assert($opened !== null && !\App\ContentPassword::has($opened), 'password removed');
Page::softDelete($dupPw);
Page::softDelete($pwId);

echo "cms_pages_smoke_test: OK\n";
