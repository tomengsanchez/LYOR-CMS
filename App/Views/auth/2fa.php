<?php
$authTitle = 'Two-Factor Verification';
require __DIR__ . '/partials/head.php';
?>
<body class="auth-page">
    <div class="card auth-card">
        <div class="card-body">
            <h4 class="mb-2 text-center auth-brand">
                <?php if ($logoUrl !== ''): ?>
                <img src="<?= htmlspecialchars($logoUrl) ?>" alt="" class="auth-logo mb-2" width="48" height="48">
                <?php endif; ?>
                Two-Factor Verification
            </h4>
            <p class="text-center auth-sub mb-3">Enter the 6-digit code sent to your email.</p>
            <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <?php if (isset($_GET['error']) && $_GET['error'] === 'csrf'): ?>
            <div class="alert alert-warning">Invalid or expired request. Please try again.</div>
            <?php endif; ?>
            <form method="post" action="<?= admin_url('login/2fa/verify') ?>">
                <?= \Core\Csrf::field() ?>
                <div class="mb-3">
                    <label class="form-label">Verification Code</label>
                    <input type="text" name="code" class="form-control text-center auth-code-input" maxlength="6" pattern="[0-9]{6}" placeholder="000000" required autofocus>
                </div>
                <button type="submit" class="btn btn-primary w-100">Verify</button>
            </form>
            <p class="text-center mt-3 mb-0">
                <a href="<?= admin_url('login') ?>" class="auth-back">Back to login</a>
            </p>
        </div>
    </div>
</body>
</html>
