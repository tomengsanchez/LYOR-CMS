<?php
$publicTitle = !empty($publicShare['title']) ? $publicShare['title'] : ($branding->app_name ?? 'Simple CMS');
$publicNavActive = 'home';
ob_start();
?>
<div class="public-hero">
    <h1>Welcome</h1>
    <p class="lead">No homepage is published yet. Sign in to the admin panel to create a page with slug <code>welcome</code> or <code>home</code>.</p>
</div>
<div class="public-card text-center">
    <p class="mb-3 text-muted">Get started by adding your first page or blog post.</p>
    <a href="<?= admin_url('login') ?>" class="btn btn-primary">Go to admin</a>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
