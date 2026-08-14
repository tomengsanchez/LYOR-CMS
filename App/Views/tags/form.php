<?php ob_start(); ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><?= !empty($isCreate) ? 'Add Tag' : 'Edit Tag' ?></h2>
    <a href="<?= admin_url('tags') ?>" class="btn btn-outline-secondary">Back</a>
</div>
<div class="card"><div class="card-body">
<?php if (!empty($formError)): ?><div class="alert alert-danger"><?= htmlspecialchars($formError) ?></div><?php endif; ?>
<form method="post" action="<?= !empty($isCreate) ? admin_url('tags/store') : admin_url('tags/update/' . (int)$tag->id) ?>">
<?= \Core\Csrf::field() ?>
<div class="mb-3"><label class="form-label">Name</label><input type="text" name="name" class="form-control" value="<?= htmlspecialchars($tag->name) ?>" required></div>
<div class="mb-3"><label class="form-label">Slug</label><input type="text" name="slug" class="form-control" value="<?= htmlspecialchars($tag->slug ?? '') ?>" placeholder="auto-generated if empty"></div>
<button type="submit" class="btn btn-primary">Save</button>
</form></div></div>
<?php $content = ob_get_clean(); $pageTitle = !empty($isCreate) ? 'Add Tag' : 'Edit Tag'; $currentPage = 'tags'; require __DIR__ . '/../layout/main.php';
