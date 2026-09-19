<?php
$listColumns = $listColumns ?? [];
$listBaseUrl = $listBaseUrl ?? admin_url('posts');
$listSort = $listSort ?? 'published_at';
$listOrder = $listOrder ?? 'desc';
$listExtraParams = $listExtraParams ?? [];
$postFilters = $postFilters ?? [
    'status' => '', 'category_id' => '', 'author_id' => '', 'sticky' => '',
    'date_field' => 'published_at', 'date_from' => '', 'date_to' => '',
];
$postFilterAuthors = $postFilterAuthors ?? [];
$postFilterCategories = $postFilterCategories ?? [];
$postFiltersActive = !empty($postFiltersActive);
$baseQuery = '?q=' . urlencode($listSearch ?? '') . '&columns=' . urlencode(implode(',', $listColumns)) . '&per_page=' . (int)($listPagination['per_page'] ?? 15);
foreach ($listExtraParams as $k => $v) {
    if ($v === '' || $v === null) {
        continue;
    }
    $baseQuery .= '&' . urlencode($k) . '=' . urlencode((string) $v);
}
$canBulkEdit = \Core\Auth::can('edit_posts');
$canBulkDelete = \Core\Auth::can('delete_posts');
$showBulk = $canBulkEdit || $canBulkDelete;
$colspan = count($listColumns) + 1 + ($showBulk ? 1 : 0);
$dateColumns = ['published_at', 'created_at', 'updated_at'];
$clearFilterQuery = '?q=' . urlencode($listSearch ?? '') . '&columns=' . urlencode(implode(',', $listColumns)) . '&sort=' . urlencode($listSort) . '&order=' . urlencode($listOrder) . '&per_page=' . (int)($listPagination['per_page'] ?? 15);
ob_start();
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0">Posts</h2>
    <div class="d-flex gap-2">
    <?php if (\Core\Auth::can('add_posts')): ?>
    <a href="<?= admin_url('posts/import') ?>" class="btn btn-outline-secondary">Import</a>
    <a href="<?= admin_url('posts/create') ?>" class="btn btn-primary">Add Post</a>
    <?php endif; ?>
    </div>
