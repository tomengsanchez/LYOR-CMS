<?php
$statuses = $statuses ?? [];
$currentStatus = $currentStatus ?? '';
$searchQuery = $searchQuery ?? '';
$pendingCount = (int) ($pendingCount ?? 0);
$confirmedCount = (int) ($confirmedCount ?? 0);
$canManage = !empty($canManage);
$canExport = !empty($canExport);
$subscribers = $subscribers ?? [];
ob_start();
?>
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h2 class="mb-0">Subscribers</h2>
        <p class="text-muted small mb-0">Newsletter list. Public signups use double opt-in unless you turn it off in General.</p>
    </div>
    <div class="d-flex gap-2 align-items-center">
        <?php if ($pendingCount > 0): ?>
        <span class="badge bg-warning text-dark"><?= $pendingCount ?> pending</span>
        <?php endif; ?>
        <span class="badge bg-secondary"><?= $confirmedCount ?> confirmed</span>
        <?php if ($canExport): ?>
        <a class="btn btn-sm btn-outline-secondary" href="<?= admin_url('subscribers/export' . ($currentStatus !== '' ? '?status=' . urlencode($currentStatus) : '')) ?>">Export CSV</a>
        <?php endif; ?>
    </div>
</div>

<form method="get" action="<?= admin_url('subscribers') ?>" class="row g-2 mb-3">
    <div class="col-auto">
        <input type="search" name="q" class="form-control form-control-sm" placeholder="Filter email" value="<?= htmlspecialchars($searchQuery) ?>" maxlength="100">
    </div>
    <div class="col-auto">
        <button type="submit" class="btn btn-sm btn-outline-secondary">Search</button>
    </div>
</form>

<div class="btn-group mb-3" role="group" aria-label="Filter by status">
    <a href="<?= admin_url('subscribers') ?>" class="btn btn-sm <?= $currentStatus === '' ? 'btn-primary' : 'btn-outline-secondary' ?>">All</a>
    <?php foreach ($statuses as $key => $label): ?>
    <a href="<?= admin_url('subscribers?status=' . urlencode($key)) ?>"
        class="btn btn-sm <?= $currentStatus === $key ? 'btn-primary' : 'btn-outline-secondary' ?>"><?= htmlspecialchars($label) ?></a>
    <?php endforeach; ?>
</div>

<div class="card"><div class="card-body p-0">
<table class="table table-hover mb-0 align-middle">
<thead><tr><th>Email</th><th>Status</th><th>Consented</th><th>Confirmed</th><th></th></tr></thead>
<tbody>
<?php if ($subscribers === []): ?>
<tr><td colspan="5" class="text-muted p-4">No subscribers<?= $currentStatus !== '' ? ' with this status' : '' ?>.</td></tr>
<?php else: foreach ($subscribers as $s): ?>
<tr>
    <td><?= htmlspecialchars((string) ($s->email ?? '')) ?></td>
    <td><span class="badge bg-secondary"><?= htmlspecialchars($statuses[$s->status ?? ''] ?? (string) ($s->status ?? '')) ?></span></td>
    <td class="text-muted small"><?= htmlspecialchars(substr((string) ($s->consent_at ?? ''), 0, 16)) ?></td>
    <td class="text-muted small"><?= htmlspecialchars(substr((string) ($s->confirmed_at ?? ''), 0, 16)) ?></td>
    <td class="text-end text-nowrap">
        <?php if ($canManage && ($s->status ?? '') !== 'confirmed'): ?>
        <form method="post" action="<?= admin_url('subscribers/confirm/' . (int) $s->id) ?>" class="d-inline"><?= \Core\Csrf::field() ?><button type="submit" class="btn btn-sm btn-outline-success">Confirm</button></form>
        <?php endif; ?>
        <?php if ($canManage && ($s->status ?? '') === 'confirmed'): ?>
        <form method="post" action="<?= admin_url('subscribers/unsubscribe/' . (int) $s->id) ?>" class="d-inline"><?= \Core\Csrf::field() ?><button type="submit" class="btn btn-sm btn-outline-warning">Unsubscribe</button></form>
        <?php endif; ?>
        <?php if ($canManage): ?>
        <form method="post" action="<?= admin_url('subscribers/delete/' . (int) $s->id) ?>" class="d-inline" data-confirm="Permanently delete this address?"><?= \Core\Csrf::field() ?><button type="submit" class="btn btn-sm btn-outline-danger">Delete</button></form>
        <?php endif; ?>
    </td>
</tr>
<?php endforeach; endif; ?>
</tbody></table></div></div>
<?php
$content = ob_get_clean();
$pageTitle = 'Subscribers';
$currentPage = 'subscribers';
require __DIR__ . '/../layout/main.php';
