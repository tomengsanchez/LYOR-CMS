<?php
/**
 * Help: Grievance → Options → Preferred Languages
 */
?>
<div class="card mb-4">
    <div class="card-body">
        <h5 class="mb-2">Preferred Languages</h5>
        <p class="mb-2">
            Preferred language(s) of communication for a grievance.
            On the form, <strong>at least one language is required</strong> when options exist for the selected project
            (project list or shared defaults).
        </p>
        <ul class="mb-0">
            <li>Filter by <strong>Project scope</strong> to manage defaults or one project’s list.</li>
            <li>Use <strong>Initialize this project</strong> to copy defaults into a project so you can customize them.</li>
            <li>Use <strong>Re-map existing records</strong> after Initialize if that project already has grievances.</li>
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
            <li><a href="/help?from=grievance-progress-levels">In Progress Stages</a></li>
            <li><a href="/help?from=grievance-create">Create grievance</a></li>
        </ul>
    </div>
</div>
