<?php
use App\Models\Page;

$mediaImages = $mediaImages ?? [];
$selectedFeatured = (int) ($page->featured_image_id ?? 0);
$canUploadMedia = \Core\Auth::can('upload_media')
    || \Core\Auth::canAny(['add_posts', 'edit_posts', 'add_pages', 'edit_pages']);
$jsonPreviewUrl = '';
if (!empty($page->slug) && ($page->status ?? '') === 'published') {
    $jsonPreviewUrl = Page::isHomepageSlug($page->slug) ? '/index.json' : '/p/' . rawurlencode($page->slug) . '.json';
}
$blockMediaJson = json_encode(\App\Models\Media::listImagesForPicker(), JSON_UNESCAPED_UNICODE);
$blockTypesJson = json_encode(\App\ContentBlocks::types(), JSON_UNESCAPED_UNICODE);
$blocksInitialJson = json_encode(\App\ContentBlocks::parse($page->blocks_json ?? null), JSON_UNESCAPED_UNICODE);
ob_start();
?>
<link href="/public/assets/css/admin/content-editor.css" rel="stylesheet">
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><?= !empty($isCreate) ? 'Add Page' : 'Edit Page' ?></h2>
    <a href="<?= admin_url('pages') ?>" class="btn btn-outline-secondary">Back</a>
</div>
<div class="card mb-4"><div class="card-header"><h5 class="mb-0">Content</h5></div><div class="card-body">
<?php if (!empty($formError)): ?><div class="alert alert-danger"><?= htmlspecialchars($formError) ?></div><?php endif; ?>
<form method="post" action="<?= !empty($isCreate) ? admin_url('pages/store') : admin_url('pages/update/' . (int)$page->id) ?>" id="pageForm"
    data-media-upload-url="<?= htmlspecialchars(admin_url('media/upload-json')) ?>"
    data-can-upload-media="<?= $canUploadMedia ? '1' : '0' ?>">
<?= \Core\Csrf::field() ?>
<div class="mb-3"><label class="form-label">Title</label><input type="text" name="title" class="form-control" value="<?= htmlspecialchars($page->title) ?>" required id="pageTitle"></div>
<div class="mb-3"><label class="form-label">Slug</label><input type="text" name="slug" class="form-control" value="<?= htmlspecialchars($page->slug ?? '') ?>" placeholder="auto-generated if empty"></div>
<div class="mb-3"><label class="form-label">Status</label><select name="status" class="form-select"><option value="draft" <?= ($page->status ?? '') === 'draft' ? 'selected' : '' ?>>Draft</option><option value="published" <?= ($page->status ?? '') === 'published' ? 'selected' : '' ?>>Published</option></select></div>
<div class="mb-3">
    <label class="form-label">Content width (public)</label>
    <select name="content_layout" class="form-select">
        <?php foreach (\App\PublicTheme::contentLayoutOptions() as $key => $label): ?>
        <option value="<?= htmlspecialchars($key) ?>" <?= (string)($page->content_layout ?? '') === (string)$key ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
        <?php endforeach; ?>
    </select>
    <small class="text-muted d-block mt-1">Override the site theme width for this page only.</small>
</div>
<div class="mb-3">
    <label class="form-label">Parent page</label>
    <select name="parent_id" class="form-select">
        <option value="">— None (top level) —</option>
        <?php foreach (\App\Models\Page::parentOptions(!empty($isCreate) ? null : (int)($page->id ?? 0)) as $parent): ?>
        <option value="<?= (int)$parent->id ?>" <?= (int)($page->parent_id ?? 0) === (int)$parent->id ? 'selected' : '' ?>><?= htmlspecialchars($parent->title) ?></option>
        <?php endforeach; ?>
    </select>
    <small class="text-muted d-block mt-1">WordPress-style hierarchy; breadcrumbs show on child pages.</small>
</div>

<div class="card mb-4"><div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
    <h5 class="mb-0">Visual layout builder</h5>
    <?php if (empty($isCreate) && !empty($page->id)): ?>
    <a href="<?= admin_url('builder/page/' . (int) $page->id) ?>" class="btn btn-primary btn-sm">Edit via Frontend editor</a>
    <?php else: ?>
    <small class="text-muted">Save the page first to open the frontend visual editor.</small>
    <?php endif; ?>
</div><div class="card-body">
    <p class="small text-muted mb-0">Divi-style sections → rows → columns → modules. When a layout is saved, it takes precedence over the block builder and body on the public site.</p>
</div></div>

