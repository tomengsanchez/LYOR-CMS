<?php
$pscProfileId = (int) ($profileStructureCrudProfileId ?? 0);
$pscOwnerLabel = (string) ($profileStructureCrudOwnerLabel ?? '');
$pscShowImages = !empty($profileStructureCrudShowImageColumn);
if ($pscProfileId <= 0) {
    return;
}
?>
<div class="modal fade" id="profileStructureCrudModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="profileStructureCrudTitle">Structure</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="profileStructureViewPanel" class="d-none"></div>
                <form id="profileStructureCrudForm" class="d-none" enctype="multipart/form-data">
                    <input type="hidden" name="owner_id" id="profileStructureOwnerId" value="<?= (int) $pscProfileId ?>">
                    <input type="hidden" id="profileStructureEditId" value="" autocomplete="off">
                    <div class="mb-3 d-none" id="profileStructureStridWrap">
                        <label class="form-label">Structure ID</label>
                        <input type="text" name="strid" id="profileStructureStrid" class="form-control" value="" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Paps / Owner</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($pscOwnerLabel) ?>" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Structure Tag #</label>
                        <input type="text" name="structure_tag" id="profileStructureTag" class="form-control" value="">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="profileStructurePnNumber">PN number</label>
                        <input type="text" name="pn_number" id="profileStructurePnNumber" class="form-control" maxlength="255" value="">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description / Comment</label>
                        <textarea name="description" id="profileStructureDesc" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Other details</label>
                        <textarea name="other_details" id="profileStructureOther" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tagging images</label>
                        <input type="file" name="tagging_images[]" id="profileStructureTaggingFiles" class="form-control" accept="image/*" multiple>
                        <div id="profileStructureTaggingThumbs" class="d-flex flex-wrap gap-2 mt-2"></div>
                        <div id="profileStructureTaggingRemove"></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Structure images</label>
                        <input type="file" name="structure_images[]" id="profileStructureStructFiles" class="form-control" accept="image/*" multiple>
                        <div id="profileStructureStructThumbs" class="d-flex flex-wrap gap-2 mt-2"></div>
                        <div id="profileStructureStructRemove"></div>
                    </div>
                </form>
            </div>
            <div class="modal-footer d-flex flex-wrap gap-2 justify-content-between" id="profileStructureCrudFooter">
                <div class="d-flex flex-wrap gap-2" id="profileStructureCrudFooterLeft"></div>
                <div class="d-flex flex-wrap gap-2" id="profileStructureCrudFooterRight"></div>
            </div>
        </div>
    </div>
</div>
