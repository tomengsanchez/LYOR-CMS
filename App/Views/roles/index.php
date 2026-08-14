<?php
use App\Capabilities;
$listColumns = $listColumns ?? [];
$listSort = $listSort ?? ($listColumns[0] ?? '');
$listOrder = $listOrder ?? 'asc';
$listBaseUrl = $listBaseUrl ?? admin_url('users/roles');
$baseQuery = '?q=' . urlencode($listSearch ?? '') . '&columns=' . urlencode(implode(',', $listColumns)) . '&per_page=' . (int)($listPagination['per_page'] ?? 15);
ob_start();
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>User Roles &amp; Capabilities</h2>
    <?php if (\Core\Auth::can('add_roles')): ?>
    <a href="<?= admin_url('users/roles/create') ?>" class="btn btn-primary">Add Role</a>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/../partials/list_toolbar.php'; ?>
<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <?php foreach ($listColumns as $key): $col = \App\ListConfig::getColumnByKey('roles', $key); if (!$col) continue; ?>
                    <th>
                        <?php if (!empty($col['sortable'])): ?><a href="<?= htmlspecialchars($listBaseUrl . $baseQuery . '&sort=' . urlencode($key) . '&order=' . (($listSort === $key && $listOrder === 'asc') ? 'desc' : 'asc')) ?>" class="text-decoration-none"><?php endif; ?>
                        <?= htmlspecialchars($col['label']) ?>
                        <?php if ($listSort === $key): ?><span class="ms-1"><?= $listOrder === 'asc' ? '↑' : '↓' ?></span><?php endif; ?>
                        <?php if (!empty($col['sortable'])): ?></a><?php endif; ?>
                    </th>
                    <?php endforeach; ?>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($roles as $r): ?>
                <tr>
                    <?php foreach ($listColumns as $key): ?>
                    <td><?php
                        if ($key === 'capabilities'):
                            if (!empty($r->capabilities)) { foreach ($r->capabilities as $c) { echo '<span class="badge bg-secondary me-1">' . htmlspecialchars(Capabilities::getLabel($c)) . '</span>'; } }
                            else { echo '<span class="text-muted">None</span>'; }
                        else:
                            echo htmlspecialchars(\App\ListHelper::getValue($r, $key) ?? '-');
                        endif;
                    ?></td>
                    <?php endforeach; ?>
                    <td>
                        <?php if (\Core\Auth::can('view_roles')): ?><a href="<?= admin_url('users/roles/view/' . (int)$r->id ) ?>" class="btn btn-sm btn-outline-secondary">View</a><?php endif; ?>
                        <?php if (\Core\Auth::can('edit_roles')): ?><a href="<?= admin_url('users/roles/edit/' . (int)$r->id ) ?>" class="btn btn-sm btn-outline-primary">Edit</a><?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($roles)): ?>
                <tr><td colspan="<?= count($listColumns) + 1 ?>" class="text-muted text-center py-4">No roles.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require __DIR__ . '/../partials/list_pagination.php'; ?>
<?php
$content = ob_get_clean();
$pageTitle = 'User Roles & Capabilities';
$currentPage = 'user-roles';
require __DIR__ . '/../layout/main.php';
?>
