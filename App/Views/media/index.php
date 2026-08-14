<?php ob_start(); ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0">Media Library</h2>
</div>
<?php if (!empty($uploadError)): ?><div class="alert alert-danger"><?= htmlspecialchars($uploadError) ?></div><?php endif; ?>
<?php if (!empty($uploadSuccess)): ?><div class="alert alert-success"><?= htmlspecialchars($uploadSuccess) ?></div><?php endif; ?>
<?php if (\Core\Auth::can('upload_media')): ?>
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
<?php endif; ?>
<div class="row g-3">
<?php foreach ($mediaItems as $m): ?>
<div class="col-6 col-md-3">
<div class="card h-100">
<?php if (strpos($m->mime_type ?? '', 'image/') === 0 && ($m->mime_type ?? '') !== 'image/svg+xml'): ?>
<img src="/serve/media/<?= (int)$m->id ?>/thumbnail" class="card-img-top" alt="<?= htmlspecialchars($m->alt_text ?? '') ?>" style="height:120px;object-fit:cover;" loading="lazy" decoding="async">
<?php elseif (strpos($m->mime_type ?? '', 'image/') === 0): ?>
<img src="/serve/media/<?= (int)$m->id ?>" class="card-img-top" alt="<?= htmlspecialchars($m->alt_text ?? '') ?>" style="height:120px;object-fit:cover;" loading="lazy">
<?php else: ?>
<div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height:120px;"><?= htmlspecialchars($m->mime_type ?? 'file') ?></div>
<?php endif; ?>
<div class="card-body p-2"><small class="text-truncate d-block" title="<?= htmlspecialchars($m->original_name) ?>"><?= htmlspecialchars($m->original_name) ?></small>
<?php if (!empty($m->width) && !empty($m->height)): ?>
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
