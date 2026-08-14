<?php
$user = $user ?? null;
$roles = $roles ?? [];
ob_start();
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><?= $user ? 'Edit User' : 'Add User' ?></h2>
    <a href="<?= admin_url('users') ?>" class="btn btn-outline-secondary">Back</a>
</div>
<div class="card"><div class="card-body">
<form method="post" action="<?= $user ? admin_url('users/update/' . (int)$user->id) : admin_url('users/store') ?>">
<?= \Core\Csrf::field() ?>
<?php if (!empty($_SESSION['user_password_error'])): ?>
<div class="alert alert-danger"><?= htmlspecialchars($_SESSION['user_password_error']) ?></div>
<?php unset($_SESSION['user_password_error']); endif; ?>
<div class="mb-3"><label class="form-label">Username</label><input type="text" name="username" class="form-control" value="<?= htmlspecialchars($user->username ?? '') ?>" required></div>
<div class="mb-3"><label class="form-label">Display name</label><input type="text" name="display_name" class="form-control" value="<?= htmlspecialchars($user->display_name ?? '') ?>"></div>
<div class="mb-3"><label class="form-label">Email</label><input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user->email ?? '') ?>"></div>
<div class="mb-3"><label class="form-label">Password <?= $user ? '(leave blank to keep)' : '' ?></label><input type="password" name="password" class="form-control" <?= $user ? '' : 'required' ?>></div>
<div class="mb-3"><label class="form-label">Role</label><select name="role_id" class="form-select" required>
<?php foreach ($roles as $r): ?><option value="<?= (int)$r->id ?>" <?= (int)($user->role_id ?? 0) === (int)$r->id ? 'selected' : '' ?>><?= htmlspecialchars($r->name) ?></option><?php endforeach; ?>
</select></div>
<button type="submit" class="btn btn-primary">Save</button>
</form></div></div>
<?php $content = ob_get_clean(); $pageTitle = $user ? 'Edit User' : 'Add User'; $currentPage = 'users'; require __DIR__ . '/../layout/main.php';
