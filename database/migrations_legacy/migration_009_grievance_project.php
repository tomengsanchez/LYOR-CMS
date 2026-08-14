<?php
/**
 * Migration 009: Add project_id to grievances
 */
return [
    'name' => 'migration_009_grievance_project',
    'up' => function (\PDO $db): void {
        $db->exec("ALTER TABLE grievances ADD COLUMN project_id INT NULL AFTER grievance_case_number");
        $db->exec("ALTER TABLE grievances ADD CONSTRAINT fk_grievances_project FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE SET NULL");
    },
    'down' => function (\PDO $db): void {
        $db->exec('ALTER TABLE grievances DROP FOREIGN KEY fk_grievances_project');
        $db->exec('ALTER TABLE grievances DROP COLUMN project_id');
    },
];
