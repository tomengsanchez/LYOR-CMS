<?php
$menu = $menu ?? null;
$items = $items ?? [];
$itemTypes = $itemTypes ?? \App\Models\NavMenu::itemTypes();
$pages = $pages ?? [];
$posts = $posts ?? [];
$categories = $categories ?? [];
ob_start();
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-0">Menus</h2>
        <p class="text-muted small mb-0">WordPress-style navigation for the public site header (Primary Menu).</p>
    </div>
</div>

<?php if (!empty($saved)): ?>
<div class="alert alert-success">Menu saved.</div>
<?php endif; ?>

<div class="card mb-4">
    <div class="card-header"><h5 class="mb-0">Primary Menu</h5></div>
    <div class="card-body">
        <form method="post" action="<?= admin_url('menus/save') ?>" id="menuForm">
            <?= \Core\Csrf::field() ?>
            <div class="table-responsive">
                <table class="table align-middle" id="menuItemsTable">
                    <thead>
                        <tr>
                            <th>Label</th>
                            <th>Type</th>
                            <th>Target</th>
                            <th>New tab</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($items === []): ?>
                        <tr class="menu-item-row">
                            <td><input type="text" name="item_label[]" class="form-control form-control-sm" value="Home" required></td>
                            <td><select name="item_type[]" class="form-select form-select-sm menu-item-type"><?php foreach ($itemTypes as $k => $l): ?><option value="<?= htmlspecialchars($k) ?>" <?= $k === 'home' ? 'selected' : '' ?>><?= htmlspecialchars($l) ?></option><?php endforeach; ?></select></td>
                            <td class="menu-item-target"><input type="text" name="item_custom_url[]" class="form-control form-control-sm" value="/" placeholder="URL"></td>
                            <td class="text-center"><input type="checkbox" name="item_new_tab[0]" value="1" class="form-check-input"></td>
                            <td><button type="button" class="btn btn-sm btn-outline-danger menu-remove-row" aria-label="Remove">&times;</button></td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($items as $idx => $item): ?>
                        <tr class="menu-item-row">
                            <td><input type="text" name="item_label[]" class="form-control form-control-sm" value="<?= htmlspecialchars($item->label) ?>" required></td>
                            <td><select name="item_type[]" class="form-select form-select-sm menu-item-type"><?php foreach ($itemTypes as $k => $l): ?><option value="<?= htmlspecialchars($k) ?>" <?= ($item->item_type ?? '') === $k ? 'selected' : '' ?>><?= htmlspecialchars($l) ?></option><?php endforeach; ?></select></td>
                            <td class="menu-item-target">
                                <?php $type = $item->item_type ?? 'custom'; ?>
                                <?php if ($type === 'page'): ?>
                                <select name="item_object_id[]" class="form-select form-select-sm"><option value="">— Page —</option><?php foreach ($pages as $p): ?><option value="<?= (int)$p->id ?>" <?= (int)($item->object_id ?? 0) === (int)$p->id ? 'selected' : '' ?>><?= htmlspecialchars($p->title) ?></option><?php endforeach; ?></select>
                                <input type="hidden" name="item_custom_url[]" value="">
                                <?php elseif ($type === 'post'): ?>
                                <select name="item_object_id[]" class="form-select form-select-sm"><option value="">— Post —</option><?php foreach ($posts as $p): ?><option value="<?= (int)$p->id ?>" <?= (int)($item->object_id ?? 0) === (int)$p->id ? 'selected' : '' ?>><?= htmlspecialchars($p->title) ?></option><?php endforeach; ?></select>
                                <input type="hidden" name="item_custom_url[]" value="">
                                <?php elseif ($type === 'category'): ?>
                                <select name="item_object_id[]" class="form-select form-select-sm"><option value="">— Category —</option><?php foreach ($categories as $c): ?><option value="<?= (int)$c->id ?>" <?= (int)($item->object_id ?? 0) === (int)$c->id ? 'selected' : '' ?>><?= htmlspecialchars($c->name) ?></option><?php endforeach; ?></select>
                                <input type="hidden" name="item_custom_url[]" value="">
                                <?php elseif (in_array($type, ['home', 'blog'], true)): ?>
                                <span class="text-muted small">Auto URL</span>
                                <input type="hidden" name="item_object_id[]" value="">
                                <input type="hidden" name="item_custom_url[]" value="<?= htmlspecialchars($item->custom_url ?? '') ?>">
                                <?php else: ?>
                                <input type="text" name="item_custom_url[]" class="form-control form-control-sm" value="<?= htmlspecialchars($item->custom_url ?? '') ?>" placeholder="https://… or /path">
                                <input type="hidden" name="item_object_id[]" value="">
                                <?php endif; ?>
                            </td>
                            <td class="text-center"><input type="checkbox" name="item_new_tab[<?= (int)$idx ?>]" value="1" class="form-check-input" <?= !empty($item->open_in_new_tab) ? 'checked' : '' ?>></td>
                            <td><button type="button" class="btn btn-sm btn-outline-danger menu-remove-row" aria-label="Remove">&times;</button></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <button type="button" class="btn btn-outline-secondary btn-sm mb-3" id="menuAddRow">+ Add menu item</button>
            <div>
                <button type="submit" class="btn btn-primary">Save menu</button>
            </div>
        </form>
    </div>
</div>

<template id="menuRowTemplate">
    <tr class="menu-item-row">
        <td><input type="text" name="item_label[]" class="form-control form-control-sm" required></td>
        <td><select name="item_type[]" class="form-select form-select-sm menu-item-type"><?php foreach ($itemTypes as $k => $l): ?><option value="<?= htmlspecialchars($k) ?>"><?= htmlspecialchars($l) ?></option><?php endforeach; ?></select></td>
        <td class="menu-item-target"><input type="text" name="item_custom_url[]" class="form-control form-control-sm" placeholder="URL"><input type="hidden" name="item_object_id[]" value=""></td>
        <td class="text-center"><input type="checkbox" name="item_new_tab[]" value="1" class="form-check-input"></td>
        <td><button type="button" class="btn btn-sm btn-outline-danger menu-remove-row" aria-label="Remove">&times;</button></td>
    </tr>
</template>

<script type="application/json" id="menuPagesJson"><?= json_encode(array_map(static fn ($p) => ['id' => (int)$p->id, 'title' => $p->title], $pages)) ?></script>
<script type="application/json" id="menuPostsJson"><?= json_encode(array_map(static fn ($p) => ['id' => (int)$p->id, 'title' => $p->title], $posts)) ?></script>
<script type="application/json" id="menuCategoriesJson"><?= json_encode(array_map(static fn ($c) => ['id' => (int)$c->id, 'name' => $c->name], $categories)) ?></script>
<script src="/public/assets/js/menus/form.js"></script>
<?php
$content = ob_get_clean();
$pageTitle = 'Menus';
$currentPage = 'menus';
require __DIR__ . '/../layout/main.php';
