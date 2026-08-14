<?php
$uiTheme = $uiTheme ?? \App\UserUiSettings::THEME_DEFAULT;
$uiLayout = $uiLayout ?? \App\UserUiSettings::LAYOUT_SIDEBAR;
$uiColorMode = $uiColorMode ?? \App\UserUiSettings::COLOR_MODE_LIGHT;
$uiMobileFriendly = $uiMobileFriendly ?? false;
$notifyPrefs = $notifyPrefs ?? \App\UserNotificationSettings::get();
$themes = \App\UserUiSettings::themes();
$layouts = \App\UserUiSettings::layouts();
$colorModes = \App\UserUiSettings::colorModes();
ob_start();
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Settings</h2>
</div>
<?php if (!empty($_SESSION['settings_ui_saved'])): unset($_SESSION['settings_ui_saved']); ?>
<div class="alert alert-success alert-dismissible fade show">UI preferences saved.</div>
<?php endif; ?>
<?php if (!empty($_SESSION['settings_notifications_saved'])): unset($_SESSION['settings_notifications_saved']); ?>
<div class="alert alert-success alert-dismissible fade show">Notification preferences saved.</div>
<?php endif; ?>

<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">Custom UI</h5>
    </div>
    <div class="card-body">
        <p class="text-muted small mb-4">Customize appearance for your account. Changes are saved per user.</p>
        <form method="post" action="<?= admin_url('settings/ui') ?>">
            <?= \Core\Csrf::field() ?>
            <div class="row">
                <div class="col-md-6 mb-4">
                    <label class="form-label fw-semibold">Color theme</label>
                    <div class="d-flex flex-wrap gap-3 mt-2">
                        <?php foreach ($themes as $key => $label): ?>
                        <label class="d-flex align-items-center gap-2 cursor-pointer">
                            <input type="radio" name="ui_theme" value="<?= htmlspecialchars($key) ?>" class="form-check-input" <?= $uiTheme === $key ? 'checked' : '' ?>>
                            <span><?= htmlspecialchars($label) ?></span>
                            <?php if ($key !== 'default'): ?>
                            <span class="ui-theme-swatch ui-theme-swatch-<?= htmlspecialchars($key) ?>" title="<?= htmlspecialchars($label) ?>"></span>
                            <?php else: ?>
                            <span class="ui-theme-swatch ui-theme-swatch-default" title="<?= htmlspecialchars($label) ?>"></span>
                            <?php endif; ?>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="col-md-6 mb-4">
                    <label class="form-label fw-semibold">Color mode</label>
                    <div class="d-flex flex-wrap gap-3 mt-2">
                        <?php foreach ($colorModes as $key => $label): ?>
                        <label class="d-flex align-items-center gap-2">
                            <input type="radio" name="ui_color_mode" value="<?= htmlspecialchars($key) ?>" class="form-check-input" <?= $uiColorMode === $key ? 'checked' : '' ?>>
                            <span><?= htmlspecialchars($label) ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                    <small class="text-muted d-block mt-1">Dark mode adjusts admin panels, cards, and forms. Sidebar accent themes still apply.</small>
                </div>
                <div class="col-md-6 mb-4">
                    <label class="form-label fw-semibold">Navigation layout</label>
                    <div class="d-flex flex-wrap gap-3 mt-2">
                        <?php foreach ($layouts as $key => $label): ?>
                        <label class="d-flex align-items-center gap-2">
                            <input type="radio" name="ui_layout" value="<?= htmlspecialchars($key) ?>" class="form-check-input" <?= $uiLayout === $key ? 'checked' : '' ?>>
                            <span><?= htmlspecialchars($label) ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                    <div class="mt-3">
                        <label class="d-flex align-items-center gap-2">
                            <input type="checkbox" name="ui_mobile_friendly" value="1" class="form-check-input" <?= $uiMobileFriendly ? 'checked' : '' ?>>
                            <span>Enhanced mobile layout</span>
                        </label>
                        <small class="text-muted d-block mt-1">Navigation is responsive by default. Enable this to apply stronger column stacking and compact table behavior on small screens.</small>
                    </div>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Save UI preferences</button>
        </form>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">Notification Settings</h5>
    </div>
    <div class="card-body">
        <p class="text-muted small mb-4">Notify you when CMS content changes (administrators only for email delivery).</p>
        <form method="post" action="<?= admin_url('settings/notifications') ?>">
            <?= \Core\Csrf::field() ?>
            <div class="d-flex flex-column gap-2">
                <label class="d-flex align-items-center gap-2">
                    <input type="checkbox" name="notify_page_published" value="1" class="form-check-input" <?= !empty($notifyPrefs['notify_page_published']) ? 'checked' : '' ?>>
                    <span>Page published</span>
                </label>
                <label class="d-flex align-items-center gap-2">
                    <input type="checkbox" name="notify_post_published" value="1" class="form-check-input" <?= !empty($notifyPrefs['notify_post_published']) ? 'checked' : '' ?>>
                    <span>Post published</span>
                </label>
                <label class="d-flex align-items-center gap-2">
                    <input type="checkbox" name="notify_media_uploaded" value="1" class="form-check-input" <?= !empty($notifyPrefs['notify_media_uploaded']) ? 'checked' : '' ?>>
                    <span>Media uploaded</span>
                </label>
            </div>
            <button type="submit" class="btn btn-primary mt-3">Save notification preferences</button>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
$pageTitle = 'Settings';
$currentPage = 'settings';
require __DIR__ . '/../layout/main.php';
