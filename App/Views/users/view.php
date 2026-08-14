<?php ob_start(); ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><?= htmlspecialchars($user->username) ?></h2>
    <div>
        <?php if (\Core\Auth::can('edit_users')): ?><a href="<?= admin_url('users/edit/' . (int)$user->id ) ?>" class="btn btn-primary">Edit</a><?php endif; ?>
        <a href="<?= admin_url('users') ?>" class="btn btn-outline-secondary">Back</a>
    </div>
</div>
<div class="card"><div class="card-body">
<p><strong>Display name:</strong> <?= htmlspecialchars($user->display_name ?? '—') ?></p>
<p><strong>Email:</strong> <?= htmlspecialchars($user->email ?? '—') ?></p>
<p><strong>Role:</strong> <?= htmlspecialchars($user->role_name ?? '—') ?></p>
</div></div>
<?php $content = ob_get_clean(); $pageTitle = $user->username; $currentPage = 'users'; require __DIR__ . '/../layout/main.php';
