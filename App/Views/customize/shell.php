<?php
/** @var object $theme */
/** @var object $branding */
/** @var string $previewUrl */
/** @var string $syncUrl */
/** @var string $publishUrl */
/** @var string $closeUrl */
/** @var string $returnUrl */
/** @var array $bridgeConfig */
$appName = htmlspecialchars($branding->app_name ?? 'Simple CMS');
$themePresets = \App\PublicTheme::presets();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Customize — <?= $appName ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/public/assets/css/admin/customizer.css" rel="stylesheet">
</head>
<body class="cms-customizer-app">
<header class="cms-customizer-topbar">
    <div class="cms-customizer-topbar-left">
        <form method="post" action="<?= htmlspecialchars($closeUrl) ?>" class="d-inline mb-0">
            <?= \Core\Csrf::field() ?>
            <button type="submit" class="btn btn-sm btn-outline-light">← Close</button>
        </form>
        <strong class="cms-customizer-title">Customize</strong>
        <span class="badge text-bg-secondary">Public theme</span>
    </div>
    <div class="cms-customizer-topbar-actions">
        <div class="cms-customizer-device-toggle" role="group" aria-label="Preview page">
            <button type="button" class="btn btn-sm btn-outline-light is-active" data-preview-path="/" title="Homepage">Home</button>
            <button type="button" class="btn btn-sm btn-outline-light" data-preview-path="/blog" title="Blog">Blog</button>
        </div>
        <span class="cms-customizer-status small" id="cmsCustomizerStatus" aria-live="polite"></span>
        <button type="button" class="btn btn-sm btn-success" id="cmsCustomizerPublish">Publish</button>
    </div>
</header>

