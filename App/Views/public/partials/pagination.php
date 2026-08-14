<?php
/** @var array{page:int,total_pages:int,total:int,base:string} $pagination */
$page = max(1, (int) ($pagination['page'] ?? 1));
$totalPages = max(1, (int) ($pagination['total_pages'] ?? 1));
$base = rtrim((string) ($pagination['base'] ?? '/blog'), '/');
$querySep = strpos($base, '?') !== false ? '&' : '?';
?>
<nav class="public-pagination" aria-label="Pagination">
    <ul class="public-pagination-list">
        <?php if ($page > 1): ?>
        <li><a href="<?= $page === 2 ? htmlspecialchars($base) : htmlspecialchars($base . $querySep . 'page=' . ($page - 1)) ?>">&larr; Newer</a></li>
        <?php endif; ?>
        <li><span class="public-pagination-status">Page <?= (int) $page ?> of <?= (int) $totalPages ?></span></li>
        <?php if ($page < $totalPages): ?>
        <li><a href="<?= htmlspecialchars($base . $querySep . 'page=' . ($page + 1)) ?>">Older &rarr;</a></li>
        <?php endif; ?>
    </ul>
</nav>
