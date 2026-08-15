<?php
ob_start();
$q = $q ?? '';
$pages = $pages ?? [];
$posts = $posts ?? [];
$media = $media ?? [];
?>
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <h2>Content library</h2>
</div>
<form method="get" action="<?= admin_url('search') ?>" class="mb-4" id="cmsContentSearchForm">
    <div class="input-group" style="max-width: 520px">
        <input type="search" name="q" class="form-control" value="<?= htmlspecialchars($q) ?>" placeholder="Search pages, posts, media…" minlength="2" required autofocus>
        <button type="submit" class="btn btn-primary">Search</button>
    </div>
    <small class="text-muted">Matches titles, slugs, SEO fields, tags, and media filenames (min 2 characters).</small>
</form>

<?php if ($q !== '' && mb_strlen($q) < 2): ?>
<div class="alert alert-warning">Enter at least 2 characters.</div>
<?php elseif ($q !== ''): ?>

<?php if (\Core\Auth::can('view_pages')): ?>
<div class="card mb-3"><div class="card-header"><h5 class="mb-0">Pages (<?= count($pages) ?>)</h5></div>
<ul class="list-group list-group-flush">
<?php if (!$pages): ?><li class="list-group-item text-muted">No pages matched.</li>
<?php else: foreach ($pages as $p): ?>
<li class="list-group-item d-flex justify-content-between align-items-center">
    <div>
        <a href="<?= admin_url('pages/view/' . (int)$p->id) ?>"><?= htmlspecialchars($p->title) ?></a>
        <span class="text-muted small"> · <?= htmlspecialchars($p->slug) ?> · <?= htmlspecialchars($p->status) ?></span>
    </div>
    <?php if (\Core\Auth::can('edit_pages')): ?><a class="btn btn-sm btn-outline-secondary" href="<?= admin_url('pages/edit/' . (int)$p->id) ?>">Edit</a><?php endif; ?>
</li>
<?php endforeach; endif; ?>
</ul></div>
<?php endif; ?>

<?php if (\Core\Auth::can('view_posts')): ?>
<div class="card mb-3"><div class="card-header"><h5 class="mb-0">Posts (<?= count($posts) ?>)</h5></div>
<ul class="list-group list-group-flush">
<?php if (!$posts): ?><li class="list-group-item text-muted">No posts matched.</li>
<?php else: foreach ($posts as $p): ?>
<li class="list-group-item d-flex justify-content-between align-items-center">
    <div>
        <a href="<?= admin_url('posts/view/' . (int)$p->id) ?>"><?= htmlspecialchars($p->title) ?></a>
        <span class="text-muted small"> · <?= htmlspecialchars($p->slug) ?> · <?= htmlspecialchars($p->status) ?><?php if (!empty($p->category_name)): ?> · <?= htmlspecialchars($p->category_name) ?><?php endif; ?></span>
    </div>
    <?php if (\Core\Auth::can('edit_posts')): ?><a class="btn btn-sm btn-outline-secondary" href="<?= admin_url('posts/edit/' . (int)$p->id) ?>">Edit</a><?php endif; ?>
</li>
<?php endforeach; endif; ?>
</ul></div>
<?php endif; ?>

<?php if (\Core\Auth::can('view_media')): ?>
<div class="card mb-3"><div class="card-header"><h5 class="mb-0">Media (<?= count($media) ?>)</h5></div>
<ul class="list-group list-group-flush">
<?php if (!$media): ?><li class="list-group-item text-muted">No media matched.</li>
<?php else: foreach ($media as $m): ?>
<li class="list-group-item d-flex justify-content-between align-items-center">
    <div>
        <a href="<?= admin_url('media') ?>"><?= htmlspecialchars($m->original_name) ?></a>
        <span class="text-muted small"> · <?= htmlspecialchars($m->mime_type ?? '') ?><?php if (!empty($m->alt_text)): ?> · <?= htmlspecialchars($m->alt_text) ?><?php endif; ?></span>
    </div>
    <a class="btn btn-sm btn-outline-secondary" href="/serve/media/<?= (int)$m->id ?>" target="_blank" rel="noopener">Open</a>
</li>
<?php endforeach; endif; ?>
</ul></div>
<?php endif; ?>

<?php endif; ?>
<?php
$content = ob_get_clean();
$pageTitle = 'Content library';
$currentPage = 'search';
require __DIR__ . '/../layout/main.php';
