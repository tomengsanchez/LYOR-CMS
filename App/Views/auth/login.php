<?php
$authTitle = 'Sign in';
require __DIR__ . '/partials/head.php';
?>
<body class="auth-page">
    <div class="card auth-card">
        <div class="card-body">
            <h4 class="mb-1 text-center auth-brand">
                <?php if ($logoUrl !== ''): ?>
                <img src="<?= htmlspecialchars($logoUrl) ?>" alt="" class="auth-logo mb-2" width="48" height="48">
                <?php endif; ?>
                <?= $appName ?>
            </h4>
            <p class="text-center auth-sub mb-4">Admin sign in</p>
            <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <?php if (isset($_GET['error']) && $_GET['error'] === '2fa_expired'): ?>
            <div class="alert alert-warning">Verification session expired. Please log in again.</div>
            <?php endif; ?>
            <?php if (isset($_GET['error']) && $_GET['error'] === 'csrf'): ?>
            <div class="alert alert-warning">Invalid or expired request. Please try again.</div>
            <?php endif; ?>
            <?php if (isset($_GET['timeout'])): ?>
            <div class="alert alert-info">You were logged out due to inactivity. Please sign in again.</div>
            <?php endif; ?>
            <form method="post" action="<?= admin_url('login') ?>">
                <?= \Core\Csrf::field() ?>
                <div class="mb-3">
                    <label class="form-label">Username</label>
                    <input type="text" name="username" class="form-control" required autofocus>
                </div>
                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Sign In</button>
            </form>
            <p class="text-center mt-3 mb-0"><a href="/" class="auth-back">← Back to site</a></p>
        </div>
    </div>
</body>
</html>
