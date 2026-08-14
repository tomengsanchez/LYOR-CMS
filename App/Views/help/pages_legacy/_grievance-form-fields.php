<?php
/** Shared field guide for grievance create/edit forms. */
$isEdit = !empty($grievanceFormHelpEdit);
?>
<div class="card mb-3">
    <div class="card-body">
        <h5 class="mb-2">How to use this form</h5>
        <p class="mb-2">
            Work through the cards from top to bottom. Fields marked <span class="text-danger">*</span> on the form are required
            when the related lookup lists are configured in the system. Optional fields improve contact, classification, and reporting but can be left blank.
        </p>
        <?php if ($isEdit): ?>
        <p class="mb-0 text-muted">
            On <strong>edit</strong>, changing <strong>Date Recorded</strong> cannot move it later than any existing status history effective date.
            Status and progress are updated on the <a href="/help?from=grievance-view">grievance detail</a> page, not on this form.
        </p>
        <?php else: ?>
        <p class="mb-0 text-muted">
            Users with <strong>Change Status</strong> permission can set the initial status, progress stage, effective date, note, and status attachments before saving.
            Otherwise, the grievance starts as <strong>Open</strong>. Later updates remain on the grievance detail page.
        </p>
        <?php endif; ?>
    </div>
</div>

<?php if (!$isEdit): ?>
<div class="card mb-3">
    <div class="card-body">
        <h5 class="mb-2">Initial Status</h5>
        <p class="mb-2 text-muted small">Shown only to users with <code>change_grievance_status</code>. Leave unchanged for a normal new Open grievance.</p>
        <ul class="mb-0">
            <li><strong>Status</strong> – Open, In Progress, or Closed. Use non-Open values for late encoding of a case already being handled or completed.</li>
            <li><strong>Level</strong> – Required for In Progress and loaded from the selected project's stages (or system defaults).</li>
            <li><strong>Effective date</strong> – When the initial status took effect. Prefills with <strong>Date Recorded</strong> and updates when Date Recorded changes (you can still override). Cannot be earlier than Date Recorded or in the future.</li>
            <li><strong>Note and attachments</strong> – Optional initial history details. Images and PDF files are accepted.</li>
        </ul>
        <p class="mb-0 mt-2">Saving creates one matching Status History entry and starts the escalation clock at the effective date when the initial status is In Progress.</p>
    </div>
</div>
<?php endif; ?>

