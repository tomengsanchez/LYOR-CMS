<?php ob_start(); ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Security Settings</h2>
</div>
<div class="card">
    <div class="card-body">
        <?php if (\Core\Auth::can('manage_security_settings')): ?>
        <form method="post" action="<?= admin_url('settings/security/update') ?>" id="securityForm">
            <?= \Core\Csrf::field() ?>
            <div class="mb-3">
                <div class="form-check form-switch">
                    <input type="checkbox" name="enable_email_2fa" value="1" class="form-check-input" id="enable2fa"
                        <?= $config->enable_email_2fa ? 'checked' : '' ?>>
                    <label class="form-check-label" for="enable2fa">Enable email 2FA</label>
                </div>
                <small class="text-muted">When enabled, users receive a one-time code via email during login.</small>
            </div>
            <div class="mb-3" id="expirationWrap">
                <label class="form-label" for="2fa_expiration">2FA expiration (minutes)</label>
                <input type="number" name="2fa_expiration_minutes" id="2fa_expiration" class="form-control"
                    value="<?= (int)$config->{'2fa_expiration_minutes'} ?>" min="1" max="1440" placeholder="15"
                    <?= !$config->enable_email_2fa ? 'disabled' : '' ?>>
                <small class="text-muted">How long the 2FA code remains valid (1–1440 minutes).</small>
            </div>
            <div class="mb-3">
                <label class="form-label" for="user_logout_after">User logout after (minutes)</label>
                <input type="number" name="user_logout_after_minutes" id="user_logout_after" class="form-control"
                    value="<?= (int)$config->user_logout_after_minutes ?>" min="0" max="10080" placeholder="30">
                <small class="text-muted">Automatically log out users after this many minutes of inactivity. Use 0 to disable.</small>
            </div>
            <hr class="my-4">
            <div class="mb-3">
                <div class="form-check form-switch">
                    <input type="checkbox" name="login_throttle_enabled" value="1" class="form-check-input" id="enableLoginThrottle"
                        <?= !empty($config->login_throttle_enabled) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="enableLoginThrottle">Enable login attempt throttling</label>
                </div>
                <small class="text-muted">Block IP addresses for a short time after repeated failed logins to slow brute-force attacks.</small>
            </div>
            <div class="row mb-3">
                <div class="col-md-6 mb-3 mb-md-0">
                    <label class="form-label" for="loginThrottleMaxAttempts">Max failed attempts before lockout</label>
                    <input type="number" name="login_throttle_max_attempts" id="loginThrottleMaxAttempts" class="form-control"
                        value="<?= (int)($config->login_throttle_max_attempts ?? 5) ?>" min="1" max="50">
                    <small class="text-muted">Number of consecutive failed login attempts from the same IP before it is temporarily blocked.</small>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="loginThrottleLockoutMinutes">Lockout duration (minutes)</label>
                    <input type="number" name="login_throttle_lockout_minutes" id="loginThrottleLockoutMinutes" class="form-control"
                        value="<?= (int)($config->login_throttle_lockout_minutes ?? 15) ?>" min="1" max="1440">
                    <small class="text-muted">How long an IP remains blocked after hitting the maximum failed attempts.</small>
                </div>
            </div>
            <hr class="my-4">
            <div class="mb-3">
                <label class="form-label" for="passwordMinLength">Password minimum length</label>
                <input type="number" name="password_min_length" id="passwordMinLength" class="form-control"
                    value="<?= (int)($config->password_min_length ?? 8) ?>" min="1" max="128">
                <small class="text-muted">Minimum number of characters required for new passwords.</small>
            </div>
            <div class="mb-3">
                <label class="form-label">Password complexity</label>
                <div class="d-flex flex-wrap gap-3 mt-1">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="requireUpper" name="password_require_upper" value="1" <?= !empty($config->password_require_upper) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="requireUpper">Require uppercase letter</label>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="requireLower" name="password_require_lower" value="1" <?= !empty($config->password_require_lower) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="requireLower">Require lowercase letter</label>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="requireNumber" name="password_require_number" value="1" <?= !empty($config->password_require_number) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="requireNumber">Require number</label>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="requireSymbol" name="password_require_symbol" value="1" <?= !empty($config->password_require_symbol) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="requireSymbol">Require symbol</label>
                    </div>
                </div>
                <small class="text-muted d-block mt-1">Unchecked options are allowed but not required.</small>
            </div>
            <div class="row mb-3">
                <div class="col-md-6 mb-3 mb-md-0">
                    <label class="form-label" for="passwordExpiryDays">Password expiry (days)</label>
                    <input type="number" name="password_expiry_days" id="passwordExpiryDays" class="form-control"
                        value="<?= (int)($config->password_expiry_days ?? 0) ?>" min="0" max="3650">
                    <small class="text-muted">Number of days before passwords are considered expired. Use 0 to disable expiry.</small>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="passwordHistoryLimit">Prevent reuse of last N passwords</label>
                    <input type="number" name="password_history_limit" id="passwordHistoryLimit" class="form-control"
                        value="<?= (int)($config->password_history_limit ?? 5) ?>" min="0" max="50">
                    <small class="text-muted">0 disables reuse checks; otherwise, users cannot reuse any of their last N passwords.</small>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Save Security Settings</button>
        </form>
        <p class="small text-muted mt-3 mb-0">
            Mobile/frontend API credentials are managed under
            <a href="<?= admin_url('system/api-clients') ?>">System → API Clients</a> (Dashboard / Clients / logs / usage).
        </p>
        <?php $scripts = ($scripts ?? '') . '<script src="/public/assets/js/security_settings/index.js"></script>'; ?>
        <?php else: ?>
        <div class="alert alert-info">You do not have permission to manage security settings.</div>
        <dl class="row mb-0">
            <dt class="col-sm-4">Enable email 2FA</dt>
            <dd class="col-sm-8"><?= $config->enable_email_2fa ? 'Yes' : 'No' ?></dd>
            <dt class="col-sm-4">2FA expiration (minutes)</dt>
            <dd class="col-sm-8"><?= $config->enable_email_2fa ? (int)$config->{'2fa_expiration_minutes'} : '-' ?></dd>
            <dt class="col-sm-4">User logout after (minutes)</dt>
            <dd class="col-sm-8"><?= (int)$config->user_logout_after_minutes ? (int)$config->user_logout_after_minutes : 'Disabled' ?></dd>
            <dt class="col-sm-4">Login attempt throttling</dt>
            <dd class="col-sm-8"><?= !empty($config->login_throttle_enabled) ? 'Enabled' : 'Disabled' ?></dd>
            <dt class="col-sm-4">Max failed attempts</dt>
            <dd class="col-sm-8"><?= isset($config->login_throttle_max_attempts) ? (int)$config->login_throttle_max_attempts : 5 ?></dd>
            <dt class="col-sm-4">Lockout duration (minutes)</dt>
            <dd class="col-sm-8"><?= isset($config->login_throttle_lockout_minutes) ? (int)$config->login_throttle_lockout_minutes : 15 ?></dd>
            <dt class="col-sm-4">Password minimum length</dt>
            <dd class="col-sm-8"><?= isset($config->password_min_length) ? (int)$config->password_min_length : 8 ?></dd>
            <dt class="col-sm-4">Password complexity</dt>
            <dd class="col-sm-8">
                <?php
                $rules = [];
                if (!empty($config->password_require_upper)) $rules[] = 'Uppercase';
                if (!empty($config->password_require_lower)) $rules[] = 'Lowercase';
                if (!empty($config->password_require_number)) $rules[] = 'Number';
                if (!empty($config->password_require_symbol)) $rules[] = 'Symbol';
                echo $rules ? htmlspecialchars(implode(', ', $rules)) : 'None (length only)';
                ?>
            </dd>
            <dt class="col-sm-4">Password expiry (days)</dt>
            <dd class="col-sm-8"><?= isset($config->password_expiry_days) && (int)$config->password_expiry_days > 0 ? (int)$config->password_expiry_days : 'Disabled' ?></dd>
            <dt class="col-sm-4">Password history limit</dt>
            <dd class="col-sm-8"><?= isset($config->password_history_limit) ? (int)$config->password_history_limit : 5 ?></dd>
        </dl>
        <?php endif; ?>
    </div>
</div>
<?php
$content = ob_get_clean();
$pageTitle = 'Security Settings';
$currentPage = 'security-settings';
require __DIR__ . '/../layout/main.php';
?>
