<?php
/** Page help: Structure Options Library — Tagging Status */
?>
<div class="card mb-3">
    <div class="card-body">
        <h5 class="mb-2">Tagging Status (Options Library)</h5>
        <p class="mb-2">
            Maintain the <strong>Status of tagging</strong> values used on Structure create/edit and list filters.
            Each row has a stable <strong>code</strong> (stored on structure records) and a display <strong>name</strong>.
        </p>
        <ul class="mb-0">
            <li>Requires <code>manage_structure_options</code> (Administrators receive it on install/migrate).</li>
            <li>Default statuses are seeded on install (Tagged, Owner Refused, Vacant, Other, etc.).</li>
            <li>Flags: <em>Other text</em> shows the “Other (specify)” field; <em>Refusal reason</em> shows the refusal textarea.</li>
            <li>Avoid renaming codes already used on structures — change the name instead when possible.</li>
            <li>Mobile apps load the same list via <code>GET /api/structure/options</code>.</li>
        </ul>
    </div>
</div>
