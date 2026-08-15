<?php
$settings = $settings ?? ['region' => '', 'timezone' => \App\GeneralSettings::DEFAULT_TIMEZONE];
$regions = $regions ?? \App\GeneralSettings::regions();
$timezones = $timezones ?? \App\GeneralSettings::timezones();
$branding = $branding ?? \App\Models\AppSettings::getBrandingConfig();
$siteSeo = $siteSeo ?? \App\Models\AppSettings::getSiteSeoConfig();
$publicTheme = $publicTheme ?? \App\PublicTheme::getConfig();
$reading = $reading ?? \App\ReadingSettings::get();
$discussion = $discussion ?? \App\DiscussionSettings::get();
$permalinks = $permalinks ?? \App\PermalinkSettings::get();
$themePresets = \App\PublicTheme::presets();
$mediaImages = $mediaImages ?? [];
$helpChat = $helpChat ?? \App\HelpChatSettings::get();
$baseUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
$logoPath = $branding->logo_path ?? '';
$logoUrl = $logoPath !== '' ? $baseUrl . '/serve/app-logo' : '';
$saved = !empty($_SESSION['general_saved']);
if ($saved) {
    unset($_SESSION['general_saved']);
}
$helpChatSaved = !empty($_SESSION['help_chat_saved']);
if ($helpChatSaved) {
    unset($_SESSION['help_chat_saved']);
}
ob_start();
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0">General</h2>
</div>

<?php if ($saved): ?>
<div class="alert alert-success alert-dismissible fade show">General settings saved.</div>
<?php endif; ?>
<?php if ($helpChatSaved): ?>
<div class="alert alert-success alert-dismissible fade show">Ask Help settings saved.</div>
<?php endif; ?>

