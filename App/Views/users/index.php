<?php
$listColumns = $listColumns ?? [];
$listSort = $listSort ?? 'username';
$listOrder = $listOrder ?? 'asc';
$listBaseUrl = $listBaseUrl ?? admin_url('users');
$baseQuery = '?q=' . urlencode($listSearch ?? '') . '&columns=' . urlencode(implode(',', $listColumns)) . '&per_page=' . (int)($listPagination['per_page'] ?? 15);
ob_start();
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Users</h2>
    <?php if (\Core\Auth::can('add_users')): ?><a href="<?= admin_url('users/create') ?>" class="btn btn-primary">Add User</a><?php endif; ?>
</div>
<?php if (isset($_GET['error']) && $_GET['error'] === 'self'): ?><div class="alert alert-warning">You cannot delete your own account.</div><?php endif; ?>
<?php
$listCanExport = \Core\Auth::can('export_users');
require __DIR__ . '/../partials/list_toolbar.php';
?>
<div class="card"><div class="table-responsive"><table class="table table-hover mb-0">
<thead><tr><?php foreach ($listColumns as $key): $col = \App\ListConfig::getColumnByKey('users', $key); if (!$col) continue; ?><th><?= htmlspecialchars($col['label']) ?></th><?php endforeach; ?><th>Actions</th></tr></thead>
<tbody>
<?php foreach ($users as $u): ?><tr>
<?php foreach ($listColumns as $key): ?><td><?= htmlspecialchars(\App\ListHelper::getValue($u, $key) ?? '-') ?></td><?php endforeach; ?>
<td>
<?php if (\Core\Auth::can('view_users')): ?><a href="<?= admin_url('users/view/' . (int)$u->id ) ?>" class="btn btn-sm btn-outline-secondary">View</a><?php endif; ?>
<?php if (\Core\Auth::can('edit_users')): ?><a href="<?= admin_url('users/edit/' . (int)$u->id ) ?>" class="btn btn-sm btn-outline-primary">Edit</a><?php endif; ?>
<?php if (\Core\Auth::can('delete_users') && $u->id != \Core\Auth::id()): ?>
<form method="post" action="<?= admin_url('users/delete/' . (int)$u->id ) ?>" class="d-inline" onsubmit="return confirm('Delete?');"><?= \Core\Csrf::field() ?><button type="submit" class="btn btn-sm btn-outline-danger">Delete</button></form>
<?php endif; ?>
</td></tr><?php endforeach; ?>
<?php if (empty($users)): ?><tr><td colspan="<?= count($listColumns)+1 ?>" class="text-muted text-center py-4">No users.</td></tr><?php endif; ?>
</tbody></table></div></div>
<?php require __DIR__ . '/../partials/list_pagination.php'; ?>
<?php $content = ob_get_clean(); $pageTitle = 'Users'; $currentPage = 'users'; require __DIR__ . '/../layout/main.php';
