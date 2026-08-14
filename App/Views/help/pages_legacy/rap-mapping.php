<div class="card mb-3">
    <div class="card-body">
        <h5 class="mb-2">Overview</h5>
        <p class="mb-2">
            <strong>SES → RAP Mapping</strong> connects survey and operational columns to Resettlement Action Plan (RAP) fields.
            Sources can be <strong>SES</strong> CSV columns, <strong>Structure</strong> fields, or <strong>Grievance (Concern)</strong> fields.
            Project RAP summaries stay read-only — they do not change Main profile fields or source storage.
        </p>
        <p class="mb-0 text-muted">
            RAP field definitions and maps are global. Project <strong>RAP summary</strong> pages apply those maps per project.
            Structure values link via owner / tagged profile; grievances via linked <code>profile_id</code>.
        </p>
    </div>
</div>
<div class="card mb-3">
    <div class="card-body">
        <h5 class="mb-2">Steps — configure fields &amp; maps</h5>
        <ol class="mb-0">
            <li>Import SES data under <a href="/help?from=socio-economic">System → Socio Economic</a> (optional if you only map Structure/Grievance).</li>
            <li>Open <strong>System → Data tools → SES → RAP Mapping</strong> (<code>view_rap_mapping</code> / <code>manage_rap_mapping</code>).</li>
            <li><strong>Add RAP field:</strong> label, unique field key (slug), category, operation. For range ops, set Min and/or Max. Drag the ⋮⋮ handle on the fields table to set report order.</li>
            <li><strong>Add map:</strong> choose RAP field, source entity (SES / Structure / Grievance), then use the searchable picker (Select2) for the SES column or Structure/Grievance catalog field.</li>
            <li>Edit or deactivate fields from the table (inactive fields are excluded from summaries; maps are kept).</li>
        </ol>
    </div>
</div>
<div class="card mb-3">
    <div class="card-body">
        <h5 class="mb-2">Operations</h5>
        <ul class="mb-0">
            <li><code>first</code> — first non-empty mapped value</li>
            <li><code>list</code> — unique non-empty values joined with <code>; </code></li>
            <li><code>sum</code> — numeric sum (commas stripped)</li>
            <li><code>average</code> — numeric mean of parsed numbers (empty/non-numeric ignored)</li>
            <li><code>count</code> — count of non-empty values</li>
            <li><code>count_in_range</code> — count of numeric values between Min and Max (inclusive; either bound optional)</li>
            <li><code>sum_in_range</code> — sum of numeric values in that range</li>
        </ul>
        <p class="mb-0 mt-2 small text-muted">
            Example: income column with <code>count_in_range</code> Min=10000 Max=200000 → per-PAP count of values in that band.
        </p>
    </div>
</div>
<div class="card mb-3">
    <div class="card-body">
        <h5 class="mb-2">Project RAP summary</h5>
        <ul class="mb-0">
            <li>From <strong>Library → View project</strong>, open <strong>RAP summary</strong> (or <code>/library/view/{id}/rap</code>).</li>
            <li>Requires <code>view_projects</code> plus <code>view_rap_mapping</code>, <code>manage_rap_mapping</code>, or <code>view_socio_economic</code>.</li>
            <li>Shows counts, distributions / numeric totals, and a per-PAP table (first 100 profiles).</li>
            <li>API: <code>GET /api/library/{id}/rap-summary</code>; maps/columns: <code>GET /api/system/rap-mapping</code>, <code>GET /api/system/rap-mapping/columns</code>.</li>
        </ul>
    </div>
</div>
<div class="card mb-4">
    <div class="card-body">
        <h5 class="mb-2">Capabilities</h5>
        <ul class="mb-0">
            <li><code>view_rap_mapping</code> — System mapping page and RAP summaries</li>
            <li><code>manage_rap_mapping</code> — RAP fields (add/edit/activate), operations, and column maps</li>
        </ul>
        <p class="mb-0 mt-2 small text-muted">
            Related: <a href="/help?from=socio-economic">Socio Economic (SES)</a>,
            <a href="/help?from=structure">Structure</a>,
            <a href="/help?from=grievance">Grievance</a>,
            <a href="/help?from=library">Library</a>.
        </p>
    </div>
</div>
