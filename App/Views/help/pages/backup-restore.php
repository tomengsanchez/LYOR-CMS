<?php
/**
 * Help: System → Backup/Restore
 */
?>
<div class="help-section">
    <h3>Backup / Restore</h3>
    <p>
        <strong>System → Backup &amp; Restore</strong> (<code>/admin/system/backup-restore</code>) lets administrators create and download full Simple CMS
        backup ZIP archives. <strong>Restore is CLI-only</strong> (not available in the web UI) so production
        restores stay intentional and auditable.
    </p>

    <h4>Who can access it</h4>
    <ul>
        <li>Administrators (System Backup/Restore page).</li>
        <li>Server operators who can run PHP CLI on the application host for restore.</li>
    </ul>

    <h4>Create a backup (web or CLI)</h4>
    <ul>
        <li>In the UI: choose <strong>Create backup now</strong>. Check <strong>Exclude uploads (DB-only backup)</strong>
            for SQL-only ZIPs (CLI equivalent: <code>php cli/backup.php --no-uploads</code>).</li>
        <li>Or from the project root: <code>php cli/backup.php</code></li>
        <li>Archives are stored under <code>storage/backups/</code> as
            <code>paper-backup-YYYYmmdd-HHMMSS.zip</code> (treat as secret: full database + files).</li>
        <li>Each ZIP includes <code>manifest.json</code> (app id <code>SimpleCMS</code>, source dbname, schema snapshot),
            <code>database.sql</code>, and usually <code>uploads/</code> (installed theme style packs live under
            <code>public/uploads/theme-packs/library/</code>; active pack ids/settings are in <code>app_settings</code>).
            Page and post visual layouts are stored as <code>layout_json</code> in the SQL dump (frontend-editor autosave uses the same column).</li>
    </ul>

    <h4>Restore (CLI only)</h4>
    <p>From the project root on the target server:</p>
    <pre class="bg-light border rounded p-2 small mb-2"><code>php cli/restore.php --from=storage/backups/paper-backup-YYYYmmdd-HHMMSS.zip --yes</code></pre>
    <ul>
        <li>Imports into the database named in <code>config/database.php</code>.</li>
        <li>Creates a pre-restore safety ZIP unless <code>--skip-safety-backup</code>.</li>
        <li><strong>Wipes</strong> all tables/views in the target database before import unless <code>--keep-extra-tables</code>.</li>
        <li><strong>Refuses</strong> backups whose manifest <code>app</code> is not <code>SimpleCMS</code> (legacy <code>PAPeR</code> archives are also accepted), or whose
            dbname differs from config, unless you pass <code>--force</code>.</li>
        <li>After import, runs pending migrations unless <code>--no-migrate</code>.</li>
    </ul>

    <h4>Common flags</h4>
    <ul>
        <li><code>--yes</code> — skip interactive confirmation</li>
        <li><code>--force</code> — allow foreign/missing app id or dbname mismatch</li>
        <li><code>--keep-extra-tables</code> — do not wipe leftover tables before import</li>
        <li><code>--no-uploads</code> / <code>--no-migrate</code> / <code>--large-mode</code></li>
    </ul>

    <h4>FAQs</h4>
    <ul>
        <li><strong>Why can’t I restore in the browser?</strong> – Restore replaces the live database; it is restricted to CLI on purpose.</li>
        <li><strong>Restore says app/dbname mismatch.</strong> – Confirm the ZIP is a Simple CMS backup. For a renamed database on the same product, add <code>--force</code>.</li>
        <li><strong>Local content reset (not restore).</strong> – To wipe CMS content back to Welcome / Hello World without importing a ZIP, use
            <code>php cli/truncate_fresh_install.php</code> (never on production). See DEVELOPMENTGUIDE → Fresh-install truncate.</li>
        <li><strong>More detail for operators</strong> – See the
            <a href="<?= admin_url('admin-guide') ?>">Administrator Guide</a> and README § Backup and restore.</li>
    </ul>
</div>
