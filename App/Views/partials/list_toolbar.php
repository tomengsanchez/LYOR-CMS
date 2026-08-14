<?php
// Expects: $listModule, $listBaseUrl, $listSearch, $listSort, $listOrder, $listColumns, $listAllColumns, $listPagination, $listHasCustomColumns
// Optional: $listExtraParams (associative array of extra query params to preserve across actions)
// Optional: $listCanExport (bool), $listExportUrl (string)
// Optional: $listExportColumns (array) - all fields for both Select Columns and Export dialogs; if not set, falls back to $listAllColumns
$listModule = $listModule ?? '';
$listBaseUrl = $listBaseUrl ?? '';
$listSearch = $listSearch ?? '';
$listSort = $listSort ?? '';
$listOrder = $listOrder ?? 'asc';
$listColumns = $listColumns ?? [];
$listAllColumns = $listAllColumns ?? [];
$listPagination = $listPagination ?? ['total' => 0, 'page' => 1, 'per_page' => 15, 'total_pages' => 0];
$listHasCustomColumns = $listHasCustomColumns ?? false;
$listExtraParams = $listExtraParams ?? [];
$listCanExport = $listCanExport ?? false;
$listExportColumns = $listExportColumns ?? $listAllColumns ?? [];
$listExportUrl = $listExportUrl ?? ($listBaseUrl !== '' ? $listBaseUrl . '/export' : '');
$listCanPdf = $listCanPdf ?? false;
$listPdfUrl = $listPdfUrl ?? '';
$listSearchPlaceholder = $listSearchPlaceholder ?? 'Search in visible columns...';
$p = $listPagination;
$modalId = 'listColumnsModal_' . preg_replace('/[^a-z0-9]/', '_', $listModule);
$exportModalId = 'listExportModal_' . preg_replace('/[^a-z0-9]/', '_', $listModule);

$extraQuery = '';
foreach ($listExtraParams as $k => $v) {
    if ($v === '' || $v === null) continue;
    $extraQuery .= '&' . urlencode($k) . '=' . urlencode((string)$v);
}
$pdfQueryParts = array_filter([
    'q' => $listSearch,
    'columns' => implode(',', $listColumns),
    'sort' => $listSort,
    'order' => $listOrder,
    'per_page' => (string) (int) ($p['per_page'] ?? 15),
], static fn ($v) => $v !== '' && $v !== null);
foreach ($listExtraParams as $k => $v) {
    if ($v === '' || $v === null) {
        continue;
    }
    $pdfQueryParts[$k] = (string) $v;
}
$pdfListUrl = ($listCanPdf && $listPdfUrl !== '') ? ($listPdfUrl . '?' . http_build_query($pdfQueryParts)) : '';
?>
<div class="list-toolbar d-flex flex-wrap align-items-center gap-2 mb-3">
    <form method="get" action="<?= htmlspecialchars($listBaseUrl) ?>" class="d-flex gap-2 flex-grow-1 list-toolbar-search-form">
        <input type="hidden" name="columns" value="<?= htmlspecialchars(implode(',', $listColumns)) ?>">
        <input type="hidden" name="sort" value="<?= htmlspecialchars($listSort) ?>">
        <input type="hidden" name="order" value="<?= htmlspecialchars($listOrder) ?>">
        <input type="hidden" name="per_page" value="<?= (int)($p['per_page'] ?? 15) ?>">
        <?php foreach ($listExtraParams as $k => $v): if ($v === '' || $v === null) continue; ?>
        <input type="hidden" name="<?= htmlspecialchars($k) ?>" value="<?= htmlspecialchars((string)$v) ?>">
        <?php endforeach; ?>
        <input type="search" name="q" class="form-control form-control-sm list-toolbar-search-input" placeholder="<?= htmlspecialchars($listSearchPlaceholder) ?>" value="<?= htmlspecialchars($listSearch) ?>">
        <button type="submit" class="btn btn-sm btn-outline-secondary">Search</button>
        <?php if ($listSearch): ?><a href="<?= htmlspecialchars($listBaseUrl) ?>?columns=<?= htmlspecialchars(implode(',', $listColumns)) ?>&sort=<?= htmlspecialchars($listSort) ?>&order=<?= htmlspecialchars($listOrder) ?>&per_page=<?= (int)($p['per_page'] ?? 15) ?><?= $extraQuery ?>" class="btn btn-sm btn-outline-secondary">Clear search</a><?php endif; ?>
    </form>
    <div class="d-flex align-items-center gap-2 list-toolbar-actions">
        <span class="text-muted small fw-medium"><?= number_format((int)$p['total']) ?> record<?= (int)$p['total'] !== 1 ? 's' : '' ?></span>
        <div class="dropdown">
            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown"><?= (int)($p['per_page'] ?? 15) ?> / page</button>
            <ul class="dropdown-menu">
                <?php foreach ([10, 15, 25, 50, 100] as $n): 
                    $url = $listBaseUrl . '?q=' . urlencode($listSearch) . '&columns=' . urlencode(implode(',', $listColumns)) . '&sort=' . urlencode($listSort) . '&order=' . urlencode($listOrder) . $extraQuery . '&per_page=' . $n;
                ?><li><a class="dropdown-item" href="<?= htmlspecialchars($url) ?>"><?= $n ?></a></li><?php endforeach; ?>
            </ul>
        </div>
        <button type="button" class="btn btn-sm btn-outline-secondary position-relative" data-bs-toggle="modal" data-bs-target="#<?= $modalId ?>" title="Customize columns">Columns<?php if ($listHasCustomColumns): ?><span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-primary" style="font-size:0.5rem" title="Custom columns saved">●</span><?php endif; ?></button>
        <?php if ($listCanExport && $listExportUrl !== ''): ?>
        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#<?= $exportModalId ?>">Export</button>
        <?php endif; ?>
        <?php if ($pdfListUrl !== ''): ?>
        <a href="<?= htmlspecialchars($pdfListUrl) ?>" class="btn btn-sm btn-outline-secondary" target="_blank" rel="noopener noreferrer" title="Download PDF (same filters as this list)">PDF</a>
        <?php endif; ?>
    </div>
