<?php
$mediaImages = $mediaImages ?? [];
$selectedFeatured = (int) ($post->featured_image_id ?? 0);
$allTags = $allTags ?? [];
$canUploadMedia = \Core\Auth::can('upload_media')
    || \Core\Auth::canAny(['add_posts', 'edit_posts', 'add_pages', 'edit_pages']);
$canQuickCategory = \Core\Auth::can('manage_categories')
    || \Core\Auth::canAny(['add_posts', 'edit_posts']);
$blockMediaJson = json_encode(\App\Models\Media::listImagesForPicker(), JSON_UNESCAPED_UNICODE);
$blockTypesJson = json_encode(\App\ContentBlocks::types(), JSON_UNESCAPED_UNICODE);
$blocksInitialJson = json_encode(\App\ContentBlocks::parse($post->blocks_json ?? null), JSON_UNESCAPED_UNICODE);
ob_start();
?>
<link href="/public/assets/css/admin/content-editor.css" rel="stylesheet">
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><?= !empty($isCreate) ? 'Add Post' : 'Edit Post' ?></h2>
    <a href="<?= admin_url('posts') ?>" class="btn btn-outline-secondary">Back</a>
</div>
<div class="card"><div class="card-body">
<?php if (!empty($formError)): ?><div class="alert alert-danger"><?= htmlspecialchars($formError) ?></div><?php endif; ?>
<form method="post" action="<?= !empty($isCreate) ? admin_url('posts/store') : admin_url('posts/update/' . (int)$post->id) ?>" id="postForm"
    data-media-upload-url="<?= htmlspecialchars(admin_url('media/upload-json')) ?>"
    data-can-upload-media="<?= $canUploadMedia ? '1' : '0' ?>">
<?= \Core\Csrf::field() ?>
<div class="mb-3"><label class="form-label">Title</label><input type="text" name="title" class="form-control" value="<?= htmlspecialchars($post->title) ?>" required></div>
<div class="mb-3"><label class="form-label">Slug</label><input type="text" name="slug" class="form-control" value="<?= htmlspecialchars($post->slug ?? '') ?>"></div>
<div class="mb-3" data-category-quick data-quick-url="<?= htmlspecialchars(admin_url('categories/quick-store')) ?>">
    <label class="form-label">Category</label>
    <select name="category_id" class="form-select"><option value="">— None —</option>
    <?php foreach ($categories as $c): ?><option value="<?= (int)$c->id ?>" <?= (int)($post->category_id ?? 0) === (int)$c->id ? 'selected' : '' ?>><?= htmlspecialchars($c->name) ?></option><?php endforeach; ?>
    </select>
    <?php if ($canQuickCategory): ?>
    <div class="cms-category-quick">
        <input type="text" class="form-control form-control-sm" data-category-name maxlength="100" placeholder="New category name">
        <button type="button" class="btn btn-outline-secondary btn-sm" data-category-add-btn>Add category</button>
        <span class="small text-muted" data-category-status></span>
    </div>
    <?php endif; ?>
</div>
<div class="mb-3"><label class="form-label">Status</label><select name="status" class="form-select"><option value="draft" <?= ($post->status ?? '') === 'draft' ? 'selected' : '' ?>>Draft</option><option value="published" <?= ($post->status ?? '') === 'published' ? 'selected' : '' ?>>Published</option></select>
<small class="text-muted d-block mt-1">A future publish time keeps the post hidden until then (scheduled).</small></div>
<div class="mb-3">
    <label class="form-label" for="postPublishedAt">Publish at</label>
    <input type="datetime-local" name="published_at" id="postPublishedAt" class="form-control" value="<?= htmlspecialchars(!empty($post->published_at) ? \App\UserTime::format((string) $post->published_at, \App\UserTime::KIND_SYSTEM, 'Y-m-d\\TH:i') : '') ?>">
</div>
<div class="mb-3 form-check">
    <input type="hidden" name="is_sticky" value="0">
    <input type="checkbox" class="form-check-input" name="is_sticky" value="1" id="postSticky" <?= !empty($post->is_sticky) ? 'checked' : '' ?>>
    <label class="form-check-label" for="postSticky">Pin to top of the blog (sticky)</label>
</div>
<?php
$entity = $post;
$passwordInputId = 'postContentPassword';
$passwordRemoveId = 'postRemoveContentPassword';
require __DIR__ . '/../partials/content_password_field.php';
?>
<div class="mb-3">
    <label class="form-label">Content width (public)</label>
    <select name="content_layout" class="form-select">
        <?php foreach (\App\PublicTheme::contentLayoutOptions() as $key => $label): ?>
        <option value="<?= htmlspecialchars($key) ?>" <?= (string)($post->content_layout ?? '') === (string)$key ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
        <?php endforeach; ?>
    </select>
    <small class="text-muted d-block mt-1">Override the site theme width for this post only.</small>
