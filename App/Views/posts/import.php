<?php
ob_start();
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0">Import posts</h2>
    <a href="<?= admin_url('posts') ?>" class="btn btn-outline-secondary">Back to posts</a>
</div>
<p class="text-muted">Upload an Atom or Blogger <code>.atom</code> / <code>.xml</code> export. Feed publish times are stored as <strong>Publish at</strong>: dates in the past go live immediately (backdated); future dates stay off the public site, RSS, and sitemap until that time. Internal links are rewritten using <code>base_url</code> from <code>config/app.php</code> so the same feed works on another host.</p>
<div class="card"><div class="card-body">
<form method="post" action="<?= admin_url('posts/import') ?>" enctype="multipart/form-data">
<?= \Core\Csrf::field() ?>
<div class="mb-3">
    <label class="form-label" for="atomFeedFile">Feed file</label>
    <input type="file" name="feed" id="atomFeedFile" class="form-control" required accept=".atom,.xml,application/xml,text/xml,application/atom+xml">
    <small class="text-muted d-block mt-1">Maximum 8 MB. Follow <code>docs/samples/cms-atom-import/sample.atom</code> (backdated, scheduled, draft, PAGE, and optional <code>cms:*</code> SEO/AEO fields). PAGE entries are skipped unless included below.</small>
</div>
<div class="form-check mb-2">
    <input type="checkbox" class="form-check-input" name="update_existing" value="1" id="atomUpdateExisting">
    <label class="form-check-label" for="atomUpdateExisting">Update existing posts/pages with the same slug</label>
</div>
<div class="form-check mb-2">
    <input type="checkbox" class="form-check-input" name="include_pages" value="1" id="atomIncludePages">
    <label class="form-check-label" for="atomIncludePages">Include PAGE entries from the feed</label>
</div>
<div class="mb-3">
    <label class="form-label" for="atomSpreadYear">Spread publish dates across year</label>
    <input type="number" name="spread_year" id="atomSpreadYear" class="form-control" min="2000" max="2100" placeholder="Leave empty to keep feed dates" style="max-width:16rem">
    <small class="text-muted d-block mt-1">Optional. Evenly spaces LIVE posts from 1 January through 31 December (oldest feed date first). Future days stay scheduled until due.</small>
</div>
<div class="form-check mb-3">
    <input type="checkbox" class="form-check-input" name="dry_run" value="1" id="atomDryRun">
    <label class="form-check-label" for="atomDryRun">Dry run (parse and count only; do not write)</label>
</div>
<button type="submit" class="btn btn-primary">Import</button>
</form>
</div></div>
<p class="small text-muted mt-3 mb-0">CLI: <code>php cli/import_atom_feed.php docs/samples/cms-atom-import/sample.atom</code> &nbsp;·&nbsp; The Filipino Men scheduled collection: <code>php cli/import_atom_feed.php docs/atoms-collections/the-filipino-men-sept-21-30-2026.atom --dry-run --verbose</code></p>
<?php
$content = ob_get_clean();
$pageTitle = 'Import posts';
$currentPage = 'posts';
require __DIR__ . '/../layout/main.php';
