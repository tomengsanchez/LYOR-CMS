<?php
$isEdit = !empty($structureFormHelpEdit);
?>
<div class="card mb-3">
    <div class="card-body">
        <h5 class="mb-2">How to use this form</h5>
        <p class="mb-0">
            Record a physical structure (building, house, etc.). PAPS/owners are <em>not</em> assigned here — they link automatically when
            a <a href="/help?from=profile">Profile</a> may select the same <strong>Structure Tag #</strong> (one or more) for the project.
        </p>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <h5 class="mb-2">Required vs optional</h5>
        <div class="table-responsive">
            <table class="table table-sm table-bordered mb-0">
                <thead><tr><th>Field</th><th>Required?</th><th>Notes</th></tr></thead>
                <tbody>
                    <tr><td>Structure Tag #</td><td>Recommended</td><td>Matches profile <em>Structure Tag #</em> to link people to this structure.</td></tr>
                    <tr><td>Project / Municipality / Barangay</td><td>Optional</td><td>Same Library linkage as profiles; all three work together when used.</td></tr>
                    <tr><td>Phase</td><td>Required when Project is set</td><td>After Barangay. Project-level only (not tied to municipality/barangay). Configure under <a href="/help?from=library">Library → Edit Project</a>. If the project has no phases, save is blocked until phases are added.</td></tr>
                    <tr><td>Tagging &amp; visitation fields</td><td>Optional</td><td>Location, <strong>PN number</strong>, classification (<strong>Primary</strong> / <strong>Secondary</strong>), GPS (click coordinates or <em>Preview map</em> for a satellite dialog; <em>View full on new page</em> opens Google Maps), <strong>Actual usage</strong> (free text with suggestions from previously saved values), and <strong>Status</strong> (from Structure → Options Library). <strong>Visitation Data</strong> has First / 2nd / Third visit cards: for each visit, Name of Witness 1–2, Position and Organization 1–2, Date of Visitation 1–2, and Remarks. Legacy single visit dates remain in the database (synced from Witness 1’s date on save for list filters). When <strong>Secondary</strong> and a Primary is linked, use <em>Copy visitation from primary</em> to fill witnesses/dates (remarks get an <em>Adapted/inherited from primary</em> note; still editable). Status changes appear in <strong>Status History</strong> on the view page.</td></tr>
                    <tr><td>Description</td><td>Optional</td><td>Free-text comments.</td></tr>
                    <tr><td>Other details</td><td>Optional</td><td>Additional notes; shown on Structure tagging information (after Description), not on a separate measurements tab.</td></tr>
                    <tr><td>Tagging / Structure images</td><td>Optional</td><td>Upload images; remove existing on edit with × on thumbnail.</td></tr>
                    <tr><td>Structure ID (STRID)</td><td>System</td><td><?= $isEdit ? 'Read-only on edit.' : 'Assigned on create.' ?></td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <h5 class="mb-2">Related modules</h5>
        <ul class="mb-0">
            <li><a href="/help?from=profile-create">Add Profile</a> / <a href="/help?from=profile-edit">Edit Profile</a> – optionally link PAPS to existing structure tags</li>
            <li><a href="/help?from=library">Library</a> – project and location master data</li>
            <li><a href="/help?from=structure">Structure list</a> – browse with filters (tag #, first visit dates, linked PAPS); view page shows linked PAPS (Owner, Co-Owner, Renter, Sharer/Occupants, Caretaker) and Status History</li>
            <li><a href="/help?from=structure-tagging-statuses">Tagging Status</a> – Options Library dropdown / <a href="/help?from=structure-actual-usages">Actual Usage</a> – free-text suggestion store</li>
        </ul>
    </div>
</div>