</div>
<?php require __DIR__ . '/../partials/list_toolbar.php'; ?>
<form method="get" action="<?= htmlspecialchars($listBaseUrl) ?>" class="card mb-3 post-list-filters" id="postListFilters">
    <input type="hidden" name="q" value="<?= htmlspecialchars($listSearch ?? '') ?>">
    <input type="hidden" name="columns" value="<?= htmlspecialchars(implode(',', $listColumns)) ?>">
    <input type="hidden" name="sort" value="<?= htmlspecialchars($listSort) ?>">
    <input type="hidden" name="order" value="<?= htmlspecialchars($listOrder) ?>">
    <input type="hidden" name="per_page" value="<?= (int)($listPagination['per_page'] ?? 15) ?>">
    <div class="card-body py-3">
        <div class="row g-2 align-items-end">
            <div class="col-6 col-md-3 col-xl-2">
                <label class="form-label form-label-sm mb-1" for="postFilterStatus">Status</label>
                <select name="status" id="postFilterStatus" class="form-select form-select-sm" data-filter-autosubmit>
                    <option value="">All statuses</option>
                    <option value="draft" <?= ($postFilters['status'] ?? '') === 'draft' ? 'selected' : '' ?>>Draft</option>
                    <option value="published" <?= ($postFilters['status'] ?? '') === 'published' ? 'selected' : '' ?>>Published</option>
                    <option value="scheduled" <?= ($postFilters['status'] ?? '') === 'scheduled' ? 'selected' : '' ?>>Scheduled</option>
                </select>
            </div>
            <div class="col-6 col-md-3 col-xl-2">
                <label class="form-label form-label-sm mb-1" for="postFilterCategory">Category</label>
                <select name="category_id" id="postFilterCategory" class="form-select form-select-sm" data-filter-autosubmit>
                    <option value="">All categories</option>
                    <option value="0" <?= ($postFilters['category_id'] ?? '') === '0' ? 'selected' : '' ?>>Uncategorized</option>
                    <?php foreach ($postFilterCategories as $cat): ?>
                    <option value="<?= (int) $cat->id ?>" <?= ($postFilters['category_id'] ?? '') === (string) (int) $cat->id ? 'selected' : '' ?>><?= htmlspecialchars((string) $cat->name) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-3 col-xl-2">
                <label class="form-label form-label-sm mb-1" for="postFilterAuthor">Author</label>
                <select name="author_id" id="postFilterAuthor" class="form-select form-select-sm" data-filter-autosubmit>
                    <option value="">All authors</option>
                    <?php foreach ($postFilterAuthors as $authorId => $authorName): ?>
                    <option value="<?= (int) $authorId ?>" <?= ($postFilters['author_id'] ?? '') === (string) (int) $authorId ? 'selected' : '' ?>><?= htmlspecialchars($authorName) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-3 col-xl-2">
                <label class="form-label form-label-sm mb-1" for="postFilterSticky">Pinned</label>
                <select name="sticky" id="postFilterSticky" class="form-select form-select-sm" data-filter-autosubmit>
                    <option value="">All</option>
                    <option value="1" <?= ($postFilters['sticky'] ?? '') === '1' ? 'selected' : '' ?>>Pinned only</option>
                    <option value="0" <?= ($postFilters['sticky'] ?? '') === '0' ? 'selected' : '' ?>>Not pinned</option>
                </select>
            </div>
            <div class="col-6 col-md-3 col-xl-2">
                <label class="form-label form-label-sm mb-1" for="postFilterDateField">Date</label>
                <select name="date_field" id="postFilterDateField" class="form-select form-select-sm">
                    <option value="published_at" <?= ($postFilters['date_field'] ?? '') !== 'created_at' ? 'selected' : '' ?>>Published date</option>
                    <option value="created_at" <?= ($postFilters['date_field'] ?? '') === 'created_at' ? 'selected' : '' ?>>Created date</option>
                </select>
            </div>
            <div class="col-6 col-md-3 col-xl-2">
                <label class="form-label form-label-sm mb-1" for="postFilterDateFrom">From</label>
                <input type="date" name="date_from" id="postFilterDateFrom" class="form-control form-control-sm" value="<?= htmlspecialchars($postFilters['date_from'] ?? '') ?>">
            </div>
            <div class="col-6 col-md-3 col-xl-2">
                <label class="form-label form-label-sm mb-1" for="postFilterDateTo">To</label>
                <input type="date" name="date_to" id="postFilterDateTo" class="form-control form-control-sm" value="<?= htmlspecialchars($postFilters['date_to'] ?? '') ?>">
            </div>
            <div class="col-12 col-md-auto d-flex flex-wrap gap-2">
                <button type="submit" class="btn btn-sm btn-outline-primary">Apply filters</button>
                <?php if ($postFiltersActive || ($postFilters['date_field'] ?? '') === 'created_at'): ?>
                <a href="<?= htmlspecialchars($listBaseUrl . $clearFilterQuery) ?>" class="btn btn-sm btn-outline-secondary">Clear filters</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</form>
