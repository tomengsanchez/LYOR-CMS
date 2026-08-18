<?php
use App\Models\Post;
use App\Permalink;

$publicTitle = 'Not found — ' . ($branding->app_name ?? 'Simple CMS');
$publicNavActive = '';
$notFoundMessage = $message ?? 'The page you requested does not exist or is not published.';
$suggestedPosts = Post::publishedList(5, 0);
ob_start();
?>
<div class="public-card public-not-found">
    <h1 class="h3">Page not found</h1>
    <p class="text-muted mb-3"><?= htmlspecialchars((string) $notFoundMessage) ?></p>
    <form action="/search" method="get" class="public-not-found-search mb-3" role="search">
        <label class="visually-hidden" for="nfSearch">Search this site</label>
        <div class="d-flex gap-2 flex-wrap">
            <input id="nfSearch" type="search" name="q" class="form-control" placeholder="Search pages and posts…" aria-label="Search this site">
            <button type="submit" class="btn btn-primary">Search</button>
        </div>
    </form>
    <p class="mb-3">
        <a href="/" class="public-read-more">← Home</a>
        <span class="mx-2 text-muted">·</span>
        <a href="/blog" class="public-read-more">Blog</a>
    </p>
    <?php if ($suggestedPosts !== []): ?>
    <h2 class="h6">Latest posts</h2>
    <ul class="widget-list mb-0">
        <?php foreach ($suggestedPosts as $sp): ?>
        <li><a href="<?= htmlspecialchars(Permalink::urlForPost($sp)) ?>"><?= htmlspecialchars($sp->title) ?></a></li>
        <?php endforeach; ?>
    </ul>
    <?php endif; ?>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
