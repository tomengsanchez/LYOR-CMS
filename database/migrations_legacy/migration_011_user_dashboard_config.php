<?php
/**
 * Migration 011: User dashboard config (widget visibility and order per user per module)
 */
return [
    'name' => 'migration_011_user_dashboard_config',
    'up' => function (\PDO $db): void {
        $db->exec("
            CREATE TABLE IF NOT EXISTS user_dashboard_config (
                user_id INT NOT NULL,
                module VARCHAR(50) NOT NULL,
                config JSON,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (user_id, module),
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    },
    'down' => function (\PDO $db): void {
        $db->exec('DROP TABLE IF EXISTS user_dashboard_config');
    },
];
