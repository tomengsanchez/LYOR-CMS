<?php
ob_start();
$formError = $formError ?? '';
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Redirects</h2>
</div>
<p class="text-muted">Map old public URLs to new ones (301 permanent or 302 temporary). Applied before permalink resolution. Sources under <code>/admin</code>, <code>/api</code>, <code>/serve</code>, and <code>/share</code> are blocked.</p>
<?php if ($formError !== ''): ?><div class="alert alert-danger"><?= htmlspecialchars($formError) ?></div><?php endif; ?>

<div class="card mb-4"><div class="card-header"><h5 class="mb-0">Add redirect</h5></div><div class="card-body">
<form method="post" action="<?= admin_url('redirects/store') ?>" class="row g-3">
<?= \Core\Csrf::field() ?>
<div class="col-md-4"><label class="form-label">From path</label><input type="text" name="from_path" class="form-control" placeholder="/old-page" required></div>
<div class="col-md-4"><label class="form-label">To URL or path</label><input type="text" name="to_url" class="form-control" placeholder="/new-page or https://…" required></div>
<div class="col-md-2"><label class="form-label">Type</label>
<select name="status_code" class="form-select"><option value="301">301</option><option value="302">302</option></select></div>
<div class="col-md-2 d-flex align-items-end"><div class="form-check mb-2"><input type="checkbox" class="form-check-input" name="is_active" value="1" id="redirActive" checked><label class="form-check-label" for="redirActive">Active</label></div></div>
<div class="col-md-8"><label class="form-label">Note (optional)</label><input type="text" name="note" class="form-control" maxlength="255"></div>
<div class="col-md-4 d-flex align-items-end"><button type="submit" class="btn btn-primary">Add redirect</button></div>
</form>
</div></div>

<div class="card"><div class="card-body p-0">
<table class="table table-striped mb-0 align-middle">
<thead><tr><th>From</th><th>To</th><th>Code</th><th>Hits</th><th>Active</th><th></th></tr></thead>
<tbody>
<?php if (empty($redirects)): ?>
<tr><td colspan="6" class="text-muted p-3">No redirects yet.</td></tr>
<?php else: foreach ($redirects as $r): ?>
<tr>
<td colspan="6" class="p-2">
<form method="post" action="<?= admin_url('redirects/update/' . (int) $r->id) ?>" class="row g-2 align-items-center">
<?= \Core\Csrf::field() ?>
<div class="col-md-3"><input type="text" name="from_path" class="form-control form-control-sm" value="<?= htmlspecialchars($r->from_path) ?>" required></div>
<div class="col-md-3"><input type="text" name="to_url" class="form-control form-control-sm" value="<?= htmlspecialchars($r->to_url) ?>" required></div>
<div class="col-auto"><select name="status_code" class="form-select form-select-sm"><option value="301" <?= (int)$r->status_code === 301 ? 'selected' : '' ?>>301</option><option value="302" <?= (int)$r->status_code === 302 ? 'selected' : '' ?>>302</option></select></div>
<div class="col-auto small text-muted"><?= (int) $r->hit_count ?> hits</div>
<div class="col-auto"><div class="form-check mb-0"><input type="checkbox" class="form-check-input" name="is_active" value="1" id="act<?= (int)$r->id ?>" <?= !empty($r->is_active) ? 'checked' : '' ?>><label class="form-check-label" for="act<?= (int)$r->id ?>">Active</label></div></div>
<div class="col-auto"><button type="submit" class="btn btn-sm btn-outline-primary">Save</button></div>
</form>
<form method="post" action="<?= admin_url('redirects/delete/' . (int) $r->id) ?>" class="d-inline ms-1" onsubmit="return confirm('Delete this redirect?');">
<?= \Core\Csrf::field() ?>
<button type="submit" class="btn btn-sm btn-outline-danger">Del</button>
</form>
</td>
</tr>
<?php endforeach; endif; ?>
</tbody>
</table>
</div></div>
<?php
$content = ob_get_clean();
$pageTitle = 'Redirects';
$currentPage = 'redirects';
require __DIR__ . '/../layout/main.php';