</div>
<?php
$featuredLabel = 'Featured image (social sharing)';
$featuredHelp = 'Used on the blog and as <code>og:image</code>. Recommended <strong>1200×630 px</strong>.';
require __DIR__ . '/../partials/featured_image_field.php';
?>
<div class="mb-3"><label class="form-label">Excerpt</label><textarea name="excerpt" class="form-control" rows="2" placeholder="Short summary for blog listing and social description"><?= htmlspecialchars($post->excerpt ?? '') ?></textarea></div>
<div class="mb-3">
    <label class="form-label" for="postTagsInput">Tags</label>
    <input type="text" name="tags" id="postTagsInput" class="form-control" value="<?= htmlspecialchars(\App\Models\Tag::namesForPost((int)($post->id ?? 0))) ?>" placeholder="news, updates, tips">
    <small class="text-muted d-block mt-1">Comma-separated — new names are created automatically. Click a chip to add an existing tag.</small>
    <?php if (!empty($allTags)): ?>
    <div class="cms-tag-chips" data-tag-chips data-target="postTagsInput">
        <?php foreach ($allTags as $tag): ?>
        <button type="button" class="cms-tag-chip" data-tag-name="<?= htmlspecialchars($tag->name) ?>"><?= htmlspecialchars($tag->name) ?></button>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<div class="card mb-4"><div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
    <h5 class="mb-0">Visual layout builder</h5>
    <?php if (empty($isCreate) && !empty($post->id)): ?>
    <a href="<?= admin_url('builder/post/' . (int) $post->id) ?>" class="btn btn-primary btn-sm">Edit via Frontend editor</a>
    <?php else: ?>
    <small class="text-muted">Save the post first to open the frontend visual editor.</small>
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
<div class="mb-3"><label class="form-label">Body (HTML)</label><textarea name="body" class="form-control" rows="12"><?= htmlspecialchars($post->body ?? '') ?></textarea></div>

<div class="card mb-4"><div class="card-header"><h5 class="mb-0">SEO &amp; social sharing</h5></div><div class="card-body">
<div class="mb-3">
    <label class="form-label" for="metaTitle">SEO title</label>
    <input type="text" name="meta_title" id="metaTitle" class="form-control" maxlength="255" value="<?= htmlspecialchars($post->meta_title ?? '') ?>" placeholder="Browser tab &amp; search result title (defaults to post title)">
    <small class="text-muted"><span id="metaTitleCount">0</span>/255 · Keep under ~60 characters for Google.</small>
</div>
<div class="mb-3">
    <label class="form-label" for="metaDescription">Meta description</label>
    <textarea name="meta_description" id="metaDescription" class="form-control" rows="2" maxlength="500" placeholder="Search snippet &amp; social preview text (defaults to excerpt)"><?= htmlspecialchars($post->meta_description ?? '') ?></textarea>
    <small class="text-muted"><span id="metaDescriptionCount">0</span>/500 · Aim for 150–160 characters.</small>
</div>
<div class="form-check mb-0">
    <input type="checkbox" class="form-check-input" name="robots_noindex" value="1" id="robotsNoindex" <?= !empty($post->robots_noindex) ? 'checked' : '' ?>>
    <label class="form-check-label" for="robotsNoindex">Hide from search engines (<code>noindex</code>) — excludes post from sitemap, RSS, and llms.txt</label>
</div>
</div></div>

<div class="card mb-4"><div class="card-header"><h5 class="mb-0">LLM / AI discovery</h5></div><div class="card-body">
<div class="mb-3">
    <label class="form-label" for="llmSummary">Plain-language summary</label>
    <textarea name="llm_summary" id="llmSummary" class="form-control" rows="4" maxlength="2000" placeholder="Concise summary for AI assistants and crawlers (JSON export, Schema.org, llms.txt)"><?= htmlspecialchars($post->llm_summary ?? '') ?></textarea>
    <small class="text-muted"><span id="llmSummaryCount">0</span>/2000 · Machine-readable export: <?php
        $jsonPreviewUrl = !empty($post->slug) ? '/blog/' . htmlspecialchars($post->slug) . '.json' : '';
    ?><?php if ($jsonPreviewUrl !== ''): ?><a href="<?= $jsonPreviewUrl ?>" target="_blank" rel="noopener"><code><?= $jsonPreviewUrl ?></code></a><?php else: ?>publish the post to preview<?php endif; ?></small>
</div>
<?php $entity = $post; require __DIR__ . '/../partials/ai_citation_fields.php'; ?>
</div></div>

<button type="submit" class="btn btn-primary">Save</button>
</form></div></div>
<script src="/public/assets/js/content/media-picker.js"></script>
<script src="/public/assets/js/posts/form.js"></script>
<script src="/public/assets/js/content/ai-seo-form.js"></script>
<script src="/public/assets/js/content/blocks.js"></script>
<?php $content = ob_get_clean(); $pageTitle = !empty($isCreate) ? 'Add Post' : 'Edit Post'; $currentPage = 'posts'; require __DIR__ . '/../layout/main.php';
