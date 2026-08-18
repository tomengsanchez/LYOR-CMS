<?php
$ui = \App\UserUiSettings::get();
$uiTheme = $ui['theme'] ?? \App\UserUiSettings::THEME_DEFAULT;
$uiLayout = $ui['layout'] ?? \App\UserUiSettings::LAYOUT_SIDEBAR;
$uiColorMode = $ui['color_mode'] ?? \App\UserUiSettings::COLOR_MODE_LIGHT;
$currentPage = $currentPage ?? '';
$navTrail = \App\NavTrail::resolve((string) $currentPage);
$branding = \App\Models\AppSettings::getBrandingConfig();
$pageTitle = $pageTitle ?? ($branding->app_name ?? 'Simple CMS');
$user = \Core\Auth::user();
$appName = $branding->app_name ?? 'Simple CMS';
$brandInitial = strtoupper(mb_substr(trim($appName), 0, 1, 'UTF-8') ?: 'S');
$mobileFriendly = !empty($ui['mobile_friendly']);
$baseUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
$logoPath = $branding->logo_path ?? '';
$logoUrl = $logoPath !== '' ? $baseUrl . '/serve/app-logo' : '';
$helpFrom = $currentPage !== '' ? $currentPage : 'dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <?php if ($logoUrl !== ''): ?>
    <link rel="icon" href="<?= htmlspecialchars($logoUrl) ?>">
    <?php endif; ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/public/assets/css/layout/admin.css" rel="stylesheet">
    <?php if ($mobileFriendly): ?>
    <link href="/public/assets/css/layout/responsive.css" rel="stylesheet">
    <?php endif; ?>
