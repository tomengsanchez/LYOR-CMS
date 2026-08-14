<?php
$publicTitle = 'Not found — ' . ($branding->app_name ?? 'Simple CMS');
$publicNavActive = '';
ob_start();
?>
<div class="public-card text-center">
    <h1 class="h3">Page not found</h1>
    <p class="text-muted mb-3">The page you requested does not exist or is not published.</p>
    <a href="/" class="public-read-more">← Home</a>
    <span class="mx-2 text-muted">·</span>
    <a href="/blog" class="public-read-more">Blog</a>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
