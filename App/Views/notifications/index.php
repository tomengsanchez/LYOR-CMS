<?php
$notifications = $notifications ?? [];
$filters = $filters ?? ['from' => '', 'to' => '', 'module' => ''];
$pagination = $pagination ?? ['page' => 1, 'per_page' => 20, 'total' => 0, 'total_pages' => 0];
ob_start();
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="mb-0">Notification History</h2>
</div>
<div class="card mb-3"><div class="card-body">
<form method="get" class="row g-3 align-items-end">
<div class="col-md-3"><label class="form-label">From</label><input type="date" name="from" class="form-control" value="<?= htmlspecialchars($filters['from'] ?? '') ?>"></div>
<div class="col-md-3"><label class="form-label">To</label><input type="date" name="to" class="form-control" value="<?= htmlspecialchars($filters['to'] ?? '') ?>"></div>
<div class="col-md-3"><label class="form-label">Module</label>
<select name="module" class="form-select">
<option value="">All</option>
<option value="page" <?= ($filters['module'] ?? '') === 'page' ? 'selected' : '' ?>>Pages</option>
<option value="post" <?= ($filters['module'] ?? '') === 'post' ? 'selected' : '' ?>>Posts</option>
<option value="media" <?= ($filters['module'] ?? '') === 'media' ? 'selected' : '' ?>>Media</option>
</select></div>
<div class="col-md-3"><button type="submit" class="btn btn-primary">Apply</button> <a href="<?= admin_url('notifications') ?>" class="btn btn-outline-secondary">Clear</a></div>
</form></div></div>
<div class="card"><div class="table-responsive"><table class="table table-hover mb-0">
<thead><tr><th>When</th><th>Message</th><th>Type</th><th>Status</th><th></th></tr></thead>
<tbody>
<?php foreach ($notifications as $n): ?>
<tr>
<td><?= htmlspecialchars($n->created_at ?? '') ?></td>
<td><?= htmlspecialchars($n->message ?? '') ?></td>
<td><?= htmlspecialchars(ucfirst(str_replace('_', ' ', (string)($n->type ?? '')))) ?></td>
<td><?= !empty($n->clicked_at) ? '<span class="badge bg-secondary">Opened</span>' : '<span class="badge bg-success">New</span>' ?></td>
<td><a href="<?= admin_url('notifications/click/' . (int)$n->id ) ?>" class="btn btn-sm btn-outline-primary">Open</a></td>
</tr>
<?php endforeach; ?>
<?php if (empty($notifications)): ?><tr><td colspan="5" class="text-muted text-center py-4">No notifications.</td></tr><?php endif; ?>
</tbody></table></div>
<div class="card-body border-top"><?php
$listBaseUrl = admin_url('notificationsnotifications'); $listSearch = ''; $listColumns = []; $listSort = ''; $listOrder = 'desc';
$listPagination = $pagination;
$listExtraParams = ['from' => $filters['from'] ?? '', 'to' => $filters['to'] ?? '', 'module' => $filters['module'] ?? ''];
include __DIR__ . '/../partials/list_pagination.php';
?></div></div>
<?php $content = ob_get_clean(); $pageTitle = 'Notifications'; $currentPage = 'notifications'; require __DIR__ . '/../layout/main.php';
