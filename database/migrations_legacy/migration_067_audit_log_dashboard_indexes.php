<?php
/**
 * Migration 067: Audit log index for dashboard attachment/status aggregates.
 */
return [
    'name' => 'migration_067_audit_log_dashboard_indexes',
    'up' => function (\PDO $db): void {
        $has = static function (string $name) use ($db): bool {
            $stmt = $db->prepare('SHOW INDEX FROM audit_log WHERE Key_name = ?');
            $stmt->execute([$name]);

            return (bool) $stmt->fetch(\PDO::FETCH_ASSOC);
        };

        if (!$has('idx_audit_entity_action_created')) {
            $db->exec('ALTER TABLE audit_log ADD INDEX idx_audit_entity_action_created (entity_type, action, created_at)');
        }
    },
    'down' => function (\PDO $db): void {
        $has = static function (string $name) use ($db): bool {
            $stmt = $db->prepare('SHOW INDEX FROM audit_log WHERE Key_name = ?');
            $stmt->execute([$name]);

            return (bool) $stmt->fetch(\PDO::FETCH_ASSOC);
        };

        if ($has('idx_audit_entity_action_created')) {
            $db->exec('ALTER TABLE audit_log DROP INDEX idx_audit_entity_action_created');
        }
    },
];
