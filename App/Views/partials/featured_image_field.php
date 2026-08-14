<?php
/** Featured image field with inline upload (pages + posts). */
$mediaImages = $mediaImages ?? [];
$selectedFeatured = (int) ($selectedFeatured ?? 0);
$featuredLabel = $featuredLabel ?? 'Featured / share image';
$featuredHelp = $featuredHelp ?? 'Open Graph / social image. Recommended <strong>1200×630 px</strong>.';
$canUploadMedia = $canUploadMedia ?? \Core\Auth::can('upload_media')
    || \Core\Auth::canAny(['add_posts', 'edit_posts', 'add_pages', 'edit_pages']);
?>
<div class="mb-3" data-featured-media>
    <label class="form-label" for="featuredImageSelect"><?= htmlspecialchars($featuredLabel) ?></label>
    <select name="featured_image_id" id="featuredImageSelect" class="form-select">
        <option value="">— None —</option>
        <?php foreach ($mediaImages as $m): ?>
        <option value="<?= (int)$m->id ?>"
            data-preview="/serve/media/<?= (int)$m->id ?>/medium"
            data-width="<?= (int)($m->width ?? 0) ?>"
            data-height="<?= (int)($m->height ?? 0) ?>"
            <?= $selectedFeatured === (int)$m->id ? 'selected' : '' ?>>
            <?= htmlspecialchars($m->original_name) ?><?php if (!empty($m->width) && !empty($m->height)): ?> (<?= (int)$m->width ?>×<?= (int)$m->height ?>)<?php endif; ?>
        </option>
        <?php endforeach; ?>
    </select>
    <?php if ($canUploadMedia): ?>
    <div class="cms-media-inline">
        <button type="button" class="btn btn-outline-primary btn-sm" data-featured-upload-btn>Upload image</button>
        <input type="file" class="d-none" data-featured-upload accept="image/jpeg,image/png,image/gif,image/webp,.jpg,.jpeg,.png,.gif,.webp">
        <span class="small text-muted" data-featured-upload-status></span>
    </div>
    <?php endif; ?>
    <small class="text-muted d-block mt-1"><?= $featuredHelp ?> You can upload here — no need to visit Media first. <a href="<?= admin_url('media') ?>">Media library</a>.</small>
    <div id="featuredImagePreview" class="featured-image-preview mt-2<?= $selectedFeatured ? '' : ' d-none' ?>"></div>
</div>