<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">Branding (App &amp; Company)</h5>
    </div>
    <div class="card-body">
        <p class="text-muted small mb-4">
            Configure the application name, company name, and logo. The logo is also used as the favicon.
        </p>
        <form method="post" action="<?= admin_url('system/general/save') ?>" enctype="multipart/form-data">
            <?= \Core\Csrf::field() ?>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">App name</label>
                    <input type="text" name="app_name" class="form-control" value="<?= htmlspecialchars($branding->app_name ?? 'Simple CMS') ?>" maxlength="100">
                    <small class="text-muted d-block mt-1">Shown in the header and as the default part of the page title.</small>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Company / organization name</label>
                    <input type="text" name="company_name" class="form-control" value="<?= htmlspecialchars($branding->company_name ?? '') ?>" maxlength="150">
                    <small class="text-muted d-block mt-1">Optional. When set, it is appended to the page title.</small>
                </div>
            </div>
            <div class="row g-3 mt-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Logo (also used as favicon)</label>
                    <input type="file" name="app_logo" class="form-control" accept=".png,.jpg,.jpeg,.ico,image/png,image/jpeg,image/x-icon">
                    <small class="text-muted d-block mt-1">
                        Recommended: square PNG (at least 64x64). Uploading a new logo replaces the existing one.
                    </small>
                </div>
                <div class="col-md-6 d-flex flex-column align-items-start justify-content-center">
                    <label class="form-label fw-semibold mb-2">Current logo preview</label>
                    <?php if ($logoUrl !== ''): ?>
                        <div class="d-flex align-items-center gap-3">
                            <img src="<?= htmlspecialchars($logoUrl) ?>" alt="App logo" style="width:48px;height:48px;border-radius:8px;border:1px solid #e5e7eb;object-fit:contain;">
                            <span class="text-muted small"><?= htmlspecialchars($logoPath) ?></span>
                        </div>
                    <?php else: ?>
                        <span class="text-muted small">No logo uploaded yet. The browser will use the default icon (if any).</span>
                    <?php endif; ?>
                </div>
            </div>
            <hr class="my-4">
            <h6 class="mb-3">Reading (homepage &amp; blog)</h6>
            <p class="text-muted small">WordPress-style front page: static page or latest posts. Blog archives stay at <code>/blog</code>.</p>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Homepage displays</label>
                    <select name="reading_show_on_front" class="form-select">
                        <option value="page" <?= ($reading->show_on_front ?? 'page') === 'page' ? 'selected' : '' ?>>A static page</option>
                        <option value="posts" <?= ($reading->show_on_front ?? '') === 'posts' ? 'selected' : '' ?>>Latest posts</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Homepage</label>
                    <select name="reading_page_on_front" class="form-select">
                        <option value="0">— welcome / home slug fallback —</option>
                        <?php foreach ($publishedPages as $p): ?>
                        <option value="<?= (int)$p->id ?>" <?= (int)($reading->page_on_front ?? 0) === (int)$p->id ? 'selected' : '' ?>><?= htmlspecialchars($p->title) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Posts per page</label>
                    <input type="number" name="reading_posts_per_page" class="form-control" min="1" max="50"
                        value="<?= (int)($reading->posts_per_page ?? 10) ?>">
                </div>
            </div>
            <hr class="my-4">
            <h6 class="mb-3">Discussion (comments)</h6>
            <p class="text-muted small">WordPress-style comments on blog posts. Moderation queue and sidebar visibility.</p>
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" name="discussion_comments_enabled" value="1" id="discCommentsEnabled"
                            <?= !empty($discussion->comments_enabled) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="discCommentsEnabled">Allow comments on posts</label>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" name="discussion_moderation" value="1" id="discModeration"
                            <?= !empty($discussion->moderation) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="discModeration">Comments must be manually approved</label>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" name="discussion_require_name_email" value="1" id="discRequireName"
                            <?= !empty($discussion->require_name_email) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="discRequireName">Require name and email</label>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" name="discussion_show_sidebar" value="1" id="discShowSidebar"
                            <?= !empty($discussion->show_sidebar) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="discShowSidebar">Show sidebar widget area on public pages</label>
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold" for="discCommentRate">Max comments per IP / hour</label>
                    <input type="number" name="discussion_comment_rate_limit" id="discCommentRate" class="form-control"
                        min="0" max="100" value="<?= (int)($discussion->comment_rate_limit_per_hour ?? 10) ?>">
                    <small class="text-muted">0 = unlimited. Helps reduce spam.</small>
                </div>
            </div>
            <hr class="my-4">
            <h6 class="mb-3">Permalinks</h6>
            <p class="text-muted small">Custom URL structures for pages and posts. Default routes (<code>/p/…</code>, <code>/blog/…</code>) always work; these add alternate patterns.</p>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Page URLs</label>
                    <select name="permalink_page_structure" class="form-select">
                        <?php foreach (\App\PermalinkSettings::pageStructures() as $key => $label): ?>
                        <option value="<?= htmlspecialchars($key) ?>" <?= ($permalinks->page_structure ?? '') === $key ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Post URLs</label>
                    <select name="permalink_post_structure" class="form-select">
                        <?php foreach (\App\PermalinkSettings::postStructures() as $key => $label): ?>
                        <option value="<?= htmlspecialchars($key) ?>" <?= ($permalinks->post_structure ?? '') === $key ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <hr class="my-4">
            <h6 class="mb-3">Public site theme</h6>
            <p class="text-muted small">Colors, typography, layout, and default light/dark mode for the public site and auth pages.</p>
            <div class="row g-3">
                <div class="col-12">
                    <div class="border rounded p-3">
                        <h6 class="fw-semibold mb-2">Style packs</h6>
                        <p class="small text-muted">Upload into the library, then activate the pack you want. WordPress zips map colors only. <a href="<?= admin_url('customize') ?>">Open Customizer</a> for live preview.</p>
                        <?php
                        $stylePackFlash = $_SESSION['style_pack_flash'] ?? null;
                        unset($_SESSION['style_pack_flash']);
                        ?>
                        <?php if (!empty($stylePackFlash) && is_array($stylePackFlash)): ?>
                        <div class="alert alert-<?= !empty($stylePackFlash['ok']) ? 'success' : 'danger' ?> py-2"><?= htmlspecialchars((string) ($stylePackFlash['message'] ?? '')) ?></div>
                        <?php endif; ?>
                        <?php $returnTarget = 'general'; require __DIR__ . '/../partials/theme_style_pack_library.php'; ?>
                        <form method="post" action="<?= admin_url('customize/import-style-pack') ?>" enctype="multipart/form-data" class="row g-2 align-items-end">
                            <?= \Core\Csrf::field() ?>
                            <input type="hidden" name="return" value="general">
                            <div class="col-md-6">
                                <label class="form-label">Upload zip to library</label>
                                <input type="file" name="style_pack" class="form-control" accept=".zip,application/zip" required>
                            </div>
                            <div class="col-md-6 d-flex flex-wrap gap-2">
                                <button type="submit" class="btn btn-primary">Upload &amp; activate</button>
                                <a class="btn btn-outline-secondary" href="<?= admin_url('customize/export-style-pack') ?>">Export current</a>
                                <a class="btn btn-outline-secondary" href="<?= admin_url('customize/sample-style-pack') ?>">Download template</a>
                            </div>
                        </form>
                        <form method="post" action="<?= admin_url('customize/clear-style-pack') ?>" class="mt-2">
                            <?= \Core\Csrf::field() ?>
                            <input type="hidden" name="return" value="general">
                            <button type="submit" class="btn btn-sm btn-outline-danger">Clear active pack</button>
                        </form>
                    </div>
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold d-block">Color preset</label>
                    <div class="pub-theme-swatch-grid">
                        <?php foreach ($themePresets as $key => $label): ?>
                        <label title="<?= htmlspecialchars($label) ?>">
                            <input type="radio" name="pub_theme_preset" value="<?= htmlspecialchars($key) ?>"
                                <?= ($publicTheme->preset ?? 'default') === $key ? 'checked' : '' ?>>
                            <span class="pub-preset-swatch pub-preset-swatch-<?= htmlspecialchars($key) ?>" aria-hidden="true"></span>
                            <span class="visually-hidden"><?= htmlspecialchars($label) ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Accent color</label>
                    <input type="color" name="public_accent_color" class="form-control form-control-color"
                        value="<?= htmlspecialchars($publicTheme->accent_color ?? '#2563eb') ?>" title="Accent color">
                    <small class="text-muted d-block mt-1">Overrides the preset accent on links and buttons.</small>
                </div>
                <div class="col-12">
                    <div class="border rounded p-3 bg-light">
                        <h6 class="fw-semibold mb-2">Editorial magazine</h6>
                        <div class="form-check mb-2">
                            <input type="hidden" name="pub_theme_apply_editorial_pack" value="0">
                            <input type="checkbox" class="form-check-input" name="pub_theme_apply_editorial_pack" value="1" id="pubApplyEditorialPack">
                            <label class="form-check-label" for="pubApplyEditorialPack">Apply editorial style pack on save (colors, magazine layout, chrome, kicker)</label>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Site chrome</label>
                                <select name="pub_theme_chrome" class="form-select">
                                    <?php foreach (\App\PublicTheme::chromeStyles() as $key => $label): ?>
                                    <option value="<?= htmlspecialchars($key) ?>" <?= ($publicTheme->chrome ?? 'default') === $key ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Blog section kicker</label>
                                <input type="text" name="pub_theme_blog_kicker" class="form-control" maxlength="80"
                                    value="<?= htmlspecialchars($publicTheme->blog_kicker ?? '') ?>" placeholder="HERE'S WHAT'S NEW">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Public date format</label>
                                <select name="pub_theme_date_format" class="form-select">
                                    <?php foreach (\App\PublicTheme::dateFormats() as $key => $label): ?>
                                    <option value="<?= htmlspecialchars($key) ?>" <?= ($publicTheme->date_format ?? 'human') === $key ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12">
                                <div class="form-check">
                                    <input type="hidden" name="pub_theme_show_site_tagline" value="0">
                                    <input type="checkbox" class="form-check-input" name="pub_theme_show_site_tagline" value="1" id="pubShowTagline"
                                        <?= !empty($publicTheme->show_site_tagline) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="pubShowTagline">Show company name as header tagline</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Typography</label>
                    <select name="pub_theme_font" class="form-select">
                        <?php foreach (\App\PublicTheme::fonts() as $key => $label): ?>
                        <option value="<?= htmlspecialchars($key) ?>" <?= ($publicTheme->font ?? 'system') === $key ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Corner radius</label>
                    <select name="pub_theme_radius" class="form-select">
                        <?php foreach (\App\PublicTheme::radii() as $key => $label): ?>
                        <option value="<?= htmlspecialchars($key) ?>" <?= ($publicTheme->radius ?? 'md') === $key ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Content width</label>
                    <select name="pub_theme_width" class="form-select">
                        <?php foreach (\App\PublicTheme::contentWidths() as $key => $label): ?>
                        <option value="<?= htmlspecialchars($key) ?>" <?= ($publicTheme->content_width ?? 'full') === $key ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Blog listing width</label>
                    <select name="pub_theme_blog_width" class="form-select">
                        <?php foreach (\App\PublicTheme::blogContentWidths() as $key => $label): ?>
                        <option value="<?= htmlspecialchars($key) ?>" <?= ($publicTheme->blog_content_width ?? 'inherit') === $key ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <small class="text-muted d-block mt-1">Width for <code>/blog</code> only. Use <strong>Wide</strong> for card layouts.</small>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Default color mode</label>
                    <select name="pub_theme_color_mode" class="form-select">
                        <?php foreach (\App\PublicTheme::colorModes() as $key => $label): ?>
                        <option value="<?= htmlspecialchars($key) ?>" <?= ($publicTheme->default_color_mode ?? 'system') === $key ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <small class="text-muted d-block mt-1">Applies site-wide to the public site and auth pages. Visitors cannot change this.</small>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Header style</label>
                    <select name="pub_theme_header" class="form-select">
                        <?php foreach (\App\PublicTheme::headerStyles() as $key => $label): ?>
                        <option value="<?= htmlspecialchars($key) ?>" <?= ($publicTheme->header_style ?? 'solid') === $key ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Footer style</label>
                    <select name="pub_theme_footer_style" class="form-select">
                        <?php foreach (\App\PublicTheme::footerStyles() as $key => $label): ?>
                        <option value="<?= htmlspecialchars($key) ?>" <?= ($publicTheme->footer_style ?? 'solid') === $key ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Base font size</label>
                    <select name="pub_theme_font_size" class="form-select">
                        <?php foreach (\App\PublicTheme::fontSizes() as $key => $label): ?>
                        <option value="<?= htmlspecialchars($key) ?>" <?= ($publicTheme->font_size ?? 'md') === $key ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Line height</label>
                    <select name="pub_theme_line_height" class="form-select">
                        <?php foreach (\App\PublicTheme::lineHeights() as $key => $label): ?>
                        <option value="<?= htmlspecialchars($key) ?>" <?= ($publicTheme->line_height ?? 'normal') === $key ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Content spacing</label>
                    <select name="pub_theme_content_spacing" class="form-select">
                        <?php foreach (\App\PublicTheme::contentSpacings() as $key => $label): ?>
                        <option value="<?= htmlspecialchars($key) ?>" <?= ($publicTheme->content_spacing ?? 'comfortable') === $key ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Button style</label>
                    <select name="pub_theme_button_style" class="form-select">
                        <?php foreach (\App\PublicTheme::buttonStyles() as $key => $label): ?>
                        <option value="<?= htmlspecialchars($key) ?>" <?= ($publicTheme->button_style ?? 'solid') === $key ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Card shadow</label>
                    <select name="pub_theme_card_shadow" class="form-select">
                        <?php foreach (\App\PublicTheme::cardShadows() as $key => $label): ?>
                        <option value="<?= htmlspecialchars($key) ?>" <?= ($publicTheme->card_shadow ?? 'soft') === $key ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Link style</label>
                    <select name="pub_theme_link_style" class="form-select">
                        <?php foreach (\App\PublicTheme::linkStyles() as $key => $label): ?>
                        <option value="<?= htmlspecialchars($key) ?>" <?= ($publicTheme->link_style ?? 'accent') === $key ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Navigation style</label>
                    <select name="pub_theme_nav_style" class="form-select">
                        <?php foreach (\App\PublicTheme::navStyles() as $key => $label): ?>
                        <option value="<?= htmlspecialchars($key) ?>" <?= ($publicTheme->nav_style ?? 'inline') === $key ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Brand weight</label>
                    <select name="pub_theme_brand_weight" class="form-select">
                        <?php foreach (\App\PublicTheme::brandWeights() as $key => $label): ?>
                        <option value="<?= htmlspecialchars($key) ?>" <?= ($publicTheme->brand_weight ?? 'bold') === $key ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Heading scale</label>
                    <select name="pub_theme_heading_scale" class="form-select">
                        <?php foreach (\App\PublicTheme::headingScales() as $key => $label): ?>
                        <option value="<?= htmlspecialchars($key) ?>" <?= ($publicTheme->heading_scale ?? 'normal') === $key ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Blog list style</label>
                    <select name="pub_theme_blog_list_style" class="form-select">
                        <?php foreach (\App\PublicTheme::blogListStyles() as $key => $label): ?>
                        <option value="<?= htmlspecialchars($key) ?>" <?= ($publicTheme->blog_list_style ?? 'list') === $key ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Blog grid columns</label>
                    <select name="pub_theme_blog_grid_columns" class="form-select">
                        <?php foreach (\App\PublicTheme::blogGridColumns() as $key => $label): ?>
                        <option value="<?= htmlspecialchars($key) ?>" <?= ($publicTheme->blog_grid_columns ?? '3') === $key ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Blog image ratio</label>
                    <select name="pub_theme_blog_image_ratio" class="form-select">
                        <?php foreach (\App\PublicTheme::blogImageRatios() as $key => $label): ?>
                        <option value="<?= htmlspecialchars($key) ?>" <?= ($publicTheme->blog_image_ratio ?? 'landscape') === $key ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Featured image style</label>
                    <select name="pub_theme_image_style" class="form-select">
                        <?php foreach (\App\PublicTheme::imageStyles() as $key => $label): ?>
                        <option value="<?= htmlspecialchars($key) ?>" <?= ($publicTheme->image_style ?? 'soft') === $key ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Card / article borders</label>
                    <select name="pub_theme_border_style" class="form-select">
                        <?php foreach (\App\PublicTheme::borderStyles() as $key => $label): ?>
                        <option value="<?= htmlspecialchars($key) ?>" <?= ($publicTheme->border_style ?? 'subtle') === $key ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Header height</label>
                    <select name="pub_theme_header_height" class="form-select">
                        <?php foreach (\App\PublicTheme::headerHeights() as $key => $label): ?>
                        <option value="<?= htmlspecialchars($key) ?>" <?= ($publicTheme->header_height ?? 'comfortable') === $key ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <div class="form-check mt-2">
                        <input type="hidden" name="pub_theme_sticky_header" value="0">
                        <input type="checkbox" class="form-check-input" name="pub_theme_sticky_header" value="1" id="pubThemeSticky"
                            <?= !empty($publicTheme->sticky_header) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="pubThemeSticky">Sticky header on public site</label>
                    </div>
                    <div class="form-check mt-2">
                        <input type="hidden" name="pub_theme_show_admin_link" value="0">
                        <input type="checkbox" class="form-check-input" name="pub_theme_show_admin_link" value="1" id="pubThemeAdminLink"
                            <?= !empty($publicTheme->show_admin_link) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="pubThemeAdminLink">Show Admin login links publicly</label>
                    </div>
                    <div class="form-check mt-2">
                        <input type="hidden" name="pub_theme_show_site_title" value="0">
                        <input type="checkbox" class="form-check-input" name="pub_theme_show_site_title" value="1" id="pubThemeSiteTitle"
                            <?= !empty($publicTheme->show_site_title) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="pubThemeSiteTitle">Show site title next to logo</label>
                    </div>
                    <div class="form-check mt-2">
                        <input type="hidden" name="pub_theme_show_breadcrumbs" value="0">
                        <input type="checkbox" class="form-check-input" name="pub_theme_show_breadcrumbs" value="1" id="pubThemeCrumbs"
                            <?= !empty($publicTheme->show_breadcrumbs) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="pubThemeCrumbs">Show breadcrumbs</label>
                    </div>
                    <div class="form-check mt-2">
                        <input type="hidden" name="pub_theme_nav_uppercase" value="0">
                        <input type="checkbox" class="form-check-input" name="pub_theme_nav_uppercase" value="1" id="pubThemeNavUpper"
                            <?= !empty($publicTheme->nav_uppercase) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="pubThemeNavUpper">Uppercase navigation</label>
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Button size</label>
                    <select name="pub_theme_button_size" class="form-select">
                        <?php foreach (\App\PublicTheme::buttonSizes() as $key => $label): ?>
                        <option value="<?= htmlspecialchars($key) ?>" <?= ($publicTheme->button_size ?? 'md') === $key ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Sidebar style</label>
                    <select name="pub_theme_sidebar_style" class="form-select">
                        <?php foreach (\App\PublicTheme::sidebarStyles() as $key => $label): ?>
                        <option value="<?= htmlspecialchars($key) ?>" <?= ($publicTheme->sidebar_style ?? 'card') === $key ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Footer alignment</label>
                    <select name="pub_theme_footer_align" class="form-select">
                        <?php foreach (\App\PublicTheme::footerAlignments() as $key => $label): ?>
                        <option value="<?= htmlspecialchars($key) ?>" <?= ($publicTheme->footer_align ?? 'split') === $key ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Article text align</label>
                    <select name="pub_theme_prose_align" class="form-select">
                        <?php foreach (\App\PublicTheme::proseAlignments() as $key => $label): ?>
                        <option value="<?= htmlspecialchars($key) ?>" <?= ($publicTheme->prose_align ?? 'left') === $key ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Logo size</label>
                    <select name="pub_theme_logo_size" class="form-select">
                        <?php foreach (\App\PublicTheme::logoSizes() as $key => $label): ?>
                        <option value="<?= htmlspecialchars($key) ?>" <?= ($publicTheme->logo_size ?? 'md') === $key ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Motion</label>
                    <select name="pub_theme_transition" class="form-select">
                        <?php foreach (\App\PublicTheme::transitionStyles() as $key => $label): ?>
                        <option value="<?= htmlspecialchars($key) ?>" <?= ($publicTheme->transition_style ?? 'subtle') === $key ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Focus ring</label>
                    <select name="pub_theme_focus" class="form-select">
                        <?php foreach (\App\PublicTheme::focusStyles() as $key => $label): ?>
                        <option value="<?= htmlspecialchars($key) ?>" <?= ($publicTheme->focus_style ?? 'accent') === $key ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <div class="form-check mt-2">
                        <input type="hidden" name="pub_theme_show_blog_search" value="0">
                        <input type="checkbox" class="form-check-input" name="pub_theme_show_blog_search" value="1" id="pubThemeBlogSearch"
                            <?= !empty($publicTheme->show_blog_search) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="pubThemeBlogSearch">Show blog search box</label>
                    </div>
                    <div class="form-check mt-2">
                        <input type="hidden" name="pub_theme_show_post_dates" value="0">
                        <input type="checkbox" class="form-check-input" name="pub_theme_show_post_dates" value="1" id="pubThemeDates"
                            <?= !empty($publicTheme->show_post_dates) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="pubThemeDates">Show post dates</label>
                    </div>
                    <div class="form-check mt-2">
                        <input type="hidden" name="pub_theme_show_list_featured" value="0">
                        <input type="checkbox" class="form-check-input" name="pub_theme_show_list_featured" value="1" id="pubThemeListFeat"
                            <?= !empty($publicTheme->show_list_featured) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="pubThemeListFeat">Show featured images on blog list</label>
                    </div>
                    <div class="form-check mt-2">
                        <input type="hidden" name="pub_theme_blog_show_excerpt" value="0">
                        <input type="checkbox" class="form-check-input" name="pub_theme_blog_show_excerpt" value="1" id="pubThemeBlogExcerpt"
                            <?= !empty($publicTheme->blog_show_excerpt) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="pubThemeBlogExcerpt">Show excerpts on blog list</label>
                    </div>
                    <div class="form-check mt-2">
                        <input type="hidden" name="pub_theme_blog_show_read_more" value="0">
                        <input type="checkbox" class="form-check-input" name="pub_theme_blog_show_read_more" value="1" id="pubThemeBlogMore"
                            <?= !empty($publicTheme->blog_show_read_more) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="pubThemeBlogMore">Show “Read more” links</label>
                    </div>
                    <div class="form-check mt-2">
                        <input type="hidden" name="pub_theme_blog_show_category" value="0">
                        <input type="checkbox" class="form-check-input" name="pub_theme_blog_show_category" value="1" id="pubThemeBlogCat"
                            <?= !empty($publicTheme->blog_show_category) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="pubThemeBlogCat">Show categories on blog list</label>
                    </div>
                    <div class="form-check mt-2">
                        <input type="hidden" name="pub_theme_blog_view_switcher" value="0">
                        <input type="checkbox" class="form-check-input" name="pub_theme_blog_view_switcher" value="1" id="pubThemeBlogSwitch"
                            <?= !empty($publicTheme->blog_view_switcher) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="pubThemeBlogSwitch">Allow visitors to switch list/grid view</label>
                    </div>
                </div>
                <div id="pubThemeCustomColors" class="col-12 pub-theme-custom-colors <?= ($publicTheme->preset ?? '') === 'custom' ? '' : 'd-none' ?>">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Custom background</label>
                            <input type="color" name="pub_theme_custom_bg" class="form-control form-control-color"
                                value="<?= htmlspecialchars($publicTheme->custom_bg ?? '#f8fafc') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Custom surface</label>
                            <input type="color" name="pub_theme_custom_surface" class="form-control form-control-color"
                                value="<?= htmlspecialchars($publicTheme->custom_surface ?? '#ffffff') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Custom text</label>
                            <input type="color" name="pub_theme_custom_text" class="form-control form-control-color"
                                value="<?= htmlspecialchars($publicTheme->custom_text ?? '#0f172a') ?>">
                        </div>
                    </div>
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold">Live preview</label>
                    <div class="pub-theme-preview">
                        <div id="pubThemePreview" class="pub-theme-preview-inner pub-theme-<?= htmlspecialchars($publicTheme->preset ?? 'default') ?> pub-font-<?= htmlspecialchars($publicTheme->font ?? 'system') ?> pub-radius-<?= htmlspecialchars($publicTheme->radius ?? 'md') ?> pub-header-<?= htmlspecialchars($publicTheme->header_style ?? 'solid') ?>">
                            <div class="pub-theme-preview-header">
                                <span><?= htmlspecialchars($branding->app_name ?? 'Simple CMS') ?></span>
                                <span class="pub-preview-link">Blog</span>
                            </div>
                            <div class="pub-theme-preview-card">
                                <strong>Sample page</strong>
                                <p class="mb-0 mt-1 text-muted small">Preview of colors, font, and corner radius.</p>
                                <span class="pub-preview-btn">Read more</span>
                            </div>
                        </div>
                    </div>
                    <div class="mt-3 d-flex flex-wrap gap-2 align-items-center">
                        <a href="<?= admin_url('customize') ?>" class="btn btn-primary btn-sm">Open Customizer</a>
                        <button type="button" class="btn btn-outline-primary btn-sm" id="pubThemePreviewSite">Preview on live site</button>
                        <small class="text-muted">Customizer: live iframe + Publish. Preview opens homepage with unsaved settings.</small>
                    </div>
                </div>
            </div>
            <hr class="my-4">
            <h6 class="mb-3">SEO &amp; LLM (site-wide defaults)</h6>
            <p class="text-muted small">Fallbacks for pages and posts without their own SEO fields. Per-page overrides remain in <strong>Pages</strong>.</p>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Title suffix</label>
                    <input type="text" name="seo_title_suffix" class="form-control" maxlength="100"
                        value="<?= htmlspecialchars($siteSeo->title_suffix ?? '') ?>" placeholder=" — My Company">
                    <small class="text-muted d-block mt-1">Appended to public page/post titles (e.g. in browser tab).</small>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Locale</label>
                    <input type="text" name="seo_locale" class="form-control" maxlength="10"
                        value="<?= htmlspecialchars($siteSeo->locale ?? 'en_US') ?>" placeholder="en_US">
                    <small class="text-muted d-block mt-1">Open Graph locale (e.g. <code>en_US</code>, <code>fil_PH</code>).</small>
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold" for="seoDefaultDescription">Default meta description</label>
                    <textarea name="seo_default_description" id="seoDefaultDescription" class="form-control" rows="2" maxlength="500"><?= htmlspecialchars($siteSeo->default_description ?? '') ?></textarea>
                    <small class="text-muted"><span id="seoDefaultDescriptionCount">0</span>/500 · Used when a page/post has no description.</small>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold" for="seoDefaultImage">Default share image (Open Graph)</label>
                    <select name="seo_default_image_id" id="seoDefaultImage" class="form-select">
                        <option value="">— Logo fallback —</option>
                        <?php foreach ($mediaImages as $m): ?>
                        <option value="<?= (int)$m->id ?>" <?= (int)($siteSeo->default_image_id ?? 0) === (int)$m->id ? 'selected' : '' ?>>
                            <?= htmlspecialchars($m->original_name) ?><?php if (!empty($m->width) && !empty($m->height)): ?> (<?= (int)$m->width ?>×<?= (int)$m->height ?>)<?php endif; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <small class="text-muted d-block mt-1">1200×630 recommended for Facebook/LinkedIn. <a href="<?= admin_url('media') ?>">Media library</a>.</small>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Twitter / X handle</label>
                    <div class="input-group">
                        <span class="input-group-text">@</span>
                        <input type="text" name="seo_twitter_handle" class="form-control" maxlength="15"
                            value="<?= htmlspecialchars($siteSeo->twitter_handle ?? '') ?>" placeholder="yourbrand">
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Google site verification</label>
                    <input type="text" name="seo_google_site_verification" class="form-control" maxlength="120"
                        value="<?= htmlspecialchars($siteSeo->google_site_verification ?? '') ?>" placeholder="meta tag content only">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Site keywords</label>
                    <input type="text" name="seo_site_keywords" class="form-control" maxlength="255"
                        value="<?= htmlspecialchars($siteSeo->site_keywords ?? '') ?>" placeholder="optional, comma-separated">
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold" for="llmSiteSummary">LLM site summary</label>
                    <textarea name="llm_site_summary" id="llmSiteSummary" class="form-control" rows="4" maxlength="2000"
                        placeholder="Plain-language overview of your site for AI crawlers and assistants"><?= htmlspecialchars($siteSeo->llm_site_summary ?? '') ?></textarea>
                    <small class="text-muted"><span id="llmSiteSummaryCount">0</span>/2000 · Used in <code>/llms.txt</code>, <code>/site.json</code>, and Schema.org.</small>
                </div>
                <div class="col-md-6">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" name="seo_enable_json_export" value="1" id="seoEnableJson"
                            <?= !empty($siteSeo->enable_json_export) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="seoEnableJson">Enable JSON exports (<code>/site.json</code>, <code>/blog.json</code>, page/post <code>.json</code>)</label>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" name="seo_enable_sitemap" value="1" id="seoEnableSitemap"
                            <?= !empty($siteSeo->enable_sitemap) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="seoEnableSitemap">Enable <code>/sitemap.xml</code></label>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" name="seo_enable_rss_feed" value="1" id="seoEnableRss"
                            <?= !empty($siteSeo->enable_rss_feed) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="seoEnableRss">Enable RSS feed (<code>/feed.xml</code>)</label>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" name="seo_enable_llms_txt" value="1" id="seoEnableLlms"
                            <?= !empty($siteSeo->enable_llms_txt) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="seoEnableLlms">Enable LLM index (<code>/llms.txt</code>, <code>/llms-full.txt</code>)</label>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" name="seo_allow_ai_crawlers" value="1" id="seoAllowAi"
                            <?= !empty($siteSeo->allow_ai_crawlers) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="seoAllowAi">Allow AI crawlers in <code>robots.txt</code> (GPTBot, ClaudeBot, Perplexity, and others)</label>
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Facebook page URL</label>
                    <input type="url" name="seo_facebook_url" class="form-control" maxlength="255"
                        value="<?= htmlspecialchars($siteSeo->facebook_url ?? '') ?>" placeholder="https://www.facebook.com/yourpage">
                    <small class="text-muted d-block mt-1">Used in Schema.org <code>sameAs</code> for organization markup.</small>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">LinkedIn URL</label>
                    <input type="url" name="seo_linkedin_url" class="form-control" maxlength="255"
                        value="<?= htmlspecialchars($siteSeo->linkedin_url ?? '') ?>" placeholder="https://www.linkedin.com/company/yourorg">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Reddit community URL</label>
                    <input type="url" name="seo_reddit_url" class="form-control" maxlength="255"
                        value="<?= htmlspecialchars($siteSeo->reddit_url ?? '') ?>" placeholder="https://www.reddit.com/r/yourcommunity">
                    <small class="text-muted d-block mt-1">Third-party authority signal for Schema.org <code>sameAs</code> (AI systems often cite Reddit/LinkedIn).</small>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">YouTube channel URL</label>
                    <input type="url" name="seo_youtube_url" class="form-control" maxlength="255"
                        value="<?= htmlspecialchars($siteSeo->youtube_url ?? '') ?>" placeholder="https://www.youtube.com/@yourbrand">
                </div>
            </div>
            <hr class="my-4">
            <h6 class="mb-3">AI search &amp; citation</h6>
            <p class="text-muted small mb-3">
                Helps AI search (ChatGPT Search, Perplexity, Google AI Overviews) extract passage-level answers and cite your site with clear E-E-A-T signals.
                Pair with per-page/post <strong>citation snippet (BLUF)</strong> and optional FAQ pairs.
            </p>
            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label fw-semibold" for="seoPublisherExpertise">Publisher expertise (E-E-A-T)</label>
                    <textarea name="seo_publisher_expertise" id="seoPublisherExpertise" class="form-control" rows="3" maxlength="1000"
                        placeholder="Who you are, firsthand experience, credentials, and what original reporting or case studies this site publishes"><?= htmlspecialchars($siteSeo->publisher_expertise ?? '') ?></textarea>
                    <small class="text-muted"><span id="seoPublisherExpertiseCount">0</span>/1000 · Used in Schema.org Organization and <code>/llms.txt</code>.</small>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold" for="seoPreferredCitation">Preferred citation</label>
                    <input type="text" name="seo_preferred_citation" id="seoPreferredCitation" class="form-control" maxlength="500"
                        value="<?= htmlspecialchars($siteSeo->preferred_citation ?? '') ?>"
                        placeholder="e.g. Cite as: Site Name (Year). Article title. URL">
                    <small class="text-muted"><span id="seoPreferredCitationCount">0</span>/500 · Short how-to-cite line for assistants.</small>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold" for="seoCitationGuidance">Citation guidance</label>
                    <textarea name="seo_citation_guidance" id="seoCitationGuidance" class="form-control" rows="2" maxlength="1000"
                        placeholder="Optional policy for AI: prefer citing primary pages, quote citation snippets, attribute authors"><?= htmlspecialchars($siteSeo->citation_guidance ?? '') ?></textarea>
                    <small class="text-muted"><span id="seoCitationGuidanceCount">0</span>/1000 · Appears in <code>/llms.txt</code> and <code>/site.json</code>.</small>
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold" for="seoPillarTopics">Topic clusters / pillar subjects</label>
                    <textarea name="seo_pillar_topics" id="seoPillarTopics" class="form-control" rows="3" maxlength="2000"
                        placeholder="One subject per line (pillar pages and related clusters). Example:&#10;CMS SEO for AI search&#10;Backup and restore&#10;Public theme customization"><?= htmlspecialchars($siteSeo->pillar_topics ?? '') ?></textarea>
                    <small class="text-muted"><span id="seoPillarTopicsCount">0</span>/2000 · Signals comprehensive topic coverage to LLM indexes.</small>
                </div>
                <div class="col-md-4">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" name="seo_enable_faq_schema" value="1" id="seoEnableFaq"
                            <?= !empty($siteSeo->enable_faq_schema) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="seoEnableFaq">Emit FAQPage Schema.org when a page/post has FAQ pairs</label>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" name="seo_enable_speakable" value="1" id="seoEnableSpeakable"
                            <?= !empty($siteSeo->enable_speakable) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="seoEnableSpeakable">Mark citation snippets as speakable / answer passages</label>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" name="seo_show_ai_writing_tips" value="1" id="seoShowAiTips"
                            <?= !empty($siteSeo->show_ai_writing_tips) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="seoShowAiTips">Show AI writing tips on page/post editors (structure, BLUF, Q&amp;A)</label>
                    </div>
                </div>
            </div>
            <hr class="my-4">
            <h6 class="mb-3">System general options</h6>
            <div class="mb-3">
                <label for="region" class="form-label">Region</label>
                <select class="form-select" name="region" id="region">
                    <?php foreach ($regions as $value => $label): ?>
                    <option value="<?= htmlspecialchars($value) ?>" <?= ($settings['region'] ?? '') === $value ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                    <?php endforeach; ?>
                </select>
                <div class="form-text">Optional. Geographic region for the application.</div>
            </div>
            <div class="mb-3">
                <label for="timezone" class="form-label">Timezone</label>
                <select class="form-select" name="timezone" id="timezone">
                    <?php foreach ($timezones as $tz): ?>
                    <option value="<?= htmlspecialchars($tz) ?>" <?= ($settings['timezone'] ?? 'UTC') === $tz ? 'selected' : '' ?>><?= htmlspecialchars($tz) ?></option>
                    <?php endforeach; ?>
                </select>
                <div class="form-text">Organization timezone for Activity History, audit timestamps, and PHP/MySQL session time. Status <strong>effective dates</strong> and Date Recorded stay as entered (no conversion). The header clock shows your local device time.</div>
            </div>
            <button type="submit" class="btn btn-primary">Save</button>
        </form>
    </div>
