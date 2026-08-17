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
    <style id="cmsBuilderLiveCss" class="cms-layout-css"></style>
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
            <button type="button" class="btn btn-sm btn-outline-light is-active" data-device="desktop" title="Desktop — edit default styles">Desktop</button>
            <button type="button" class="btn btn-sm btn-outline-light" data-device="tablet" title="Tablet — preview and edit styles below 1024px">Tablet</button>
            <button type="button" class="btn btn-sm btn-outline-light" data-device="mobile" title="Mobile — preview and edit styles below 768px">Mobile</button>
        </div>
        <button type="button" class="btn btn-sm btn-outline-light" id="cmsBuilderLayersBtn" title="Structure tree" aria-pressed="false">Layers</button>
        <button type="button" class="btn btn-sm btn-outline-light" id="cmsBuilderCopy" title="Copy (Ctrl+C)" disabled>Copy</button>
        <button type="button" class="btn btn-sm btn-outline-light" id="cmsBuilderPaste" title="Paste (Ctrl+V)" disabled>Paste</button>
        <div class="cms-builder-zoom" role="group" aria-label="Canvas zoom">
            <button type="button" class="btn btn-sm btn-outline-light" data-zoom="0.85" title="Zoom 85%">85%</button>
            <button type="button" class="btn btn-sm btn-outline-light is-active" data-zoom="1" title="Zoom 100%">100%</button>
            <button type="button" class="btn btn-sm btn-outline-light" data-zoom="1.15" title="Zoom 115%">115%</button>
        </div>
        <button type="button" class="btn btn-sm btn-outline-light" id="cmsBuilderUndo" title="Undo (Ctrl+Z)" disabled>Undo</button>
        <button type="button" class="btn btn-sm btn-outline-light" id="cmsBuilderRedo" title="Redo (Ctrl+Y)" disabled>Redo</button>
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
        <button type="button" class="btn btn-sm btn-outline-light" id="cmsBuilderHelp" title="Keyboard shortcuts (?)" aria-expanded="false" aria-controls="cmsBuilderShortcuts">?</button>
        <button type="button" class="btn btn-sm btn-success" id="cmsBuilderSave">Save</button>
        <span class="cms-builder-status small" id="cmsBuilderStatus" aria-live="polite"></span>
    </div>
</header>

<div class="cms-builder-shell">
    <aside class="cms-builder-layers" id="cmsBuilderLayers" hidden>
        <div class="cms-builder-layers-head">
            <span>Layers</span>
            <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-1" id="cmsBuilderLayersClose" aria-label="Close layers">×</button>
        </div>
        <input type="search" class="form-control form-control-sm cms-builder-layers-filter" id="cmsBuilderLayersFilter" placeholder="Filter layers" aria-label="Filter layers">
        <div class="cms-builder-layers-body" id="cmsBuilderLayersBody"></div>
    </aside>
    <main class="cms-builder-canvas-wrap" id="cmsBuilderCanvasWrap" data-device="desktop">
        <div class="cms-builder-canvas public-site" id="cmsBuilderCanvas"></div>
    </main>
    <aside class="cms-builder-panel" id="cmsBuilderPanel" hidden>
        <div class="cms-builder-panel-head">
            <div class="cms-builder-panel-head-text">
                <h2 class="h6 mb-0" id="cmsBuilderPanelTitle">Settings</h2>
                <nav class="cms-lb-crumbs" id="cmsBuilderPanelCrumbs" aria-label="Selected layout" hidden></nav>
            </div>
            <button type="button" class="btn btn-sm btn-outline-secondary" id="cmsBuilderPanelClose" aria-label="Close">×</button>
        </div>
        <ul class="nav nav-tabs nav-fill cms-builder-tabs" role="tablist" aria-label="Module settings">
            <li class="nav-item" role="presentation">
                <button type="button" class="nav-link active" role="tab" id="cmsBuilderTabContent" data-panel-tab="content" aria-selected="true" aria-controls="cmsBuilderPanelBody">Content</button>
            </li>
            <li class="nav-item" role="presentation">
                <button type="button" class="nav-link" role="tab" id="cmsBuilderTabDesign" data-panel-tab="design" aria-selected="false" aria-controls="cmsBuilderPanelBody">Design</button>
            </li>
            <li class="nav-item" role="presentation">
                <button type="button" class="nav-link" role="tab" id="cmsBuilderTabAdvanced" data-panel-tab="advanced" aria-selected="false" aria-controls="cmsBuilderPanelBody">Advanced</button>
            </li>
        </ul>
        <div class="cms-builder-panel-body" id="cmsBuilderPanelBody" role="tabpanel" aria-labelledby="cmsBuilderTabContent"></div>
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

