<?php
$timestamp = $timestamp ?? '';
$loadTimeMs = $loadTimeMs ?? 0;
$queries = $queries ?? [];
$classesLoaded = $classesLoaded ?? [];
$functions = $functions ?? [];
$logFiles = $logFiles ?? [];

ob_start();
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0">System Debug Log</h2>
</div>

<div class="card mb-4">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <strong class="text-muted small text-uppercase">Request timestamp</strong>
                <div class="fw-semibold"><?= htmlspecialchars($timestamp) ?></div>
            </div>
            <div class="col-md-6">
                <strong class="text-muted small text-uppercase">Loading time</strong>
                <div class="fw-semibold"><?= number_format($loadTimeMs, 2) ?> ms</div>
            </div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header bg-transparent border-bottom">
        <h5 class="mb-0">Application log files</h5>
        <small class="text-muted">Logs under the <code>logs/</code> directory. Contents are loaded on demand.</small>
    </div>
    <div class="card-body p-0">
        <?php if (empty($logFiles)): ?>
            <p class="text-muted mb-0 p-3">No log files found in the logs directory.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead>
                    <tr>
                        <th>Log file</th>
                        <th style="width: 10rem;" class="text-end">Size</th>
                        <th style="width: 14rem;">Last modified</th>
                        <th style="width: 7rem;"></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($logFiles as $log): ?>
                        <tr>
                            <td><?= htmlspecialchars($log['name'] ?? '') ?></td>
                            <td class="text-end small text-muted">
                                <?php
                                $size = (int)($log['size'] ?? 0);
                                if ($size >= 1048576) {
                                    echo number_format($size / 1048576, 2) . ' MB';
                                } elseif ($size >= 1024) {
                                    echo number_format($size / 1024, 1) . ' KB';
                                } else {
                                    echo $size . ' B';
                                }
                                ?>
                            </td>
                            <td class="small text-muted">
                                <?php
                                $modified = $log['modified'] ?? null;
                                echo $modified ? htmlspecialchars(date('Y-m-d H:i:s', (int)$modified)) : '—';
                                ?>
                            </td>
                            <td class="text-end">
                                <button type="button"
                                        class="btn btn-sm btn-outline-primary js-view-log"
                                        data-log-name="<?= htmlspecialchars($log['name'] ?? '') ?>">
                                    View
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="row g-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent border-bottom">
                <h5 class="mb-0">Database queries</h5>
                <small class="text-muted"><?= count($queries) ?> query(ies)</small>
            </div>
            <div class="card-body p-0">
                <?php if (empty($queries)): ?>
                    <p class="text-muted mb-0 p-3">No queries recorded for this request.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead>
                            <tr>
                                <th style="width: 3rem;">#</th>
                                <th>Query</th>
                                <th class="text-end" style="width: 7rem;">Duration (ms)</th>
                                <th class="text-end" style="width: 7rem;">Time (ms)</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($queries as $i => $q): ?>
                                <tr>
                                    <td class="text-muted"><?= $i + 1 ?></td>
                                    <td>
                                        <code class="small text-break"><?= htmlspecialchars(preg_replace('/\s+/', ' ', trim($q['sql'] ?? ''))) ?></code>
                                        <?php if (!empty($q['params']) && is_array($q['params'])): ?>
                                            <div class="mt-1 small text-muted">Params: <?= htmlspecialchars(json_encode($q['params'])) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end"><?= number_format($q['duration'] ?? 0, 2) ?></td>
                                    <td class="text-end text-muted"><?= number_format($q['time'] ?? 0, 2) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent border-bottom">
                <h5 class="mb-0">PHP functions (user-defined)</h5>
                <small class="text-muted"><?= count($functions) ?> function(s)</small>
            </div>
            <div class="card-body p-0">
                <?php if (empty($functions)): ?>
                    <p class="text-muted mb-0 p-3">No user-defined functions listed.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead>
                            <tr>
                                <th style="width: 3rem;">#</th>
                                <th>Function</th>
                                <th>File</th>
                                <th class="text-end" style="width: 5rem;">Line</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($functions as $i => $f): ?>
                                <tr>
                                    <td class="text-muted"><?= $i + 1 ?></td>
                                    <td><code><?= htmlspecialchars($f['function'] ?? '') ?></code></td>
                                    <td class="small text-break"><?= htmlspecialchars($f['file'] ?? '') ?></td>
                                    <td class="text-end"><?= (int)($f['line'] ?? 0) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent border-bottom">
                <h5 class="mb-0">Class loading</h5>
                <small class="text-muted"><?= count($classesLoaded) ?> class(es)</small>
            </div>
            <div class="card-body p-0">
                <?php if (empty($classesLoaded)): ?>
                    <p class="text-muted mb-0 p-3">No classes recorded for this request.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead>
                            <tr>
                                <th style="width: 3rem;">#</th>
                                <th>Class</th>
                                <th class="text-end" style="width: 8rem;">Loaded at (ms)</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($classesLoaded as $i => $c): ?>
                                <tr>
                                    <td class="text-muted"><?= $i + 1 ?></td>
                                    <td><code><?= htmlspecialchars($c['class'] ?? '') ?></code></td>
                                    <td class="text-end"><?= number_format($c['time'] ?? 0, 2) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="logModal" tabindex="-1" aria-labelledby="logModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="logModalLabel">Log file</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="log-modal-loading" class="text-center py-4">
                    <div class="spinner-border text-secondary" role="status" aria-hidden="true"></div>
                    <div class="mt-2 small text-muted">Loading log contents...</div>
                </div>
                <div id="log-modal-content" class="d-none">
                    <div class="small text-muted mb-2" id="log-modal-meta"></div>
                    <pre id="log-modal-text" class="small mb-0" style="white-space: pre-wrap; background:#0f172a; color:#e5e7eb; padding:0.75rem; border-radius:4px; max-height:60vh; overflow:auto;"></pre>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<?php
$apiSystemLog = (defined('BASE_URL') ? rtrim((string) BASE_URL, '/') : '') . '/api/system/log';
$scripts = ($scripts ?? '')
    . '<script>window.DEBUG_LOG_API=' . json_encode($apiSystemLog, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . ';</script>'
    . '<script src="/public/assets/js/debug_log/index.js"></script>';
?>

<?php
$content = ob_get_clean();
$pageTitle = 'System Debug Log';
$currentPage = 'debug-log';
require __DIR__ . '/../layout/main.php';
?>

