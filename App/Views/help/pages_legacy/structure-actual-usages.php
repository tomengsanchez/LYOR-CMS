<?php
/** Page help: Structure Options Library — Actual Usage */
?>
<div class="card mb-3">
    <div class="card-body">
        <h5 class="mb-2">Actual Usage (suggestion store)</h5>
        <p class="mb-2">
            Structure <strong>Actual usage</strong> remains <strong>free text</strong>. This library only stores previously encoded values
            so the form can suggest them (browser datalist / mobile options list). Saving a new usage on a structure adds it here automatically.
        </p>
        <ul class="mb-0">
            <li>Requires <code>manage_structure_options</code> to manage this list.</li>
            <li>On upgrade, distinct existing structure usages are imported when the store is empty.</li>
            <li>Deleting a suggestion does not clear values already saved on structures.</li>
            <li>Mobile: <code>GET /api/structure/options</code> → <code>actual_usages</code> for suggestions; still send free-text <code>actual_usage</code>.</li>
        </ul>
    </div>
</div>
