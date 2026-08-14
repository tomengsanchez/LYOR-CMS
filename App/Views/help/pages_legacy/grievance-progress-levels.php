<?php
/**
 * Help: Grievance → Options → In Progress Stages
 */
?>
<div class="card mb-4">
    <div class="card-body">
        <h5 class="mb-2">In Progress Stages</h5>
        <p class="mb-2">
            Progress stages (levels) used when a grievance status is <strong>In Progress</strong>,
            including days-to-address for escalation. Stages are <strong>per project</strong>,
            with shared defaults until a project is initialized.
        </p>
        <ul class="mb-0">
            <li>Filter by <strong>Project scope</strong> to manage defaults or one project’s stages.</li>
            <li>Use <strong>Initialize this project</strong> to copy default Level 1/2/3 into a project.</li>
            <li>Use <strong>Re-map existing records</strong> after Initialize if that project already has grievances or status history pointing at default stage IDs.</li>
            <li>Requires <code>manage_grievance_options</code>.</li>
        </ul>
    </div>
</div>
<?php require __DIR__ . '/_grievance-project-scoped-options.php'; ?>
<div class="card mb-4">
    <div class="card-body">
        <h5 class="mb-2">Related</h5>
        <ul class="mb-0">
            <li><a href="/help?from=grievance-grm-channels">GRM Channels</a></li>
            <li><a href="/help?from=grievance-preferred-languages">Preferred Languages</a></li>
            <li><a href="/help?from=grievance-view">Grievance detail / status change</a></li>
            <li><a href="/help?from=grievance-settings">Grievance Settings</a> (weekend/holiday exclusions)</li>
        </ul>
    </div>
</div>
