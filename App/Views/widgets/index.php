<?php
use App\Models\Widget;

$area = $area ?? Widget::AREA_SIDEBAR;
$areas = $areas ?? Widget::areas();
$widgets = $widgets ?? [];
$widgetTypes = $widgetTypes ?? Widget::types();
$areaHelp = $areaHelp ?? Widget::areaHelp($area);
ob_start();
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-0">Widgets</h2>
        <p class="text-muted small mb-0">Public widget areas (header, homepage, after content, sidebar, footer). Drag ⋮⋮ to reorder.</p>
    </div>
</div>

<?php if (!empty($saved)): ?>
<div class="alert alert-success">Widgets saved.</div>
<?php endif; ?>

<ul class="nav nav-tabs mb-3">
    <?php foreach ($areas as $key => $label): ?>
    <li class="nav-item">
        <a class="nav-link <?= $area === $key ? 'active' : '' ?>" href="<?= admin_url('widgets?area=' . urlencode($key)) ?>"><?= htmlspecialchars($label) ?></a>
    </li>
    <?php endforeach; ?>
</ul>

<div class="card mb-4">
    <div class="card-header"><h5 class="mb-0"><?= htmlspecialchars($areas[$area] ?? $area) ?> widgets</h5></div>
    <div class="card-body">
        <?php if ($areaHelp !== ''): ?>
        <p class="small text-muted"><?= htmlspecialchars($areaHelp) ?></p>
        <?php endif; ?>
        <form method="post" action="<?= admin_url('widgets/save') ?>" id="widgetsForm">
            <?= \Core\Csrf::field() ?>
            <input type="hidden" name="area" value="<?= htmlspecialchars($area) ?>">
            <div id="widgetRows">
                <?php if ($widgets === []): ?>
                <div class="widget-row border rounded p-3 mb-3" data-index="0">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label small">Type</label>
                            <select name="widget_type[]" class="form-select form-select-sm widget-type-select">
                                <?php foreach ($widgetTypes as $k => $l): ?>
                                <option value="<?= htmlspecialchars($k) ?>"><?= htmlspecialchars($l) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">Title (optional)</label>
                            <input type="text" name="widget_title[]" class="form-control form-control-sm" value="">
                        </div>
                        <div class="col-md-4 widget-config-fields">
                            <label class="form-label small">Posts to show</label>
                            <input type="number" class="form-control form-control-sm widget-field-count" min="1" max="10" value="5">
                        </div>
                        <div class="col-md-1">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" name="widget_enabled[0]" value="1" checked id="we0">
                                <label class="form-check-label small" for="we0">On</label>
                            </div>
                        </div>
                        <div class="col-md-1 text-end">
                            <button type="button" class="btn btn-sm btn-outline-danger widget-remove" aria-label="Remove">&times;</button>
                        </div>
                    </div>
                    <input type="hidden" name="widget_config[]" class="widget-config-json" value='{"count":5}'>
                </div>
                <?php else: ?>
                <?php foreach ($widgets as $idx => $w): ?>
                <?php $cfg = Widget::decodeConfig($w->config_json ?? null); ?>
                <div class="widget-row border rounded p-3 mb-3" data-index="<?= (int)$idx ?>">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label small">Type</label>
                            <select name="widget_type[]" class="form-select form-select-sm widget-type-select">
                                <?php foreach ($widgetTypes as $k => $l): ?>
                                <option value="<?= htmlspecialchars($k) ?>" <?= ($w->widget_type ?? '') === $k ? 'selected' : '' ?>><?= htmlspecialchars($l) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">Title (optional)</label>
                            <input type="text" name="widget_title[]" class="form-control form-control-sm" value="<?= htmlspecialchars($w->title ?? '') ?>">
                        </div>
                        <div class="col-md-4 widget-config-fields">
                            <?php if (($w->widget_type ?? '') === 'custom_html'): ?>
                            <label class="form-label small">HTML</label>
                            <textarea class="form-control form-control-sm widget-field-html" rows="2"><?= htmlspecialchars($cfg['html'] ?? '') ?></textarea>
                            <?php elseif (($w->widget_type ?? '') === 'recent_posts' || ($w->widget_type ?? '') === 'featured_posts' || ($w->widget_type ?? '') === 'archives'): ?>
                            <label class="form-label small"><?= ($w->widget_type ?? '') === 'archives' ? 'Months to show' : 'Posts to show' ?></label>
                            <input type="number" class="form-control form-control-sm widget-field-count" min="1" max="<?= ($w->widget_type ?? '') === 'featured_posts' ? '6' : (($w->widget_type ?? '') === 'archives' ? '24' : '10') ?>" value="<?= (int)($cfg['count'] ?? (($w->widget_type ?? '') === 'featured_posts' ? 3 : (($w->widget_type ?? '') === 'archives' ? 12 : 5))) ?>">
                            <?php elseif (($w->widget_type ?? '') === 'cta'): ?>
                            <label class="form-label small">Headline</label>
                            <input type="text" class="form-control form-control-sm widget-field-headline mb-1" value="<?= htmlspecialchars($cfg['headline'] ?? '') ?>">
                            <label class="form-label small">Text</label>
                            <textarea class="form-control form-control-sm widget-field-cta-text mb-1" rows="2"><?= htmlspecialchars($cfg['text'] ?? '') ?></textarea>
                            <label class="form-label small">Button label</label>
                            <input type="text" class="form-control form-control-sm widget-field-label mb-1" value="<?= htmlspecialchars($cfg['label'] ?? '') ?>">
                            <label class="form-label small">Button URL</label>
                            <input type="text" class="form-control form-control-sm widget-field-url" value="<?= htmlspecialchars($cfg['url'] ?? '') ?>" placeholder="/blog or https://">
                            <?php elseif (($w->widget_type ?? '') === 'social'): ?>
                            <label class="form-label small">Links (Label|URL per line)</label>
                            <textarea class="form-control form-control-sm widget-field-social" rows="4"><?= htmlspecialchars(\App\Models\Widget::socialLinesFromConfig($cfg)) ?></textarea>
                            <?php else: ?>
                            <span class="text-muted small">No extra options for this widget type.</span>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-1">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" name="widget_enabled[<?= (int)$idx ?>]" value="1" <?= !empty($w->is_enabled) ? 'checked' : '' ?> id="we<?= (int)$idx ?>">
                                <label class="form-check-label small" for="we<?= (int)$idx ?>">On</label>
                            </div>
                        </div>
                        <div class="col-md-1 text-end">
                            <button type="button" class="btn btn-sm btn-outline-danger widget-remove" aria-label="Remove">&times;</button>
                        </div>
                    </div>
                    <input type="hidden" name="widget_config[]" class="widget-config-json" value="<?= htmlspecialchars(json_encode($cfg, JSON_UNESCAPED_UNICODE) ?: '{}') ?>">
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <button type="button" class="btn btn-outline-secondary btn-sm mb-3" id="widgetAddRow">+ Add widget</button>
            <div><button type="submit" class="btn btn-primary">Save widgets</button></div>
        </form>
    </div>
</div>

<script type="application/json" id="widgetTypesJson"><?= json_encode($widgetTypes, JSON_UNESCAPED_UNICODE) ?></script>
<link href="/public/assets/css/admin/widgets.css" rel="stylesheet">
<script src="/public/assets/js/widgets/form.js"></script>
<?php
$content = ob_get_clean();
$pageTitle = 'Widgets';
$currentPage = 'widgets';
require __DIR__ . '/../layout/main.php';
