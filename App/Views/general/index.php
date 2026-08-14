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
                        <option value="<?= htmlspecialchars($key) ?>" <?= ($publicTheme->content_width ?? 'narrow') === $key ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
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
                    <small class="text-muted d-block mt-1">Visitors can still override with the header toggle when enabled.</small>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Header style</label>
                    <select name="pub_theme_header" class="form-select">
                        <?php foreach (\App\PublicTheme::headerStyles() as $key => $label): ?>
                        <option value="<?= htmlspecialchars($key) ?>" <?= ($publicTheme->header_style ?? 'solid') === $key ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <div class="form-check mt-2">
                        <input type="checkbox" class="form-check-input" name="pub_theme_show_toggle" value="1" id="pubThemeShowToggle"
                            <?= !empty($publicTheme->show_color_toggle) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="pubThemeShowToggle">Show light/dark toggle in public header</label>
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
                        <button type="button" class="btn btn-outline-primary btn-sm" id="pubThemePreviewSite">Preview on live site</button>
                        <small class="text-muted">Opens the homepage with unsaved theme settings (admin only).</small>
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
