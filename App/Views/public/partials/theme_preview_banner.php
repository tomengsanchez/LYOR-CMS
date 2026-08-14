<?php
/** Theme preview banner (admin-only, unsaved settings). */
?>
<div class="pub-theme-live-banner" role="status">
    <div class="container d-flex flex-wrap align-items-center justify-content-between gap-2">
        <span><strong>Theme preview</strong> — unsaved changes. Save in General settings to apply site-wide.</span>
        <span class="d-flex flex-wrap gap-2 align-items-center">
            <a href="<?= admin_url('system/general') ?>" class="btn btn-sm btn-light">Back to General</a>
            <form method="post" action="<?= admin_url('system/general/theme-preview-clear') ?>" class="d-inline mb-0">
                <?= \Core\Csrf::field() ?>
                <button type="submit" class="btn btn-sm btn-outline-light">Exit preview</button>
            </form>
        </span>
    </div>
</div>
