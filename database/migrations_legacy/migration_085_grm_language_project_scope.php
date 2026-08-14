<?php
/**
 * Migration 085: Scope GRM channels and preferred languages by project.
 * Adds nullable project_id; NULL rows are global defaults (same pattern as progress levels).
 */
return [
    'name' => 'migration_085_grm_language_project_scope',
    'up' => function (\PDO $db): void {
        foreach ([
            'grievance_grm_channels' => 'fk_grievance_grm_channels_project',
            'grievance_preferred_languages' => 'fk_grievance_preferred_languages_project',
        ] as $table => $fkName) {
            $hasProjectId = false;
            try {
                $stmt = $db->query("SHOW COLUMNS FROM {$table} LIKE 'project_id'");
                $hasProjectId = (bool) $stmt->fetch(\PDO::FETCH_ASSOC);
            } catch (\Throwable $e) {
                $hasProjectId = false;
            }
            if ($hasProjectId) {
                continue;
            }
            $db->exec("
                ALTER TABLE {$table}
                ADD COLUMN project_id INT NULL AFTER id,
                ADD INDEX idx_{$table}_project_id (project_id)
            ");
            $db->exec("
                ALTER TABLE {$table}
                ADD CONSTRAINT {$fkName}
                FOREIGN KEY (project_id) REFERENCES projects(id)
                ON DELETE SET NULL
            ");
        }
    },
    'down' => function (\PDO $db): void {
        foreach ([
            'grievance_grm_channels' => 'fk_grievance_grm_channels_project',
            'grievance_preferred_languages' => 'fk_grievance_preferred_languages_project',
        ] as $table => $fkName) {
            $hasProjectId = false;
            try {
                $stmt = $db->query("SHOW COLUMNS FROM {$table} LIKE 'project_id'");
                $hasProjectId = (bool) $stmt->fetch(\PDO::FETCH_ASSOC);
            } catch (\Throwable $e) {
                $hasProjectId = false;
            }
            if (!$hasProjectId) {
                continue;
            }
            try {
                $db->exec("ALTER TABLE {$table} DROP FOREIGN KEY {$fkName}");
            } catch (\Throwable $e) {
                // ignore if constraint name differs
            }
            try {
                $db->exec("ALTER TABLE {$table} DROP INDEX idx_{$table}_project_id");
            } catch (\Throwable $e) {
                // ignore if index missing
            }
            $db->exec("ALTER TABLE {$table} DROP COLUMN project_id");
        }
    },
];
