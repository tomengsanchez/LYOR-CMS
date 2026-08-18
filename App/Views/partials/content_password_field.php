<?php
$entity = $entity ?? $page ?? $post ?? (object) [];
$hasPw = \App\ContentPassword::has($entity);
$inputId = $passwordInputId ?? 'contentPassword';
$removeId = $passwordRemoveId ?? 'removeContentPassword';
?>
<div class="mb-3">
    <label class="form-label" for="<?= htmlspecialchars($inputId) ?>">Password protect</label>
    <input type="password" name="content_password" id="<?= htmlspecialchars($inputId) ?>" class="form-control" autocomplete="new-password" maxlength="72" placeholder="<?= $hasPw ? 'Leave blank to keep the current password' : 'Optional — visitors must enter this to read the body' ?>">
    <small class="text-muted d-block mt-1">At least 6 characters. The password is stored as a hash, never shown again. Logged-in editors skip this gate.</small>
    <?php if ($hasPw): ?>
    <div class="form-check mt-2">
        <input type="checkbox" class="form-check-input" name="remove_content_password" value="1" id="<?= htmlspecialchars($removeId) ?>">
        <label class="form-check-label" for="<?= htmlspecialchars($removeId) ?>">Remove password (make public)</label>
    </div>
    <?php endif; ?>
</div>
