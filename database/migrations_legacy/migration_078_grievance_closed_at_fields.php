<?php
/**
 * Migration 078: Denormalize closure stage and date on grievances for dashboard and detail views.
 */
return [
    'name' => 'migration_078_grievance_closed_at_fields',
    'up' => function (\PDO $db): void {
        \Core\MigrationScope::ddlThenTransactionalDml(
            $db,
            static function (\PDO $db): void {
                $stmt = $db->prepare('SHOW COLUMNS FROM grievances LIKE ?');
                $stmt->execute(['closed_at_progress_level']);
                if (!$stmt->fetch(\PDO::FETCH_ASSOC)) {
                    $db->exec('ALTER TABLE grievances ADD COLUMN closed_at_progress_level INT NULL AFTER current_level_started_at');
                }

                $stmt->execute(['closed_at']);
                if (!$stmt->fetch(\PDO::FETCH_ASSOC)) {
                    $db->exec('ALTER TABLE grievances ADD COLUMN closed_at DATETIME NULL AFTER closed_at_progress_level');
                }

                $idx = $db->prepare('SHOW INDEX FROM grievances WHERE Key_name = ?');
                $idx->execute(['idx_grievances_closed_dashboard']);
                if (!$idx->fetch(\PDO::FETCH_ASSOC)) {
                    $db->exec('ALTER TABLE grievances ADD INDEX idx_grievances_closed_dashboard (status, closed_at, closed_at_progress_level)');
                }
            },
            static function (\PDO $db): void {
                \App\Models\Grievance::backfillClosedAtFields($db);
            }
        );
    },
    'down' => function (\PDO $db): void {
        $idx = $db->prepare('SHOW INDEX FROM grievances WHERE Key_name = ?');
        $idx->execute(['idx_grievances_closed_dashboard']);
        if ($idx->fetch(\PDO::FETCH_ASSOC)) {
            $db->exec('ALTER TABLE grievances DROP INDEX idx_grievances_closed_dashboard');
        }

        $stmt = $db->prepare('SHOW COLUMNS FROM grievances LIKE ?');
        $stmt->execute(['closed_at']);
        if ($stmt->fetch(\PDO::FETCH_ASSOC)) {
            $db->exec('ALTER TABLE grievances DROP COLUMN closed_at');
        }

        $stmt->execute(['closed_at_progress_level']);
        if ($stmt->fetch(\PDO::FETCH_ASSOC)) {
            $db->exec('ALTER TABLE grievances DROP COLUMN closed_at_progress_level');
        }
    },
];
