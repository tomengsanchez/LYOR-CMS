<?php
$backups = $backups ?? [];
$saved = !empty($saved);
$error = $error ?? '';
$output = $output ?? '';
$latestBackup = $latestBackup ?? '';
ob_start();
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0">Backup</h2>
</div>

<?php if ($saved): ?>
<div class="alert alert-success alert-dismissible fade show">Operation completed.</div>
<?php endif; ?>
<?php if ($error !== ''): ?>
<div class="alert alert-danger alert-dismissible fade show"><?= htmlspecialchars((string) $error) ?></div>
<?php endif; ?>

<div class="row g-3">
    <div class="col-lg-12">
        <div class="card h-100">
            <div class="card-header"><h6 class="mb-0">Create backup</h6></div>
            <div class="card-body">
                <p class="text-muted small mb-3">Creates a ZIP in <code>storage/backups</code> with the database dump. Uploads are included unless you check Exclude uploads.</p>
                <form method="post" action="<?= admin_url('system/backup-restore/create') ?>">
                    <?= \Core\Csrf::field() ?>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" value="1" id="noUploadsBackup" name="no_uploads">
                        <label class="form-check-label" for="noUploadsBackup">Exclude uploads (DB-only backup)</label>
                    </div>
                    <button type="submit" class="btn btn-primary">Create backup now</button>
                </form>
                <p class="small text-muted mt-3 mb-0">Restore is disabled in web UI. Use CLI:
                    <code>php cli/restore.php --from=storage/backups/your-file.zip --yes</code>
                    (add <code>--force</code> only when restoring a legacy PAPeR ZIP or a differently named database).
                    Prefer the <code>mysql</code> client on PATH (or <code>--mysql=...</code>).
                    Restore wipes the target schema first, refuses foreign app/dbname mismatches unless
                    <code>--force</code>, audits dump row counts vs the live DB, and auto-rolls back from the latest
                    safety/backup ZIP on mismatch.
                    Open <a href="<?= admin_url('help?from=backup-restore') ?>">Help</a> or the
                    <a href="<?= admin_url('admin-guide') ?>">Administrator Guide</a> for the full operator checklist.
                    Optional flags: <code>--no-completion-audit</code>, <code>--no-auto-rollback</code>,
                    <code>--keep-extra-tables</code>.</p>
            </div>
        </div>
    </div>
</div>

<?php if ($latestBackup !== ''): ?>
<div class="alert alert-info mt-3 d-flex justify-content-between align-items-center">
    <span class="small">Latest backup ready: <code><?= htmlspecialchars((string) $latestBackup) ?></code></span>
    <form method="post" action="<?= admin_url('system/backup-restore/download') ?>" class="m-0">
        <?= \Core\Csrf::field() ?>
        <input type="hidden" name="file" value="<?= htmlspecialchars((string) $latestBackup) ?>">
        <button type="submit" class="btn btn-sm btn-outline-primary">Download latest</button>
    </form>
</div>
<?php endif; ?>

<div class="card mt-3">
    <div class="card-header"><h6 class="mb-0">Available backups</h6></div>
    <div class="card-body">
        <?php if (empty($backups)): ?>
            <p class="text-muted mb-0">No backups found in <code>storage/backups</code>.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead><tr><th>File</th><th>Restore file</th><th>Size</th><th>Last modified</th><th class="text-end">Action</th></tr></thead>
                    <tbody>
                        <?php foreach ($backups as $b): ?>
                        <tr>
                            <td><code><?= htmlspecialchars((string) $b->name) ?></code></td>
                            <td>
                                <?php if (!empty($b->restoreFile)): ?>
                                    <code><?= htmlspecialchars((string) $b->restoreFile) ?></code>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td><?= number_format(((int) $b->size) / 1048576, 2) ?> MB</td>
                            <td><?= htmlspecialchars(date('Y-m-d H:i:s', (int) $b->mtime)) ?></td>
                            <td class="text-end">
                                <form method="post" action="<?= admin_url('system/backup-restore/download') ?>" class="d-inline m-0">
                                    <?= \Core\Csrf::field() ?>
                                    <input type="hidden" name="file" value="<?= htmlspecialchars((string) $b->name) ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-secondary">Download</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php if ($output !== ''): ?>
<div class="card mt-3">
    <div class="card-header"><h6 class="mb-0">Last command output</h6></div>
    <div class="card-body">
        <pre class="mb-0 small" style="white-space:pre-wrap;max-height:320px;overflow:auto;"><?= htmlspecialchars((string) $output) ?></pre>
    </div>
</div>
<?php endif; ?>

<?php
$content = ob_get_clean();
$pageTitle = 'Backup';
$currentPage = 'backup-restore';
require __DIR__ . '/../layout/main.php';
?>
