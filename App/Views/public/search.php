<?php
use App\GoogleSettings;
use App\Models\Post;
use App\Permalink;

$searchQuery = $searchQuery ?? '';
$searchPosts = $searchPosts ?? [];
$searchPages = $searchPages ?? [];
$searchLead = $searchLead ?? 'Search published pages and posts.';
$publicTitle = 'Search — ' . ($branding->app_name ?? 'Simple CMS');
$publicNavActive = '';
ob_start();
?>
<div class="public-hero public-hero--compact">
    <h1>Search</h1>
    <p class="lead"><?= htmlspecialchars((string) $searchLead) ?></p>
</div>
<form action="/search" method="get" class="public-blog-search mb-4" role="search">
    <label class="visually-hidden" for="siteSearchInput">Search this site</label>
    <div class="input-group">
        <input type="search" name="q" id="siteSearchInput" class="form-control" placeholder="Search pages and posts…"
            value="<?= htmlspecialchars((string) $searchQuery) ?>" maxlength="100">
        <button type="submit" class="btn btn-primary">Search</button>
        <?php if ($searchQuery !== ''): ?>
        <a href="/search" class="btn btn-outline-secondary">Clear</a>
        <?php endif; ?>
    </div>
</form>
<?php if ($searchPages !== []): ?>
<section class="public-card mb-4" aria-labelledby="searchPagesHeading">
    <h2 id="searchPagesHeading" class="h5">Pages</h2>
    <ul class="widget-list mb-0">
        <?php foreach ($searchPages as $pg): ?>
        <li><a href="<?= htmlspecialchars(Permalink::urlForPage($pg)) ?>"><?= htmlspecialchars((string) $pg->title) ?></a></li>
        <?php endforeach; ?>
    </ul>
</section>
<?php endif; ?>
<?php if ($searchPosts !== []): ?>
<section class="public-card" aria-labelledby="searchPostsHeading">
    <h2 id="searchPostsHeading" class="h5">Posts</h2>
    <ul class="widget-list mb-0">
        <?php foreach ($searchPosts as $sp): ?>
        <li>
            <a href="<?= htmlspecialchars(Permalink::urlForPost($sp)) ?>"><?= htmlspecialchars((string) $sp->title) ?></a>
            <?php if (!empty($sp->is_sticky)): ?><span class="public-badge ms-1">Pinned</span><?php endif; ?>
        </li>
        <?php endforeach; ?>
    </ul>
</section>
<?php endif; ?>
<?php
$googleCseCx = GoogleSettings::get()->cse_cx ?? '';
if ($googleCseCx !== '' && trim((string) $searchQuery) !== ''):
?>
<section class="public-card mt-4" aria-labelledby="googleSearchHeading">
    <h2 id="googleSearchHeading" class="h5">Google Search</h2>
    <script async src="https://cse.google.com/cse.js?cx=<?= htmlspecialchars((string) $googleCseCx) ?>"></script>
    <div class="gcse-searchresults-only" data-queryParameterName="q"></div>
</section>
<?php endif; ?>
<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
