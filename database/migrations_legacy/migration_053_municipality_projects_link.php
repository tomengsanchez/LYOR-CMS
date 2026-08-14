<?php
/**
 * Migration 053: municipality <-> projects junction table.
 */
return [
    'name' => 'migration_053_municipality_projects_link',
    'up' => function (\PDO $db): void {
        $db->exec("
            CREATE TABLE IF NOT EXISTS municipality_projects (
                municipality_id INT NOT NULL,
                project_id INT NOT NULL,
                PRIMARY KEY (municipality_id, project_id),
                KEY idx_municipality_projects_project (project_id),
                CONSTRAINT fk_municipality_projects_municipality FOREIGN KEY (municipality_id) REFERENCES municipalities(id) ON DELETE CASCADE,
                CONSTRAINT fk_municipality_projects_project FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    },
    'down' => function (\PDO $db): void {
        $db->exec('DROP TABLE IF EXISTS municipality_projects');
    },
];
