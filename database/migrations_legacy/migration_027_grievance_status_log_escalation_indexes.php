<?php
/**
 * Migration 027: Add targeted grievance_status_log indexes for escalation queries.
 *
 * These indexes optimize:
 * - current in-progress segment start lookup (s1)
 * - transition existence checks by grievance timeline (s2)
 */
return [
    'name' => 'migration_027_grievance_status_log_escalation_indexes',
    'up' => function (\PDO $db): void {
        $hasIndex = static function (string $indexName) use ($db): bool {
            $stmt = $db->prepare("SHOW INDEX FROM grievance_status_log WHERE Key_name = ?");
            $stmt->execute([$indexName]);
            return (bool) $stmt->fetch(\PDO::FETCH_ASSOC);
        };

        if (!$hasIndex('idx_gsl_grievance_status_level_created_id')) {
            $db->exec("
                ALTER TABLE grievance_status_log
                ADD INDEX idx_gsl_grievance_status_level_created_id
                    (grievance_id, status, progress_level, created_at, id)
            ");
        }

        if (!$hasIndex('idx_gsl_grievance_created_id_status_level')) {
            $db->exec("
                ALTER TABLE grievance_status_log
                ADD INDEX idx_gsl_grievance_created_id_status_level
                    (grievance_id, created_at, id, status, progress_level)
            ");
        }
    },
    'down' => function (\PDO $db): void {
        $hasIndex = static function (string $indexName) use ($db): bool {
            $stmt = $db->prepare("SHOW INDEX FROM grievance_status_log WHERE Key_name = ?");
            $stmt->execute([$indexName]);
            return (bool) $stmt->fetch(\PDO::FETCH_ASSOC);
        };

        if ($hasIndex('idx_gsl_grievance_status_level_created_id')) {
            $db->exec('
                ALTER TABLE grievance_status_log
                DROP INDEX idx_gsl_grievance_status_level_created_id
            ');
        }

        if ($hasIndex('idx_gsl_grievance_created_id_status_level')) {
            $db->exec('
                ALTER TABLE grievance_status_log
                DROP INDEX idx_gsl_grievance_created_id_status_level
            ');
        }
    },
];

