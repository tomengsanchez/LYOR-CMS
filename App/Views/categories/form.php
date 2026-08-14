<?php ob_start(); ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><?= !empty($isCreate) ? 'Add Category' : 'Edit Category' ?></h2>
    <a href="<?= admin_url('categories') ?>" class="btn btn-outline-secondary">Back</a>
</div>
<div class="card"><div class="card-body">
<?php if (!empty($formError)): ?><div class="alert alert-danger"><?= htmlspecialchars($formError) ?></div><?php endif; ?>
<form method="post" action="<?= !empty($isCreate) ? admin_url('categories/store') : admin_url('categories/update/' . (int)$category->id) ?>">
<?= \Core\Csrf::field() ?>
<div class="mb-3"><label class="form-label">Name</label><input type="text" name="name" class="form-control" value="<?= htmlspecialchars($category->name) ?>" required></div>
<div class="mb-3"><label class="form-label">Slug</label><input type="text" name="slug" class="form-control" value="<?= htmlspecialchars($category->slug ?? '') ?>"></div>
<div class="mb-3"><label class="form-label">Description</label><input type="text" name="description" class="form-control" value="<?= htmlspecialchars($category->description ?? '') ?>"></div>
<button type="submit" class="btn btn-primary">Save</button>
</form></div></div>
<?php $content = ob_get_clean(); $pageTitle = !empty($isCreate) ? 'Add Category' : 'Edit Category'; $currentPage = 'categories'; require __DIR__ . '/../layout/main.php';
