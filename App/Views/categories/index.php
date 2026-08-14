<?php ob_start(); ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0">Categories</h2>
    <?php if (\Core\Auth::can('manage_categories')): ?><a href="<?= admin_url('categories/create') ?>" class="btn btn-primary">Add Category</a><?php endif; ?>
</div>
<div class="card"><div class="table-responsive"><table class="table table-hover mb-0">
<thead><tr><th>Name</th><th>Slug</th><th>Description</th><th>Actions</th></tr></thead>
<tbody>
<?php foreach ($categories as $c): ?><tr>
<td><?= htmlspecialchars($c->name) ?></td><td><?= htmlspecialchars($c->slug) ?></td><td><?= htmlspecialchars($c->description ?? '') ?></td>
<td><?php if (\Core\Auth::can('manage_categories')): ?><a href="<?= admin_url('categories/edit/' . (int)$c->id ) ?>" class="btn btn-sm btn-outline-primary">Edit</a><?php endif; ?></td>
</tr><?php endforeach; ?>
<?php if (empty($categories)): ?><tr><td colspan="4" class="text-center text-muted py-4">No categories.</td></tr><?php endif; ?>
</tbody></table></div></div>
<?php $content = ob_get_clean(); $pageTitle = 'Categories'; $currentPage = 'categories'; require __DIR__ . '/../layout/main.php';
