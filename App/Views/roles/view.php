<?php ob_start();
use App\Capabilities;
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>View Role</h2>
    <div>
        <a href="<?= admin_url('users/roles') ?>" class="btn btn-outline-secondary">Back</a>
        <a href="<?= admin_url('users/roles/view/' . (int)$role->id . '/pdf') ?>" class="btn btn-outline-secondary" target="_blank" rel="noopener noreferrer">PDF</a>
        <?php if (\Core\Auth::can('edit_roles')): ?><a href="<?= admin_url('users/roles/edit/' . (int)$role->id) ?>" class="btn btn-primary">Edit</a><?php endif; ?>
    </div>
</div>
<div class="card">
    <div class="card-body">
        <dl class="row mb-0">
            <dt class="col-sm-3">Role</dt>
            <dd class="col-sm-9"><?= htmlspecialchars($role->name ?? '') ?></dd>
            <dt class="col-sm-3">Capabilities</dt>
            <dd class="col-sm-9">
                <?php if (!empty($role->capabilities)): ?>
                <?php foreach ($role->capabilities as $c): ?>
                <span class="badge bg-secondary me-1 mb-1"><?= htmlspecialchars(Capabilities::getLabel($c)) ?></span>
                <?php endforeach; ?>
                <?php else: ?><span class="text-muted">None</span><?php endif; ?>
            </dd>
        </dl>
    </div>
</div>
<?php $content = ob_get_clean(); $pageTitle = 'View Role'; $currentPage = 'user-roles'; require __DIR__ . '/../layout/main.php'; ?>
