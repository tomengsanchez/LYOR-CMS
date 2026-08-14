<div class="card mb-3">
    <div class="card-body">
        <h5 class="mb-2">Overview</h5>
        <p class="mb-2">
            <strong>Socio Economic (SES)</strong> stores field-survey CSV data linked to PAP profiles by
            <code>CONTROL ID</code> (also accepted: <code>Control Number</code>, <code>CONTROL_ID</code>).
            It does <strong>not</strong> update Main profile fields.
        </p>
        <p class="mb-0 text-muted">
            Upload one <code>.zip</code> that contains all SES CSV files. Each CSV filename becomes a collapsible
            section on the profile <strong>Socio Economic</strong> tab. Version history is per zip upload.
        </p>
    </div>
</div>
<div class="card mb-3">
    <div class="card-body">
        <h5 class="mb-2">ZIP rules</h5>
        <ul class="mb-0">
            <li>Accept <strong>.zip only</strong> (`.7z` is blocked), maximum <strong>10 MB</strong> per upload.</li>
            <li>Put all CSV files at the <strong>archive root</strong> — no subfolders.</li>
            <li>Non-CSV files are ignored.</li>
            <li>Do <strong>not rename CSV filenames casually</strong>; the filename (without <code>.csv</code>) is the section title and identity for create vs update.</li>
            <li>Every data CSV must include a control column: <code>CONTROL ID</code>, <code>Control Number</code>, or <code>CONTROL_ID</code>.</li>
            <li>Multiple rows per control number are allowed (shown as Row 1, Row 2, … ordered by <code>EntryID</code> when present).</li>
            <li>Empty CONTROL ID rows are skipped and counted in the import log.</li>
            <li>Unknown control numbers are skipped (no new profiles are created) and listed in the audit detail.</li>
            <li>Only profiles in your allowed projects are matched.</li>
        </ul>
    </div>
</div>
<div class="card mb-3">
    <div class="card-body">
        <h5 class="mb-2">Steps — import</h5>
        <ol class="mb-0">
            <li>Open <strong>System → Data tools → Socio Economic</strong> (requires <code>import_socio_economic</code> and/or <code>view_socio_economic_audit</code>).</li>
            <li>Choose a flat <code>.zip</code> of SES CSVs, click <strong>Preview</strong>, review matched/unmatched summary and progress.</li>
            <li>Click <strong>Import</strong> to write socio sections, create a full version snapshot per affected profile, and record the system audit batch.</li>
        </ol>
    </div>
</div>
<div class="card mb-3">
    <div class="card-body">
        <h5 class="mb-2">Profile Socio Economic tab</h5>
        <ul class="mb-0">
            <li>Requires <code>view_socio_economic</code>.</li>
            <li>Version dropdown lists each zip upload date/time; selecting a version shows <strong>all</strong> sections from that snapshot (view-only).</li>
            <li>Latest version is the current data. Fields changed vs the previous version are highlighted; click a changed value to see previous vs current.</li>
            <li>Use <strong>Show empty fields</strong> to include blank survey columns (off by default). Preference is saved in the browser and also available on System → Socio Economic.</li>
            <li>Collapsible headers use CSV filenames. Sections and rows are ordered by <strong>EntryID</strong> (ascending); missing EntryID sorts last.</li>
        </ul>
    </div>
</div>
<div class="card mb-3">
    <div class="card-body">
        <h5 class="mb-2">Audit log</h5>
        <p class="mb-0">
            Each import records date, uploader, projects affected, profiles/sections created vs updated, and unmatched control numbers.
            Original ZIP files are stored under uploads and included in backup/restore with the database tables.
        </p>
    </div>
</div>
<div class="card mb-4">
    <div class="card-body">
        <h5 class="mb-2">Capabilities</h5>
        <ul class="mb-0">
            <li><code>import_socio_economic</code> — upload/preview/import ZIP</li>
            <li><code>view_socio_economic_audit</code> — System SES audit list/detail</li>
            <li><code>view_socio_economic</code> — profile Socio Economic tab</li>
        </ul>
        <p class="mb-0 mt-2 small text-muted">
            After import, map CSV columns to RAP fields under
            <a href="/help?from=rap-mapping">System → SES → RAP Mapping</a>.
        </p>
    </div>
</div>
