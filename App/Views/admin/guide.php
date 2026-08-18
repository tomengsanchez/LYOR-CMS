<?php
ob_start();
$branding = \App\Models\AppSettings::getBrandingConfig();
$appName = htmlspecialchars($branding->app_name ?? 'Simple CMS');
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-0">Administrator Guide</h2>
        <p class="text-muted small mb-0">Operate, configure, and maintain <?= $appName ?> from an administrator perspective.</p>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <h5 class="mb-2">1. Overview</h5>
        <p class="mb-2"><?= $appName ?> is a lightweight PHP CMS for pages, blog posts, categories, and media. The public site is served at <code>/</code>; the admin panel lives under <code>/admin</code>.</p>
        <ul class="mb-0">
            <li><strong>Public routes</strong> – <code>/</code> (homepage), <code>/p/{slug}</code>, <code>/blog</code>, <code>/search</code>, <code>/subscribe</code></li>
            <li><strong>Admin routes</strong> – <code>/admin/login</code>, <code>/admin/pages</code>, <code>/admin/posts</code>, …</li>
            <li><strong>Stack</strong> – PHP 8+, MySQL/MariaDB, Bootstrap 5, jQuery; entry point <code>public/index.php</code></li>
            <li><strong>Config</strong> – <code>config/database.php</code>, optional <code>config/app.php</code> for base URL</li>
        </ul>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <h5 class="mb-2">2. Content management</h5>
        <ul class="mb-0">
            <li><strong>Pages</strong> – Static HTML pages. Set homepage under System → General → Reading.</li>
            <li><strong>Posts</strong> – Blog with categories, tags, featured images, sticky/scheduled publish, archives, author pages, and list bulk actions.</li>
            <li><strong>Menus</strong> – Custom public header navigation (<code>/admin/menus</code>).</li>
            <li><strong>Media</strong> – Upload in Media library or directly on page/post forms (featured + block images); public <code>/share/media/{id}</code>; responsive <code>srcset</code>.</li>
            <li><strong>Categories</strong> – Optional grouping for posts.</li>
        </ul>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <h5 class="mb-2">3. Users, security, and settings</h5>
        <ul class="mb-0">
            <li><strong>Users &amp; roles</strong> – <code>/admin/users</code>, capabilities per role (Administrator bypasses checks).</li>
            <li><strong>Security</strong> – Email 2FA, idle logout, login throttling, password policy (<code>/admin/settings/security</code>).</li>
            <li><strong>Email</strong> – SMTP or MailerSend for system mail and optional notification emails.</li>
            <li><strong>Branding &amp; theme</strong> – App name, company, logo, and public site theme (presets, colors, typography) under <code>/admin/system/general</code>.</li>
        </ul>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <h5 class="mb-2">4. Backup and restore</h5>
        <ul class="mb-2">
            <li>Create backups from <a href="<?= admin_url('system/backup-restore') ?>">System → Backup &amp; Restore</a> or <code>php cli/backup.php</code>.</li>
            <li>Archives land in <code>storage/backups/</code> (database + uploads). Treat ZIPs as confidential.</li>
            <li>Restore is <strong>CLI-only</strong>: <code>php cli/restore.php --from=storage/backups/your-file.zip --yes</code></li>
        </ul>
        <p class="mb-0 text-muted small">See Help → Backup/Restore and README § Backup and restore for the full operator checklist.</p>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <h5 class="mb-2">5. API</h5>
        <p class="mb-0">REST endpoints under <code>/api/*</code> (auth, pages, posts, media, notifications). Import <code>docs/postman/Simple-CMS-API.postman_collection.json</code> for examples. API paths are unchanged by the <code>/admin</code> UI prefix.</p>
    </div>
</div>

<?php
$content = ob_get_clean();
$pageTitle = 'Administrator Guide';
$currentPage = 'admin-guide';
require __DIR__ . '/../layout/main.php';
