<?php
$listColumns = $listColumns ?? [];
$listBaseUrl = $listBaseUrl ?? admin_url('posts');
$baseQuery = '?q=' . urlencode($listSearch ?? '') . '&columns=' . urlencode(implode(',', $listColumns)) . '&per_page=' . (int)($listPagination['per_page'] ?? 15);
ob_start();
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0">Posts</h2>
    <?php if (\Core\Auth::can('add_posts')): ?><a href="<?= admin_url('posts/create') ?>" class="btn btn-primary">Add Post</a><?php endif; ?>
</div>
<?php require __DIR__ . '/../partials/list_toolbar.php'; ?>
<div class="card"><div class="table-responsive"><table class="table table-hover mb-0">
<thead><tr><?php foreach ($listColumns as $key): $col = \App\ListConfig::getColumnByKey('posts', $key); if (!$col) continue; ?><th><?= htmlspecialchars($col['label']) ?></th><?php endforeach; ?><th>Actions</th></tr></thead>
<tbody>
<?php foreach ($posts as $p): ?><tr>
<?php foreach ($listColumns as $key): ?><td><?= htmlspecialchars(\App\ListHelper::getValue($p, $key) ?? '-') ?></td><?php endforeach; ?>
<td>
<a href="<?= admin_url('posts/view/' . (int)$p->id ) ?>" class="btn btn-sm btn-outline-secondary">View</a>
<?php if (\Core\Auth::can('edit_posts')): ?><a href="<?= admin_url('posts/edit/' . (int)$p->id ) ?>" class="btn btn-sm btn-outline-primary">Edit</a><?php endif; ?>
</td></tr><?php endforeach; ?>
<?php if (empty($posts)): ?><tr><td colspan="<?= count($listColumns)+1 ?>" class="text-center text-muted py-4">No posts.</td></tr><?php endif; ?>
</tbody></table></div></div>
<?php require __DIR__ . '/../partials/list_pagination.php'; ?>
<?php $content = ob_get_clean(); $pageTitle = 'Posts'; $currentPage = 'posts'; require __DIR__ . '/../layout/main.php';
