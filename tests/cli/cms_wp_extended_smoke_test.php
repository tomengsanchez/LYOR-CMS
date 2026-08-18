<?php
/**
 * Smoke test: Comments, widgets, permalinks, block builder (migration 013).
 */
require dirname(__DIR__, 2) . '/bootstrap.php';

use App\ContentBlocks;
use App\DiscussionSettings;
use App\Permalink;
use App\PermalinkSettings;
use App\Models\Comment;
use App\Models\Post;
use App\Models\Widget;
use Core\Database;

$db = Database::getInstance();

foreach (['cms_comments', 'cms_widgets'] as $table) {
    $db->query('SELECT 1 FROM ' . $table . ' LIMIT 1');
}

$pageCol = $db->query("SHOW COLUMNS FROM cms_pages LIKE 'blocks_json'")->fetchAll();
assert(count($pageCol) === 1, 'cms_pages.blocks_json exists');
$postCol = $db->query("SHOW COLUMNS FROM cms_posts LIKE 'blocks_json'")->fetchAll();
assert(count($postCol) === 1, 'cms_posts.blocks_json exists');

$discussion = DiscussionSettings::get();
assert(isset($discussion->comments_enabled), 'discussion settings');

DiscussionSettings::save([
    'discussion_comments_enabled' => 1,
    'discussion_moderation' => 1,
    'discussion_require_name_email' => 1,
    'discussion_show_sidebar' => 1,
]);
assert(DiscussionSettings::get()->moderation === true, 'discussion save');

PermalinkSettings::save([
    'permalink_page_structure' => PermalinkSettings::PAGE_PLAIN,
    'permalink_post_structure' => PermalinkSettings::POST_DEFAULT,
]);
$perm = PermalinkSettings::get();
assert($perm->page_structure === PermalinkSettings::PAGE_PLAIN, 'permalink page save');

$page = (object) ['slug' => 'about'];
assert(Permalink::urlForPage($page) === '/about', 'plain page url');

$post = (object) ['slug' => 'hello', 'published_at' => '2026-08-01 12:00:00'];
assert(str_contains(Permalink::urlForPost($post), 'hello'), 'post url');

PermalinkSettings::save([
    'permalink_page_structure' => PermalinkSettings::PAGE_DEFAULT,
    'permalink_post_structure' => PermalinkSettings::POST_DEFAULT,
]);

$blocks = ContentBlocks::parse('[{"type":"heading","data":{"text":"Hi","level":2}}]');
assert(count($blocks) === 1, 'blocks parse');
$html = ContentBlocks::render($blocks);
assert(str_contains($html, 'cms-block-heading'), 'blocks render');

Widget::saveAreaWidgets(Widget::AREA_SIDEBAR, [
    ['widget_type' => 'search', 'title' => 'Search', 'config' => [], 'is_enabled' => true],
]);
assert(Widget::areaHasWidgets(Widget::AREA_SIDEBAR), 'widget area');
    $rendered = Widget::renderArea(Widget::AREA_SIDEBAR);
assert(str_contains($rendered, 'widget-search'), 'widget render');

assert(isset(Widget::areas()[Widget::AREA_HEADER]), 'header widget area');
assert(isset(Widget::areas()[Widget::AREA_HOME]), 'home widget area');
assert(isset(Widget::types()['featured_posts']), 'featured posts type');
assert(isset(Widget::types()['cta']), 'cta widget type');
assert(isset(Widget::types()['newsletter']), 'newsletter widget type');

$ctaBad = Widget::sanitizeConfig('cta', [
    'headline' => 'Go',
    'text' => 'Hi',
    'label' => 'Click',
    'url' => 'javascript:alert(1)',
]);
assert(($ctaBad['url'] ?? 'x') === '', 'cta drops javascript url');
$ctaOk = Widget::sanitizeConfig('cta', ['url' => '/blog', 'label' => 'Blog', 'headline' => 'H', 'text' => 'T']);
assert(($ctaOk['url'] ?? '') === '/blog', 'cta keeps path url');

Widget::saveAreaWidgets(Widget::AREA_AFTER_HEADER, []);
$filled = Widget::installStarterIfEmpty([
    'after_header' => [[
        'widget_type' => 'cta',
        'title' => '',
        'is_enabled' => true,
        'config' => ['headline' => 'Smoke CTA', 'text' => '', 'label' => 'Go', 'url' => '/blog'],
    ]],
    'sidebar' => [[
        'widget_type' => 'search',
        'title' => 'Should skip',
        'is_enabled' => true,
        'config' => [],
    ]],
]);
assert($filled === 1, 'starter fills only empty areas');
$ctaHtml = Widget::renderArea(Widget::AREA_AFTER_HEADER);
assert(str_contains($ctaHtml, 'Smoke CTA'), 'cta starter rendered');
assert(str_contains(Widget::renderArea(Widget::AREA_SIDEBAR), 'Search'), 'occupied sidebar title kept');
assert(!str_contains(Widget::renderArea(Widget::AREA_SIDEBAR), 'Should skip'), 'occupied sidebar not overwritten');
Widget::saveAreaWidgets(Widget::AREA_AFTER_HEADER, []);
Widget::saveAreaWidgets(Widget::AREA_SIDEBAR, []);

