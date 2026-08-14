<?php
use App\Permalink;

$statuses = $statuses ?? [];
$currentStatus = $currentStatus ?? '';
$pendingCount = (int) ($pendingCount ?? 0);
ob_start();
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-0">Comments</h2>
        <p class="text-muted small mb-0">Moderate visitor comments on blog posts.</p>
    </div>
    <?php if ($pendingCount > 0): ?>
    <span class="badge bg-warning text-dark"><?= $pendingCount ?> pending</span>
    <?php endif; ?>
</div>

<div class="btn-group mb-3" role="group" aria-label="Filter by status">
    <a href="<?= admin_url('comments') ?>" class="btn btn-sm <?= $currentStatus === '' ? 'btn-primary' : 'btn-outline-secondary' ?>">All</a>
    <?php foreach ($statuses as $key => $label): ?>
    <a href="<?= admin_url('comments?status=' . urlencode($key)) ?>"
        class="btn btn-sm <?= $currentStatus === $key ? 'btn-primary' : 'btn-outline-secondary' ?>"><?= htmlspecialchars($label) ?></a>
    <?php endforeach; ?>
</div>

<div class="card"><div class="card-body p-0">
<table class="table table-hover mb-0 align-middle">
<thead><tr><th>Author</th><th>Comment</th><th>Post</th><th>Status</th><th>Date</th><th></th></tr></thead>
<tbody>
<?php if (empty($comments)): ?>
<tr><td colspan="6" class="text-muted p-4">No comments<?= $currentStatus !== '' ? ' with this status' : '' ?>.</td></tr>
<?php else: foreach ($comments as $c): ?>
<tr>
    <td>
        <strong><?= htmlspecialchars($c->author_name ?? '') ?></strong><br>
        <span class="text-muted small"><?= htmlspecialchars($c->author_email ?? '') ?></span>
    </td>
    <td class="small" style="max-width:320px"><?= nl2br(htmlspecialchars($c->content ?? '')) ?></td>
    <td><a href="<?= htmlspecialchars(Permalink::urlForPost((object) ['slug' => $c->post_slug ?? ''])) ?>" target="_blank" rel="noopener"><?= htmlspecialchars($c->post_title ?? '') ?></a></td>
    <td><span class="badge bg-secondary"><?= htmlspecialchars($statuses[$c->status ?? ''] ?? $c->status ?? '') ?></span></td>
    <td class="text-muted small"><?= htmlspecialchars($c->created_at ?? '') ?></td>
    <td class="text-end text-nowrap">
        <?php if (($c->status ?? '') !== 'approved'): ?>
        <form method="post" action="<?= admin_url('comments/approve/' . (int)$c->id) ?>" class="d-inline"><?= \Core\Csrf::field() ?><button type="submit" class="btn btn-sm btn-outline-success">Approve</button></form>
        <?php endif; ?>
        <?php if (($c->status ?? '') !== 'spam'): ?>
        <form method="post" action="<?= admin_url('comments/spam/' . (int)$c->id) ?>" class="d-inline"><?= \Core\Csrf::field() ?><button type="submit" class="btn btn-sm btn-outline-warning">Spam</button></form>
        <?php endif; ?>
        <form method="post" action="<?= admin_url('comments/trash/' . (int)$c->id) ?>" class="d-inline" onsubmit="return confirm('Move to trash?');"><?= \Core\Csrf::field() ?><button type="submit" class="btn btn-sm btn-outline-danger">Trash</button></form>
    </td>
</tr>
<?php endforeach; endif; ?>
</tbody></table></div></div>
<?php
$content = ob_get_clean();
$pageTitle = 'Comments';
$currentPage = 'comments';
require __DIR__ . '/../layout/main.php';
