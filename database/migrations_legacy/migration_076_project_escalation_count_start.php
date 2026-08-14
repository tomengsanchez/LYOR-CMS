<?php
/**
 * Migration 076: Per-project escalation SLA day-count start (effective date vs next day).
 */
return [
    'name' => 'migration_076_project_escalation_count_start',
    'up' => function (\PDO $db): void {
        $stmt = $db->query("SHOW COLUMNS FROM projects LIKE 'escalation_count_start'");
        if (!$stmt->fetch(\PDO::FETCH_ASSOC)) {
            $db->exec("
                ALTER TABLE projects
                ADD COLUMN escalation_count_start
                    ENUM('effective_date', 'next_day') NOT NULL DEFAULT 'next_day'
                    AFTER description
            ");
        }
        $db->exec("UPDATE projects SET escalation_count_start = 'next_day' WHERE escalation_count_start IS NULL OR escalation_count_start = ''");
    },
    'down' => function (\PDO $db): void {
        $stmt = $db->query("SHOW COLUMNS FROM projects LIKE 'escalation_count_start'");
        if ($stmt->fetch(\PDO::FETCH_ASSOC)) {
            $db->exec('ALTER TABLE projects DROP COLUMN escalation_count_start');
        }
    },
];
