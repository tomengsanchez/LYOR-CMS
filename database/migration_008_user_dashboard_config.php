<?php
/**
 * Migration 008: Per-user UI and notification preferences
 */
return [
    'name' => 'migration_008_user_dashboard_config',
    'up' => function (\PDO $db): void {
        $db->exec("
            CREATE TABLE IF NOT EXISTS user_dashboard_config (
                user_id INT NOT NULL,
                module VARCHAR(50) NOT NULL,
                config TEXT NULL,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (user_id, module),
                CONSTRAINT fk_user_dashboard_config_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    },
    'down' => function (\PDO $db): void {
        $db->exec('DROP TABLE IF EXISTS user_dashboard_config');
    },
];