<div class="card mb-4"><div class="card-header d-flex justify-content-between align-items-center"><h5 class="mb-0">Block builder</h5><small class="text-muted">Optional — used when no visual layout is saved. Upload images here.</small></div><div class="card-body">
<div id="cmsBlockBuilder"
    data-types="<?= htmlspecialchars($blockTypesJson) ?>"
    data-media="<?= htmlspecialchars($blockMediaJson) ?>"
    data-initial="<?= htmlspecialchars($blocksInitialJson) ?>"
    data-can-upload="<?= $canUploadMedia ? '1' : '0' ?>">
    <div class="d-flex flex-wrap gap-2 mb-3">
        <?php foreach (\App\ContentBlocks::types() as $typeKey => $typeLabel): ?>
        <button type="button" class="btn btn-outline-secondary btn-sm" data-add-block="<?= htmlspecialchars($typeKey) ?>">+ <?= htmlspecialchars($typeLabel) ?></button>
        <?php endforeach; ?>
    </div>
    <div id="cmsBlockList"></div>
    <input type="hidden" name="blocks_json" id="blocksJsonInput" value="">
</div>
</div></div>
<div class="mb-3"><label class="form-label">Body (HTML)</label><textarea name="body" class="form-control" rows="12"><?= htmlspecialchars($page->body ?? '') ?></textarea><small class="text-muted">Fallback when no visual layout and no blocks are saved.</small></div>

<div class="card mb-4"><div class="card-header"><h5 class="mb-0">SEO &amp; social sharing</h5></div><div class="card-body">
<div class="mb-3">
    <label class="form-label" for="metaTitle">SEO title</label>
    <input type="text" name="meta_title" id="metaTitle" class="form-control" maxlength="255" value="<?= htmlspecialchars($page->meta_title ?? '') ?>" placeholder="Browser tab &amp; search result title (defaults to page title)">
    <small class="text-muted"><span id="metaTitleCount">0</span>/255 · Keep under ~60 characters for Google.</small>
</div>
<div class="mb-3">
    <label class="form-label" for="metaDescription">Meta description</label>
    <textarea name="meta_description" id="metaDescription" class="form-control" rows="2" maxlength="500" placeholder="Search snippet &amp; social preview text"><?= htmlspecialchars($page->meta_description ?? '') ?></textarea>
    <small class="text-muted"><span id="metaDescriptionCount">0</span>/500 · Aim for 150–160 characters.</small>
</div>
<?php
$featuredLabel = 'Featured / share image';
$featuredHelp = 'Open Graph / Facebook / LinkedIn image. Recommended <strong>1200×630 px</strong>.';
require __DIR__ . '/../partials/featured_image_field.php';
?>
<div class="form-check mb-0">
    <input type="checkbox" class="form-check-input" name="robots_noindex" value="1" id="robotsNoindex" <?= !empty($page->robots_noindex) ? 'checked' : '' ?>>
    <label class="form-check-label" for="robotsNoindex">Hide from search engines (<code>noindex</code>) — excludes page from sitemap</label>
</div>
</div></div>

<div class="card mb-4"><div class="card-header"><h5 class="mb-0">LLM / AI discovery</h5></div><div class="card-body">
<div class="mb-3">
    <label class="form-label" for="llmSummary">Plain-language summary</label>
    <textarea name="llm_summary" id="llmSummary" class="form-control" rows="4" maxlength="2000" placeholder="Concise summary for AI assistants and crawlers (also used in JSON export &amp; Schema.org)"><?= htmlspecialchars($page->llm_summary ?? '') ?></textarea>
    <small class="text-muted"><span id="llmSummaryCount">0</span>/2000 · Machine-readable export: <?php if ($jsonPreviewUrl !== ''): ?><a href="<?= htmlspecialchars($jsonPreviewUrl) ?>" target="_blank" rel="noopener"><code><?= htmlspecialchars($jsonPreviewUrl) ?></code></a><?php else: ?>publish the page to preview<?php endif; ?></small>
</div>
<?php $entity = $page; require __DIR__ . '/../partials/ai_citation_fields.php'; ?>
</div></div>

<button type="submit" class="btn btn-primary">Save</button>
</form></div></div>
<script src="/public/assets/js/content/media-picker.js"></script>
<script src="/public/assets/js/pages/form.js"></script>
<script src="/public/assets/js/content/ai-seo-form.js"></script>
<script src="/public/assets/js/content/blocks.js"></script>
<?php $content = ob_get_clean(); $pageTitle = !empty($isCreate) ? 'Add Page' : 'Edit Page'; $currentPage = 'pages'; require __DIR__ . '/../layout/main.php';
