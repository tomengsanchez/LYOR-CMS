<?php
use App\ContentPassword;

$unlockType = $unlockType ?? 'page';
$unlockEntity = $unlockEntity ?? null;
$unlockId = (int) ($unlockEntity->id ?? 0);
$unlockAction = $unlockType === 'post' ? '/unlock/post/' . $unlockId : '/unlock/page/' . $unlockId;
$unlockError = $_SESSION['content_unlock_error'] ?? '';
unset($_SESSION['content_unlock_error']);
?>
<div class="public-password-gate">
    <h2 class="public-password-gate-title">Password required</h2>
    <p class="public-password-gate-lead"><?= htmlspecialchars(ContentPassword::gateMessage()) ?></p>
    <?php if ($unlockError !== ''): ?>
    <p class="public-password-gate-error"><?= htmlspecialchars($unlockError) ?></p>
    <?php endif; ?>
    <form method="post" action="<?= htmlspecialchars($unlockAction) ?>" class="public-password-gate-form">
        <?= \Core\Csrf::field() ?>
        <label class="visually-hidden" for="contentPasswordInput">Password</label>
        <input type="password" name="content_password" id="contentPasswordInput" class="form-control" required autocomplete="current-password" maxlength="72">
        <button type="submit" class="btn btn-primary">Unlock</button>
    </form>
</div>
