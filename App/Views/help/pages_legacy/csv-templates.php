<?php
/**
 * Help: System → CSV Templates
 */
?>
<div class="help-section">
    <h3>CSV Import Templates</h3>
    <p>
        <strong>System → Data tools → CSV Templates</strong> hosts downloadable sample CSVs and a column guide
        for <strong>Profile</strong>, <strong>Structure</strong>, and <strong>Grievance</strong>.
        Imports still run from each module list (Preview, then Import ready rows).
    </p>

    <h4>Who can access it</h4>
    <ul>
        <li>Administrators, or users with <code>add_profiles</code>, <code>add_structure</code>, or <code>add_grievance</code>.</li>
        <li>Each template download also requires the matching add capability for that module.</li>
    </ul>

    <h4>Column guide</h4>
    <ul>
        <li><strong>Required</strong> – Must be present / valid for a row to become ready.</li>
        <li><strong>Needs clarification</strong> – Name-based lookups (project, place, option names, dual identity fields). Unknown or ambiguous values are not auto-created and are not imported until fixed.</li>
        <li><strong>Optional</strong> – Mapped when present (including visitation witness fields for Structure).</li>
    </ul>

    <h4>Matching (new vs update)</h4>
    <ul>
        <li>Prefer numeric <code>id</code> when present and found.</li>
        <li>Otherwise: Profile <code>papsid</code> or <code>control_number</code>; Structure <code>strid</code> or project + <code>structure_tag</code>; Grievance <code>grievance_case_number</code>.</li>
        <li>Preview shows <strong>New</strong>, <strong>Update</strong>, <strong>Failed</strong>, and <strong>Needs clarification</strong> counts before commit. Cap: 2000 data rows per file.</li>
        <li><strong>Profile</strong> template includes invitation card fields (RSVP, distribution status, <em>invitation 1st visit</em> and <em>2nd visit</em> staff/dates) plus optional legacy <code>visit_1</code>/<code>visit_2</code>/<code>visit_3</code> columns, address, and representative fields.</li>
    </ul>

    <h4>Related</h4>
    <ul>
        <li><a href="/help?from=profile">Profiles</a> – list Import</li>
        <li><a href="/help?from=structure">Structure</a> – list Import</li>
        <li><a href="/help?from=grievance-list">Grievance list</a> – Import CSV</li>
        <li><a href="/system/csv-templates">Open CSV Templates</a></li>
    </ul>
</div>
