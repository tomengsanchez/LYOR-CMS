<?php
/** @var array<int,object> $history */
/** @var array<int,object>|null $statusLog */
$history = $history ?? [];
$statusLog = $statusLog ?? [];
$statusLogKind = $statusLogKind ?? 'grievance';
$historyEntityType = $historyEntityType ?? null;
$historyEntityId = $historyEntityId ?? null;
$historyPageSize = $historyPageSize ?? 20;
$historyHasMore = !empty($historyHasMore);
$canEditStatusLogEffectiveAt = $canEditStatusLogEffectiveAt ?? false;
?>
<div class="card mb-3">
    <div class="card-header py-2">
        <h6 class="mb-0 small text-uppercase text-muted">Activity History</h6>
    </div>
    <div class="card-body p-2 history-scroll"
         style="max-height: 360px; overflow-y: auto;"
         data-entity-type="<?= htmlspecialchars((string) $historyEntityType) ?>"
         data-entity-id="<?= (int) $historyEntityId ?>"
         data-page="1"
         data-page-size="<?= (int) $historyPageSize ?>"
         data-has-more="<?= $historyHasMore ? '1' : '0' ?>">
        <?php if (empty($history)): ?>
        <p class="text-muted small mb-0 history-empty-message">No activity recorded yet.</p>
        <?php else: ?>
        <ul class="list-unstyled mb-0 small history-list">
            <?php foreach ($history as $entry): ?>
            <li class="mb-2">
                <div><strong><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $entry->action ?? ''))) ?></strong></div>
                <div class="text-muted"><?= htmlspecialchars(\App\UserTime::formatSystem($entry->created_at ?? '')) ?><?= $entry->created_by_name ? ' · ' . htmlspecialchars($entry->created_by_name) : '' ?></div>
                <?php if (!empty($entry->changes) && is_array($entry->changes)): ?>
                <ul class="mb-0 mt-1 ps-3">
                    <?php foreach ($entry->changes as $field => $change): ?>
                    <?php if (is_array($change) && (array_key_exists('from', $change) || array_key_exists('to', $change))): ?>
                    <li><?= htmlspecialchars($field) ?>: <span class="text-muted"><?= htmlspecialchars((string)($change['from'] ?? '')) ?></span> → <span class="text-success"><?= htmlspecialchars((string)($change['to'] ?? '')) ?></span></li>
                    <?php else: ?>
                    <li><?= htmlspecialchars($field) ?>: <span class="text-success"><?= htmlspecialchars(is_scalar($change) ? (string) $change : json_encode($change)) ?></span></li>
                    <?php endif; ?>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>
        <?php if ($historyHasMore): ?>
        <div class="text-center small text-muted mt-2 history-loading" style="display:none;">Loading more…</div>
        <?php endif; ?>
    </div>
