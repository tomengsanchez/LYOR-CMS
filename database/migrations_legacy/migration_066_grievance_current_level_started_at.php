<?php
/**
 * Migration 066: Denormalize escalation clock on grievances for fast overdue queries.
 */
return [
    'name' => 'migration_066_grievance_current_level_started_at',
    'up' => function (\PDO $db): void {
        \Core\MigrationScope::ddlThenTransactionalDml(
            $db,
            static function (\PDO $db): void {
                $stmt = $db->prepare('SHOW COLUMNS FROM grievances LIKE ?');
                $stmt->execute(['current_level_started_at']);
                if (!$stmt->fetch(\PDO::FETCH_ASSOC)) {
                    $db->exec('ALTER TABLE grievances ADD COLUMN current_level_started_at DATETIME NULL AFTER progress_level');
                }

                $idx = $db->prepare('SHOW INDEX FROM grievances WHERE Key_name = ?');
                $idx->execute(['idx_grievances_escalation_clock']);
                if (!$idx->fetch(\PDO::FETCH_ASSOC)) {
                    $db->exec('ALTER TABLE grievances ADD INDEX idx_grievances_escalation_clock (status, progress_level, current_level_started_at)');
                }
            },
            static function (\PDO $db): void {
                \App\Models\Grievance::backfillCurrentLevelStartedAt($db);
            }
        );
    },
    'down' => function (\PDO $db): void {
        $idx = $db->prepare('SHOW INDEX FROM grievances WHERE Key_name = ?');
        $idx->execute(['idx_grievances_escalation_clock']);
        if ($idx->fetch(\PDO::FETCH_ASSOC)) {
            $db->exec('ALTER TABLE grievances DROP INDEX idx_grievances_escalation_clock');
        }

        $stmt = $db->prepare('SHOW COLUMNS FROM grievances LIKE ?');
        $stmt->execute(['current_level_started_at']);
        if ($stmt->fetch(\PDO::FETCH_ASSOC)) {
            $db->exec('ALTER TABLE grievances DROP COLUMN current_level_started_at');
        }
    },
];
