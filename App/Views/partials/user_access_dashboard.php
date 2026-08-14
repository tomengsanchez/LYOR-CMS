<?php
/**
 * Shared access dashboard: web sessions, API tokens, recent activity.
 *
 * @var array $accessDashboard
 * @var bool $canRevoke
 * @var bool $isSelf
 * @var string $revokeSessionUrlTpl  e.g. /users/1/sessions/{id}/revoke or /account/sessions/logout/{id}
 * @var string $revokeTokenUrlTpl
 * @var bool $showLogoutOthers
 * @var string $logoutOthersUrl
 */
$accessDashboard = $accessDashboard ?? [];
$canRevoke = !empty($canRevoke);
$isSelf = !empty($isSelf);
$revokeSessionUrlTpl = (string) ($revokeSessionUrlTpl ?? '');
$revokeTokenUrlTpl = (string) ($revokeTokenUrlTpl ?? '');
$showLogoutOthers = !empty($showLogoutOthers);
$logoutOthersUrl = (string) ($logoutOthersUrl ?? admin_url('account/sessions/logout-others'));
$sessions = $accessDashboard['sessions'] ?? [];
$tokens = $accessDashboard['tokens'] ?? [];
$activityByModule = $accessDashboard['activity_by_module'] ?? [];
$summary = $accessDashboard['summary'] ?? [];
$windowMinutes = (int) ($accessDashboard['window_minutes'] ?? 10);
$userIdForAccess = (int) ($accessDashboard['user_id'] ?? 0);
$pageSize = 10;

$revokeSessionUrl = static function (int $id) use ($revokeSessionUrlTpl): string {
    return str_replace('{id}', (string) $id, $revokeSessionUrlTpl);
};
$revokeTokenUrl = static function (int $id) use ($revokeTokenUrlTpl): string {
    return str_replace('{id}', (string) $id, $revokeTokenUrlTpl);
};

$baseUrl = defined('BASE_URL') ? rtrim((string) BASE_URL, '/') : '';
?>
<div class="row g-3 mb-3">
    <div class="col-6 col-md-3">
        <div class="border rounded bg-white p-3 h-100">
            <div class="text-muted small">Active web sessions</div>
            <div class="fs-4 fw-bold"><?= (int) ($summary['active_sessions'] ?? 0) ?></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="border rounded bg-white p-3 h-100">
            <div class="text-muted small">Online now (<?= $windowMinutes ?> min)</div>
            <div class="fs-4 fw-bold"><?= (int) ($summary['online_sessions'] ?? 0) ?></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="border rounded bg-white p-3 h-100">
            <div class="text-muted small">API tokens</div>
            <div class="fs-4 fw-bold"><?= (int) ($summary['api_tokens'] ?? 0) ?></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="border rounded bg-white p-3 h-100">
            <div class="text-muted small">API recently used</div>
            <div class="fs-4 fw-bold"><?= (int) ($summary['recent_api_tokens'] ?? 0) ?></div>
        </div>
    </div>
</div>

