<?php
$listColumns = $listColumns ?? [];
$listBaseUrl = $listBaseUrl ?? admin_url('pages');
$baseQuery = '?q=' . urlencode($listSearch ?? '') . '&columns=' . urlencode(implode(',', $listColumns)) . '&per_page=' . (int)($listPagination['per_page'] ?? 15);
$canBulkEdit = \Core\Auth::can('edit_pages');
$canBulkDelete = \Core\Auth::can('delete_pages');
$showBulk = $canBulkEdit || $canBulkDelete;
$colspan = count($listColumns) + 1 + ($showBulk ? 1 : 0);
ob_start();
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0">Pages</h2>
    <?php if (\Core\Auth::can('add_pages')): ?><a href="<?= admin_url('pages/create') ?>" class="btn btn-primary">Add Page</a><?php endif; ?>
</div>
<?php require __DIR__ . '/../partials/list_toolbar.php'; ?>
<div data-bulk-scope>
<?php if ($showBulk): ?>
<form method="post" action="<?= admin_url('pages/bulk') ?>" id="pageBulkForm" data-bulk-form class="d-flex flex-wrap align-items-center gap-2 mb-2">
    <?= \Core\Csrf::field() ?>
    <label class="small text-muted mb-0" for="pageBulkAction">Bulk</label>
    <select name="bulk_action" id="pageBulkAction" class="form-select form-select-sm" style="width:auto">
        <?php if ($canBulkEdit): ?>
        <option value="publish">Publish</option>
        <option value="draft">Set to draft</option>
        <?php endif; ?>
        <?php if ($canBulkDelete): ?>
        <option value="delete">Delete</option>
        <?php endif; ?>
    </select>
    <button type="submit" class="btn btn-sm btn-outline-primary" data-bulk-apply disabled>Apply</button>
    <span class="small text-muted" data-bulk-count>None selected</span>
</form>
<?php endif; ?>
<div class="card"><div class="table-responsive"><table class="table table-hover mb-0">
<thead><tr>
<?php if ($showBulk): ?><th class="text-nowrap" style="width:2.2rem"><input type="checkbox" class="form-check-input js-bulk-all" form="pageBulkForm" aria-label="Select all pages on this page"></th><?php endif; ?>
<?php foreach ($listColumns as $key): $col = \App\ListConfig::getColumnByKey('pages', $key); if (!$col) continue; ?>
<th><?= htmlspecialchars($col['label']) ?></th>
<?php endforeach; ?><th>Actions</th></tr></thead>
<tbody>
<?php foreach ($pages as $p): ?><tr>
<?php if ($showBulk): ?><td><input type="checkbox" class="form-check-input js-bulk-row" form="pageBulkForm" name="ids[]" value="<?= (int)$p->id ?>" aria-label="Select <?= htmlspecialchars((string)$p->title) ?>"></td><?php endif; ?>
<?php foreach ($listColumns as $key): ?><td><?= htmlspecialchars(\App\ListHelper::getValue($p, $key) ?? '-') ?></td><?php endforeach; ?>
<td>
<a href="<?= admin_url('pages/view/' . (int)$p->id ) ?>" class="btn btn-sm btn-outline-secondary">View</a>
<?php if (\Core\Auth::can('edit_pages')): ?><a href="<?= admin_url('pages/edit/' . (int)$p->id ) ?>" class="btn btn-sm btn-outline-primary">Edit</a><?php endif; ?>
<?php if (\Core\Auth::can('add_pages')): ?>
<form method="post" action="<?= admin_url('pages/duplicate/' . (int)$p->id) ?>" class="d-inline"><?= \Core\Csrf::field() ?><button type="submit" class="btn btn-sm btn-outline-secondary">Duplicate</button></form>
<?php endif; ?>
<?php if (\Core\Auth::can('delete_pages')): ?>
<form method="post" action="<?= admin_url('pages/delete/' . (int)$p->id ) ?>" class="d-inline" data-confirm="Delete this page?"><?= \Core\Csrf::field() ?><button type="submit" class="btn btn-sm btn-outline-danger">Delete</button></form>
<?php endif; ?>
</td></tr><?php endforeach; ?>
<?php if (empty($pages)): ?><tr><td colspan="<?= (int)$colspan ?>" class="text-center text-muted py-4">No pages.</td></tr><?php endif; ?>
</tbody></table></div></div>
</div>
<?php require __DIR__ . '/../partials/list_pagination.php'; ?>
<?php
$scripts = ($scripts ?? '') . '<script src="/public/assets/js/content/bulk-list.js"></script>';
$content = ob_get_clean(); $pageTitle = 'Pages'; $currentPage = 'pages'; require __DIR__ . '/../layout/main.php';
