<?php
/** @var object $entity */
/** @var string $entityType page|post */
/** @var string $layoutJson */
/** @var string $moduleTypesJson */
/** @var string $mediaJson */
/** @var string $backUrl */
/** @var string $saveUrl */
/** @var string $previewUrl */
/** @var bool $canUploadMedia */
/** @var string $uploadUrl */
$title = htmlspecialchars((string) ($entity->title ?? 'Untitled'));
$branding = \App\Models\AppSettings::getBrandingConfig();
$appName = htmlspecialchars($branding->app_name ?? 'Simple CMS');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Visual Builder — <?= $title ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/public/assets/css/public/themes.css" rel="stylesheet">
    <link href="/public/assets/css/public/site.css" rel="stylesheet">
    <link href="/public/assets/css/admin/builder.css" rel="stylesheet">
</head>
<body class="cms-builder-app">
<header class="cms-builder-toolbar">
    <div class="cms-builder-toolbar-left">
        <a href="<?= htmlspecialchars($backUrl) ?>" class="btn btn-sm btn-outline-light">← Back</a>
        <strong class="cms-builder-title"><?= $title ?></strong>
        <span class="badge text-bg-secondary"><?= $entityType === 'page' ? 'Page' : 'Post' ?></span>
    </div>
    <div class="cms-builder-toolbar-actions">
        <div class="cms-builder-device-toggle" role="group" aria-label="Preview width">
            <button type="button" class="btn btn-sm btn-outline-light is-active" data-device="desktop" title="Desktop">Desktop</button>
            <button type="button" class="btn btn-sm btn-outline-light" data-device="tablet" title="Tablet">Tablet</button>
            <button type="button" class="btn btn-sm btn-outline-light" data-device="mobile" title="Mobile">Mobile</button>
        </div>
        <button type="button" class="btn btn-sm btn-outline-light" id="cmsBuilderAddSection">+ Section</button>
        <div class="dropdown">
            <button type="button" class="btn btn-sm btn-outline-light dropdown-toggle" id="cmsBuilderTemplatesBtn" data-bs-toggle="dropdown" aria-expanded="false">Templates</button>
            <ul class="dropdown-menu dropdown-menu-end" id="cmsBuilderTemplatesMenu">
                <li><h6 class="dropdown-header">Load template</h6></li>
                <li id="cmsBuilderTemplatesEmpty" class="dropdown-item-text small text-muted">No saved templates</li>
                <li><hr class="dropdown-divider"></li>
                <li><button type="button" class="dropdown-item" id="cmsBuilderSaveTemplate">Save current as template…</button></li>
            </ul>
        </div>
        <?php if ($previewUrl !== ''): ?>
        <a href="<?= htmlspecialchars($previewUrl) ?>" class="btn btn-sm btn-outline-light" target="_blank" rel="noopener">Preview</a>
        <?php endif; ?>
        <button type="button" class="btn btn-sm btn-success" id="cmsBuilderSave">Save</button>
        <span class="cms-builder-status small" id="cmsBuilderStatus" aria-live="polite"></span>
    </div>
</header>

<div class="cms-builder-shell">
    <main class="cms-builder-canvas-wrap" id="cmsBuilderCanvasWrap" data-device="desktop">
        <div class="cms-builder-canvas public-site" id="cmsBuilderCanvas"></div>
    </main>
    <aside class="cms-builder-panel" id="cmsBuilderPanel" hidden>
        <div class="cms-builder-panel-head">
            <h2 class="h6 mb-0" id="cmsBuilderPanelTitle">Settings</h2>
            <button type="button" class="btn btn-sm btn-outline-secondary" id="cmsBuilderPanelClose" aria-label="Close">×</button>
        </div>
        <ul class="nav nav-tabs nav-fill cms-builder-tabs" role="tablist">
            <li class="nav-item"><button type="button" class="nav-link active" data-panel-tab="content">Content</button></li>
            <li class="nav-item"><button type="button" class="nav-link" data-panel-tab="design">Design</button></li>
            <li class="nav-item"><button type="button" class="nav-link" data-panel-tab="advanced">Advanced</button></li>
        </ul>
        <div class="cms-builder-panel-body" id="cmsBuilderPanelBody"></div>
    </aside>
</div>

<div id="cmsBuilderConfig"
    class="d-none"
    data-save-url="<?= htmlspecialchars($saveUrl) ?>"
    data-templates-url="<?= htmlspecialchars($templatesUrl ?? '') ?>"
    data-upload-url="<?= htmlspecialchars($uploadUrl) ?>"
    data-can-upload="<?= !empty($canUploadMedia) ? '1' : '0' ?>"
    data-layout="<?= htmlspecialchars($layoutJson) ?>"
    data-modules="<?= htmlspecialchars($moduleTypesJson) ?>"
    data-media="<?= htmlspecialchars($mediaJson) ?>"
    data-templates="<?= htmlspecialchars($templatesJson ?? '[]') ?>"
    data-entity-type="<?= htmlspecialchars($entityType) ?>"
    data-csrf="<?= htmlspecialchars(\Core\Csrf::token()) ?>"></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="/public/assets/js/content/media-picker.js"></script>
<script src="/public/assets/js/builder/editor.js"></script>
</body>
</html>
