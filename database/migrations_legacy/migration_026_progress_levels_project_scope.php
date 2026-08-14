<?php
/**
 * Migration 026: Scope grievance progress levels by project.
 * Adds nullable project_id; NULL rows are global defaults (Level 1/2/3 fallback).
 */
return [
    'name' => 'migration_026_progress_levels_project_scope',
    'up' => function (\PDO $db): void {
        $hasProjectId = false;
        try {
            $stmt = $db->query("SHOW COLUMNS FROM grievance_progress_levels LIKE 'project_id'");
            $hasProjectId = (bool) $stmt->fetch(\PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            $hasProjectId = false;
        }

        if (!$hasProjectId) {
            $db->exec("
                ALTER TABLE grievance_progress_levels
                ADD COLUMN project_id INT NULL AFTER id,
                ADD INDEX idx_grievance_progress_levels_project_id (project_id)
            ");
            $db->exec("
                ALTER TABLE grievance_progress_levels
                ADD CONSTRAINT fk_grievance_progress_levels_project
                FOREIGN KEY (project_id) REFERENCES projects(id)
                ON DELETE SET NULL
            ");
        }
    },
    'down' => function (\PDO $db): void {
        $hasProjectId = false;
        try {
            $stmt = $db->query("SHOW COLUMNS FROM grievance_progress_levels LIKE 'project_id'");
            $hasProjectId = (bool) $stmt->fetch(\PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            $hasProjectId = false;
        }
        if (!$hasProjectId) {
            return;
        }

        try {
            $db->exec('ALTER TABLE grievance_progress_levels DROP FOREIGN KEY fk_grievance_progress_levels_project');
        } catch (\Throwable $e) {
            // ignore if constraint name differs
        }
        try {
            $db->exec('ALTER TABLE grievance_progress_levels DROP INDEX idx_grievance_progress_levels_project_id');
        } catch (\Throwable $e) {
            // ignore if index missing
        }
        $db->exec('ALTER TABLE grievance_progress_levels DROP COLUMN project_id');
    },
];

