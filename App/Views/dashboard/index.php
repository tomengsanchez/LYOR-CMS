<?php ob_start(); ?>
<h2 class="mb-4">Dashboard</h2>
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="dashboard-stat">
            <div class="stat-label">Pages published</div>
            <div class="stat-value"><?= (int)($pageCounts['published'] ?? 0) ?></div>
            <div class="stat-meta"><?= (int)($pageCounts['draft'] ?? 0) ?> draft</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="dashboard-stat">
            <div class="stat-label">Posts published</div>
            <div class="stat-value"><?= (int)($postCounts['published'] ?? 0) ?></div>
            <div class="stat-meta"><?= (int)($postCounts['draft'] ?? 0) ?> draft</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="dashboard-stat">
            <div class="stat-label">Media files</div>
            <div class="stat-value"><?= (int)$mediaCount ?></div>
            <div class="stat-meta">&nbsp;</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="dashboard-stat">
            <div class="stat-label">Comments pending</div>
            <div class="stat-value"><?= (int)($pendingComments ?? 0) ?></div>
            <div class="stat-meta">
                <?php if (\Core\Auth::can('moderate_comments')): ?>
                <a href="<?= admin_url('comments?status=pending') ?>">Moderate</a>
                <?php else: ?>&nbsp;<?php endif; ?>
            </div>
        </div>
    </div>
</div>
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="dashboard-stat">
            <div class="stat-label">Quick links</div>
            <div class="d-flex flex-column gap-1 mt-2">
                <a href="<?= admin_url('pages') ?>">Pages</a>
                <a href="<?= admin_url('posts') ?>">Posts</a>
                <a href="<?= admin_url('menus') ?>">Menus</a>
                <a href="<?= admin_url('widgets') ?>">Widgets</a>
                <a href="<?= admin_url('tags') ?>">Tags</a>
                <a href="/" target="_blank" rel="noopener">Public site</a>
                <a href="/blog" target="_blank" rel="noopener">Public blog</a>
            </div>
        </div>
    </div>
</div>
<?php if (!empty($recentDraftPages) || !empty($recentDraftPosts) || !empty($recentPendingComments)): ?>
<div class="row g-3 mb-4">
    <?php if (!empty($recentDraftPages)): ?>
    <div class="col-lg-4">
        <div class="card h-100"><div class="card-header"><h6 class="mb-0">Recent draft pages</h6></div><ul class="list-group list-group-flush">
            <?php foreach ($recentDraftPages as $p): ?>
            <li class="list-group-item d-flex justify-content-between align-items-center">
                <a href="<?= admin_url('pages/edit/' . (int)$p->id) ?>"><?= htmlspecialchars($p->title) ?></a>
                <span class="text-muted small"><?= htmlspecialchars(substr($p->updated_at ?? '', 0, 10)) ?></span>
            </li>
            <?php endforeach; ?>
        </ul></div>
    </div>
    <?php endif; ?>
    <?php if (!empty($recentDraftPosts)): ?>
    <div class="col-lg-4">
        <div class="card h-100"><div class="card-header"><h6 class="mb-0">Recent draft posts</h6></div><ul class="list-group list-group-flush">
            <?php foreach ($recentDraftPosts as $p): ?>
            <li class="list-group-item d-flex justify-content-between align-items-center">
                <a href="<?= admin_url('posts/edit/' . (int)$p->id) ?>"><?= htmlspecialchars($p->title) ?></a>
                <span class="text-muted small"><?= htmlspecialchars(substr($p->updated_at ?? '', 0, 10)) ?></span>
            </li>
            <?php endforeach; ?>
        </ul></div>
    </div>
    <?php endif; ?>
    <?php if (!empty($recentPendingComments) && \Core\Auth::can('moderate_comments')): ?>
    <div class="col-lg-4">
        <div class="card h-100"><div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0">Pending comments</h6>
            <a href="<?= admin_url('comments?status=pending') ?>" class="small">View all</a>
        </div><ul class="list-group list-group-flush">
            <?php foreach ($recentPendingComments as $c): ?>
            <li class="list-group-item">
                <div class="small fw-semibold"><?= htmlspecialchars($c->author_name ?? '') ?> on <?= htmlspecialchars($c->post_title ?? '') ?></div>
                <div class="text-muted small text-truncate"><?= htmlspecialchars($c->content ?? '') ?></div>
            </li>
            <?php endforeach; ?>
        </ul></div>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>
<?php if (\Core\Auth::isAdmin() && !empty($recentAudit)): ?>
<div class="card border-0 shadow-sm"><div class="card-header bg-transparent"><h5 class="mb-0">Recent audit activity</h5></div>
<div class="table-responsive"><table class="table table-sm mb-0">
<thead><tr><th>When</th><th>Entity</th><th>Action</th><th>User</th></tr></thead>
<tbody>
<?php foreach ($recentAudit as $a): ?>
<tr>
<td><?= htmlspecialchars($a->created_at ?? '') ?></td>
<td><?= htmlspecialchars($a->entity_type) ?> #<?= (int)$a->entity_id ?></td>
<td><?= htmlspecialchars($a->action) ?></td>
<td><?= htmlspecialchars($a->created_by_name ?? '—') ?></td>
</tr>
<?php endforeach; ?>
</tbody></table></div></div>
<?php endif; ?>
<?php
$content = ob_get_clean();
$pageTitle = 'Dashboard';
$currentPage = 'dashboard';
require __DIR__ . '/../layout/main.php';
