<?php
use App\Models\Media;
use App\Models\Page;
use App\ContentBlocks;
use App\PublicToc;
use App\Permalink;

$publicTitle = ($page->meta_title ?: $page->title) . ' — ' . ($branding->app_name ?? 'Simple CMS');
$contentLocked = !empty($contentLocked);
if ($contentLocked) {
    $bodyHtml = '';
    $tocNav = '';
} else {
    $bodyHtml = ContentBlocks::renderEntity($page, (string) ($page->body ?? ''));
    $tocPack = PublicToc::enhance($bodyHtml);
    $bodyHtml = $tocPack['html'];
    $tocNav = PublicToc::renderNav($tocPack['items']);
}
$publicNavActive = $publicNavActive ?? 'home';
$isHomepage = !empty($isHomepage);
$pageLayout = \App\PublicTheme::normalizeContentLayout($page->content_layout ?? null);
if ($pageLayout !== null) {
    $publicLayout = $pageLayout;
}
$pageBreadcrumbs = $pageBreadcrumbs ?? [];
$editBarType = 'page';
$editBarId = (int) ($page->id ?? 0);
ob_start();
?>
<?php require __DIR__ . '/partials/edit_bar.php'; ?>
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
<article class="public-article">
    <?php if (Page::hasFeaturedImage($page)): ?>
    <figure class="public-featured-figure mb-4">
        <?= Media::responsiveImg((int) $page->featured_image_id, [
            'alt' => (string) ($page->featured_alt_text ?? $page->title),
            'class' => 'public-featured-img',
            'sizes' => '(max-width: 768px) 100vw, min(1320px, 100vw)',
            'preferred_width' => 1200,
            'loading' => 'eager',
        ]) ?>
    </figure>
    <?php endif; ?>
    <?php if (!$isHomepage && !empty($page->updated_at)): ?>
    <p class="text-muted small mb-3"><time datetime="<?= htmlspecialchars($page->updated_at) ?>">Updated <?= htmlspecialchars($page->updated_at) ?></time></p>
    <?php endif; ?>
    <?php if ($contentLocked): ?>
    <?php $unlockType = 'page'; $unlockEntity = $page; require __DIR__ . '/partials/password_gate.php'; ?>
    <?php else: ?>
    <?php
    $bluf = trim((string) ($page->citation_snippet ?? ''));
    if ($bluf !== ''):
    ?>
    <p class="cms-ai-answer"><?= htmlspecialchars($bluf) ?></p>
    <?php endif; ?>
    <?= $tocNav ?>
    <div class="cms-body"><?= $bodyHtml ?></div>
    <?php endif; ?>
</article>
<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