</head>
<body class="admin-app ui-theme-<?= htmlspecialchars($uiTheme) ?> ui-layout-<?= htmlspecialchars($uiLayout) ?> ui-color-<?= htmlspecialchars($uiColorMode) ?><?= $mobileFriendly ? ' ui-mobile-friendly' : '' ?>">
    <div class="admin-sidebar-overlay" id="adminSidebarOverlay" aria-hidden="true"></div>
    <aside class="admin-sidebar" id="adminSidebar" aria-label="Admin navigation">
        <a href="<?= admin_url() ?>" class="brand">
            <?php if ($logoUrl !== ''): ?>
            <img src="<?= htmlspecialchars($logoUrl) ?>" alt="" class="brand-logo" width="32" height="32">
            <?php else: ?>
            <span class="brand-mark" aria-hidden="true"><?= htmlspecialchars($brandInitial) ?></span>
            <?php endif; ?>
            <span><?= htmlspecialchars($appName) ?></span>
        </a>
        <nav class="py-2">
            <a href="<?= admin_url() ?>" class="<?= $currentPage === 'dashboard' ? 'active' : '' ?>">Dashboard</a>
            <div class="nav-label">Content</div>
            <?php if (\Core\Auth::can('view_pages')): ?><a href="<?= admin_url('pages') ?>" class="nav-sub <?= $currentPage === 'pages' ? 'active' : '' ?>">Pages</a><?php endif; ?>
            <?php if (\Core\Auth::can('view_posts')): ?><a href="<?= admin_url('posts') ?>" class="nav-sub <?= $currentPage === 'posts' ? 'active' : '' ?>">Posts</a><?php endif; ?>
            <?php if (\Core\Auth::canAny(['view_pages', 'view_posts', 'view_media'])): ?><a href="<?= admin_url('search') ?>" class="nav-sub <?= $currentPage === 'search' ? 'active' : '' ?>">Library search</a><?php endif; ?>
            <?php if (\Core\Auth::can('view_categories')): ?><a href="<?= admin_url('categories') ?>" class="nav-sub <?= $currentPage === 'categories' ? 'active' : '' ?>">Categories</a><?php endif; ?>
            <?php if (\Core\Auth::can('manage_categories')): ?><a href="<?= admin_url('tags') ?>" class="nav-sub <?= $currentPage === 'tags' ? 'active' : '' ?>">Tags</a><?php endif; ?>
            <?php if (\Core\Auth::isAdmin()): ?><a href="<?= admin_url('menus') ?>" class="nav-sub <?= $currentPage === 'menus' ? 'active' : '' ?>">Menus</a><?php endif; ?>
            <?php if (\Core\Auth::can('moderate_comments')): ?><a href="<?= admin_url('comments') ?>" class="nav-sub <?= $currentPage === 'comments' ? 'active' : '' ?>">Comments</a><?php endif; ?>
            <?php if (\Core\Auth::can('view_subscribers')): ?><a href="<?= admin_url('subscribers') ?>" class="nav-sub <?= $currentPage === 'subscribers' ? 'active' : '' ?>">Subscribers</a><?php endif; ?>
            <?php if (\Core\Auth::can('view_media')): ?><a href="<?= admin_url('media') ?>" class="nav-sub <?= $currentPage === 'media' ? 'active' : '' ?>">Media</a><?php endif; ?>
            <div class="nav-label">Appearance</div>
            <?php if (\Core\Auth::isAdmin()): ?><a href="<?= admin_url('customize') ?>" class="nav-sub <?= $currentPage === 'customize' ? 'active' : '' ?>">Customize</a><?php endif; ?>
            <?php if (\Core\Auth::isAdmin()): ?><a href="<?= admin_url('widgets') ?>" class="nav-sub <?= $currentPage === 'widgets' ? 'active' : '' ?>">Widgets</a><?php endif; ?>
            <div class="nav-label">Settings</div>
            <?php if (\Core\Auth::can('view_settings')): ?><a href="<?= admin_url('settings') ?>" class="nav-sub <?= $currentPage === 'settings' ? 'active' : '' ?>">UI &amp; Notifications</a><?php endif; ?>
            <?php if (\Core\Auth::can('view_email_settings')): ?><a href="<?= admin_url('settings/email') ?>" class="nav-sub <?= $currentPage === 'email-settings' ? 'active' : '' ?>">Email</a><?php endif; ?>
            <?php if (\Core\Auth::can('view_security_settings')): ?><a href="<?= admin_url('settings/security') ?>" class="nav-sub <?= $currentPage === 'security-settings' ? 'active' : '' ?>">Security</a><?php endif; ?>
            <?php if (\Core\Auth::can('view_settings')): ?><a href="<?= admin_url('system/general') ?>" class="nav-sub <?= $currentPage === 'general' ? 'active' : '' ?>">General</a><?php endif; ?>
            <?php if (\Core\Auth::can('manage_settings')): ?><a href="<?= admin_url('redirects') ?>" class="nav-sub <?= $currentPage === 'redirects' ? 'active' : '' ?>">Redirects</a><?php endif; ?>
            <div class="nav-label">System</div>
            <?php if (\Core\Auth::isAdmin()): ?>
            <a href="<?= admin_url('system/backup-restore') ?>" class="nav-sub <?= $currentPage === 'backup-restore' ? 'active' : '' ?>">Backup &amp; Restore</a>
            <a href="<?= admin_url('system/audit-trail') ?>" class="nav-sub <?= $currentPage === 'audit-trail' ? 'active' : '' ?>">Audit Trail</a>
            <?php endif; ?>
            <div class="nav-label">Users</div>
            <?php if (\Core\Auth::can('view_users')): ?><a href="<?= admin_url('users') ?>" class="nav-sub <?= $currentPage === 'users' ? 'active' : '' ?>">Users</a><?php endif; ?>
            <?php if (\Core\Auth::can('view_roles')): ?><a href="<?= admin_url('users/roles') ?>" class="nav-sub <?= $currentPage === 'user-roles' ? 'active' : '' ?>">Roles</a><?php endif; ?>
            <a href="<?= admin_url('help') ?>?from=<?= urlencode($helpFrom) ?>" class="nav-sub <?= $currentPage === 'help' ? 'active' : '' ?>">Help</a>
            <a href="/" class="nav-sub" target="_blank" rel="noopener">View Site</a>
        </nav>
    </aside>
    <div class="admin-main-wrap">
        <header class="admin-header">
            <div class="admin-header-start">
                <button type="button" class="admin-sidebar-toggle" id="adminSidebarToggle" aria-label="Toggle menu" aria-expanded="false" aria-controls="adminSidebar">☰</button>
                <span class="admin-user-chip d-none d-md-inline"><?= htmlspecialchars($user->username ?? '') ?></span>
                <?php if (\Core\Auth::canAny(['view_pages', 'view_posts', 'view_media'])): ?>
                <form method="get" action="<?= admin_url('search') ?>" class="admin-header-search d-none d-md-flex ms-2">
                    <input type="search" name="q" class="form-control form-control-sm" placeholder="Search content…" aria-label="Search content" minlength="2" style="min-width:11rem">
                </form>
                <?php endif; ?>
            </div>
            <a href="<?= admin_url('notifications') ?>" class="btn btn-sm btn-outline-secondary">Notifications</a>
            <a href="<?= admin_url('account') ?>" class="btn btn-sm btn-outline-secondary">Account</a>
            <form method="post" action="<?= admin_url('logout') ?>" class="d-inline"><?= \Core\Csrf::field() ?><button type="submit" class="btn btn-sm btn-outline-danger">Logout</button></form>
        </header>
        <main class="admin-content">
            <?php foreach (\App\Flash::pull() as $flash): ?>
            <div class="alert alert-<?= htmlspecialchars($flash['type'] ?? 'info') ?> alert-dismissible fade show">
                <?= htmlspecialchars((string) ($flash['message'] ?? '')) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endforeach; ?>
            <?php if (count($navTrail) > 1): ?>
            <nav aria-label="breadcrumb"><ol class="breadcrumb">
                <?php foreach ($navTrail as $i => $crumb): ?>
                <?php if ($i === count($navTrail) - 1): ?>
                <li class="breadcrumb-item active"><?= htmlspecialchars($crumb['label']) ?></li>
                <?php elseif (!empty($crumb['url'])): ?>
                <li class="breadcrumb-item"><a href="<?= htmlspecialchars($crumb['url']) ?>"><?= htmlspecialchars($crumb['label']) ?></a></li>
                <?php else: ?>
                <li class="breadcrumb-item"><?= htmlspecialchars($crumb['label']) ?></li>
                <?php endif; ?>
                <?php endforeach; ?>
            </ol></nav>
            <?php endif; ?>
            <?= $content ?? '' ?>
        </main>
    </div>
    <div class="modal fade" id="helpModal" tabindex="-1" aria-labelledby="helpModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="helpModalLabel">Help</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="helpModalBody">
                    <div class="text-center py-5 text-muted">Loading help…</div>
                </div>
                <div class="modal-footer">
                    <a href="<?= admin_url('help') ?>" id="helpModalFullPage" class="btn btn-outline-secondary js-help-full-page">Open full page</a>
                </div>
            </div>
        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/public/assets/js/shared/form-submit-guard.js"></script>
    <script src="/public/assets/js/layout/admin-sidebar.js"></script>
    <script src="/public/assets/js/layout/help-modal.js"></script>
    <script src="/public/assets/js/layout/main.js"></script>
    <?= $scripts ?? '' ?>
</body>
</html>
