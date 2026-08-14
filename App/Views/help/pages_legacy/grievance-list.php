<div class="card mb-3">
    <div class="card-body">
        <h5 class="mb-2">This page</h5>
        <p class="mb-0">
            The <strong>Grievances</strong> list shows cases you can access (scoped to your linked projects unless you are an administrator).
            Use filters, column selection, export, and <strong>Import CSV</strong>; open a row to view detail or use <strong>Add Grievance</strong> to register a new case.
        </p>
    </div>
</div>
<div class="card mb-3">
    <div class="card-body">
        <h5 class="mb-2">Actions</h5>
        <ul class="mb-0">
            <li><strong>Add Grievance</strong> – Opens <a href="/help?from=grievance-create">registration form</a> (requires <code>add_grievance</code>).</li>
            <li><strong>Search / filters</strong> – Status, project, stage, respondent, date recorded range, escalation flags.</li>
            <li><strong>Select Columns</strong> – Choose which columns appear in the table.</li>
            <li><strong>Export</strong> – CSV download using current filters and columns.</li>
            <li><strong>Import CSV</strong> – Bulk create/update from CSV (requires <code>add_grievance</code> to open; updates also need <code>edit_grievance</code>). Download the template from <a href="/system/csv-templates/grievance">System → CSV Templates</a> or <a href="/grievance/import/sample">sample download</a>, replace placeholders, run <strong>Preview</strong>, then <strong>Import ready rows</strong>. Match prefers <code>id</code>, then existing <code>grievance_case_number</code>; otherwise creates a new case. Unknown or ambiguous names need clarification (options/locations are not auto-created). Cap 2000 data rows per file.</li>
            <li><strong>Row click / View</strong> – Opens <a href="/help?from=grievance-view">grievance detail</a>.</li>
        </ul>
    </div>
</div>
<div class="card mb-3">
    <div class="card-body">
        <h5 class="mb-2">Related modules</h5>
        <ul class="mb-0">
            <li><a href="/help?from=grievance-create">Create grievance</a></li>
            <li><a href="/help?from=grievance-dashboard">Grievance Dashboard</a></li>
            <li><a href="/help?from=grievance-respondents">Respondent Profiles</a></li>
            <li><a href="/help?from=profile">Profiles</a> – for PAPS-linked complainants</li>
            <li><a href="/help?from=csv-templates">CSV Import Templates</a> – column guide and sample downloads</li>
        </ul>
    </div>
</div>
