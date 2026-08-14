<?php
/**
 * Migration 004: User list column preferences (persist selected columns per user per module)
 */
return [
    'name' => 'migration_004_user_list_columns',
    'up' => function (\PDO $db): void {
        $db->exec("
            CREATE TABLE IF NOT EXISTS user_list_columns (
                user_id INT NOT NULL,
                module VARCHAR(50) NOT NULL,
                column_keys TEXT NOT NULL,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (user_id, module),
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    },
    'down' => function (\PDO $db): void {
        $db->exec('DROP TABLE IF EXISTS user_list_columns');
    },
];
