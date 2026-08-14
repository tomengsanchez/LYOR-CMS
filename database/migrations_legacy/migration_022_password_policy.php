<?php
/**
 * Migration 022: Password policy support
 *
 * - Add password_changed_at to users
 * - Create user_password_history table
 */
return [
    'name' => 'migration_022_password_policy',
    'up' => function (\PDO $db): void {
        $db->exec("
            ALTER TABLE users
            ADD COLUMN password_changed_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP AFTER password_hash
        ");

        $db->exec("
            CREATE TABLE IF NOT EXISTS user_password_history (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                password_hash VARCHAR(255) NOT NULL,
                changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                INDEX idx_user_password_history_user (user_id, changed_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    },
    'down' => function (\PDO $db): void {
        $db->exec("DROP TABLE IF EXISTS user_password_history");
        $db->exec("ALTER TABLE users DROP COLUMN password_changed_at");
    },
];

