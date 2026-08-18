<?php
use App\Models\NewsletterSubscriber;

$settings = $newsletterSettings ?? \App\NewsletterSettings::get();
$confirmState = $confirmState ?? '';
$hideForm = !empty($hideSubscribeForm);
$publicTitle = ($confirmState !== '' ? 'Subscription' : 'Subscribe') . ' — ' . ($branding->app_name ?? 'Simple CMS');
$publicNavActive = '';
ob_start();
?>
<div class="public-hero public-hero--compact">
    <h1><?= $confirmState !== '' ? 'Subscription' : 'Subscribe' ?></h1>
    <?php if ($confirmState === ''): ?>
    <p class="lead">Get occasional email updates from <?= htmlspecialchars((string) ($branding->app_name ?? 'this site')) ?>.</p>
    <?php endif; ?>
</div>
<div class="public-card">
<?php if ($confirmState === 'ok'): ?>
    <p class="mb-0">Your subscription is confirmed. Thank you.</p>
<?php elseif ($confirmState === 'already'): ?>
    <p class="mb-0">This address is already subscribed.</p>
<?php elseif ($confirmState === 'expired'): ?>
    <p class="mb-0">This confirmation link has expired. Submit the form again to receive a new email.</p>
<?php elseif ($confirmState === 'invalid'): ?>
    <p class="mb-0">This confirmation link is invalid or has already been used.</p>
<?php elseif (!$settings->enabled): ?>
    <p class="mb-0 text-muted">Signups are paused.</p>
<?php elseif (!$hideForm): ?>
    <?= NewsletterSubscriber::renderForm() ?>
<?php endif; ?>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
