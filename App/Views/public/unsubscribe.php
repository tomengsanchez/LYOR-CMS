<?php
$unsubToken = (string) ($unsubToken ?? '');
$unsubValid = !empty($unsubValid);
$unsubDone = !empty($unsubDone);
$unsubError = (string) ($unsubError ?? '');
$publicTitle = 'Unsubscribe — ' . ($branding->app_name ?? 'Simple CMS');
$publicNavActive = '';
ob_start();
?>
<div class="public-hero public-hero--compact">
    <h1>Unsubscribe</h1>
</div>
<div class="public-card">
<?php if ($unsubDone): ?>
    <p class="mb-0">You have been unsubscribed. You will not receive further updates from this list.</p>
<?php elseif (!$unsubValid): ?>
    <p class="mb-0">This unsubscribe link is invalid or has already been used.</p>
<?php else: ?>
    <p>Click the button to stop receiving email updates.</p>
    <?php if ($unsubError !== ''): ?>
    <p class="newsletter-flash newsletter-flash-err"><?= htmlspecialchars($unsubError) ?></p>
    <?php endif; ?>
    <form method="post" action="/unsubscribe/<?= htmlspecialchars(rawurlencode($unsubToken)) ?>">
        <?= \Core\Csrf::field() ?>
        <button type="submit" class="btn btn-outline-secondary">Unsubscribe</button>
    </form>
<?php endif; ?>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
