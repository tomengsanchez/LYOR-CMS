<?php
/**
 * Help: System → Remap Audit
 */
?>
<div class="card mb-4">
    <div class="card-body">
        <h5 class="mb-2">Remap Audit</h5>
        <p class="mb-2">
            System guide for administrators: see whether each project still needs
            <strong>Remap</strong> for GRM Channels, Preferred Languages, or In Progress Stages
            after those lists were Initialized for the project.
        </p>
        <ul class="mb-0">
            <li><strong>On defaults</strong> — project not Initialized; Remap is <em>not</em> needed.</li>
            <li><strong>Needs Remap</strong> — project has its own options, but grievances still store default IDs.</li>
            <li><strong>OK</strong> — Initialized and no stale default IDs found.</li>
            <li>Requires <code>view_remap_audit</code>. Running Remap from this page requires <code>run_remap_audit</code>.</li>
            <li>Path: <a href="/system/remap-audit">System → Data tools → Remap Audit</a>.</li>
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
            <li><a href="/help?from=grievance-progress-levels">In Progress Stages</a></li>
            <li><a href="/help?from=admin-guide">Administrator Guide</a></li>
        </ul>
    </div>
</div>
