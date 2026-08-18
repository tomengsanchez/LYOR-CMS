<?php
use App\Models\Media;
use App\Models\Post;
use App\Permalink;
use App\PublicTheme;

$listTitle = $blogListTitle ?? 'Blog';
$listLead = $blogListLead ?? ('Articles and updates from ' . ($branding->app_name ?? 'Simple CMS') . '.');
$blogSearchQuery = $blogSearchQuery ?? '';
$pagination = $blogPagination ?? null;
$publicTitle = $listTitle . ' — ' . ($branding->app_name ?? 'Simple CMS');
$publicNavActive = !empty($isFrontPosts) ? 'home' : 'blog';
$publicUseBlogWidth = true;
$blogTheme = PublicTheme::getConfig();
$blogStyle = PublicTheme::blogListStyle($blogTheme);
$showSwitcher = PublicTheme::showBlogViewSwitcher($blogTheme);
$showFeat = PublicTheme::showListFeatured($blogTheme);
$showExcerpt = PublicTheme::showBlogExcerpt($blogTheme);
$showMore = PublicTheme::showBlogReadMore($blogTheme);
$showCat = PublicTheme::showBlogCategory($blogTheme);
$showDates = PublicTheme::showPostDates($blogTheme);
$isEditorial = PublicTheme::isEditorialChrome($blogTheme);
$blogKicker = PublicTheme::blogKicker($blogTheme);
$switcherViews = [
    'list' => 'List',
    'grid' => 'Grid',
    'cards' => 'Cards',
    'magazine' => 'Magazine',
    'compact' => 'Compact',
];
ob_start();
?>
<?php if ($isEditorial && $blogKicker !== '' && empty($archiveCategory) && empty($archiveTag) && $blogSearchQuery === ''): ?>
<div class="public-blog-kicker" role="presentation"><?= htmlspecialchars($blogKicker) ?></div>
<?php if (!empty($isFrontPosts)): ?>
<p class="public-blog-kicker-lead lead"><?= htmlspecialchars($listLead) ?></p>
<?php endif; ?>
<?php else: ?>
<div class="public-hero public-hero--compact">
    <h1><?= htmlspecialchars($listTitle) ?></h1>
    <p class="lead"><?= htmlspecialchars($listLead) ?></p>
    <?php if (!empty($archiveCategory)): ?>
    <p class="mb-0"><a href="/blog" class="public-back">&larr; All posts</a></p>
    <?php elseif (!empty($archiveTag)): ?>
    <p class="mb-0"><a href="/blog" class="public-back">&larr; All posts</a></p>
    <?php elseif (!empty($archiveAuthor) || !empty($archiveYear)): ?>
    <p class="mb-0"><a href="/blog" class="public-back">&larr; All posts</a></p>
    <?php endif; ?>
</div>
<?php endif; ?>
<?php if (!empty($archiveCategory) || !empty($archiveTag) || !empty($archiveAuthor) || !empty($archiveYear)): ?>
<p class="mb-3"><a href="/blog" class="public-back">&larr; All posts</a></p>
<?php endif; ?>
<?php if (PublicTheme::showBlogSearch($blogTheme)): ?>
<form action="/blog" method="get" class="public-blog-search mb-3" role="search">
    <label class="visually-hidden" for="blogSearchInput">Search blog</label>
    <div class="input-group">
        <input type="search" name="q" id="blogSearchInput" class="form-control" placeholder="Search posts…"
            value="<?= htmlspecialchars($blogSearchQuery) ?>" maxlength="100">
        <button type="submit" class="btn btn-primary">Search</button>
        <?php if ($blogSearchQuery !== ''): ?>
        <a href="/blog" class="btn btn-outline-secondary">Clear</a>
        <?php endif; ?>
    </div>
</form>
<?php endif; ?>
<?php if ($showSwitcher): ?>
<div class="public-blog-view-switcher mb-3" role="group" aria-label="Blog layout"
    data-blog-view-switcher
    data-default-view="<?= htmlspecialchars($blogStyle) ?>">
    <?php foreach ($switcherViews as $viewKey => $viewLabel): ?>
    <button type="button"
        class="public-blog-view-btn<?= $viewKey === $blogStyle ? ' is-active' : '' ?>"
        data-blog-view="<?= htmlspecialchars($viewKey) ?>"
        aria-pressed="<?= $viewKey === $blogStyle ? 'true' : 'false' ?>">
        <?= htmlspecialchars($viewLabel) ?>
    </button>
    <?php endforeach; ?>
</div>
<?php endif; ?>
<div class="public-card public-blog-list"
    data-blog-list
    data-blog-style="<?= htmlspecialchars($blogStyle) ?>">
    <?php if (empty($posts)): ?>
    <div class="public-empty"><p>No posts yet. Check back soon.</p></div>
    <?php else: ?>
    <?php foreach ($posts as $index => $post): ?>
    <article class="public-blog-item<?= $index === 0 ? ' public-blog-item--first' : '' ?>">
        <?php if ($showFeat && Post::hasFeaturedImage($post)): ?>
        <a href="<?= htmlspecialchars(Permalink::urlForPost($post)) ?>" class="public-blog-thumb-link">
            <?= Media::responsiveImg((int) $post->featured_image_id, [
                'alt' => (string) ($post->featured_alt_text ?? $post->title),
                'class' => 'public-blog-thumb',
                'sizes' => '(max-width: 576px) 100vw, (max-width: 992px) 50vw, 320px',
                'preferred_width' => 640,
                'loading' => 'lazy',
            ]) ?>
        </a>
        <?php endif; ?>
        <div class="public-blog-item-body">
        <h2><a href="<?= htmlspecialchars(Permalink::urlForPost($post)) ?>"><?= htmlspecialchars($post->title) ?></a></h2>
        <div class="public-post-meta">
            <?php if ($showDates && !empty($post->published_at)): ?>
            <time class="public-date" datetime="<?= htmlspecialchars($post->published_at) ?>"><?= htmlspecialchars(PublicTheme::formatPublicDate($post->published_at, $blogTheme)) ?></time>
            <?php endif; ?>
            <?php if ($showCat && !empty($post->category_name) && !empty($post->category_id)): ?>
            <a href="/blog/category/<?= htmlspecialchars($post->category_slug ?? '') ?>" class="public-badge public-badge-link"><?= htmlspecialchars($post->category_name) ?></a>
            <?php elseif ($showCat && !empty($post->category_name)): ?>
            <span class="public-badge"><?= htmlspecialchars($post->category_name) ?></span>
            <?php endif; ?>
        </div>
        <?php if ($showExcerpt && !empty($post->excerpt) && !\App\ContentPassword::isLocked('post', $post)): ?><p class="public-excerpt"><?= htmlspecialchars($post->excerpt) ?></p><?php endif; ?>
        <?php if ($showMore): ?>
        <a href="<?= htmlspecialchars(Permalink::urlForPost($post)) ?>" class="public-read-more">Read more →</a>
        <?php endif; ?>
        </div>
    </article>
    <?php endforeach; ?>
    <?php endif; ?>
</div>
<?php if ($pagination && ($pagination['total_pages'] ?? 1) > 1): ?>
<?php require __DIR__ . '/partials/pagination.php'; ?>
<?php endif; ?>
<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
