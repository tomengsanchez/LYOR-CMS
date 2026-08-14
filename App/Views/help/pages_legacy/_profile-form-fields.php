<?php
/** @var bool $isEdit */
$isEdit = !empty($isEdit);
?>
<div class="card mb-3">
    <div class="card-body">
        <h5 class="mb-2">How to use this form</h5>
        <p class="mb-2">
            A <strong>Profile</strong> is a project-affected person (PAP) or business/institution record — not your login account
            (<a href="/help?from=account">My Profile / Account</a>).
        </p>
        <?php if ($isEdit): ?>
        <p class="mb-0 text-muted">
            On edit, use tabs: <strong>Main</strong>, <strong>Socio Economic</strong>, <strong>Structure</strong>, <strong>Validation Data</strong>.
            Socio Economic data is imported from <a href="/help?from=socio-economic">System → Socio Economic</a> ZIP files (requires <code>view_socio_economic</code> to view); it does not change Main fields.
            Save buttons are per tab where shown.
        </p>
        <?php else: ?>
        <p class="mb-0 text-muted">
            On create, the form submits once. Choose <strong>Project</strong> first so municipality and barangay load from Library.
        </p>
        <?php endif; ?>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <h5 class="mb-2">Required vs optional (main fields)</h5>
        <div class="table-responsive">
            <table class="table table-sm table-bordered mb-0">
                <thead><tr><th>Field</th><th>Required?</th><th>Notes</th></tr></thead>
                <tbody>
                    <tr><td>Project</td><td><strong>Required</strong></td><td>Municipality and barangay load from <a href="/help?from=library">Library</a> affected areas for the selected project.</td></tr>
                    <tr><td>Municipality / Barangay</td><td><strong>Required</strong></td><td>Must match project configuration in Library. If the project has only one municipality or barangay, it is selected automatically when you choose the project.</td></tr>
                    <tr><td>Phase</td><td><strong>Required</strong></td><td>Project-level phase from <a href="/help?from=library">Library → Edit Project</a>. Independent of municipality/barangay. Structure Tags still filter by project + municipality + barangay only.</td></tr>
                    <tr><td>Profile type</td><td><strong>Required</strong></td><td>Person or Business / Institution. Editable later. Choose this before entering the identity name.</td></tr>
                    <tr><td>Last Name + First Name</td><td><strong>Required for Person</strong></td><td>Middle name and suffix optional. Age and Birthday apply to Person only.</td></tr>
                    <tr><td>Registered Business Name</td><td><strong>Required for Business</strong></td><td>Official registered business or institution name. Shown in lists, search, and related modules instead of a person name.</td></tr>
                    <tr><td>Representative information</td><td>Optional</td><td>Available for both Person and Business profiles. Name + contact numbers (and address) appear on the Profile list / CSV export when those columns are selected.</td></tr>
                    <tr><td>Contact numbers</td><td><strong>At least one</strong></td><td>Add person label and number; more rows optional.</td></tr>
                    <tr><td>Control Number</td><td>Optional</td><td>Must be unique if provided.</td></tr>
                    <tr><td>Structure Tags</td><td>Optional</td><td>Multi-select from existing tags in the <a href="/help?from=structure">Structure</a> module for the selected <strong>project, municipality, and barangay</strong>. The dropdown shows tag #, structure ID, and classification. Does not create new structures.</td></tr>
                    <tr><td>Type of structure ownership</td><td>Optional</td><td>Owner, <strong>Co-Owner</strong>, Renter, Sharer or Occupants, Caretaker. Owner and Co-Owner show Civil Status.</td></tr>
                    <tr><td>Civil Status</td><td>When Owner/Co-Owner</td><td>Single, Married, Widowed, <strong>Live-In/Common Law Partner</strong>.</td></tr>
                    <tr><td>Spouse / Partner</td><td>When Married or Live-In</td><td>Shown for Owner or Co-Owner when civil status is Married or Live-In/Common Law Partner.</td></tr>
                    <tr><td>Age / Birthday</td><td>Optional</td><td>Validated for minimum age rules when provided.</td></tr>
                    <tr><td>Field Personnel</td><td>Optional</td><td>Users linked to projects.</td></tr>
                    <tr><td>Attachments</td><td>Optional</td><td>Title required when uploading a file. Available on web and API (multipart create/update; listed on profile GET).</td></tr>
                    <tr><td>Socio-economic &amp; validation tabs</td><td>Mostly optional</td><td>Household, representative, address text fields support census-style data.</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php if ($isEdit): ?>
<div class="card mb-3">
    <div class="card-body">
        <h5 class="mb-2">Structure tab (edit)</h5>
        <p class="mb-2">Shows structures linked by the profile's <strong>Structure Tags</strong>. Columns include Structure ID, tag #, <strong>Phase</strong>, classification, and description. You can view or edit existing structures in a modal.</p>
        <p class="mb-0 text-muted">To add links, set <strong>Structure Tags</strong> on the Main tab (or create new structures in the Structure module first). You cannot create structures from this tab.</p>
    </div>
</div>
<?php endif; ?>

<div class="card mb-3">
    <div class="card-body">
        <h5 class="mb-2">Related modules</h5>
        <ul class="mb-0">
            <li><a href="/help?from=library">Library</a> – projects, municipalities, barangays</li>
            <li><a href="/help?from=structure">Structure</a> – physical structures linked via structure tag</li>
            <li><a href="/help?from=grievance-create">Grievance registration</a> – when complainant is PAPS, profile supplies location and contacts</li>
            <li><a href="/help?from=profile">Profile list</a> – search, export, import</li>
        </ul>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body">
        <h5 class="mb-2">Common issues</h5>
        <ul class="mb-0">
            <li><strong>At least one contact number is required</strong> – Add a row with a number before saving.</li>
            <li><strong>Municipality and barangay are required when a project is selected</strong> – Pick both from Library-linked dropdowns (auto-filled when the project has only one of each).</li>
            <li><strong>Duplicate control number</strong> – Use a unique value or leave blank.</li>
            <li><strong>Structure Tags picker is disabled</strong> – Select project, municipality, and barangay on Main first.</li>
            <li><strong>Tag not in the list</strong> – Create the structure (with that tag #) in Structure for the same project and location.</li>
        </ul>
    </div>
</div>
