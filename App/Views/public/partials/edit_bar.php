<?php
/**
 * Public front-end edit links for logged-in users with edit capability.
 *
 * Expected: $editBarType = 'page'|'post', $editBarId = int
 */
$editBarType = $editBarType ?? '';
$editBarId = (int) ($editBarId ?? 0);
if ($editBarId <= 0 || !in_array($editBarType, ['page', 'post'], true)) {
    return;
}
$canEdit = $editBarType === 'page'
    ? \Core\Auth::can('edit_pages')
    : \Core\Auth::can('edit_posts');
if (!$canEdit) {
    return;
}
$adminEdit = $editBarType === 'page'
    ? admin_url('pages/edit/' . $editBarId)
    : admin_url('posts/edit/' . $editBarId);
$builderEdit = $editBarType === 'page'
    ? admin_url('builder/page/' . $editBarId)
    : admin_url('builder/post/' . $editBarId);
$kindLabel = $editBarType === 'page' ? 'page' : 'post';
?>
<div class="public-edit-bar" role="region" aria-label="Editor shortcuts">
    <span class="public-edit-bar-label">Editing this <?= htmlspecialchars($kindLabel) ?></span>
    <a class="public-edit-bar-link public-edit-bar-link--primary" href="<?= htmlspecialchars($builderEdit) ?>">Edit via Frontend editor</a>
    <a class="public-edit-bar-link" href="<?= htmlspecialchars($adminEdit) ?>">Edit in admin</a>
</div>
