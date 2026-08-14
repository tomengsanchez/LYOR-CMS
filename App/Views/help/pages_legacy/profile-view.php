<?php
?>
<div class="card mb-3">
    <div class="card-body">
        <h5 class="mb-2">View Profile</h5>
        <p class="mb-0">
            Read-only summary of a PAP record. Use tabs for <strong>Main</strong>, <strong>Socio Economic</strong>,
            <strong>Structure</strong>, and <strong>Validation Data</strong>. The right sidebar shows <strong>Activity History</strong>
            (times follow <a href="/help?from=general">System → General</a> timezone; the header clock is local device time).
        </p>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <h5 class="mb-2">Socio Economic tab</h5>
        <ul class="mb-0">
            <li>Requires <code>view_socio_economic</code>. Data comes from <a href="/help?from=socio-economic">System → Socio Economic</a> ZIP imports (not Main fields).</li>
            <li>Use the version dropdown (zip upload date) to browse historical snapshots (view-only). Changed fields vs the previous version are highlighted; click a changed value to compare previous vs current.</li>
            <li>Toggle <strong>Show empty fields</strong> to include blank survey columns (off by default; preference saved in the browser).</li>
            <li>Each CSV filename is a collapsible section. Sections and nested rows are ordered by <strong>EntryID</strong> (ascending); sections or rows without EntryID appear last (then by section name).</li>
        </ul>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <h5 class="mb-2">Structure tab</h5>
        <ul class="mb-0">
            <li>Lists structures linked by matching <strong>Structure Tag #</strong> values on this profile (same project and location).</li>
            <li>Columns include Structure ID, tag #, <strong>Phase</strong>, <strong>Classification</strong> (Primary / Secondary), description, and images.</li>
            <li>Use <strong>View</strong> or <strong>Edit</strong> to open the structure modal — you cannot add new structures here.</li>
            <li>To link more structures, edit the profile and add tags under <strong>Structure Tags</strong> on the Main tab (tags must already exist in the <a href="/help?from=structure">Structure</a> module).</li>
        </ul>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body">
        <h5 class="mb-2">Related help</h5>
        <ul class="mb-0">
            <li><a href="/help?from=socio-economic">Socio Economic (SES)</a> – ZIP import and audit</li>
            <li><a href="/help?from=profile-edit">Edit Profile</a> – change Structure Tags and other fields</li>
            <li><a href="/help?from=structure">Structure module</a> – create structures and set classification / tagging status</li>
        </ul>
    </div>
</div>
