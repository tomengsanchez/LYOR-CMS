<?php ob_start(); ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0">Tags</h2>
    <a href="<?= admin_url('tags/create') ?>" class="btn btn-primary">Add tag</a>
</div>
<div class="card"><div class="card-body p-0">
<table class="table table-hover mb-0">
<thead><tr><th>Name</th><th>Slug</th><th>Posts</th><th></th></tr></thead>
<tbody>
<?php if (empty($tags)): ?><tr><td colspan="4" class="text-muted p-4">No tags yet. Add tags on post forms (comma-separated) or create them here.</td></tr>
<?php else: foreach ($tags as $t): ?>
<tr>
    <td><?= htmlspecialchars($t->name) ?></td>
    <td><code><?= htmlspecialchars($t->slug) ?></code></td>
    <td><?= (int)($t->post_count ?? 0) ?></td>
    <td class="text-end">
        <a href="/blog/tag/<?= htmlspecialchars($t->slug) ?>" class="btn btn-sm btn-outline-secondary" target="_blank" rel="noopener">View</a>
        <a href="<?= admin_url('tags/edit/' . (int)$t->id) ?>" class="btn btn-sm btn-outline-primary">Edit</a>
        <form method="post" action="<?= admin_url('tags/delete/' . (int)$t->id) ?>" class="d-inline" onsubmit="return confirm('Delete this tag?');"><?= \Core\Csrf::field() ?><button type="submit" class="btn btn-sm btn-outline-danger">Delete</button></form>
    </td>
</tr>
<?php endforeach; endif; ?>
</tbody></table></div></div>
<?php $content = ob_get_clean(); $pageTitle = 'Tags'; $currentPage = 'tags'; require __DIR__ . '/../layout/main.php';
