<?php
/**
 * Migration 039: Add profile_attachments table for card-style profile files.
 */
return [
    'name' => 'migration_039_profile_attachments',
    'up' => function (\PDO $db): void {
        $db->exec("
            CREATE TABLE IF NOT EXISTS profile_attachments (
                id INT AUTO_INCREMENT PRIMARY KEY,
                profile_id INT NOT NULL,
                title VARCHAR(255) NOT NULL,
                description TEXT NULL,
                file_path VARCHAR(255) NOT NULL,
                sort_order INT NOT NULL DEFAULT 0,
                is_deleted TINYINT(1) NOT NULL DEFAULT 0,
                deleted_at DATETIME NULL,
                deleted_by INT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                CONSTRAINT fk_profile_attachments_profile
                    FOREIGN KEY (profile_id) REFERENCES profiles(id) ON DELETE CASCADE,
                INDEX idx_profile_attachments_profile (profile_id),
                INDEX idx_profile_attachments_deleted (is_deleted)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    },
    'down' => function (\PDO $db): void {
        $db->exec('DROP TABLE IF EXISTS profile_attachments');
    },
];
