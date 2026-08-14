<?php
/**
 * Migration 013: Indexes for grievance dashboard performance
 *
 * Adds supporting indexes to speed up dashboard aggregates and escalation checks.
 */
return [
    'name' => 'migration_013_dashboard_indexes',
    'up' => function (\PDO $db): void {
        // Add indexes to grievances for common dashboard filters and aggregates
        $db->exec("
            ALTER TABLE grievances
                ADD INDEX idx_grievances_status (status),
                ADD INDEX idx_grievances_project (project_id),
                ADD INDEX idx_grievances_project_date (project_id, date_recorded),
                ADD INDEX idx_grievances_status_progress_level (status, progress_level)
        ");

        // Add composite index to grievance_status_log to speed escalation calculations
        $db->exec("
            ALTER TABLE grievance_status_log
                ADD INDEX idx_gsl_status_progress_grievance_created (status, progress_level, grievance_id, created_at)
        ");
    },
    'down' => function (\PDO $db): void {
        $db->exec("
            ALTER TABLE grievances
                DROP INDEX idx_grievances_status,
                DROP INDEX idx_grievances_project,
                DROP INDEX idx_grievances_project_date,
                DROP INDEX idx_grievances_status_progress_level
        ");

        $db->exec("
            ALTER TABLE grievance_status_log
                DROP INDEX idx_gsl_status_progress_grievance_created
        ");
    },
];

