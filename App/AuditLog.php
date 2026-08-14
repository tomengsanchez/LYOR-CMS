<?php
namespace App;

use Core\Auth;
use Core\Database;

/**
 * Generic audit log for entity history (creation, edits, status changes, etc.).
 *
 * Usage:
 *  - AuditLog::record('profile', $id, 'created');
 *  - AuditLog::record('profile', $id, 'updated', ['full_name' => ['from' => 'Old', 'to' => 'New']]);
 *  - AuditLog::recordWithCreatedBy($adminId, 'profile', $id, 'created', ['source' => 'seed_profiles']); // CLI / no session
 */
class AuditLog
{
    protected static function db(): \PDO
    {
        return Database::getInstance();
    }

    public static function record(string $entityType, int $entityId, string $action, array $changes = []): void
    {
        $db = self::db();
        $createdBy = Auth::id();
        $json = $changes ? json_encode($changes) : null;
        $createdAt = UserTime::nowSql();
        $stmt = $db->prepare('INSERT INTO audit_log (entity_type, entity_id, action, changes, created_by, created_at) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([$entityType, $entityId, $action, $json, $createdBy, $createdAt]);
    }

    /**
     * Same as record() but with an explicit created_by (e.g. CLI seeders where Auth is unset).
     * Pass null for created_by to store NULL in audit_log (no acting user).
     */
    public static function recordWithCreatedBy(?int $createdBy, string $entityType, int $entityId, string $action, array $changes = []): void
    {
        $db = self::db();
        $uid = ($createdBy !== null && $createdBy > 0) ? $createdBy : null;
        $json = $changes ? json_encode($changes) : null;
        $createdAt = UserTime::nowSql();
        $stmt = $db->prepare('INSERT INTO audit_log (entity_type, entity_id, action, changes, created_by, created_at) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([$entityType, $entityId, $action, $json, $uid, $createdAt]);
    }

    /**
     * History for a given entity, newest first.
     *
     * @return array<int,object> entries with properties: id, entity_type, entity_id, action, changes (array), created_at, created_by, created_by_name
     */
    public static function for(string $entityType, int $entityId, int $limit = 50): array
    {
        $db = self::db();
        $limit = max(1, min(200, (int) $limit));
        $sql = "
            SELECT a.*, u.username AS created_by_name
            FROM audit_log a
            LEFT JOIN users u ON u.id = a.created_by
            WHERE a.entity_type = ? AND a.entity_id = ? AND a.action <> 'viewed'
            ORDER BY a.created_at DESC
            LIMIT {$limit}
        ";
        $stmt = $db->prepare($sql);
        $stmt->execute([$entityType, $entityId]);
        $rows = $stmt->fetchAll(\PDO::FETCH_OBJ);
        foreach ($rows as $row) {
            $row->changes = $row->changes ? (json_decode($row->changes, true) ?: []) : [];
        }
        return $rows;
    }

    /**
     * Paginated history for a given entity, newest first.
     *
     * @return array{items: array<int,object>, page: int, per_page: int, has_more: bool}
     */
    public static function forPaginated(string $entityType, int $entityId, int $page = 1, int $perPage = 20): array
    {
        $db = self::db();
        $page = max(1, (int) $page);
        $perPage = max(1, min(100, (int) $perPage));
        $offset = ($page - 1) * $perPage;

        $sql = "
            SELECT a.id, a.entity_type, a.entity_id, a.action, a.changes, a.created_at, a.created_by,
                   u.username AS created_by_name
            FROM audit_log a
            LEFT JOIN users u ON u.id = a.created_by
            WHERE a.entity_type = :etype AND a.entity_id = :eid AND a.action <> 'viewed'
            ORDER BY a.created_at DESC, a.id DESC
            LIMIT {$perPage} OFFSET {$offset}
        ";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            'etype' => $entityType,
            'eid'   => $entityId,
        ]);
        $items = $stmt->fetchAll(\PDO::FETCH_OBJ);
        foreach ($items as $row) {
            $row->changes = $row->changes ? (json_decode($row->changes, true) ?: []) : [];
        }

        $hasMore = count($items) === $perPage;

        return [
            'items'    => $items,
            'page'     => $page,
            'per_page' => $perPage,
            'has_more' => $hasMore,
        ];
    }

    /**
     * Paginated list for Audit Trail (all modules: user, profile, structure, grievance).
     *
     * @param array $filters ['module' => entity_type, 'from' => date, 'to' => date, 'user_id' => created_by]
     * @return array{items: array, total: int, page: int, per_page: int, total_pages: int}
     */
    public static function listForTrail(array $filters, int $page = 1, int $perPage = 25): array
    {
        $db = self::db();
        $page = max(1, (int) $page);
        $perPage = max(1, min(100, (int) $perPage));

        $where = ['1=1'];
        $params = [];

        $module = $filters['module'] ?? '';
        if (in_array($module, ['user', 'profile', 'structure', 'grievance', 'project'], true)) {
            $where[] = 'a.entity_type = :entity_type';
            $params['entity_type'] = $module;
        }

        if (!empty($filters['from'])) {
            $where[] = 'a.created_at >= :from';
            $params['from'] = $filters['from'] . ' 00:00:00';
        }
        if (!empty($filters['to'])) {
            $where[] = 'a.created_at <= :to';
            $params['to'] = $filters['to'] . ' 23:59:59';
        }
        if (!empty($filters['user_id'])) {
            $where[] = 'a.created_by = :created_by';
            $params['created_by'] = (int) $filters['user_id'];
        }

        $whereSql = implode(' AND ', $where);
        $offset = ($page - 1) * $perPage;

        $sql = "
            SELECT a.id, a.entity_type, a.entity_id, a.action, a.changes, a.created_at, a.created_by,
                   u.username AS created_by_name, u.display_name AS created_by_display_name
            FROM audit_log a
            LEFT JOIN users u ON u.id = a.created_by
            WHERE {$whereSql}
            ORDER BY a.created_at DESC, a.id DESC
            LIMIT {$perPage} OFFSET {$offset}
        ";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $items = $stmt->fetchAll(\PDO::FETCH_OBJ);

        $countSql = "SELECT COUNT(*) FROM audit_log a WHERE {$whereSql}";
        $stmtCount = $db->prepare($countSql);
        $stmtCount->execute($params);
        $total = (int) $stmtCount->fetchColumn();
        $totalPages = (int) ceil($total / $perPage);

        foreach ($items as $row) {
            $row->changes = $row->changes ? (json_decode($row->changes, true) ?: []) : [];
        }

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => $totalPages,
        ];
    }
}

