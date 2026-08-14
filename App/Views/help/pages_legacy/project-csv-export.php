<?php
/**
 * Help: System → Project CSV Export
 */
?>
<div class="help-section">
    <h3>Project CSV Export</h3>
    <p>
        <strong>System → Data tools → Project CSV Export</strong> builds a ZIP of CSV files for
        <strong>one Library project</strong>: profiles, structures, grievances (and related rows),
        plus <strong>audit / record-update history</strong>, project notifications, and
        <strong>user activity</strong> (sessions and API token metadata) for users linked to that project.
    </p>

    <h4>Who can use it</h4>
    <ul>
        <li>Administrators only (web UI).</li>
        <li>Server operators can run the same export from CLI.</li>
    </ul>

    <h4>What is included</h4>
    <ul>
        <li>Project row, phases, municipality/barangay project links when present</li>
        <li><code>municipalities.csv</code> and <code>barangays.csv</code> for IDs referenced by exported profiles, structures, and grievances</li>
        <li>Profiles, structures, grievances for the project (including soft-deleted)</li>
        <li>Grievance status log, attachments, respondents; profile–structure tag links</li>
        <li><strong>Socio Economic (SES):</strong> all sections from the <strong>latest version only</strong> per profile (<code>profile_socio_sections.csv</code>)</li>
        <li><code>audit_log</code> for those profile / structure / grievance IDs</li>
        <li>Notifications with matching <code>project_id</code></li>
        <li>Linked users (no password hashes), <code>user_projects</code>, <code>user_sessions</code>, API tokens without secret hashes</li>
        <li><code>manifest.json</code> with file list, row counts, and whether PII was redacted</li>
    </ul>
    <p>File-based app logs under <code>logs/</code> and global <code>traffic_events</code> are <strong>not</strong> included (not project-scoped).</p>

    <h4>Redact personal information</h4>
    <p>
        Optional on the web form and via CLI <code>--redact</code>. When enabled, <strong>person names and birthdays</strong> are replaced with <code>[REDACTED]</code>
        in CSV cells and in matching keys inside audit / SES JSON (e.g. First Name, Last Name, Birthday — not Project Name, municipality, barangay, or GPS).
        Contact numbers, addresses, email, and coordinates are <strong>not</strong> redacted.
        Redacted ZIP filenames include <code>-redacted</code>. Treat all exports as sensitive.
    </p>

    <h4>Web</h4>
    <ol>
        <li>Choose a project, optionally check <strong>Redact personal information</strong>, then click <strong>Create ZIP export</strong>.</li>
        <li>Download from the list (files live in <code>storage/project-csv-exports</code>).</li>
    </ol>

    <h4>CLI</h4>
    <pre class="bg-light border rounded p-2 small mb-2"><code>php cli/export_project_csv.php --project-id=123
php cli/export_project_csv.php --project-id=123 --redact
php cli/export_project_csv.php --project-id=123 --out=D:\exports\proj-123.zip --redact</code></pre>

    <h4>Related</h4>
    <ul>
        <li><a href="/help?from=backup-restore">Backup / Restore</a> — full SQL ZIP (not CSV)</li>
        <li><a href="/help?from=csv-templates">CSV Import Templates</a> — import templates for modules</li>
        <li><a href="/system/project-csv-export">Open Project CSV Export</a></li>
    </ul>
</div>