</div>
<script src="/public/assets/js/general/seo-form.js"></script>
<link href="/public/assets/css/public/themes.css" rel="stylesheet">
<script src="/public/assets/js/general/theme-customizer.js"></script>

<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">Ask Help (in-app assistant)</h5>
    </div>
    <div class="card-body">
        <p class="text-muted small mb-4">
            Shows a floating <strong>Ask Help</strong> button for signed-in users. Answers are based on the in-app Help guide for the current screen (user-friendly language).
            Without an API key, the app matches questions to help text locally. With an OpenAI-compatible API key, answers use that model and still stay grounded in help content.
        </p>
        <form method="post" action="<?= admin_url('system/general/help-chat') ?>">
            <?= \Core\Csrf::field() ?>
            <div class="form-check form-switch mb-3">
                <input type="checkbox" class="form-check-input" role="switch" id="helpChatEnabled" name="help_chat_enabled" value="1"
                    <?= !empty($helpChat->enabled) ? 'checked' : '' ?>>
                <label class="form-check-label" for="helpChatEnabled">Enable Ask Help widget</label>
            </div>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold" for="helpChatApiBase">API base URL</label>
                    <input type="url" class="form-control" id="helpChatApiBase" name="help_chat_api_base"
                        value="<?= htmlspecialchars($helpChat->api_base ?? 'https://api.openai.com/v1') ?>"
                        placeholder="https://api.openai.com/v1">
                    <small class="text-muted d-block mt-1">OpenAI-compatible <code>/chat/completions</code> endpoint base (no trailing slash required).</small>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold" for="helpChatModel">Model</label>
                    <input type="text" class="form-control" id="helpChatModel" name="help_chat_model"
                        value="<?= htmlspecialchars($helpChat->model ?? 'gpt-4o-mini') ?>" maxlength="120">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold" for="helpChatApiKey">API key</label>
                    <input type="password" class="form-control" id="helpChatApiKey" name="help_chat_api_key" value=""
                        placeholder="<?= !empty($helpChat->api_key_set) ? '•••••••• (leave blank to keep)' : 'Optional — leave blank for local help matching' ?>"
                        autocomplete="new-password">
                    <div class="form-check mt-2">
                        <input type="checkbox" class="form-check-input" id="helpChatClearKey" name="help_chat_clear_api_key" value="1">
                        <label class="form-check-label" for="helpChatClearKey">Clear stored API key (use local matching only)</label>
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold" for="helpChatMaxPerHour">Max questions per user / hour</label>
                    <input type="number" class="form-control" id="helpChatMaxPerHour" name="help_chat_max_per_hour"
                        value="<?= (int) ($helpChat->max_per_hour ?? 30) ?>" min="5" max="200">
                    <small class="text-muted d-block mt-1">Rate limit to protect cost and abuse. Current mode: <strong><?= htmlspecialchars($helpChat->mode ?? 'local') ?></strong>.</small>
                </div>
            </div>
            <button type="submit" class="btn btn-primary mt-4">Save Ask Help</button>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
$pageTitle = 'General';
$currentPage = 'general';
require __DIR__ . '/../layout/main.php';
