<?php
// Expects: $listBaseUrl, $listSearch, $listColumns, $listSort, $listOrder, $listPagination
// Optional: $listExtraParams (associative array of extra query params to preserve across pages)
$p = $listPagination ?? ['page' => 1, 'per_page' => 15, 'total' => 0, 'total_pages' => 0, 'first_id' => null, 'last_id' => null];
$listBaseUrl = $listBaseUrl ?? '';
$listSearch = $listSearch ?? '';
$listColumns = $listColumns ?? [];
$listSort = $listSort ?? '';
$listOrder = $listOrder ?? 'asc';
$listExtraParams = $listExtraParams ?? [];

$extraQuery = '';
foreach ($listExtraParams as $k => $v) {
    if ($v === '' || $v === null) continue;
    $extraQuery .= '&' . urlencode($k) . '=' . urlencode((string)$v);
}

$base = $listBaseUrl . '?q=' . urlencode($listSearch) . '&columns=' . urlencode(implode(',', $listColumns)) . '&sort=' . urlencode($listSort) . '&order=' . urlencode($listOrder) . $extraQuery . '&per_page=' . (int)($p['per_page'] ?? 15);
?>
<?php 
$page = $p['page'] ?? 1;
$totalPages = max(1, $p['total_pages'] ?? 0);
$total = (int)($p['total'] ?? 0);
$perPage = (int)($p['per_page'] ?? 15);
$start = $total > 0 ? (($page - 1) * $perPage) + 1 : 0;
$end = min($page * $perPage, $total);
$firstId = $p['first_id'] ?? null;
$lastId = $p['last_id'] ?? null;
$prevUrl = $page <= 1 ? '#' : ($firstId ? $base . '&before_id=' . $firstId . '&page=' . ($page - 1) : $base . '&page=' . ($page - 1));
$nextUrl = $page >= $totalPages ? '#' : ($lastId ? $base . '&after_id=' . $lastId . '&page=' . ($page + 1) : $base . '&page=' . ($page + 1));
?>
<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mt-3">
    <span class="text-muted small"><?= $total > 0 ? 'Showing ' . number_format($start) . '–' . number_format($end) . ' of ' . number_format($total) : '0 records' ?></span>
    <?php if ($totalPages > 1): ?>
    <div class="d-flex align-items-center gap-2">
        <a class="btn btn-sm btn-outline-secondary<?= $page <= 1 ? ' disabled' : '' ?>" href="<?= $page <= 1 ? '#' : htmlspecialchars($prevUrl) ?>" <?= $page <= 1 ? 'tabindex="-1" aria-disabled="true"' : '' ?>>Previous</a>
        <?php if ($totalPages <= 100): ?>
        <select class="form-select form-select-sm js-page-jump" style="width:auto;" aria-label="Go to page">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <option value="<?= htmlspecialchars($base . '&page=' . $i) ?>" <?= $i === $page ? 'selected' : '' ?>>Page <?= $i ?> of <?= $totalPages ?></option>
            <?php endfor; ?>
        </select>
        <?php else: ?>
        <span class="text-muted small">Page <?= number_format($page) ?> of <?= number_format($totalPages) ?></span>
        <?php endif; ?>
        <a class="btn btn-sm btn-outline-secondary<?= $page >= $totalPages ? ' disabled' : '' ?>" href="<?= $page >= $totalPages ? '#' : htmlspecialchars($nextUrl) ?>" <?= $page >= $totalPages ? 'tabindex="-1" aria-disabled="true"' : '' ?>>Next</a>
    </div>
    <?php endif; ?>
</div>
