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
Widget::saveAreaWidgets(Widget::AREA_SIDEBAR, []);

assert(Comment::pendingCount() >= 0, 'comment pending count');

$searchCount = Post::publishedCount(null, null, 'the');
assert($searchCount >= 0, 'search count');

$plain = ContentBlocks::plainTextFromEntity((object) [
    'body' => '<p>Fallback</p>',
    'blocks_json' => '[{"type":"paragraph","data":{"text":"Block text"}}]',
]);
assert(str_contains($plain, 'Block text'), 'plain text from blocks');

echo "cms_wp_extended_smoke_test: OK\n";
