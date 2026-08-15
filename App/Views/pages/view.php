<?php ob_start(); ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><?= htmlspecialchars($page->title) ?></h2>
    <div>
        <?php if ($page->status === 'published'): ?><?php $publicUrl = \App\Models\Page::isHomepageSlug($page->slug) ? '/' : '/p/' . htmlspecialchars($page->slug); ?><a href="<?= $publicUrl ?>" class="btn btn-outline-secondary" target="_blank" rel="noopener">View public</a><?php endif; ?>
        <?php if (\Core\Auth::can('edit_pages')): ?><a href="<?= admin_url('pages/edit/' . (int)$page->id ) ?>" class="btn btn-primary">Edit</a><?php endif; ?>
        <a href="<?= admin_url('pages') ?>" class="btn btn-outline-secondary">Back</a>
    </div>
</div>
<div class="card mb-3"><div class="card-body">
<p><strong>Slug:</strong> <code><?= htmlspecialchars($page->slug) ?></code> · <strong>Status:</strong> <?= htmlspecialchars($page->status) ?>
<?php if (!empty($page->robots_noindex)): ?> · <span class="badge bg-secondary">noindex</span><?php endif; ?></p>
<?php if (!empty($page->meta_title) || !empty($page->meta_description)): ?>
<p class="mb-1"><strong>SEO title:</strong> <?= htmlspecialchars($page->meta_title ?: '—') ?></p>
<p class="mb-0"><strong>Meta description:</strong> <?= htmlspecialchars($page->meta_description ?: '—') ?></p>
<?php endif; ?>
<?php if (!empty($page->llm_summary)): ?>
<p class="mt-2 mb-0"><strong>LLM summary:</strong> <?= htmlspecialchars($page->llm_summary) ?></p>
<?php endif; ?>
</div></div>
<?php if (\App\Models\Page::hasFeaturedImage($page)): ?>
<div class="card mb-3"><div class="card-body">
<strong>Featured image</strong>
<img src="/serve/media/<?= (int)$page->featured_image_id ?>" alt="" class="public-featured-img mt-2 d-block" style="max-width:320px">
</div></div>
<?php endif; ?>
<div class="card"><div class="card-body">
<div class="cms-preview border rounded p-3"><?= $page->body ?></div>
</div></div>
<?php
$entityType = 'page';
$entityId = (int) $page->id;
$revisions = $revisions ?? [];
require __DIR__ . '/../partials/content_revisions.php';
?>
<?php $content = ob_get_clean(); $pageTitle = $page->title; $currentPage = 'pages'; require __DIR__ . '/../layout/main.php';
