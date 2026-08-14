<?php
$items = $items ?? [];
$filters = $filters ?? ['module' => '', 'from' => '', 'to' => '', 'user_id' => null];
$pagination = $pagination ?? ['page' => 1, 'per_page' => 25, 'total' => 0, 'total_pages' => 0];
$users = $users ?? [];
$listBaseUrl = admin_url('system/audit-trail');
$listPagination = $pagination;
$listExtraParams = ['module' => $filters['module'] ?? '', 'from' => $filters['from'] ?? '', 'to' => $filters['to'] ?? '', 'user_id' => $filters['user_id'] ?? ''];

function audit_trail_action_label(string $action): string {
    return match ($action) {
        'login' => 'Login',
        'logout' => 'Logout',
        'viewed' => 'Viewed',
        'created' => 'Added',
        'updated' => 'Updated',
        'attachments_uploaded' => 'Uploaded attachments',
        'status_changed' => 'Change status',
        default => ucfirst(str_replace('_', ' ', $action)),
    };
}

function audit_trail_module_label(string $type): string {
    return match ($type) {
        'user' => 'Users',
        'page' => 'Pages',
        'post' => 'Posts',
        'category' => 'Categories',
        'media' => 'Media',
        default => ucfirst($type),
    };
}

function audit_trail_entity_url(object $row): ?string {
    $type = $row->entity_type ?? '';
    $id = (int) ($row->entity_id ?? 0);
    if ($id <= 0) return null;
    return match ($type) {
        'user' => admin_url('users/view/' . $id),
        'page' => admin_url('pages/view/' . $id),
        'post' => admin_url('posts/view/' . $id),
        'category' => admin_url('categories/edit/' . $id),
        'media' => admin_url('media'),
        default => null,
    };
}

function audit_trail_details(object $row): string {
    $changes = $row->changes ?? [];
    if (!is_array($changes) || empty($changes)) {
        return '—';
    }
    $parts = [];
    foreach ($changes as $field => $change) {
        if (isset($change['from'], $change['to'])) {
            $parts[] = $field . ': ' . (string)$change['from'] . ' → ' . (string)$change['to'];
        } elseif ($field === 'sections' && is_array($change)) {
            $parts[] = 'Sections: ' . implode(', ', $change);
        } elseif ($field === 'count') {
            $parts[] = 'Files: ' . (int)$change;
        } else {
            $parts[] = $field . ': ' . json_encode($change);
        }
    }
    return implode('; ', $parts);
}

ob_start();
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="mb-0">Audit Trail</h2>
</div>
<div class="card mb-3">
    <div class="card-body">
        <form method="get" class="row g-3 align-items-end">
            <div class="col-md-2">
                <label for="from" class="form-label">From date</label>
                <input type="date" id="from" name="from" class="form-control" value="<?= htmlspecialchars($filters['from'] ?? '') ?>">
            </div>
            <div class="col-md-2">
                <label for="to" class="form-label">To date</label>
                <input type="date" id="to" name="to" class="form-control" value="<?= htmlspecialchars($filters['to'] ?? '') ?>">
            </div>
            <div class="col-md-2">
                <label for="module" class="form-label">Module</label>
                <select id="module" name="module" class="form-select">
                    <option value="">All</option>
                    <option value="user" <?= ($filters['module'] ?? '') === 'user' ? 'selected' : '' ?>>Users</option>
                    <option value="page" <?= ($filters['module'] ?? '') === 'page' ? 'selected' : '' ?>>Pages</option>
                    <option value="post" <?= ($filters['module'] ?? '') === 'post' ? 'selected' : '' ?>>Posts</option>
                    <option value="category" <?= ($filters['module'] ?? '') === 'category' ? 'selected' : '' ?>>Categories</option>
                    <option value="media" <?= ($filters['module'] ?? '') === 'media' ? 'selected' : '' ?>>Media</option>
                </select>
            </div>
            <div class="col-md-3">
                <label for="user_id" class="form-label">User (actor)</label>
                <select id="user_id" name="user_id" class="form-select">
                    <option value="">All</option>
                    <?php foreach ($users as $u): ?>
                    <option value="<?= (int)$u->id ?>" <?= (string)($filters['user_id'] ?? '') === (string)$u->id ? 'selected' : '' ?>>
                        <?= htmlspecialchars($u->display_name ?: $u->username ?? ('User #' . (int)$u->id)) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 d-flex gap-2">
                <button type="submit" class="btn btn-primary">Apply filters</button>
                <a href="<?= admin_url('system/audit-trail') ?>" class="btn btn-outline-secondary">Clear</a>
            </div>
        </form>
    </div>
</div>
<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th style="width: 14%;">When</th>
                    <th style="width: 14%;">User</th>
                    <th style="width: 10%;">Module</th>
                    <th style="width: 14%;">Activity</th>
                    <th>Details</th>
                    <th style="width: 8%;">Link</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $row): ?>
                <tr>
                    <td><?= htmlspecialchars(\App\UserTime::formatSystem($row->created_at ?? '')) ?></td>
                    <td><?= htmlspecialchars(trim($row->created_by_display_name ?? '') ?: $row->created_by_name ?? '—') ?></td>
                    <td><?= htmlspecialchars(audit_trail_module_label($row->entity_type ?? '')) ?></td>
                    <td><?= htmlspecialchars(audit_trail_action_label($row->action ?? '')) ?></td>
                    <td class="small"><?= htmlspecialchars(audit_trail_details($row)) ?></td>
                    <td>
                        <?php $url = audit_trail_entity_url($row); ?>
                        <?php if ($url): ?>
                        <a href="<?= htmlspecialchars($url) ?>" class="btn btn-sm btn-outline-primary">Open</a>
                        <?php else: ?>
                        —
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($items)): ?>
                <tr>
                    <td colspan="6" class="text-muted text-center py-4">No audit records for the selected filters.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="card-body border-top">
        <?php
        $listSearch = '';
        $listColumns = [];
        $listSort = '';
        $listOrder = 'desc';
        include __DIR__ . '/../partials/list_pagination.php';
        ?>
    </div>
</div>
<?php
$content = ob_get_clean();
$pageTitle = 'Audit Trail';
$currentPage = 'audit-trail';
require __DIR__ . '/../layout/main.php';