</div>
<?php if (!empty($statusLog)): ?>
<div class="card mb-3">
    <div class="card-header py-2">
        <h6 class="mb-0 small text-uppercase text-muted">Status History</h6>
    </div>
    <div class="card-body p-2 status-history-scroll" style="max-height: 360px; overflow-y: auto;">
        <ul class="list-unstyled mb-0 small">
            <?php
            $statusGrievanceId = ($historyEntityType === 'grievance') ? (int) ($historyEntityId ?? 0) : 0;
            foreach ($statusLog as $entry):
                if ($statusLogKind === 'structure'):
                    $statusLabel = (string) ($entry->status_label ?? \App\Models\Structure::taggingStatusLabel(null));
                    $segmentAt = trim((string) ($entry->effective_at ?? $entry->created_at ?? ''));
                    $systemAt = (string) ($entry->created_at ?? '');
                    $showSystemAt = false;
            ?>
            <li class="mb-2" data-status-log-id="<?= (int) ($entry->id ?? 0) ?>">
                <div><strong><?= htmlspecialchars($statusLabel) ?></strong></div>
                <div class="text-muted">
                    <?= $segmentAt !== '' ? htmlspecialchars(\App\UserTime::formatBusiness($segmentAt, 'M j, Y H:i')) : '—' ?>
                    <?= !empty($entry->created_by_name) ? ' · ' . htmlspecialchars((string) $entry->created_by_name) : '' ?>
                </div>
                <?php if (!empty(trim((string) ($entry->note ?? '')))): ?>
                <div class="mt-1"><?= nl2br(htmlspecialchars((string) $entry->note)) ?></div>
                <?php endif; ?>
            </li>
            <?php
                    continue;
                endif;
                $statusAttLinks = \App\Models\GrievanceStatusLog::attachmentLinksForEntry($entry, $statusGrievanceId);
            ?>
            <li class="mb-2" data-status-log-id="<?= (int) ($entry->id ?? 0) ?>">
                <div>
                    <strong><?= htmlspecialchars(\App\Models\GrievanceStatusLog::formatEntryStatusLabel($entry)) ?></strong>
                </div>
                <?php
                $segmentAt = \App\Models\GrievanceStatusLog::segmentAtFor($entry);
                $systemAt = (string) ($entry->created_at ?? '');
                $systemAtFormatted = $systemAt !== '' ? \App\UserTime::formatSystem($systemAt) : '';
                $showSystemAt = $segmentAt !== null && $systemAtFormatted !== '' && substr($segmentAt, 0, 16) !== substr($systemAtFormatted, 0, 16);
                ?>
                <div class="text-muted">
                    <?= $segmentAt ? htmlspecialchars(\App\UserTime::formatBusiness($segmentAt, 'M j, Y H:i')) : '—' ?>
                    <?= $entry->created_by_name ? ' · ' . htmlspecialchars($entry->created_by_name) : '' ?>
                </div>
                <?php if ($showSystemAt): ?>
                <div class="text-muted small">Recorded in system: <?= htmlspecialchars(\App\UserTime::formatSystem($systemAt, 'M j, Y H:i')) ?></div>
                <?php endif; ?>
                <?php if ($canEditStatusLogEffectiveAt && $historyEntityType === 'grievance' && !empty($historyEntityId)): ?>
                <button type="button"
                    class="btn btn-link btn-sm p-0 small status-log-edit-effective-at"
                    data-log-id="<?= (int) ($entry->id ?? 0) ?>"
                    data-effective-at="<?= $segmentAt ? htmlspecialchars(\App\UserTime::formatBusiness($segmentAt, 'Y-m-d\TH:i')) : '' ?>">
                    Edit effective date
                </button>
                <form method="post"
                    action="/grievance/status-log-effective-at/<?= (int) $historyEntityId ?>/<?= (int) ($entry->id ?? 0) ?>"
                    class="status-log-effective-at-form mt-1 d-none"
                    data-log-id="<?= (int) ($entry->id ?? 0) ?>">
                    <?= \Core\Csrf::field() ?>
                    <div class="input-group input-group-sm">
                        <input type="datetime-local" name="effective_at" class="form-control" value="<?= $segmentAt ? htmlspecialchars(\App\UserTime::formatBusiness($segmentAt, 'Y-m-d\TH:i')) : '' ?>" required>
                        <button type="submit" class="btn btn-outline-primary btn-sm">Save</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm status-log-cancel-effective-at">Cancel</button>
                    </div>
                </form>
                <?php endif; ?>
                <?php if (!empty(trim($entry->note ?? ''))): ?>
                <div class="mt-1"><?= nl2br(htmlspecialchars($entry->note)) ?></div>
                <?php endif; ?>
                <?php if ($statusAttLinks !== []): ?>
                <ul class="mb-0 mt-1 ps-3">
                    <?php foreach ($statusAttLinks as $attLink): ?>
                    <li><a href="<?= htmlspecialchars($attLink['url']) ?>" target="_blank" rel="noopener"><?= htmlspecialchars($attLink['label']) ?></a></li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>
<?php endif; ?>

<?php $scripts = ($scripts ?? '') . '<script src="/public/assets/js/partials/history_sidebar.js"></script>'; ?>