<div class="border rounded bg-white p-3 mb-3" id="userAccessWebSessions">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h5 class="mb-0">Web sessions</h5>
        <?php if ($showLogoutOthers && $isSelf): ?>
        <form method="post" action="<?= htmlspecialchars($logoutOthersUrl) ?>" class="mb-0">
            <?= \Core\Csrf::field() ?>
            <button type="submit" class="btn btn-sm btn-outline-danger" data-confirm="Sign out all other devices?">Sign out other devices</button>
        </form>
        <?php endif; ?>
    </div>
    <?php if (empty($sessions)): ?>
    <p class="text-muted small mb-0">No web sessions recorded.</p>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
            <thead>
                <tr>
                    <th>Device / browser</th>
                    <th>Screen</th>
                    <th>IP</th>
                    <th>Logged in</th>
                    <th>Last seen</th>
                    <th>Status</th>
                    <th style="width: 120px;"></th>
                </tr>
            </thead>
            <tbody class="js-access-page-body" data-page-size="<?= $pageSize ?>">
                <?php foreach ($sessions as $s): ?>
                <?php
                    $ua = (string) ($s->user_agent ?? '');
                    $os = \App\UserAccessDashboard::osLabelFromUa($ua);
                    $uaShort = $ua !== '' ? substr($ua, 0, 60) : '';
                    $screen = trim((string) ($s->page_label ?? ''));
                    if ($screen === '') {
                        $screen = trim((string) ($s->current_path ?? ''));
                    }
                ?>
                <tr class="js-access-page-row">
                    <td>
                        <?php if ($ua !== ''): ?>
                        <span title="<?= htmlspecialchars($ua) ?>">
                            <strong><?= htmlspecialchars($os) ?></strong>
                            — <?= htmlspecialchars($uaShort) ?><?= strlen($ua) > 60 ? '…' : '' ?>
                        </span>
                        <?php else: ?>
                        <span class="text-muted">Unknown</span>
                        <?php endif; ?>
                    </td>
                    <td class="small"><?= $screen !== '' ? htmlspecialchars($screen) : '—' ?></td>
                    <td class="small"><?= htmlspecialchars((string) ($s->ip_address ?? '—')) ?></td>
                    <td class="small"><?= htmlspecialchars((string) ($s->created_at ?? '')) ?></td>
                    <td class="small"><?= htmlspecialchars((string) ($s->last_seen_at ?? $s->last_activity_at ?? '')) ?></td>
                    <td>
                        <?php if (!empty($s->is_current)): ?>
                        <span class="badge text-bg-primary">This device</span>
                        <?php elseif (!empty($s->is_active)): ?>
                        <span class="badge text-bg-success">Active</span>
                        <?php else: ?>
                        <span class="badge text-bg-secondary">Signed out</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-end">
                        <?php if (!empty($s->is_current) && $isSelf): ?>
                        <span class="text-muted small">Current</span>
                        <?php elseif (!empty($s->is_active) && $canRevoke && $revokeSessionUrlTpl !== ''): ?>
                        <form method="post" action="<?= htmlspecialchars($revokeSessionUrl((int) $s->id)) ?>" class="d-inline">
                            <?= \Core\Csrf::field() ?>
                            <button type="submit" class="btn btn-sm btn-outline-danger" data-confirm="<?= $isSelf ? 'Sign out this device?' : 'End this web session for the user?' ?>">
                                <?= $isSelf ? 'Sign out' : 'End session' ?>
                            </button>
                        </form>
                        <?php elseif (empty($s->is_active)): ?>
                        <span class="text-muted small">Ended</span>
                        <?php else: ?>
                        <span class="text-muted small">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="d-flex justify-content-between align-items-center mt-2 js-access-pager" data-label="sessions">
        <button type="button" class="btn btn-sm btn-outline-secondary js-access-prev" disabled>Prev</button>
        <span class="small text-muted js-access-page-info"></span>
        <button type="button" class="btn btn-sm btn-outline-secondary js-access-next" disabled>Next</button>
    </div>
    <?php endif; ?>
</div>

<div class="border rounded bg-white p-3 mb-3" id="userAccessApiTokens">
    <h5 class="mb-3">API / mobile tokens</h5>
    <p class="text-muted small">Bearer tokens used by mobile or other API clients. Revoking forces the app to sign in again.</p>
    <?php if (empty($tokens)): ?>
    <p class="text-muted small mb-0">No active API tokens.</p>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
            <thead>
                <tr>
                    <th>Client</th>
                    <th>Device</th>
                    <th>Last path</th>
                    <th>IP</th>
                    <th>Last used</th>
                    <th>Expires</th>
                    <th>Status</th>
                    <th style="width: 120px;"></th>
                </tr>
            </thead>
            <tbody class="js-access-page-body" data-page-size="<?= $pageSize ?>">
                <?php foreach ($tokens as $t): ?>
                <?php
                    $deviceParts = array_filter([
                        $t->device_model ?? null,
                        $t->os_version ?? null,
                    ], static function ($v) {
                        return $v !== null && $v !== '';
                    });
                    $device = $deviceParts !== [] ? implode(' · ', $deviceParts) : '—';
                    $client = trim((string) ($t->client_id ?? ''));
                ?>
                <tr class="js-access-page-row">
                    <td class="small"><?= $client !== '' ? htmlspecialchars($client) : '—' ?></td>
                    <td class="small"><?= htmlspecialchars($device) ?></td>
                    <td><code class="small"><?= htmlspecialchars((string) ($t->path ?? '')) ?></code></td>
                    <td class="small"><?= htmlspecialchars((string) (($t->ip ?? '') !== '' ? $t->ip : '—')) ?></td>
                    <td class="small"><?= htmlspecialchars((string) ($t->last_seen_at ?? '—')) ?></td>
                    <td class="small"><?= htmlspecialchars((string) ($t->expires_at ?? '')) ?></td>
                    <td>
                        <?php if (!empty($t->is_recent)): ?>
                        <span class="badge text-bg-success">Recent</span>
                        <?php else: ?>
                        <span class="badge text-bg-secondary">Idle</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-end">
                        <?php if ($canRevoke && $revokeTokenUrlTpl !== ''): ?>
                        <form method="post" action="<?= htmlspecialchars($revokeTokenUrl((int) $t->token_id)) ?>" class="d-inline">
                            <?= \Core\Csrf::field() ?>
                            <button type="submit" class="btn btn-sm btn-outline-danger" data-confirm="Revoke this API token?">Revoke token</button>
                        </form>
                        <?php else: ?>
                        <span class="text-muted small">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="d-flex justify-content-between align-items-center mt-2 js-access-pager" data-label="tokens">
        <button type="button" class="btn btn-sm btn-outline-secondary js-access-prev" disabled>Prev</button>
        <span class="small text-muted js-access-page-info"></span>
        <button type="button" class="btn btn-sm btn-outline-secondary js-access-next" disabled>Next</button>
    </div>
    <?php endif; ?>