<div id="cmsBuilderShortcuts" class="cms-lb-shortcuts" hidden role="dialog" aria-labelledby="cmsBuilderShortcutsTitle">
    <div class="cms-lb-shortcuts-card">
        <div class="cms-lb-shortcuts-head">
            <h2 class="h6 mb-0" id="cmsBuilderShortcutsTitle">Keyboard shortcuts</h2>
            <button type="button" class="btn btn-sm btn-outline-secondary" id="cmsBuilderShortcutsClose" aria-label="Close">×</button>
        </div>
        <ul class="cms-lb-shortcuts-list small mb-0">
            <li><kbd>Ctrl</kbd>+<kbd>S</kbd> Save</li>
            <li><kbd>Ctrl</kbd>+<kbd>Z</kbd> / <kbd>Y</kbd> Undo / Redo</li>
            <li><kbd>Ctrl</kbd>+<kbd>C</kbd> / <kbd>X</kbd> / <kbd>V</kbd> Copy / Cut / Paste</li>
            <li>Right-click <strong>Copy style</strong> / <strong>Paste style</strong> (appearance only)</li>
            <li><kbd>Ctrl</kbd>+<kbd>D</kbd> Duplicate</li>
            <li><kbd>Alt</kbd>+arrows Nudge order</li>
            <li><kbd>Delete</kbd> Remove (confirms)</li>
            <li><kbd>Esc</kbd> Deselect / close panels</li>
            <li><kbd>?</kbd> This list</li>
        </ul>
        <p class="small text-muted mb-0 mt-2">Right-click a block for Copy / Paste / Copy style / Dup / Delete. Drag in Layers to reorder. Zoom, device, and Layers stay for this browser tab.</p>
    </div>
</div>
<div id="cmsBuilderContext" class="cms-lb-context" hidden role="menu">
    <button type="button" class="cms-lb-context-item" data-ctx="copy" role="menuitem">Copy</button>
    <button type="button" class="cms-lb-context-item" data-ctx="paste" role="menuitem">Paste</button>
    <button type="button" class="cms-lb-context-item" data-ctx="copy-style" role="menuitem">Copy style</button>
    <button type="button" class="cms-lb-context-item" data-ctx="paste-style" role="menuitem">Paste style</button>
    <button type="button" class="cms-lb-context-item" data-ctx="dup" role="menuitem">Duplicate</button>
    <button type="button" class="cms-lb-context-item" data-ctx="del" role="menuitem">Delete</button>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="/public/assets/js/content/media-picker.js"></script>
<?php
$builderJsDir = dirname(__DIR__, 3) . '/public/assets/js/builder';
$builderScripts = ['ns.js', 'history.js', 'model.js', 'styles.js', 'canvas.js', 'layers.js', 'dnd.js', 'actions.js', 'panel.js', 'save.js', 'ui.js', 'editor.js'];
foreach ($builderScripts as $builderFile) {
    $builderPath = $builderJsDir . DIRECTORY_SEPARATOR . $builderFile;
    $builderVer = is_file($builderPath) ? filemtime($builderPath) : time();
    echo '<script src="/public/assets/js/builder/' . htmlspecialchars($builderFile, ENT_QUOTES, 'UTF-8') . '?v=' . (int) $builderVer . '"></script>' . "\n";
}
?>
</body>
</html>