assert(isset(Widget::types()['archives']), 'archives widget type');
$arch = Widget::sanitizeConfig('archives', ['count' => 99]);
assert((int) $arch['count'] === 24, 'archives count clamped');

$toc = \App\PublicToc::enhance('<h2>First</h2><p>x</p><h3>Second</h3>');
assert(count($toc['items']) === 2, 'toc two headings');
assert(str_contains($toc['html'], 'id='), 'toc assigns ids');
assert(str_contains(\App\PublicToc::renderNav($toc['items']), 'On this page'), 'toc nav');
$tocSkip = \App\PublicToc::enhance('<h2>Only one</h2>');
assert($tocSkip['items'] === [], 'toc skips single heading');

$db = Database::getInstance();
$col = $db->query("SHOW COLUMNS FROM cms_posts LIKE 'is_sticky'")->fetchAll();
assert(count($col) === 1, 'is_sticky column exists');

$future = date('Y-m-d H:i:s', time() + 86400);
$sid = Post::create([
    'title' => 'Smoke scheduled ' . uniqid('', true),
    'body' => '<p>Scheduled body</p>',
    'status' => 'published',
    'published_at' => $future,
]);
assert($sid > 0, 'scheduled post created');
$scheduled = Post::find($sid);
assert($scheduled !== null && Post::isScheduled($scheduled), 'post is scheduled');
assert(!Post::isLive($scheduled), 'scheduled is not live');
assert(Post::findBySlug((string) $scheduled->slug, true) === null, 'scheduled slug hidden from public find');
Post::softDelete($sid);

$tid = Post::create([
    'title' => 'Smoke sticky ' . uniqid('', true),
    'body' => '<h2>Alpha</h2><h2>Beta</h2><p>Words words words.</p>',
    'status' => 'published',
    'is_sticky' => true,
]);
assert($tid > 0, 'sticky post created');
$sticky = Post::find($tid);
assert(!empty($sticky->is_sticky), 'sticky flag saved');
$list = Post::publishedList(1, 0);
assert($list !== [] && !empty($list[0]->is_sticky), 'sticky listed first');
assert(is_array(Post::archiveMonths(12)), 'archive months');
Post::softDelete($tid);

$ba = Post::create([
    'title' => 'Smoke bulk a ' . uniqid('', true),
    'body' => '<p>A</p>',
    'status' => 'draft',
]);
$bb = Post::create([
    'title' => 'Smoke bulk b ' . uniqid('', true),
    'body' => '<p>B</p>',
    'status' => 'draft',
]);
assert($ba > 0 && $bb > 0, 'bulk posts created');
assert(Post::bulkSetStatus([$ba, $bb], 'published') >= 2, 'bulk publish posts');
assert(Post::bulkSetSticky([$ba], true) >= 1, 'bulk pin');
$pinned = Post::find($ba);
assert($pinned !== null && !empty($pinned->is_sticky), 'bulk pin saved');
assert(Post::bulkSoftDelete([$ba, $bb]) >= 2, 'bulk delete posts');

$colPw = $db->query("SHOW COLUMNS FROM cms_posts LIKE 'password_hash'")->fetchAll();
assert(count($colPw) === 1, 'posts password_hash column');
$secretPost = 'unique_post_pw_' . uniqid('', true);
$pwid = Post::create([
    'title' => 'Smoke password post ' . uniqid('', true),
    'excerpt' => 'Hidden excerpt',
    'body' => '<p>' . $secretPost . '</p>',
    'status' => 'published',
    'content_password' => 'secret99',
]);
assert($pwid > 0, 'password post created');
$pwPost = Post::find($pwid);
assert($pwPost !== null && \App\ContentPassword::has($pwPost), 'post has password hash');
assert(\App\ContentPassword::verify($pwPost, 'secret99'), 'post password_verify');
assert(Post::publishedCount(null, null, $secretPost) === 0, 'protected post body not in search');
$doc = \App\PublicSeo::postJsonDocument($pwPost);
assert(!empty($doc['protected']), 'locked post json stub');
assert(!isset($doc['content_text']), 'locked post json no body');
Post::softDelete($pwid);

assert(Comment::pendingCount() >= 0, 'comment pending count');

$searchCount = Post::publishedCount(null, null, 'the');
assert($searchCount >= 0, 'search count');

$pubPosts = Post::publishedList(2, 0);
if ($pubPosts !== []) {
    $mins = Post::readingMinutes($pubPosts[0]);
    assert($mins >= 0, 'reading minutes');
    foreach (Post::related($pubPosts[0], 3) as $rel) {
        assert((int) $rel->id !== (int) $pubPosts[0]->id, 'related excludes current post');
    }
}

$plain = ContentBlocks::plainTextFromEntity((object) [
    'body' => '<p>Fallback</p>',
    'blocks_json' => '[{"type":"paragraph","data":{"text":"Block text"}}]',
]);
assert(str_contains($plain, 'Block text'), 'plain text from blocks');

echo "cms_wp_extended_smoke_test: OK\n";
