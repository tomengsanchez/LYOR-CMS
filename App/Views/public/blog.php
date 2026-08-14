<?php
use App\Models\Media;
use App\Models\Post;
use App\Permalink;

$listTitle = $blogListTitle ?? 'Blog';
$listLead = $blogListLead ?? ('Articles and updates from ' . ($branding->app_name ?? 'Simple CMS') . '.');
$blogSearchQuery = $blogSearchQuery ?? '';
$pagination = $blogPagination ?? null;
$publicTitle = $listTitle . ' — ' . ($branding->app_name ?? 'Simple CMS');
$publicNavActive = !empty($isFrontPosts) ? 'home' : 'blog';
$publicUseBlogWidth = true;
ob_start();
?>
<div class="public-hero public-hero--compact">
    <h1><?= htmlspecialchars($listTitle) ?></h1>
    <p class="lead"><?= htmlspecialchars($listLead) ?></p>
    <?php if (!empty($archiveCategory)): ?>
    <p class="mb-0"><a href="/blog" class="public-back">&larr; All posts</a></p>
    <?php elseif (!empty($archiveTag)): ?>
    <p class="mb-0"><a href="/blog" class="public-back">&larr; All posts</a></p>
    <?php endif; ?>
</div>
<form action="/blog" method="get" class="public-blog-search mb-4" role="search">
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
<div class="public-card public-blog-list">
    <?php if (empty($posts)): ?>
    <div class="public-empty"><p>No posts yet. Check back soon.</p></div>
    <?php else: ?>
    <?php foreach ($posts as $post): ?>
    <article class="public-blog-item">
        <?php if (Post::hasFeaturedImage($post)): ?>
        <a href="<?= htmlspecialchars(Permalink::urlForPost($post)) ?>" class="public-blog-thumb-link">
            <?= Media::responsiveImg((int) $post->featured_image_id, [
                'alt' => (string) ($post->featured_alt_text ?? $post->title),
                'class' => 'public-blog-thumb',
                'sizes' => '(max-width: 576px) 100vw, 320px',
                'preferred_width' => 640,
                'loading' => 'lazy',
            ]) ?>
        </a>
        <?php endif; ?>
        <div class="public-blog-item-body">
        <h2><a href="<?= htmlspecialchars(Permalink::urlForPost($post)) ?>"><?= htmlspecialchars($post->title) ?></a></h2>
        <div class="public-post-meta">
            <?php if (!empty($post->category_name) && !empty($post->category_id)): ?>
            <a href="/blog/category/<?= htmlspecialchars($post->category_slug ?? '') ?>" class="public-badge public-badge-link"><?= htmlspecialchars($post->category_name) ?></a>
            <?php elseif (!empty($post->category_name)): ?>
            <span class="public-badge"><?= htmlspecialchars($post->category_name) ?></span>
            <?php endif; ?>
            <?php if (!empty($post->published_at)): ?><time class="public-date" datetime="<?= htmlspecialchars($post->published_at) ?>"><?= htmlspecialchars($post->published_at) ?></time><?php endif; ?>
        </div>
        <?php if (!empty($post->excerpt)): ?><p class="public-excerpt"><?= htmlspecialchars($post->excerpt) ?></p><?php endif; ?>
        <a href="<?= htmlspecialchars(Permalink::urlForPost($post)) ?>" class="public-read-more">Read more →</a>
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
