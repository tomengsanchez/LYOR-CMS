<?php
/**
 * Migration 011: Add days_to_address to grievance_progress_levels
 * Adds an integer field to record expected days to address each In Progress stage.
 */
return [
    'name' => 'migration_011_progress_levels_days_to_address',
    'up' => function (\PDO $db): void {
        $db->exec("
            ALTER TABLE grievance_progress_levels
            ADD COLUMN days_to_address INT NULL AFTER sort_order
        ");
    },
    'down' => function (\PDO $db): void {
        $db->exec("
            ALTER TABLE grievance_progress_levels
            DROP COLUMN days_to_address
        ");
    },
];

