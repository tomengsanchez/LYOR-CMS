<?php
/**
 * Shared style-pack library UI (Customize + General).
 * @var string $returnTarget customize|general
 */
$returnTarget = $returnTarget ?? 'general';
$stylePackLibrary = \App\ThemeStylePack::listLibrary();
$stylePackActiveId = \App\ThemeStylePack::activePackId();
$stylePackName = \App\ThemeStylePack::packName();
$stylePackSource = \App\ThemeStylePack::packSource();
$stylePackCssUrl = \App\ThemeStylePack::activeCssPublicUrl();
$bundledLabels = \App\ThemeStylePack::bundledPackLabels();
?>
<?php if ($stylePackName !== ''): ?>
<p class="small mb-2" id="cmsStylePackStatus">
    Active: <strong><?= htmlspecialchars($stylePackName) ?></strong>
    <?php if ($stylePackSource !== ''): ?><span class="text-muted">(<?= htmlspecialchars($stylePackSource) ?>)</span><?php endif; ?>
    <?php if ($stylePackCssUrl): ?> · <a href="<?= htmlspecialchars($stylePackCssUrl) ?>" target="_blank" rel="noopener">extra.css</a><?php endif; ?>
</p>
<?php else: ?>
<p class="small text-muted mb-2" id="cmsStylePackStatus">No style pack active. Upload or select one below.</p>
<?php endif; ?>

<?php if ($stylePackLibrary !== []): ?>
<div class="border rounded p-2 mb-3 bg-light">
    <label class="form-label small fw-semibold mb-1" for="cmsStylePackSelect">Installed packs</label>
    <form method="post" action="<?= admin_url('customize/activate-style-pack') ?>" class="row g-2 align-items-end">
        <?= \Core\Csrf::field() ?>
        <input type="hidden" name="return" value="<?= htmlspecialchars($returnTarget) ?>">
        <div class="col-md-8">
            <select name="pack_id" id="cmsStylePackSelect" class="form-select form-select-sm" required>
                <?php foreach ($stylePackLibrary as $pack): ?>
                <option value="<?= htmlspecialchars($pack['id']) ?>" <?= $stylePackActiveId === $pack['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($pack['name']) ?>
                    <?= !empty($pack['has_css']) ? ' · CSS' : '' ?>
                    <?= $stylePackActiveId === $pack['id'] ? ' (active)' : '' ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4 d-flex flex-wrap gap-1">
            <button type="submit" class="btn btn-sm btn-primary">Activate</button>
        </div>
    </form>
    <form method="post" action="<?= admin_url('customize/delete-style-pack') ?>" class="mt-2" onsubmit="return confirm('Remove this pack from the library?');">
        <?= \Core\Csrf::field() ?>
        <input type="hidden" name="return" value="<?= htmlspecialchars($returnTarget) ?>">
        <input type="hidden" name="pack_id" id="cmsStylePackDeleteId" value="<?= htmlspecialchars($stylePackActiveId !== '' ? $stylePackActiveId : ($stylePackLibrary[0]['id'] ?? '')) ?>">
        <button type="submit" class="btn btn-sm btn-outline-danger">Remove selected</button>
        <span class="small text-muted ms-1">Keeps other packs. Theme settings stay until you activate another pack.</span>
    </form>
</div>
<script src="/public/assets/js/customize/style-pack-library.js"></script>
<?php endif; ?>

<div class="mb-2">
    <span class="small text-muted d-block mb-1">Install bundled packs into the library:</span>
    <div class="d-flex flex-wrap gap-1">
        <?php foreach ($bundledLabels as $slug => $label): ?>
        <form method="post" action="<?= admin_url('customize/install-bundled-style-pack') ?>" class="d-inline">
            <?= \Core\Csrf::field() ?>
            <input type="hidden" name="return" value="<?= htmlspecialchars($returnTarget) ?>">
            <input type="hidden" name="pack" value="<?= htmlspecialchars($slug) ?>">
            <button type="submit" class="btn btn-sm btn-outline-primary"><?= htmlspecialchars($label) ?></button>
        </form>
        <?php endforeach; ?>
    </div>
</div>
