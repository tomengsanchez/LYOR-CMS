<?php
/**
 * Migration 015: Add display_name to users and user_projects for linked projects.
 * Merges User Profile into User: display name on user; many projects per user.
 */
return [
    'name' => 'migration_015_user_display_name_and_linked_projects',
    'up' => function (\PDO $db): void {
        // Add display_name to users (optional label; was "name" in user_profiles)
        $db->exec("ALTER TABLE users ADD COLUMN display_name VARCHAR(255) NULL DEFAULT NULL AFTER email");
        // Migrate existing user_profiles.name to users.display_name
        $db->exec("
            UPDATE users u
            INNER JOIN user_profiles up ON up.user_id = u.id
            SET u.display_name = NULLIF(TRIM(up.name), '')
        ");
        // Junction: user linked to many projects
        $db->exec("
            CREATE TABLE IF NOT EXISTS user_projects (
                user_id INT NOT NULL,
                project_id INT NOT NULL,
                PRIMARY KEY (user_id, project_id),
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    },
    'down' => function (\PDO $db): void {
        $db->exec('DROP TABLE IF EXISTS user_projects');
        $db->exec('ALTER TABLE users DROP COLUMN display_name');
    },
];
