<?php
/**
 * Migration 040: Track backup archives in database.
 */
return [
    'name' => 'migration_040_backup_archives',
    'up' => function (\PDO $db): void {
        $db->exec("
            CREATE TABLE IF NOT EXISTS backup_archives (
                id INT AUTO_INCREMENT PRIMARY KEY,
                file_name VARCHAR(255) NOT NULL UNIQUE,
                file_path VARCHAR(255) NOT NULL,
                file_size BIGINT NOT NULL DEFAULT 0,
                backup_reason VARCHAR(50) NULL,
                restore_source_file VARCHAR(255) NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_backup_archives_created_at (created_at),
                INDEX idx_backup_archives_reason (backup_reason)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    },
    'down' => function (\PDO $db): void {
        $db->exec('DROP TABLE IF EXISTS backup_archives');
    },
];
