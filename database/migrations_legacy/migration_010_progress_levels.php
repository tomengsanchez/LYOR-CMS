<?php
/**
 * Migration 010: Make In Progress stages maintainable via Options Library
 * Creates grievance_progress_levels table (id, name, description, sort_order)
 * Seeds Level 1, 2, 3 for backward compatibility with existing grievances.progress_level
 */
return [
    'name' => 'migration_010_progress_levels',
    'up' => function (\PDO $db): void {
        $db->exec("
            CREATE TABLE IF NOT EXISTS grievance_progress_levels (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                description TEXT,
                sort_order INT NOT NULL DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        $stmt = $db->query('SELECT COUNT(*) FROM grievance_progress_levels');
        if ((int) $stmt->fetchColumn() === 0) {
            $db->exec("INSERT INTO grievance_progress_levels (id, name, sort_order) VALUES (1, 'Level 1', 1), (2, 'Level 2', 2), (3, 'Level 3', 3)");
        }
    },
    'down' => function (\PDO $db): void {
        $db->exec('DROP TABLE IF EXISTS grievance_progress_levels');
    },
];
