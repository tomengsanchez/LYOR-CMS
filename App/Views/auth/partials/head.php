<?php
/** @var string $authTitle Suffix after em dash in document title */
$branding = $branding ?? \App\Models\AppSettings::getBrandingConfig();
$pubTheme = \App\PublicTheme::getConfig();
$appName = htmlspecialchars($branding->app_name ?? 'Simple CMS');
$accent = \App\Models\AppSettings::normalizeAccentColor($pubTheme->accent_color ?? $branding->public_accent_color ?? '#2563eb');
$baseUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
$logoPath = $branding->logo_path ?? '';
$logoUrl = $logoPath !== '' ? $baseUrl . '/serve/app-logo' : '';
$authTitle = $authTitle ?? 'Sign in';
?>
<!DOCTYPE html>
<html lang="en"
    class="<?= htmlspecialchars(\App\PublicTheme::htmlClasses($pubTheme)) ?>"
    style="<?= htmlspecialchars(\App\PublicTheme::inlineStyle($pubTheme)) ?>; --auth-accent: <?= htmlspecialchars($accent) ?>;"
    data-default-color-mode="<?= htmlspecialchars(\App\PublicTheme::defaultColorMode($pubTheme)) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($authTitle) ?> — <?= $appName ?></title>
    <?php if ($logoUrl !== ''): ?>
    <link rel="icon" href="<?= htmlspecialchars($logoUrl) ?>">
    <?php endif; ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/public/assets/css/public/themes.css" rel="stylesheet">
    <link href="/public/assets/css/layout/auth.css" rel="stylesheet">
    <script src="/public/assets/js/public/theme.js"></script>
</head>
