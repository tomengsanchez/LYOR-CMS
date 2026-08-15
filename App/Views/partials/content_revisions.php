<?php
/** @var string $entityType page|post */
/** @var int $entityId */
/** @var array $revisions */
$entityType = $entityType ?? 'page';
$entityId = (int) ($entityId ?? 0);
$revisions = $revisions ?? [];
$canRestore = $entityType === 'page' ? \Core\Auth::can('edit_pages') : \Core\Auth::can('edit_posts');
$restoreBase = $entityType === 'page'
    ? admin_url('pages/restore/' . $entityId . '/')
    : admin_url('posts/restore/' . $entityId . '/');
?>
<div class="card mt-4"><div class="card-header"><h5 class="mb-0">Revisions</h5></div>
<div class="card-body p-0">
<?php if (!$revisions): ?>
<p class="text-muted p-3 mb-0">No revisions yet. Edits and layout saves create snapshots (kept up to <?= (int) \App\ContentRevision::MAX_PER_ENTITY ?>).</p>
<?php else: ?>
<table class="table table-sm mb-0 align-middle">
<thead><tr><th>#</th><th>When</th><th>By</th><th>Note</th><th></th></tr></thead>
<tbody>
<?php foreach ($revisions as $rev): ?>
<tr>
<td><?= (int) $rev->revision_no ?></td>
<td class="small"><?= htmlspecialchars((string) $rev->created_at) ?></td>
<td class="small"><?= htmlspecialchars((string) ($rev->created_by_name ?? '—')) ?></td>
<td class="small"><?= htmlspecialchars((string) ($rev->note ?? '')) ?></td>
<td class="text-end">
<?php if ($canRestore): ?>
<form method="post" action="<?= htmlspecialchars($restoreBase . (int) $rev->id) ?>" class="d-inline" onsubmit="return confirm('Restore this revision? Current content will be saved as a new revision first.');">
<?= \Core\Csrf::field() ?>
<button type="submit" class="btn btn-sm btn-outline-warning">Restore</button>
</form>
<?php endif; ?>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php endif; ?>
</div></div>
