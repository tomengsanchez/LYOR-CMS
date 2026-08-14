<?php
$listColumns = $listColumns ?? [];
$listSort = $listSort ?? 'title';
$listOrder = $listOrder ?? 'desc';
$listBaseUrl = $listBaseUrl ?? admin_url('pages');
$baseQuery = '?q=' . urlencode($listSearch ?? '') . '&columns=' . urlencode(implode(',', $listColumns)) . '&per_page=' . (int)($listPagination['per_page'] ?? 15);
ob_start();
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0">Pages</h2>
    <?php if (\Core\Auth::can('add_pages')): ?><a href="<?= admin_url('pages/create') ?>" class="btn btn-primary">Add Page</a><?php endif; ?>
</div>
<?php require __DIR__ . '/../partials/list_toolbar.php'; ?>
<div class="card"><div class="table-responsive"><table class="table table-hover mb-0">
<thead><tr>
<?php foreach ($listColumns as $key): $col = \App\ListConfig::getColumnByKey('pages', $key); if (!$col) continue; ?>
<th><?= htmlspecialchars($col['label']) ?></th>
<?php endforeach; ?><th>Actions</th></tr></thead>
<tbody>
<?php foreach ($pages as $p): ?><tr>
<?php foreach ($listColumns as $key): ?><td><?= htmlspecialchars(\App\ListHelper::getValue($p, $key) ?? '-') ?></td><?php endforeach; ?>
<td>
<a href="<?= admin_url('pages/view/' . (int)$p->id ) ?>" class="btn btn-sm btn-outline-secondary">View</a>
<?php if (\Core\Auth::can('edit_pages')): ?><a href="<?= admin_url('pages/edit/' . (int)$p->id ) ?>" class="btn btn-sm btn-outline-primary">Edit</a><?php endif; ?>
<?php if (\Core\Auth::can('delete_pages')): ?>
<form method="post" action="<?= admin_url('pages/delete/' . (int)$p->id ) ?>" class="d-inline" onsubmit="return confirm('Delete this page?');"><?= \Core\Csrf::field() ?><button type="submit" class="btn btn-sm btn-outline-danger">Delete</button></form>
<?php endif; ?>
</td></tr><?php endforeach; ?>
<?php if (empty($pages)): ?><tr><td colspan="<?= count($listColumns)+1 ?>" class="text-center text-muted py-4">No pages.</td></tr><?php endif; ?>
</tbody></table></div></div>
<?php require __DIR__ . '/../partials/list_pagination.php'; ?>
<?php $content = ob_get_clean(); $pageTitle = 'Pages'; $currentPage = 'pages'; require __DIR__ . '/../layout/main.php';
