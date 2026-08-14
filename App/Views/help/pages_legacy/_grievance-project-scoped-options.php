<?php
/**
 * Shared guidance: when to Initialize / Remap project-scoped grievance options.
 * Used by GRM Channels, Preferred Languages, and In Progress Stages help pages.
 */
?>
<div class="card mb-4">
    <div class="card-body">
        <h5 class="mb-2">Initialize vs Remap (when do I need Remap?)</h5>
        <p class="mb-2">
            GRM Channels, Preferred Languages, and In Progress Stages can be <strong>per project</strong>.
            Until a project has its own list, the system uses <strong>shared defaults</strong>
            (<code>project_id</code> empty).
        </p>
        <div class="table-responsive">
            <table class="table table-sm table-bordered mb-3">
                <thead>
                    <tr>
                        <th>Situation</th>
                        <th>What the project uses</th>
                        <th>Need Remap?</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>No project-specific options yet (not Initialized)</td>
                        <td>Global <strong>defaults</strong></td>
                        <td><strong>No</strong> — existing grievances already point at default IDs</td>
                    </tr>
                    <tr>
                        <td>You click <strong>Initialize this project</strong></td>
                        <td>Copies of defaults with <em>new</em> IDs for that project</td>
                        <td><strong>Yes</strong> if the project already has grievances (so stored IDs match the new project list)</td>
                    </tr>
                    <tr>
                        <td>Fresh / new installation (no old grievances)</td>
                        <td>Defaults after migrate + option seeder; Initialize optional</td>
                        <td><strong>No</strong> — nothing to remap yet</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <ul class="mb-2">
            <li><strong>Do I need Remap if this project has no GRM / languages / stages of its own?</strong> —
                No. Leave it on defaults; filing grievances works without Remap.</li>
            <li><strong>Is In Progress Stages the same?</strong> —
                Yes. Same rule: Remap only after Initialize when records still reference default stage IDs.</li>
            <li><strong>Is this included on a brand-new install?</strong> —
                Yes. Run <code>php cli/migrate.php</code> (includes migration <code>085</code> and progress-level scoping)
                then <code>php database/seeders/seed_grievance_options.php</code> for default lookups.
                Per-project lists remain optional via Initialize.</li>
        </ul>
        <p class="mb-0 small text-muted">
            Prefer <a href="/system/remap-audit">System → Remap Audit</a> to see which projects need Remap
            (requires <code>view_remap_audit</code>).
            CLI (optional): <code>php cli/remap_progress_levels.php</code>,
            <code>php cli/remap_grm_language_options.php</code>
            (see <code>--project=</code> / <code>--only=</code> in script headers).
            Read-only check: <code>php cli/assess_grm_language_scope.php</code>.
        </p>
    </div>
</div>
