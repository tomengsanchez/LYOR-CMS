<?php
/**
 * Smoke: layout templates, device-preview assets, pages/posts write API helpers.
 */
require dirname(__DIR__, 2) . '/bootstrap.php';

use App\LayoutBuilder;
use App\Models\LayoutTemplate;
use App\Models\Page;
use App\Models\Post;
use Core\Auth;
use Core\Database;

$db = Database::getInstance();

assert($db->query("SHOW TABLES LIKE 'cms_layout_templates'")->fetchAll() !== [], 'cms_layout_templates exists');

$editorDir = dirname(__DIR__, 2) . '/public/assets/js/builder';
$editorJs = '';
foreach (['editor.js', 'save.js', 'ns.js'] as $builderFile) {
    $editorJs .= (string) file_get_contents($editorDir . '/' . $builderFile);
}
assert(str_contains($editorJs, 'setDevice'), 'editor setDevice');
assert(str_contains($editorJs, 'saveAsTemplate'), 'editor saveAsTemplate');
assert(str_contains($editorJs, 'data-device'), 'device attribute usage');

$builderCss = file_get_contents(dirname(__DIR__, 2) . '/public/assets/css/admin/builder.css');
assert(str_contains($builderCss, 'data-device="tablet"'), 'tablet preview CSS');
assert(str_contains($builderCss, 'data-device="mobile"'), 'mobile preview CSS');

$starter = LayoutBuilder::starterLayout();
$layoutJson = json_encode($starter, JSON_UNESCAPED_UNICODE);
$name = 'Smoke template ' . bin2hex(random_bytes(3));

// Auth context for created_by / audit (CLI may have no user)
if (!Auth::check()) {
    $admin = $db->query("SELECT id FROM users ORDER BY id ASC LIMIT 1")->fetch(\PDO::FETCH_OBJ);
    if ($admin) {
        Auth::login((int) $admin->id);
    }
}

$tplId = LayoutTemplate::create($name, $layoutJson);
assert($tplId > 0, 'template create');
$row = LayoutTemplate::find($tplId);
assert($row !== null && (string) $row->name === $name, 'template find');
$parsed = LayoutTemplate::layoutArray($row);
assert($parsed !== null && !empty($parsed['sections']), 'template layout parse');
assert(LayoutTemplate::delete($tplId) === true, 'template delete');
assert(LayoutTemplate::find($tplId) === null, 'template gone');

$suffix = bin2hex(random_bytes(3));
$pageId = Page::create([
    'title' => 'API Smoke Page ' . $suffix,
    'body' => '<p>smoke</p>',
    'status' => 'draft',
    'meta_title' => '',
    'meta_description' => '',
    'llm_summary' => '',
    'robots_noindex' => 0,
    'content_layout' => '',
    'parent_id' => null,
]);
assert($pageId > 0, 'page create');
assert(Page::update($pageId, [
    'title' => 'API Smoke Page Updated ' . $suffix,
    'slug' => 'api-smoke-page-' . $suffix,
    'body' => '<p>updated</p>',
    'status' => 'draft',
    'meta_title' => '',
    'meta_description' => '',
    'llm_summary' => '',
    'robots_noindex' => 0,
    'content_layout' => '',
    'parent_id' => null,
]) === true, 'page update');
assert(Page::saveLayoutJson($pageId, $layoutJson) === true, 'page layout save');
assert(Page::softDelete($pageId) === true, 'page soft delete');

$postId = Post::create([
    'title' => 'API Smoke Post ' . $suffix,
    'excerpt' => 'ex',
    'body' => '<p>post</p>',
    'status' => 'draft',
    'meta_title' => '',
    'meta_description' => '',
    'llm_summary' => '',
    'robots_noindex' => 0,
    'content_layout' => '',
    'tags' => 'smoke',
]);
assert($postId > 0, 'post create');
assert(Post::update($postId, [
    'title' => 'API Smoke Post Updated ' . $suffix,
    'slug' => 'api-smoke-post-' . $suffix,
    'excerpt' => 'ex2',
    'body' => '<p>post2</p>',
    'status' => 'draft',
    'meta_title' => '',
    'meta_description' => '',
    'llm_summary' => '',
    'robots_noindex' => 0,
    'content_layout' => '',
    'tags' => 'smoke,api',
]) === true, 'post update');
assert(Post::softDelete($postId) === true, 'post soft delete');

$index = file_get_contents(dirname(__DIR__, 2) . '/public/index.php');
assert(str_contains($index, "patch('/api/pages/{id}'"), 'pages patch route');
assert(str_contains($index, "post('/api/posts'"), 'posts create route');
assert(str_contains($index, "delete('/api/posts/{id}'"), 'posts delete route');
assert(str_contains($index, 'BuilderController@saveTemplate'), 'template save route');

echo "cms_builder_templates_write_api_smoke_test: OK\n";