<div class="cms-customizer-shell">
    <aside class="cms-customizer-sidebar" aria-label="Theme settings">
            <section class="cms-customizer-section" id="cmsStylePackSection"
                data-import-url="<?= htmlspecialchars($importStylePackUrl ?? '') ?>"
                data-export-url="<?= htmlspecialchars($exportStylePackUrl ?? '') ?>"
                data-clear-url="<?= htmlspecialchars($clearStylePackUrl ?? '') ?>">
                <h2 class="cms-customizer-section-title">Style packs</h2>
                <p class="small text-muted mb-2">Upload packs into your library, then <strong>Activate</strong> the one you want. CMS <code>cms-theme.json</code> or WordPress zip (colors only).</p>
                <?php if (!empty($stylePackFlash) && is_array($stylePackFlash)): ?>
                <div class="alert alert-<?= !empty($stylePackFlash['ok']) ? 'success' : 'danger' ?> py-2 small" id="cmsStylePackFlash">
                    <?= htmlspecialchars((string) ($stylePackFlash['message'] ?? '')) ?>
                </div>
                <?php endif; ?>
                <?php $returnTarget = 'customize'; require __DIR__ . '/../partials/theme_style_pack_library.php'; ?>
                <div class="mb-2">
                    <label class="form-label small" for="cmsStylePackFile">Upload zip to library</label>
                    <input type="file" id="cmsStylePackFile" class="form-control form-control-sm" accept=".zip,application/zip">
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <button type="button" class="btn btn-sm btn-primary" id="cmsStylePackImport">Upload &amp; activate</button>
                    <a class="btn btn-sm btn-outline-secondary" href="<?= htmlspecialchars($exportStylePackUrl ?? '#') ?>">Export current</a>
                    <a class="btn btn-sm btn-outline-secondary" href="<?= htmlspecialchars($sampleStylePackUrl ?? '#') ?>">Download template zip</a>
                    <button type="button" class="btn btn-sm btn-outline-danger" id="cmsStylePackClear">Clear active</button>
                </div>
                <p class="small text-muted mt-2 mb-0" id="cmsStylePackMsg" aria-live="polite"></p>
            </section>
        <form id="cmsCustomizerForm" class="cms-customizer-form">
            <?= \Core\Csrf::field() ?>
            <section class="cms-customizer-section">
                <h2 class="cms-customizer-section-title">Colors</h2>
                <label class="form-label">Color preset</label>
                <div class="cms-customizer-swatches">
                    <?php foreach ($themePresets as $key => $label): ?>
                    <label title="<?= htmlspecialchars($label) ?>">
                        <input type="radio" name="pub_theme_preset" value="<?= htmlspecialchars($key) ?>"
                            <?= ($theme->preset ?? 'default') === $key ? 'checked' : '' ?>>
                        <span class="cms-customizer-swatch cms-customizer-swatch-<?= htmlspecialchars($key) ?>"></span>
                        <span class="visually-hidden"><?= htmlspecialchars($label) ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>
                <label class="form-label mt-3">Accent color</label>
                <input type="color" name="public_accent_color" class="form-control form-control-color"
                    value="<?= htmlspecialchars($theme->accent_color ?? '#2563eb') ?>">
                <div id="cmsCustomizerCustomColors" class="mt-3 <?= ($theme->preset ?? '') === 'custom' ? '' : 'd-none' ?>">
                    <label class="form-label">Custom background</label>
                    <input type="color" name="pub_theme_custom_bg" class="form-control form-control-color"
                        value="<?= htmlspecialchars($theme->custom_bg ?? '#f8fafc') ?>">
                    <label class="form-label mt-2">Custom surface</label>
                    <input type="color" name="pub_theme_custom_surface" class="form-control form-control-color"
                        value="<?= htmlspecialchars($theme->custom_surface ?? '#ffffff') ?>">
                    <label class="form-label mt-2">Custom text</label>
                    <input type="color" name="pub_theme_custom_text" class="form-control form-control-color"
                        value="<?= htmlspecialchars($theme->custom_text ?? '#0f172a') ?>">
                </div>
            </section>

            <section class="cms-customizer-section">
                <h2 class="cms-customizer-section-title">Editorial magazine</h2>
                <p class="small text-muted mb-2">Reusable magazine chrome: kicker heading, readable dates, and tagline.</p>
                <div class="form-check mb-2">
                    <input type="hidden" name="pub_theme_apply_editorial_pack" value="0">
                    <input type="checkbox" class="form-check-input" name="pub_theme_apply_editorial_pack" value="1" id="cmsCustEditorialPack">
                    <label class="form-check-label" for="cmsCustEditorialPack">Apply editorial style pack (preset, magazine layout, chrome, kicker)</label>
                </div>
                <label class="form-label">Site chrome</label>
                <select name="pub_theme_chrome" class="form-select form-select-sm">
                    <?php foreach (\App\PublicTheme::chromeStyles() as $key => $label): ?>
                    <option value="<?= htmlspecialchars($key) ?>" <?= ($theme->chrome ?? 'default') === $key ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                    <?php endforeach; ?>
                </select>
                <label class="form-label mt-2">Blog section kicker</label>
                <input type="text" name="pub_theme_blog_kicker" class="form-control form-control-sm" maxlength="80"
                    value="<?= htmlspecialchars($theme->blog_kicker ?? '') ?>" placeholder="HERE'S WHAT'S NEW">
                <label class="form-label mt-2">Public date format</label>
                <select name="pub_theme_date_format" class="form-select form-select-sm">
                    <?php foreach (\App\PublicTheme::dateFormats() as $key => $label): ?>
                    <option value="<?= htmlspecialchars($key) ?>" <?= ($theme->date_format ?? 'human') === $key ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                    <?php endforeach; ?>
                </select>
                <div class="form-check mt-2">
                    <input type="hidden" name="pub_theme_show_site_tagline" value="0">
                    <input type="checkbox" class="form-check-input" name="pub_theme_show_site_tagline" value="1" id="cmsCustTagline"
                        <?= !empty($theme->show_site_tagline) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="cmsCustTagline">Show company name as header tagline</label>
                </div>
            </section>

            <section class="cms-customizer-section">
                <h2 class="cms-customizer-section-title">Typography &amp; shape</h2>
                <label class="form-label">Typography</label>
                <select name="pub_theme_font" class="form-select form-select-sm">
                    <?php foreach (\App\PublicTheme::fonts() as $key => $label): ?>
                    <option value="<?= htmlspecialchars($key) ?>" <?= ($theme->font ?? 'system') === $key ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                    <?php endforeach; ?>
                </select>
                <label class="form-label mt-2">Base font size</label>
                <select name="pub_theme_font_size" class="form-select form-select-sm">
                    <?php foreach (\App\PublicTheme::fontSizes() as $key => $label): ?>
                    <option value="<?= htmlspecialchars($key) ?>" <?= ($theme->font_size ?? 'md') === $key ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                    <?php endforeach; ?>
                </select>
                <label class="form-label mt-2">Line height</label>
                <select name="pub_theme_line_height" class="form-select form-select-sm">
                    <?php foreach (\App\PublicTheme::lineHeights() as $key => $label): ?>
                    <option value="<?= htmlspecialchars($key) ?>" <?= ($theme->line_height ?? 'normal') === $key ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                    <?php endforeach; ?>
                </select>
                <label class="form-label mt-2">Corner radius</label>
                <select name="pub_theme_radius" class="form-select form-select-sm">
                    <?php foreach (\App\PublicTheme::radii() as $key => $label): ?>
                    <option value="<?= htmlspecialchars($key) ?>" <?= ($theme->radius ?? 'md') === $key ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                    <?php endforeach; ?>
                </select>
                <label class="form-label mt-2">Header style</label>
                <select name="pub_theme_header" class="form-select form-select-sm">
                    <?php foreach (\App\PublicTheme::headerStyles() as $key => $label): ?>
                    <option value="<?= htmlspecialchars($key) ?>" <?= ($theme->header_style ?? 'solid') === $key ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                    <?php endforeach; ?>
                </select>
                <label class="form-label mt-2">Footer style</label>
                <select name="pub_theme_footer_style" class="form-select form-select-sm">
                    <?php foreach (\App\PublicTheme::footerStyles() as $key => $label): ?>
                    <option value="<?= htmlspecialchars($key) ?>" <?= ($theme->footer_style ?? 'solid') === $key ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </section>

            <section class="cms-customizer-section">
                <h2 class="cms-customizer-section-title">Layout</h2>
                <label class="form-label">Content width</label>
                <select name="pub_theme_width" class="form-select form-select-sm">
                    <?php foreach (\App\PublicTheme::contentWidths() as $key => $label): ?>
                    <option value="<?= htmlspecialchars($key) ?>" <?= ($theme->content_width ?? 'full') === $key ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                    <?php endforeach; ?>
                </select>
                <label class="form-label mt-2">Blog listing width</label>
                <select name="pub_theme_blog_width" class="form-select form-select-sm">
                    <?php foreach (\App\PublicTheme::blogContentWidths() as $key => $label): ?>
                    <option value="<?= htmlspecialchars($key) ?>" <?= ($theme->blog_content_width ?? 'inherit') === $key ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                    <?php endforeach; ?>
                </select>
                <label class="form-label mt-2">Content spacing</label>
                <select name="pub_theme_content_spacing" class="form-select form-select-sm">
                    <?php foreach (\App\PublicTheme::contentSpacings() as $key => $label): ?>
                    <option value="<?= htmlspecialchars($key) ?>" <?= ($theme->content_spacing ?? 'comfortable') === $key ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                    <?php endforeach; ?>
                </select>
                <label class="form-label mt-2">Color mode</label>
                <select name="pub_theme_color_mode" class="form-select form-select-sm">
                    <?php foreach (\App\PublicTheme::colorModes() as $key => $label): ?>
                    <option value="<?= htmlspecialchars($key) ?>" <?= ($theme->default_color_mode ?? 'system') === $key ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                    <?php endforeach; ?>
                </select>
                <p class="small text-muted mt-2 mb-0">Visitors cannot change color mode; Publish applies it site-wide.</p>
            </section>

            <section class="cms-customizer-section">
                <h2 class="cms-customizer-section-title">Components</h2>
                <label class="form-label">Button style</label>
                <select name="pub_theme_button_style" class="form-select form-select-sm">
                    <?php foreach (\App\PublicTheme::buttonStyles() as $key => $label): ?>
                    <option value="<?= htmlspecialchars($key) ?>" <?= ($theme->button_style ?? 'solid') === $key ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                    <?php endforeach; ?>
                </select>
                <label class="form-label mt-2">Card shadow</label>
                <select name="pub_theme_card_shadow" class="form-select form-select-sm">
                    <?php foreach (\App\PublicTheme::cardShadows() as $key => $label): ?>
                    <option value="<?= htmlspecialchars($key) ?>" <?= ($theme->card_shadow ?? 'soft') === $key ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                    <?php endforeach; ?>
                </select>
                <label class="form-label mt-2">Link style</label>
                <select name="pub_theme_link_style" class="form-select form-select-sm">
                    <?php foreach (\App\PublicTheme::linkStyles() as $key => $label): ?>
                    <option value="<?= htmlspecialchars($key) ?>" <?= ($theme->link_style ?? 'accent') === $key ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                    <?php endforeach; ?>
                </select>
                <label class="form-label mt-2">Navigation style</label>
                <select name="pub_theme_nav_style" class="form-select form-select-sm">
                    <?php foreach (\App\PublicTheme::navStyles() as $key => $label): ?>
                    <option value="<?= htmlspecialchars($key) ?>" <?= ($theme->nav_style ?? 'inline') === $key ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                    <?php endforeach; ?>
                </select>
                <label class="form-label mt-2">Brand weight</label>
                <select name="pub_theme_brand_weight" class="form-select form-select-sm">
                    <?php foreach (\App\PublicTheme::brandWeights() as $key => $label): ?>
                    <option value="<?= htmlspecialchars($key) ?>" <?= ($theme->brand_weight ?? 'bold') === $key ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                    <?php endforeach; ?>
                </select>
                <label class="form-label mt-2">Heading scale</label>
                <select name="pub_theme_heading_scale" class="form-select form-select-sm">
                    <?php foreach (\App\PublicTheme::headingScales() as $key => $label): ?>
                    <option value="<?= htmlspecialchars($key) ?>" <?= ($theme->heading_scale ?? 'normal') === $key ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                    <?php endforeach; ?>
                </select>
                <label class="form-label mt-2">Blog list style</label>
                <select name="pub_theme_blog_list_style" class="form-select form-select-sm">
                    <?php foreach (\App\PublicTheme::blogListStyles() as $key => $label): ?>
                    <option value="<?= htmlspecialchars($key) ?>" <?= ($theme->blog_list_style ?? 'list') === $key ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                    <?php endforeach; ?>
                </select>
                <label class="form-label mt-2">Blog grid columns</label>
                <select name="pub_theme_blog_grid_columns" class="form-select form-select-sm">
                    <?php foreach (\App\PublicTheme::blogGridColumns() as $key => $label): ?>
                    <option value="<?= htmlspecialchars($key) ?>" <?= ($theme->blog_grid_columns ?? '3') === $key ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                    <?php endforeach; ?>
                </select>
                <label class="form-label mt-2">Blog image ratio</label>
                <select name="pub_theme_blog_image_ratio" class="form-select form-select-sm">
                    <?php foreach (\App\PublicTheme::blogImageRatios() as $key => $label): ?>
                    <option value="<?= htmlspecialchars($key) ?>" <?= ($theme->blog_image_ratio ?? 'landscape') === $key ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                    <?php endforeach; ?>
                </select>
                <label class="form-label mt-2">Featured image style</label>
                <select name="pub_theme_image_style" class="form-select form-select-sm">
                    <?php foreach (\App\PublicTheme::imageStyles() as $key => $label): ?>
                    <option value="<?= htmlspecialchars($key) ?>" <?= ($theme->image_style ?? 'soft') === $key ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                    <?php endforeach; ?>
                </select>
                <label class="form-label mt-2">Card / article borders</label>
                <select name="pub_theme_border_style" class="form-select form-select-sm">
                    <?php foreach (\App\PublicTheme::borderStyles() as $key => $label): ?>
                    <option value="<?= htmlspecialchars($key) ?>" <?= ($theme->border_style ?? 'subtle') === $key ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                    <?php endforeach; ?>
                </select>
                <label class="form-label mt-2">Header height</label>
                <select name="pub_theme_header_height" class="form-select form-select-sm">
                    <?php foreach (\App\PublicTheme::headerHeights() as $key => $label): ?>
                    <option value="<?= htmlspecialchars($key) ?>" <?= ($theme->header_height ?? 'comfortable') === $key ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                    <?php endforeach; ?>
                </select>
                <div class="form-check mt-3">
                    <input type="hidden" name="pub_theme_sticky_header" value="0">
                    <input type="checkbox" class="form-check-input" name="pub_theme_sticky_header" value="1" id="cmsCustSticky"
                        <?= !empty($theme->sticky_header) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="cmsCustSticky">Sticky header</label>
                </div>
                <div class="form-check mt-2">
                    <input type="hidden" name="pub_theme_show_admin_link" value="0">
                    <input type="checkbox" class="form-check-input" name="pub_theme_show_admin_link" value="1" id="cmsCustAdminLink"
                        <?= !empty($theme->show_admin_link) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="cmsCustAdminLink">Show Admin login links</label>
                </div>
                <div class="form-check mt-2">
                    <input type="hidden" name="pub_theme_show_site_title" value="0">
                    <input type="checkbox" class="form-check-input" name="pub_theme_show_site_title" value="1" id="cmsCustSiteTitle"
                        <?= !empty($theme->show_site_title) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="cmsCustSiteTitle">Show site title next to logo</label>
                </div>
                <div class="form-check mt-2">
                    <input type="hidden" name="pub_theme_show_breadcrumbs" value="0">
                    <input type="checkbox" class="form-check-input" name="pub_theme_show_breadcrumbs" value="1" id="cmsCustCrumbs"
                        <?= !empty($theme->show_breadcrumbs) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="cmsCustCrumbs">Show breadcrumbs</label>
                </div>
                <div class="form-check mt-2">
                    <input type="hidden" name="pub_theme_nav_uppercase" value="0">
                    <input type="checkbox" class="form-check-input" name="pub_theme_nav_uppercase" value="1" id="cmsCustNavUpper"
                        <?= !empty($theme->nav_uppercase) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="cmsCustNavUpper">Uppercase navigation</label>
                </div>
            </section>

            <section class="cms-customizer-section">
                <h2 class="cms-customizer-section-title">More layout</h2>
                <label class="form-label">Button size</label>
                <select name="pub_theme_button_size" class="form-select form-select-sm">
                    <?php foreach (\App\PublicTheme::buttonSizes() as $key => $label): ?>
                    <option value="<?= htmlspecialchars($key) ?>" <?= ($theme->button_size ?? 'md') === $key ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                    <?php endforeach; ?>
                </select>
                <label class="form-label mt-2">Sidebar style</label>
                <select name="pub_theme_sidebar_style" class="form-select form-select-sm">
                    <?php foreach (\App\PublicTheme::sidebarStyles() as $key => $label): ?>
                    <option value="<?= htmlspecialchars($key) ?>" <?= ($theme->sidebar_style ?? 'card') === $key ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                    <?php endforeach; ?>
                </select>
                <label class="form-label mt-2">Footer alignment</label>
                <select name="pub_theme_footer_align" class="form-select form-select-sm">
                    <?php foreach (\App\PublicTheme::footerAlignments() as $key => $label): ?>
                    <option value="<?= htmlspecialchars($key) ?>" <?= ($theme->footer_align ?? 'split') === $key ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                    <?php endforeach; ?>
                </select>
                <label class="form-label mt-2">Article text align</label>
                <select name="pub_theme_prose_align" class="form-select form-select-sm">
                    <?php foreach (\App\PublicTheme::proseAlignments() as $key => $label): ?>
                    <option value="<?= htmlspecialchars($key) ?>" <?= ($theme->prose_align ?? 'left') === $key ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                    <?php endforeach; ?>
                </select>
                <label class="form-label mt-2">Logo size</label>
                <select name="pub_theme_logo_size" class="form-select form-select-sm">
                    <?php foreach (\App\PublicTheme::logoSizes() as $key => $label): ?>
                    <option value="<?= htmlspecialchars($key) ?>" <?= ($theme->logo_size ?? 'md') === $key ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                    <?php endforeach; ?>
                </select>
                <label class="form-label mt-2">Motion</label>
                <select name="pub_theme_transition" class="form-select form-select-sm">
                    <?php foreach (\App\PublicTheme::transitionStyles() as $key => $label): ?>
                    <option value="<?= htmlspecialchars($key) ?>" <?= ($theme->transition_style ?? 'subtle') === $key ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                    <?php endforeach; ?>
                </select>
                <label class="form-label mt-2">Focus ring</label>
                <select name="pub_theme_focus" class="form-select form-select-sm">
                    <?php foreach (\App\PublicTheme::focusStyles() as $key => $label): ?>
                    <option value="<?= htmlspecialchars($key) ?>" <?= ($theme->focus_style ?? 'accent') === $key ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                    <?php endforeach; ?>
                </select>
                <div class="form-check mt-3">
                    <input type="hidden" name="pub_theme_show_blog_search" value="0">
                    <input type="checkbox" class="form-check-input" name="pub_theme_show_blog_search" value="1" id="cmsCustBlogSearch"
                        <?= !empty($theme->show_blog_search) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="cmsCustBlogSearch">Show blog search box</label>
                </div>
                <div class="form-check mt-2">
                    <input type="hidden" name="pub_theme_show_post_dates" value="0">
                    <input type="checkbox" class="form-check-input" name="pub_theme_show_post_dates" value="1" id="cmsCustDates"
                        <?= !empty($theme->show_post_dates) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="cmsCustDates">Show post dates</label>
                </div>
                <div class="form-check mt-2">
                    <input type="hidden" name="pub_theme_show_list_featured" value="0">
                    <input type="checkbox" class="form-check-input" name="pub_theme_show_list_featured" value="1" id="cmsCustListFeat"
                        <?= !empty($theme->show_list_featured) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="cmsCustListFeat">Show featured images on blog list</label>
                </div>
                <div class="form-check mt-2">
                    <input type="hidden" name="pub_theme_blog_show_excerpt" value="0">
                    <input type="checkbox" class="form-check-input" name="pub_theme_blog_show_excerpt" value="1" id="cmsCustBlogExcerpt"
                        <?= !empty($theme->blog_show_excerpt) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="cmsCustBlogExcerpt">Show excerpts on blog list</label>
                </div>
                <div class="form-check mt-2">
                    <input type="hidden" name="pub_theme_blog_show_read_more" value="0">
                    <input type="checkbox" class="form-check-input" name="pub_theme_blog_show_read_more" value="1" id="cmsCustBlogMore"
                        <?= !empty($theme->blog_show_read_more) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="cmsCustBlogMore">Show “Read more” links</label>
                </div>
                <div class="form-check mt-2">
                    <input type="hidden" name="pub_theme_blog_show_category" value="0">
                    <input type="checkbox" class="form-check-input" name="pub_theme_blog_show_category" value="1" id="cmsCustBlogCat"
                        <?= !empty($theme->blog_show_category) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="cmsCustBlogCat">Show categories on blog list</label>
                </div>
                <div class="form-check mt-2">
                    <input type="hidden" name="pub_theme_blog_view_switcher" value="0">
                    <input type="checkbox" class="form-check-input" name="pub_theme_blog_view_switcher" value="1" id="cmsCustBlogSwitch"
                        <?= !empty($theme->blog_view_switcher) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="cmsCustBlogSwitch">Allow visitors to switch list/grid view</label>
                </div>
            </section>
        </form>
    </aside>
    <main class="cms-customizer-preview-wrap">
        <iframe id="cmsCustomizerFrame"
            class="cms-customizer-frame"
            title="Live site preview"
            src="<?= htmlspecialchars($previewUrl) ?>"></iframe>
    </main>
</div>

<div id="cmsCustomizerConfig"
    class="d-none"
    data-sync-url="<?= htmlspecialchars($syncUrl) ?>"
    data-publish-url="<?= htmlspecialchars($publishUrl) ?>"
    data-return-url="<?= htmlspecialchars($returnUrl) ?>"
    data-csrf="<?= htmlspecialchars(\Core\Csrf::token()) ?>"
    data-bridge="<?= htmlspecialchars(json_encode($bridgeConfig, JSON_UNESCAPED_UNICODE)) ?>"></div>
<script src="/public/assets/js/customize/customizer.js"></script>
<script src="/public/assets/js/customize/style-pack.js"></script>
</body>
</html>