<div class="card mb-3">
    <div class="card-body">
        <h5 class="mb-2">Grievance Registration</h5>
        <div class="table-responsive">
            <table class="table table-sm table-bordered mb-0">
                <thead><tr><th>Field</th><th>Required?</th><th>Notes</th></tr></thead>
                <tbody>
                    <tr><td>Date Recorded</td><td><strong>Required</strong></td><td>Prefilled with the current date/time on create. Used as the official registration time, for status-history validation, list filters, and dashboard date ranges.</td></tr>
                    <tr><td>Grievance Case Number</td><td>Optional</td><td>Auto-generated when left blank. Must be unique if you enter one manually.</td></tr>
                    <tr><td>Attendant</td><td>Optional</td><td>Choose <strong>Free text</strong> (type a name) or <strong>User</strong> (search a user linked to at least one project via <strong>User Management</strong>). When User mode is used and a project is selected, the attendant must be linked to that project.</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <h5 class="mb-2">Respondent's Profile</h5>
        <div class="table-responsive">
            <table class="table table-sm table-bordered mb-2">
                <thead><tr><th>Field</th><th>Required?</th><th>Notes</th></tr></thead>
                <tbody>
                    <tr><td>Is PAPS</td><td>Optional</td><td>Check when the complainant is a registered PAP. Shows profile picker instead of free-text name.</td></tr>
                    <tr><td>Select Profile (PAPS)</td><td>Required if PAPS</td><td>Search <a href="/help?from=profile">Profiles</a>. Autofills project, municipality, barangay, and contact fields from the profile.</td></tr>
                    <tr><td>Respondent Name</td><td>Recommended if not PAPS</td><td>First / middle / last name for non-PAPS complainants. Typing 3+ letters may suggest prior respondents and show grievance history.</td></tr>
                    <tr><td>Project</td><td>Optional (required when Municipality/Barangay are set)</td><td>From <a href="/help?from=library">Library → Project</a>. Locked when a PAPS profile is selected (comes from profile).</td></tr>
                    <tr><td>Municipality</td><td>Optional</td><td>From Library municipalities linked to the project. If set, Barangay is also required (and vice versa). PAPS: read-only from profile when the profile has location.</td></tr>
                    <tr><td>Barangay</td><td>Optional</td><td>From Library barangays for the municipality and project. If set, Municipality is also required. PAPS: read-only from profile when the profile has location.</td></tr>
                    <tr><td>Phase</td><td>Optional</td><td>After Barangay. Project-level phase from Library → Edit Project. When a PAPS profile is selected, Phase is copied from the profile and remains editable/clearable.</td></tr>
                    <tr><td>Gender</td><td>Optional</td><td>If <em>Others</em>, fill <em>Specify</em>.</td></tr>
                    <tr><td>Valid ID / ID Number</td><td>Optional</td><td>Identification details for the respondent.</td></tr>
                    <tr><td>Vulnerabilities</td><td>Optional</td><td>Configured under Grievance → Options → <a href="/help?from=grievance-vulnerabilities">Vulnerabilities</a>.</td></tr>
                    <tr><td>Respondent Type</td><td>Optional</td><td>Pick category then checkbox options from <a href="/help?from=grievance-respondent-types">Respondent Type</a>. Use <em>Other specify</em> when needed.</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <h5 class="mb-2">Contact Details</h5>
        <p class="mb-2 text-muted small">All optional. For PAPS, values may autofill from the linked profile.</p>
        <ul class="mb-0">
            <li><strong>Home / Business Address</strong></li>
            <li><strong>Mobile Number</strong> and <strong>Email Address</strong></li>
            <li><strong>Others (Specify)</strong> – alternate contact method</li>
        </ul>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <h5 class="mb-2">GRM Mode</h5>
        <div class="table-responsive">
            <table class="table table-sm table-bordered mb-0">
                <thead><tr><th>Field</th><th>Required?</th><th>Notes</th></tr></thead>
                <tbody>
                    <tr><td>GRM Channel</td><td><strong>Required</strong> (if options exist for the project)</td><td>Single choice. Options are <strong>per project</strong> (or system defaults until the project is initialized). Maintain under <a href="/help?from=grievance-grm-channels">GRM Channel</a>. Loaded after you select Project.</td></tr>
                    <tr><td>Preferred Language</td><td><strong>Required</strong> (if options exist for the project)</td><td>At least one checkbox. Options are <strong>per project</strong> (or system defaults). Options from <a href="/help?from=grievance-preferred-languages">Preferred Language</a>.</td></tr>
                    <tr><td>Type of Grievance</td><td><strong>Required</strong> (if options exist)</td><td>At least one. <a href="/help?from=grievance-types">Type of Grievances</a>.</td></tr>
                    <tr><td>Category of Grievance</td><td>Optional</td><td>Zero or more. <a href="/help?from=grievance-categories">Category of Grievance</a>.</td></tr>
                    <tr><td>Preferred Language – Others</td><td>Optional</td><td>Free text when language is not in the list.</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <h5 class="mb-2">Location of Interest</h5>
        <ul class="mb-0">
            <li><strong>Same as home/business address</strong> – Optional checkbox; checked by default on create.</li>
            <li><strong>If no, Specify</strong> – Optional text when the incident location differs from the address.</li>
        </ul>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <h5 class="mb-2">Date of Incident</h5>
        <p class="mb-2 text-muted small">All optional. You may select one or more patterns:</p>
        <ul class="mb-0">
            <li><strong>One-time occurrence</strong> – optional date field</li>
            <li><strong>Multiple occurrences</strong> – optional text (e.g. comma-separated dates)</li>
            <li><strong>Ongoing</strong> – checkbox for continuing problems</li>
        </ul>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <h5 class="mb-2">Description &amp; resolution</h5>
        <ul class="mb-0">
            <li><strong>Description of the Complaint</strong> – Optional but strongly recommended for case handling.</li>
            <li><strong>Desired resolution</strong> – Optional; what the complainant wants to happen.</li>
        </ul>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <h5 class="mb-2">Attachments</h5>
        <p class="mb-2 text-muted small">Optional. Add one or more cards with title, description, and file (images or PDF). Mobile/API clients use the same fields on multipart create/update; the grievance detail API returns download URLs for each card.</p>
        <p class="mb-0">On edit, upload a new file only when replacing an attachment; existing files remain if the file input is left empty.</p>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <h5 class="mb-2">Related modules</h5>
        <ul class="mb-0">
            <li><a href="/help?from=profile">Profiles</a> – PAP records when <em>Is PAPS</em> is checked</li>
            <li><a href="/help?from=library">Library</a> – Projects, municipalities, barangays used for location dropdowns</li>
            <li><a href="/help?from=grievance-list">Grievance list</a> – find and export cases after saving</li>
            <li><a href="/help?from=grievance-view">Grievance detail</a> – status, progress, PDF, history</li>
            <li><a href="/help?from=grievance-dashboard">Grievance Dashboard</a> – KPIs and charts</li>
            <li>Grievance <strong>Options</strong> – vulnerabilities, respondent types, GRM channel, languages, types, categories, in-progress stages (administrators / <code>manage_grievance_options</code>)</li>
            <li><a href="/help?from=users">User Management</a> – for User-mode attendants (linked to projects); Free-text attendant does not require a user account</li>
        </ul>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body">
        <h5 class="mb-2">Common issues</h5>
        <ul class="mb-0">
            <li><strong>Please select both Municipality and Barangay, or leave both empty</strong> – Fill both location fields together, or clear both. Choose project first when setting location (non-PAPS).</li>
            <li><strong>GRM / language / type / category errors</strong> – At least one option must be selected when administrators have defined lookup values.</li>
            <li><strong>Attendant not allowed</strong> – In User mode, pick a user linked to the grievance project, switch to Free text, or leave attendant blank.</li>
            <li><strong>Duplicate case number</strong> – Use another number or leave blank for auto-generation.</li>
            <li><strong>Date Recorded is required</strong> – Enter a valid date and time before saving or importing the grievance.</li>
        </ul>
    </div>
</div>
