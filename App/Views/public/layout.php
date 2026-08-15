<?php
/** @var string $publicTitle Full page title */
/** @var string $content Main HTML */
/** @var object|null $branding App branding config */
/** @var string $publicNavActive home|blog|none */
/** @var string|null $publicLayout narrow|normal|wide|full — overrides theme content width */
/** @var bool $publicUseBlogWidth when true, use blog width from theme settings */
$branding = $branding ?? \App\Models\AppSettings::getBrandingConfig();
$pubTheme = \App\PublicTheme::getConfig();
$publicTitle = $publicTitle ?? ($branding->app_name ?? 'Simple CMS');
$publicNavActive = $publicNavActive ?? '';
$appName = htmlspecialchars($branding->app_name ?? 'Simple CMS');
if (!empty($publicUseBlogWidth)) {
    $layoutClass = \App\PublicTheme::blogContentWidthClass($pubTheme);
} elseif (isset($publicLayout) && $publicLayout !== '') {
    $layoutClass = \App\PublicTheme::layoutClassForWidth((string) $publicLayout, $pubTheme);
} else {
    $layoutClass = \App\PublicTheme::contentWidthClass($pubTheme);
}
$themePreviewActive = \App\PublicTheme::isPreviewActive();
$customizerFrame = \App\PublicTheme::isCustomizerFrame();
$baseUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
$logoPath = $branding->logo_path ?? '';
$logoUrl = $logoPath !== '' ? $baseUrl . '/serve/app-logo' : '';
$showSidebar = \App\DiscussionSettings::get()->show_sidebar && \App\Models\Widget::areaHasWidgets(\App\Models\Widget::AREA_SIDEBAR);
$sidebarHtml = $showSidebar ? \App\Models\Widget::renderArea(\App\Models\Widget::AREA_SIDEBAR) : '';
$footerWidgetsHtml = \App\Models\Widget::renderArea(\App\Models\Widget::AREA_FOOTER);
$siteSeo = \App\Models\AppSettings::getSiteSeoConfig();
$rssFeedUrl = !empty($siteSeo->enable_rss_feed) ? $baseUrl . '/feed.xml' : '';
$htmlLang = \App\PublicSeo::localeLanguage($siteSeo->locale ?? 'en_US');
$llmsUrl = !empty($siteSeo->enable_llms_txt) && $baseUrl !== '' ? $baseUrl . '/llms.txt' : '';
$showSiteTitle = \App\PublicTheme::showSiteTitle($pubTheme) || $logoUrl === '';
$showTagline = \App\PublicTheme::showSiteTagline($pubTheme);
$siteTagline = trim((string) ($branding->company_name ?? ''));
$isEditorial = \App\PublicTheme::isEditorialChrome($pubTheme);
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($htmlLang) ?>"
    class="<?= htmlspecialchars(\App\PublicTheme::htmlClasses($pubTheme)) ?>"
    style="<?= htmlspecialchars(\App\PublicTheme::inlineStyle($pubTheme)) ?>"
    data-default-color-mode="<?= htmlspecialchars(\App\PublicTheme::defaultColorMode($pubTheme)) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($publicTitle) ?></title>
    <?php if (!empty($publicShare) && is_array($publicShare)): ?>
    <?php require __DIR__ . '/partials/social_meta.php'; ?>
    <?php elseif (!empty($publicMetaDescription)): ?>
    <meta name="description" content="<?= htmlspecialchars($publicMetaDescription) ?>">
    <?php endif; ?>
    <?php if (!empty($publicJsonLd) && is_array($publicJsonLd)): ?>
    <?php require __DIR__ . '/partials/json_ld.php'; ?>
    <?php endif; ?>
    <?php if ($logoUrl !== ''): ?>
    <link rel="icon" href="<?= htmlspecialchars($logoUrl) ?>">
    <?php endif; ?>
    <?php if ($rssFeedUrl !== ''): ?>
    <link rel="alternate" type="application/rss+xml" title="<?= htmlspecialchars($appName) ?> RSS" href="<?= htmlspecialchars($rssFeedUrl) ?>">
    <?php endif; ?>
    <?php if ($llmsUrl !== ''): ?>
    <link rel="alternate" type="text/plain" title="LLM information" href="<?= htmlspecialchars($llmsUrl) ?>">
    <?php endif; ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/public/assets/css/public/themes.css" rel="stylesheet">
    <link href="/public/assets/css/public/site.css" rel="stylesheet">
    <?php
    $stylePackCss = \App\ThemeStylePack::activeCssPublicUrl();
    if ($stylePackCss):
    ?>
    <link href="<?= htmlspecialchars($stylePackCss) ?>" rel="stylesheet">
    <?php endif; ?>
    <script src="/public/assets/js/public/theme.js"></script>
    <?php if ($themePreviewActive): ?>
    <script src="/public/assets/js/public/theme-preview-bridge.js"></script>
    <?php endif; ?>
