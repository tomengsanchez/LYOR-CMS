<div class="card mb-3">
    <div class="card-body">
        <h5 class="mb-2">This page</h5>
        <p class="mb-0">
            Read-only summary of one grievance: registration, respondent, contacts, GRM classification, incident details, attachments,
            and <strong>status / progress</strong> with history. Use <strong>Edit</strong> to change registration data on the
            <a href="/help?from=grievance-edit">edit form</a>.
        </p>
    </div>
</div>
<div class="card mb-3">
    <div class="card-body">
        <h5 class="mb-2">Status &amp; progress (on this page)</h5>
        <div class="table-responsive">
            <table class="table table-sm table-bordered mb-0">
                <thead><tr><th>Item</th><th>Required?</th><th>Notes</th></tr></thead>
                <tbody>
                    <tr><td>Date Recorded</td><td>On <a href="/help?from=grievance-edit">edit form</a> only</td><td>Registration date shown at the top of this page. Use <strong>Edit</strong> — not the status <strong>Effective date</strong> below.</td></tr>
                    <tr><td>Status change</td><td>When updating</td><td>Open → In Progress → Closed. Requires <code>change_grievance_status</code>.</td></tr>
                    <tr><td>Progress stage</td><td>When In Progress</td><td>Pick a stage from <a href="/help?from=grievance-progress-levels">In Progress Stages</a>.</td></tr>
                    <tr><td>Closed at stage</td><td>When Closed</td><td>Shows the <strong>last In Progress stage</strong> before closure and the <strong>closed on</strong> date (closure effective date). Tickets closed directly from Open show <em>Closed from Open</em>.</td></tr>
                    <tr><td>Effective date</td><td><strong>Required</strong> for status log</td><td>When the status/stage took effect. Defaults to your browser local date/time. Cannot be before date recorded or before prior status effective dates.</td></tr>
                    <tr><td>Notes on status change</td><td>Optional</td><td>Stored in status history.</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
<div class="card mb-3">
    <div class="card-body">
        <h5 class="mb-2">Other actions</h5>
        <ul class="mb-0">
            <li><strong>PDF</strong> – Printable summary of the grievance.</li>
            <li><strong>Attachments</strong> – Add or remove files (when permitted).</li>
            <li><strong>Escalation badges</strong> – Shown when a stage exceeds configured days-to-address; see <a href="/help?from=grievance-dashboard">dashboard help</a>.</li>
            <li><strong>Audit history</strong> – Field-level Activity History (from → to) is recorded for edits from the web form and from the API. API updates also show <code>source: api</code>. Timestamps use the organization timezone (System → General). Status History primary times use <strong>effective date</strong> (business time, not converted).</li>
        </ul>
    </div>
</div>
<div class="card mb-3">
    <div class="card-body">
        <h5 class="mb-2">Related modules</h5>
        <ul class="mb-0">
            <li><a href="/help?from=grievance-edit">Edit grievance form</a></li>
            <li><a href="/help?from=profile">Profiles</a> – linked when respondent is PAPS</li>
            <li><a href="/help?from=grievance-list">Grievance list</a></li>
        </ul>
    </div>
</div>
