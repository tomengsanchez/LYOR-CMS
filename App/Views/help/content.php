<?php
require_once __DIR__ . '/helpers.php';
$module = $module ?? 'general';
?>
<div class="mb-3">
    <h2 class="mb-2">Help: <?= htmlspecialchars(help_module_title($module)) ?></h2>
    <p class="text-muted small">Simple CMS admin guide.</p>
</div>
<?php if (($pagePartial = help_page_partial($module)) !== null): ?>
<?php require $pagePartial; ?>
<?php else: ?>
<div class="card mb-3"><div class="card-body">
<?php if ($module === 'general' || $module === 'dashboard'): ?>
<h5>Getting started</h5>
<p>Use the sidebar to manage content. Customize your admin appearance under <strong>Settings → UI &amp; Notifications</strong> (accent theme, <strong>light/dark/system</strong> color mode, sidebar layout). Customize the <strong>public</strong> site under <strong>Appearance → Customize</strong> (live preview) or <strong>System → General → Public site theme</strong>. Visitors see the color mode you choose; there is no public light/dark toggle.</p>
<ul>
<li><strong>Pages</strong> — Static content (<code>/admin/pages</code>). Slug <code>welcome</code> or <code>home</code> powers the homepage at <code>/</code>.</li>
<li><strong>Posts</strong> — Blog articles with optional category (<code>/admin/posts</code>)</li>
<li><strong>Media</strong> — Upload images and PDFs (<code>/admin/media</code>)</li>
<li><strong>System</strong> — Backup/restore and audit trail (<code>/admin/system/…</code>, administrators)</li>
</ul>
<?php else: ?>
<p>See module-specific topics: Pages, Posts, Categories, Media, Settings, Users.</p>
<?php endif; ?>
</div></div>
<?php endif; ?>
