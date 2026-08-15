<?php
$canAddMedia = \Core\Auth::can('upload_media')
    || \Core\Auth::canAny(['add_posts', 'edit_posts', 'add_pages', 'edit_pages']);
ob_start();
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0">Media Library</h2>
</div>
<?php if (!empty($uploadError)): ?><div class="alert alert-danger"><?= htmlspecialchars($uploadError) ?></div><?php endif; ?>
<?php if (!empty($uploadSuccess)): ?><div class="alert alert-success"><?= htmlspecialchars($uploadSuccess) ?></div><?php endif; ?>
<?php if ($canAddMedia): ?>
<div class="card mb-4"><div class="card-body">
<form method="post" action="<?= admin_url('media/upload') ?>" enctype="multipart/form-data">
<?= \Core\Csrf::field() ?>
<div class="row g-2 align-items-end">
<div class="col-md-5"><label class="form-label">File</label><input type="file" name="file" class="form-control" required accept="image/*,.pdf"></div>
<div class="col-md-4"><label class="form-label">Alt text</label><input type="text" name="alt_text" class="form-control"></div>
<div class="col-md-3"><button type="submit" class="btn btn-primary w-100">Upload</button></div>
</div>
<p class="text-muted small mb-0 mt-2">JPG, PNG, and WebP get WordPress-style resized copies (thumbnail, medium, large, …) for faster mobile loading via <code>srcset</code>.</p>
</form></div></div>
<div class="card mb-4"><div class="card-body">
<form method="post" action="<?= admin_url('media/register-url') ?>" id="mediaRegisterUrlForm">
<?= \Core\Csrf::field() ?>
<h3 class="h6">Add free image by URL (no download)</h3>
<p class="text-muted small">Registers an external HTTPS image without re-uploading. Store a caption that includes the source URL for attribution.</p>
<div class="row g-2 align-items-end">
<div class="col-md-5"><label class="form-label" for="mediaSourceUrl">Image URL</label><input type="url" name="source_url" id="mediaSourceUrl" class="form-control" required placeholder="https://…" maxlength="500"></div>
<div class="col-md-3"><label class="form-label" for="mediaExternalAlt">Alt text</label><input type="text" name="alt_text" id="mediaExternalAlt" class="form-control" maxlength="255"></div>
<div class="col-md-4"><label class="form-label" for="mediaExternalCaption">Caption (include URL)</label><input type="text" name="caption" id="mediaExternalCaption" class="form-control" maxlength="1000" placeholder="Credit + full image URL"></div>
</div>
<div class="mt-2"><button type="submit" class="btn btn-outline-primary">Register URL</button></div>
</form></div></div>
<?php endif; ?>
<div class="row g-3">
    <?php foreach ($mediaItems as $m):
        $isExternal = trim((string) ($m->file_path ?? '')) === 'external' && trim((string) ($m->source_url ?? '')) !== '';
        $thumb = $isExternal
            ? (string) $m->source_url
            : ((strpos($m->mime_type ?? '', 'image/') === 0 && ($m->mime_type ?? '') !== 'image/svg+xml')
                ? '/serve/media/' . (int) $m->id . '/thumbnail'
                : '/serve/media/' . (int) $m->id);
    ?>
<div class="col-6 col-md-3">
<div class="card h-100">
<?php if (strpos($m->mime_type ?? '', 'image/') === 0): ?>
<img src="<?= htmlspecialchars($thumb) ?>" class="card-img-top" alt="<?= htmlspecialchars($m->alt_text ?? '') ?>" style="height:120px;object-fit:cover;" loading="lazy" decoding="async" referrerpolicy="no-referrer">
<?php else: ?>
<div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height:120px;"><?= htmlspecialchars($m->mime_type ?? 'file') ?></div>
<?php endif; ?>
<div class="card-body p-2"><small class="text-truncate d-block" title="<?= htmlspecialchars($m->original_name) ?>"><?= htmlspecialchars($m->original_name) ?></small>
<?php if ($isExternal): ?>
<small class="text-muted d-block">External URL</small>
<?php if (!empty($m->caption)): ?>
<small class="text-muted d-block text-truncate" title="<?= htmlspecialchars((string) $m->caption) ?>"><?= htmlspecialchars((string) $m->caption) ?></small>
<?php endif; ?>
<?php elseif (!empty($m->width) && !empty($m->height)): ?>
<small class="text-muted"><?= (int)$m->width ?>×<?= (int)$m->height ?></small>
<?php endif; ?>
<?php if (\Core\Auth::can('delete_media')): ?>
<form method="post" action="<?= admin_url('media/delete/' . (int)$m->id ) ?>" class="mt-2 js-media-delete"><?= \Core\Csrf::field() ?><button type="submit" class="btn btn-sm btn-outline-danger w-100">Delete</button></form>
<?php endif; ?>
</div></div></div>
    <?php endforeach; ?>
<?php if (empty($mediaItems)): ?><div class="col-12 text-muted">No media files.</div><?php endif; ?>
</div>
<script src="/public/assets/js/media/index.js"></script>
<?php $content = ob_get_clean(); $pageTitle = 'Media'; $currentPage = 'media'; require __DIR__ . '/../layout/main.php';
