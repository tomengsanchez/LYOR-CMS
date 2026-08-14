<?php
use App\Models\Media;
use App\Models\Page;
use App\ContentBlocks;
use App\Permalink;

$publicTitle = ($page->meta_title ?: $page->title) . ' — ' . ($branding->app_name ?? 'Simple CMS');
$bodyHtml = ContentBlocks::renderEntity($page, (string) ($page->body ?? ''));
$publicNavActive = $publicNavActive ?? 'home';
$isHomepage = !empty($isHomepage);
$pageLayout = \App\PublicTheme::normalizeContentLayout($page->content_layout ?? null);
if ($pageLayout !== null) {
    $publicLayout = $pageLayout;
}
$pageBreadcrumbs = $pageBreadcrumbs ?? [];
ob_start();
?>
<?php if (!$isHomepage && !empty($pageBreadcrumbs)): ?>
<nav class="public-breadcrumbs" aria-label="Breadcrumb">
    <a href="/">Home</a>
    <?php foreach ($pageBreadcrumbs as $crumb): ?>
    <span class="public-breadcrumb-sep">/</span>
    <a href="<?= htmlspecialchars(Permalink::urlForPage($crumb)) ?>"><?= htmlspecialchars($crumb->title) ?></a>
    <?php endforeach; ?>
    <span class="public-breadcrumb-sep">/</span>
    <span aria-current="page"><?= htmlspecialchars($page->title) ?></span>
</nav>
<?php endif; ?>
<article class="public-article" aria-labelledby="page-title">
<?php if ($isHomepage): ?>
<div class="public-hero public-hero--home">
    <h1 id="page-title"><?= htmlspecialchars($page->title) ?></h1>
    <?php if (!empty($page->meta_description)): ?>
    <p class="lead"><?= htmlspecialchars($page->meta_description) ?></p>
    <?php endif; ?>
</div>
<div class="public-card">
    <?php if (Page::hasFeaturedImage($page)): ?>
    <figure class="public-featured-figure mb-4">
        <?= Media::responsiveImg((int) $page->featured_image_id, [
            'alt' => (string) ($page->featured_alt_text ?? $page->title),
            'class' => 'public-featured-img',
            'sizes' => '(max-width: 768px) 100vw, min(960px, 100vw)',
            'preferred_width' => 1200,
            'loading' => 'eager',
        ]) ?>
    </figure>
    <?php endif; ?>
    <?php if (!empty($page->updated_at)): ?>
    <p class="text-muted small mb-3"><time datetime="<?= htmlspecialchars($page->updated_at) ?>">Updated <?= htmlspecialchars($page->updated_at) ?></time></p>
    <?php endif; ?>
    <div class="cms-body"><?= $bodyHtml ?></div>
</div>
<?php else: ?>
    <?php if (Page::hasFeaturedImage($page)): ?>
    <figure class="public-featured-figure mb-4">
        <?= Media::responsiveImg((int) $page->featured_image_id, [
            'alt' => (string) ($page->featured_alt_text ?? $page->title),
            'class' => 'public-featured-img',
            'sizes' => '(max-width: 768px) 100vw, min(960px, 100vw)',
            'preferred_width' => 1200,
            'loading' => 'eager',
        ]) ?>
    </figure>
    <?php endif; ?>
    <h1 id="page-title"><?= htmlspecialchars($page->title) ?></h1>
    <?php if (!empty($page->updated_at)): ?>
    <p class="text-muted small mb-3"><time datetime="<?= htmlspecialchars($page->updated_at) ?>">Updated <?= htmlspecialchars($page->updated_at) ?></time></p>
    <?php endif; ?>
    <div class="cms-body"><?= $bodyHtml ?></div>
<?php endif; ?>
</article>
<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