</div>

<div class="modal fade" id="<?= $modalId ?>" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Select Columns</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form method="get" action="<?= htmlspecialchars($listBaseUrl) ?>" id="listColForm_<?= $modalId ?>">
                <div class="modal-body">
                    <input type="hidden" name="q" value="<?= htmlspecialchars($listSearch) ?>">
                    <input type="hidden" name="sort" value="<?= htmlspecialchars($listSort) ?>">
                    <input type="hidden" name="order" value="<?= htmlspecialchars($listOrder) ?>">
                    <input type="hidden" name="per_page" value="<?= (int)($p['per_page'] ?? 15) ?>">
                    <?php foreach ($listExtraParams as $k => $v): if ($v === '' || $v === null) continue; ?>
                    <input type="hidden" name="<?= htmlspecialchars($k) ?>" value="<?= htmlspecialchars((string)$v) ?>">
                    <?php endforeach; ?>
                    <div class="mb-2">
                        <div class="form-label form-label-sm mb-1 d-flex justify-content-between align-items-center">
                            <span>Columns</span>
                            <div class="btn-group btn-group-sm">
                                <button type="button" class="btn btn-outline-secondary btn-sm" id="columnsSelectAll_<?= $modalId ?>">Select all</button>
                                <button type="button" class="btn btn-outline-secondary btn-sm" id="columnsDeselectAll_<?= $modalId ?>">Deselect all</button>
                            </div>
                        </div>
                        <p class="text-muted small mb-1">Selected columns are shown in the table and used for search. All available fields are listed below.</p>
                        <div class="border rounded p-2" style="max-height: 280px; overflow-y: auto;">
                        <?php foreach ($listExportColumns as $col): $checked = in_array($col['key'], $listColumns, true); ?>
                        <div class="form-check"><input class="form-check-input list-col-cb columns-col-cb" type="checkbox" name="col[]" value="<?= htmlspecialchars($col['key']) ?>" id="col_<?= $modalId ?>_<?= htmlspecialchars($col['key']) ?>" data-columns-modal="<?= $modalId ?>" <?= $checked ? 'checked' : '' ?>><label class="form-check-label" for="col_<?= $modalId ?>_<?= htmlspecialchars($col['key']) ?>"><?= htmlspecialchars($col['label']) ?></label></div>
                        <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary">Apply</button></div>
            </form>
        </div>
    </div>
