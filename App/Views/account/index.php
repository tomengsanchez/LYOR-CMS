<?php
$user = $user ?? null;
$linkedProjects = $linkedProjects ?? [];
$accessSummary = $accessSummary ?? [];
if (!$user) {
    header('Location: ' . \App\AdminPath::url('login'));
    exit;
}
$displayLabel = !empty(trim($user->display_name ?? '')) ? $user->display_name : $user->username;
ob_start();
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>My Profile</h2>
    <div class="d-flex gap-2 flex-wrap">
        <a href="<?= admin_url('account/sessions') ?>" class="btn btn-outline-secondary">Access &amp; activity</a>
        <?php if (\Core\Auth::can('edit_users')): ?>
        <a href="<?= admin_url('users/edit/' . (int)$user->id ) ?>" class="btn btn-primary">Edit</a>
        <?php endif; ?>
    </div>
</div>
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="border rounded bg-white p-3 h-100">
            <div class="text-muted small">Active web sessions</div>
            <div class="fs-4 fw-bold"><?= (int) ($accessSummary['active_sessions'] ?? 0) ?></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="border rounded bg-white p-3 h-100">
            <div class="text-muted small">Online now</div>
            <div class="fs-4 fw-bold"><?= (int) ($accessSummary['online_sessions'] ?? 0) ?></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="border rounded bg-white p-3 h-100">
            <div class="text-muted small">API tokens</div>
            <div class="fs-4 fw-bold"><?= (int) ($accessSummary['api_tokens'] ?? 0) ?></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="border rounded bg-white p-3 h-100">
            <div class="text-muted small">API recently used</div>
            <div class="fs-4 fw-bold"><?= (int) ($accessSummary['recent_api_tokens'] ?? 0) ?></div>
        </div>
    </div>
</div>
<div class="card">
    <div class="card-body">
        <dl class="row mb-0">
            <dt class="col-sm-3">Username</dt>
            <dd class="col-sm-9"><?= htmlspecialchars($user->username ?? '') ?></dd>
            <?php if (!empty(trim($user->display_name ?? ''))): ?>
            <dt class="col-sm-3">Display name</dt>
            <dd class="col-sm-9"><?= htmlspecialchars($user->display_name) ?></dd>
            <?php endif; ?>
            <dt class="col-sm-3">Email</dt>
            <dd class="col-sm-9"><?= htmlspecialchars($user->email ?? '-') ?></dd>
            <dt class="col-sm-3">Role</dt>
            <dd class="col-sm-9"><?= htmlspecialchars($user->role_name ?? '-') ?></dd>
            <dt class="col-sm-3">Linked Projects</dt>
            <dd class="col-sm-9">
                <?php if (empty($linkedProjects)): ?>
                <span class="text-muted">None</span>
                <?php else: ?>
                <ul class="list-unstyled mb-0">
                    <?php foreach ($linkedProjects as $proj): ?>
                    <li><?= htmlspecialchars($proj->name) ?></li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </dd>
        </dl>
    </div>
</div>
<p class="text-muted small mt-3 mb-0">
    Manage browser sessions and API tokens from
    <a href="<?= admin_url('account/sessions') ?>">Access &amp; activity</a>.
</p>
<?php
$content = ob_get_clean();
$pageTitle = 'My Profile';
$currentPage = 'account';
require __DIR__ . '/../layout/main.php';
?>