<div data-bulk-scope>
<?php if ($showBulk): ?>
<form method="post" action="<?= admin_url('posts/bulk') ?>" id="postBulkForm" data-bulk-form class="d-flex flex-wrap align-items-center gap-2 mb-2">
    <?= \Core\Csrf::field() ?>
    <label class="small text-muted mb-0" for="postBulkAction">Bulk</label>
    <select name="bulk_action" id="postBulkAction" class="form-select form-select-sm" style="width:auto">
        <?php if ($canBulkEdit): ?>
        <option value="publish">Publish</option>
        <option value="draft">Set to draft</option>
        <option value="pin">Pin to top</option>
        <option value="unpin">Unpin</option>
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
<?php if ($showBulk): ?><th class="text-nowrap" style="width:2.2rem"><input type="checkbox" class="form-check-input js-bulk-all" form="postBulkForm" aria-label="Select all posts on this page"></th><?php endif; ?>
<?php foreach ($listColumns as $key):
    $col = \App\ListConfig::getColumnByKey('posts', $key);
    if (!$col) {
        continue;
    }
    $sortable = !empty($col['sortable']);
    $isCurrent = $sortable && $listSort === $key;
    $nextOrder = 'asc';
    if ($isCurrent) {
        $nextOrder = $listOrder === 'asc' ? 'desc' : 'asc';
    } elseif (in_array($key, $dateColumns, true)) {
        $nextOrder = 'desc';
    }
    $sortUrl = $listBaseUrl . $baseQuery . '&sort=' . urlencode($key) . '&order=' . $nextOrder;
    $ariaSort = $isCurrent ? ($listOrder === 'asc' ? 'ascending' : 'descending') : 'none';
?>
<th<?= $sortable ? ' aria-sort="' . $ariaSort . '"' : '' ?><?= $isCurrent ? ' class="is-sorted"' : '' ?>>
    <?php if ($sortable): ?><a href="<?= htmlspecialchars($sortUrl) ?>" class="list-sort-link"><?php endif; ?>
    <?= htmlspecialchars($col['label']) ?>
    <?php if ($isCurrent): ?><span class="ms-1" aria-hidden="true"><?= $listOrder === 'asc' ? '↑' : '↓' ?></span><?php endif; ?>
    <?php if ($sortable): ?></a><?php endif; ?>
</th>
<?php endforeach; ?><th>Actions</th></tr></thead>
<tbody>
<?php foreach ($posts as $p): ?><tr>
<?php if ($showBulk): ?><td><input type="checkbox" class="form-check-input js-bulk-row" form="postBulkForm" name="ids[]" value="<?= (int)$p->id ?>" aria-label="Select <?= htmlspecialchars((string)$p->title) ?>"></td><?php endif; ?>
<?php foreach ($listColumns as $key):
    $val = \App\ListHelper::getValue($p, $key);
    $display = ($val === null || $val === '') ? '-' : (string) $val;
?><td><?= htmlspecialchars($display) ?></td><?php endforeach; ?>
<td>
<a href="<?= admin_url('posts/view/' . (int)$p->id ) ?>" class="btn btn-sm btn-outline-secondary">View</a>
<?php if (\Core\Auth::can('edit_posts')): ?><a href="<?= admin_url('posts/edit/' . (int)$p->id ) ?>" class="btn btn-sm btn-outline-primary">Edit</a><?php endif; ?>
<?php if (\Core\Auth::can('add_posts')): ?>
<form method="post" action="<?= admin_url('posts/duplicate/' . (int)$p->id) ?>" class="d-inline"><?= \Core\Csrf::field() ?><button type="submit" class="btn btn-sm btn-outline-secondary">Duplicate</button></form>
<?php endif; ?>
<?php if (\Core\Auth::can('delete_posts')): ?>
<form method="post" action="<?= admin_url('posts/delete/' . (int)$p->id ) ?>" class="d-inline" data-confirm="Delete this post?"><?= \Core\Csrf::field() ?><button type="submit" class="btn btn-sm btn-outline-danger">Delete</button></form>
<?php endif; ?>
</td></tr><?php endforeach; ?>
<?php if (empty($posts)): ?><tr><td colspan="<?= (int)$colspan ?>" class="text-center text-muted py-4"><?= $postFiltersActive || ($listSearch ?? '') !== '' ? 'No posts match these filters.' : 'No posts.' ?></td></tr><?php endif; ?>
</tbody></table></div></div>
</div>
<?php require __DIR__ . '/../partials/list_pagination.php'; ?>
<?php
$scripts = ($scripts ?? '') . '<script src="/public/assets/js/content/bulk-list.js"></script><script src="/public/assets/js/posts/index.js"></script>';
$content = ob_get_clean(); $pageTitle = 'Posts'; $currentPage = 'posts'; require __DIR__ . '/../layout/main.php';