</div>

<?php if ($listCanExport && $listExportUrl !== ''): ?>
<div class="modal fade" id="<?= $exportModalId ?>" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Export <?= htmlspecialchars($listModule) ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="get" action="<?= htmlspecialchars($listExportUrl) ?>">
                <div class="modal-body">
                    <input type="hidden" name="q" value="<?= htmlspecialchars($listSearch) ?>">
                    <input type="hidden" name="sort" value="<?= htmlspecialchars($listSort) ?>">
                    <input type="hidden" name="order" value="<?= htmlspecialchars($listOrder) ?>">
                    <?php foreach ($listExtraParams as $k => $v):
                        if ($v === '' || $v === null) continue;
                        // For grievance export, show date range as explicit fields instead of hidden inputs
                        if ($listModule === 'grievance' && ($k === 'date_from' || $k === 'date_to')) continue;
                    ?>
                    <input type="hidden" name="<?= htmlspecialchars($k) ?>" value="<?= htmlspecialchars((string)$v) ?>">
                    <?php endforeach; ?>

                    <div class="mb-3">
                        <label class="form-label form-label-sm">Format</label>
                        <select name="format" class="form-select form-select-sm">
                            <option value="csv">CSV</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label form-label-sm">Scope</label>
                        <select name="scope" class="form-select form-select-sm">
                            <option value="filtered">All filtered results</option>
                            <option value="page">Current page only</option>
                        </select>
                    </div>

                    <?php if ($listModule === 'grievance'): ?>
                    <div class="mb-3">
                        <label class="form-label form-label-sm">Date range</label>
                        <div class="row g-2">
                            <div class="col-6">
                                <input type="date"
                                       name="date_from"
                                       class="form-control form-control-sm"
                                       value="<?= htmlspecialchars($listExtraParams['date_from'] ?? '') ?>">
                            </div>
                            <div class="col-6">
                                <input type="date"
                                       name="date_to"
                                       class="form-control form-control-sm"
                                       value="<?= htmlspecialchars($listExtraParams['date_to'] ?? '') ?>">
                            </div>
                        </div>
                        <p class="text-muted small mb-0">If left blank, all dates in the current filters are included.</p>
                    </div>
                    <?php endif; ?>

                    <div class="mb-2">
                        <div class="form-label form-label-sm mb-1 d-flex justify-content-between align-items-center">
                            <span>Columns</span>
                            <div class="btn-group btn-group-sm">
                                <button type="button" class="btn btn-outline-secondary btn-sm" id="exportSelectAll_<?= $exportModalId ?>">Select all</button>
                                <button type="button" class="btn btn-outline-secondary btn-sm" id="exportDeselectAll_<?= $exportModalId ?>">Deselect all</button>
                            </div>
                        </div>
                        <p class="text-muted small mb-1">Select which columns to include in the export. All available fields are listed below.</p>
                        <div class="border rounded p-2" style="max-height: 280px; overflow-y: auto;">
                        <?php foreach ($listExportColumns as $col): $checked = in_array($col['key'], $listColumns, true); ?>
                        <div class="form-check">
                            <input class="form-check-input export-col-cb" type="checkbox" name="col[]" value="<?= htmlspecialchars($col['key']) ?>" id="exportcol_<?= $exportModalId ?>_<?= htmlspecialchars($col['key']) ?>" data-export-modal="<?= $exportModalId ?>" <?= $checked ? 'checked' : '' ?>>
                            <label class="form-check-label" for="exportcol_<?= $exportModalId ?>_<?= htmlspecialchars($col['key']) ?>">
                                <?= htmlspecialchars($col['label']) ?>
                            </label>
                        </div>
                        <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Download</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>
<?php
$scripts = ($scripts ?? '') . '<script>window.listToolbarConfigs=window.listToolbarConfigs||[];window.listToolbarConfigs.push('
    . json_encode(['modalId' => $modalId, 'exportModalId' => $exportModalId], JSON_UNESCAPED_UNICODE)
    . ');</script><script src="/public/assets/js/partials/list_toolbar.js"></script>';
?>
