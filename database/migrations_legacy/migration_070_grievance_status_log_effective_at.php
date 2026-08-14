<?php
/**
 * Migration 070: Backdatable effective date on grievance_status_log for escalation clock.
 */
return [
    'name' => 'migration_070_grievance_status_log_effective_at',
    'up' => function (\PDO $db): void {
        \Core\MigrationScope::ddlThenTransactionalDml(
            $db,
            static function (\PDO $db): void {
                $stmt = $db->prepare('SHOW COLUMNS FROM grievance_status_log LIKE ?');
                $stmt->execute(['effective_at']);
                if (!$stmt->fetch(\PDO::FETCH_ASSOC)) {
                    $db->exec('ALTER TABLE grievance_status_log ADD COLUMN effective_at DATETIME NULL AFTER progress_level');
                }
            },
            static function (\PDO $db): void {
                $db->exec('UPDATE grievance_status_log SET effective_at = created_at WHERE effective_at IS NULL');
                \App\Models\Grievance::backfillCurrentLevelStartedAt($db);
            }
        );
    },
    'down' => function (\PDO $db): void {
        $stmt = $db->prepare('SHOW COLUMNS FROM grievance_status_log LIKE ?');
        $stmt->execute(['effective_at']);
        if ($stmt->fetch(\PDO::FETCH_ASSOC)) {
            $db->exec('ALTER TABLE grievance_status_log DROP COLUMN effective_at');
        }
    },
];
