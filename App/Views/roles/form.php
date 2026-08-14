<?php ob_start(); ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><?= !empty($isCreate) ? 'Add Role' : ('Edit Role: ' . htmlspecialchars($role->name)) ?></h2>
    <a href="<?= admin_url('users/roles') ?>" class="btn btn-outline-secondary">Back</a>
</div>
<div class="card">
    <div class="card-body">
        <?php if (!empty($formError)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars((string) $formError) ?></div>
        <?php endif; ?>
        <?php if (!empty($isLocked)): ?>
        <div class="alert alert-info">Administrator capabilities are locked and cannot be changed.</div>
        <?php endif; ?>
        <form method="post" action="<?= !empty($isCreate) ? admin_url('users/roles/store') : admin_url('users/roles/update/' . (int)$role->id) ?>">
            <?= \Core\Csrf::field() ?>
            <div class="mb-4">
                <label class="form-label fw-semibold">Role Name</label>
                <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($role->name) ?>" required <?= !empty($isLocked) ? 'readonly' : '' ?> style="max-width: 300px;">
            </div>
            <div class="mb-4">
                <label class="form-label fw-semibold">Entity Access Control</label>
                <p class="text-muted small mb-3">Grant view, add, edit, and delete permissions for each entity. Capabilities are defined by system modules.</p>
                <div class="table-responsive">
                    <table class="table table-bordered align-middle" style="max-width: 900px;">
                        <thead class="table-light">
                            <tr>
                                <th style="min-width: 200px;">Entity</th>
                                <th>Access Capabilities</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($allCapabilities as $entityName => $caps): ?>
                            <tr>
                                <td class="fw-medium"><?= htmlspecialchars($entityName) ?></td>
                                <td>
                                    <div class="d-flex flex-wrap gap-4">
                                        <?php foreach ($caps as $capKey => $capLabel): ?>
                                        <div class="form-check">
                                            <input type="checkbox" name="capabilities[]" value="<?= htmlspecialchars($capKey) ?>" class="form-check-input" id="cap_<?= htmlspecialchars($capKey) ?>"
                                                <?= in_array($capKey, $role->capabilities) ? 'checked' : '' ?>
                                                <?= !empty($isLocked) ? 'disabled' : '' ?>>
                                            <label class="form-check-label" for="cap_<?= htmlspecialchars($capKey) ?>"><?= htmlspecialchars($capLabel) ?></label>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php if (empty($isLocked)): ?>
            <button type="submit" class="btn btn-primary"><?= !empty($isCreate) ? 'Create Role' : 'Save Capabilities' ?></button>
            <?php else: ?>
            <a href="<?= admin_url('users/roles') ?>" class="btn btn-outline-secondary">Back</a>
            <?php endif; ?>
        </form>
    </div>
</div>
<?php
$content = ob_get_clean();
$pageTitle = !empty($isCreate) ? 'Add Role' : 'Edit Role';
$currentPage = 'user-roles';
require __DIR__ . '/../layout/main.php';
?>