</div>

<div class="border rounded bg-white p-3 mb-3">
    <h5 class="mb-3">Recent activity</h5>
    <p class="text-muted small mb-2">
        Summary by module and action (view events excluded). Click a module card to open the detailed list.
        <?php if (!empty($summary['activity_actions'])): ?>
        <?= (int) $summary['activity_actions'] ?> action<?= (int) $summary['activity_actions'] === 1 ? '' : 's' ?>
        across <?= (int) ($summary['activity_modules'] ?? 0) ?> module<?= (int) ($summary['activity_modules'] ?? 0) === 1 ? '' : 's' ?>.
        <?php endif; ?>
    </p>
    <?php if (empty($activityByModule)): ?>
    <p class="text-muted small mb-0">No recent activity recorded.</p>
    <?php else: ?>
    <div class="row g-3">
        <?php foreach ($activityByModule as $mod): ?>
        <div class="col-md-6 col-xl-4">
            <button type="button"
                    class="border rounded p-3 h-100 w-100 text-start bg-white js-access-activity-card"
                    data-module="<?= htmlspecialchars((string) ($mod['module'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                    data-label="<?= htmlspecialchars((string) ($mod['label'] ?? $mod['module'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                    <div>
                        <div class="fw-semibold"><?= htmlspecialchars((string) ($mod['label'] ?? $mod['module'] ?? '')) ?></div>
                        <div class="text-muted small">Last: <?= htmlspecialchars((string) ($mod['last_at'] ?? '—')) ?></div>
                    </div>
                    <span class="badge text-bg-secondary"><?= (int) ($mod['total'] ?? 0) ?></span>
                </div>
                <ul class="list-unstyled mb-0 small">
                    <?php foreach (($mod['actions'] ?? []) as $act): ?>
                    <li class="d-flex justify-content-between py-1 border-top">
                        <span><?= htmlspecialchars((string) ($act['action'] ?? '')) ?></span>
                        <span class="text-muted">
                            ×<?= (int) ($act['count'] ?? 0) ?>
                            <span class="ms-1"><?= htmlspecialchars(\App\UserAccessDashboard::shortDate($act['last_at'] ?? null)) ?></span>
                        </span>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <div class="text-primary small mt-2">View list →</div>
            </button>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<div class="modal fade" id="userAccessActivityModal" tabindex="-1" aria-labelledby="userAccessActivityModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="userAccessActivityModalLabel">Module activity</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="userAccessActivityLoading" class="text-center py-4">
                    <div class="spinner-border text-secondary" role="status" aria-hidden="true"></div>
                    <div class="mt-2 small text-muted">Loading activity…</div>
                </div>
                <div id="userAccessActivityEmpty" class="text-muted small d-none">No activity found for this module.</div>
                <div id="userAccessActivityError" class="alert alert-danger d-none mb-0"></div>
                <div id="userAccessActivityContent" class="d-none">
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead>
                                <tr>
                                    <th>Time</th>
                                    <th>Action</th>
                                    <th>Record</th>
                                </tr>
                            </thead>
                            <tbody id="userAccessActivityBody"></tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="userAccessActivityPrev" disabled>Prev</button>
                        <span class="small text-muted" id="userAccessActivityPageInfo"></span>
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="userAccessActivityNext" disabled>Next</button>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
<?php
$scripts = ($scripts ?? '') . '<script>window.userAccessDashboardConfig=' . json_encode([
    'userId' => $userIdForAccess,
    'activityApiUrl' => $baseUrl . '/api/users/' . $userIdForAccess . '/access/activity',
    'perPage' => 15,
    'tablePageSize' => $pageSize,
], JSON_UNESCAPED_UNICODE) . ';</script>'
    . '<script src="/public/assets/js/partials/user_access_dashboard.js"></script>';
?>