</head>
<body class="public-site<?= $themePreviewActive ? ' public-site--theme-preview' : '' ?><?= $customizerFrame ? ' public-site--customizer-frame' : '' ?>">
    <?php if ($themePreviewActive && !$customizerFrame): ?>
    <?php require __DIR__ . '/partials/theme_preview_banner.php'; ?>
    <?php endif; ?>
    <header class="public-header<?= $isEditorial ? ' public-header--editorial' : '' ?>">
        <div class="container">
            <a class="public-brand" href="/">
                <?php if ($logoUrl !== ''): ?>
                <img src="<?= htmlspecialchars($logoUrl) ?>" alt="" class="public-brand-logo" width="32" height="32">
                <?php endif; ?>
                <span class="public-brand-text">
                    <?php if ($showSiteTitle): ?><span class="public-brand-name"><?= $appName ?></span><?php endif; ?>
                    <?php if ($showTagline && $siteTagline !== ''): ?>
                    <span class="public-brand-tagline"><?= htmlspecialchars($siteTagline) ?></span>
                    <?php endif; ?>
                </span>
            </a>
            <nav class="public-nav" aria-label="Public">
                <?php foreach (\App\PublicNav::primaryItems() as $navItem): ?>
                <?php
                $navUrl = \App\PublicNav::urlForItem($navItem);
                $navActive = \App\PublicNav::isItemActive($navItem, $publicNavActive ?? '');
                ?>
                <a href="<?= htmlspecialchars($navUrl) ?>"
                    class="<?= $navActive ? 'active' : '' ?>"
                    <?= !empty($navItem->open_in_new_tab) ? 'target="_blank" rel="noopener"' : '' ?>>
                    <?= htmlspecialchars($navItem->label ?? '') ?>
                </a>
                <?php endforeach; ?>
                <?php if (\App\PublicTheme::showAdminLink($pubTheme)): ?>
                <a href="<?= admin_url('login') ?>" class="btn-admin">Admin</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>
    <main class="public-main <?= htmlspecialchars($layoutClass) ?><?= $showSidebar ? ' public-main--with-sidebar' : '' ?>">
        <div class="container">
            <?php if ($showSidebar): ?>
            <div class="row g-4 public-layout-row">
                <div class="col-lg-8 public-layout-main">
                    <?= $content ?? '' ?>
                </div>
                <aside class="col-lg-4 public-sidebar" aria-label="Sidebar">
                    <?= $sidebarHtml ?>
                </aside>
            </div>
            <?php else: ?>
            <?= $content ?? '' ?>
            <?php endif; ?>
        </div>
    </main>
    <footer class="public-footer">
        <?php if ($footerWidgetsHtml !== ''): ?>
        <div class="public-footer-widgets">
            <div class="container">
                <div class="public-footer-widgets-inner">
                    <?= $footerWidgetsHtml ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
        <div class="container d-flex flex-wrap justify-content-between gap-2">
            <span>&copy; <?= date('Y') ?> <?= $appName ?></span>
            <?php if (\App\PublicTheme::showAdminLink($pubTheme)): ?>
            <a class="public-admin-login" href="<?= admin_url('login') ?>">Admin login</a>
            <?php endif; ?>
        </div>
    </footer>
    <script src="/public/assets/js/public/carousel.js"></script>
    <script src="/public/assets/js/public/blog-view.js"></script>
</body>
</html>
