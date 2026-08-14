<?php
/**
 * Migration 003: Audit log and backup archives
 */
return [
    'name' => 'migration_003_audit_backup',
    'up' => function (\PDO $db): void {
        $db->exec("
            CREATE TABLE IF NOT EXISTS audit_log (
                id INT AUTO_INCREMENT PRIMARY KEY,
                entity_type VARCHAR(50) NOT NULL,
                entity_id INT NOT NULL,
                action VARCHAR(50) NOT NULL,
                changes TEXT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                created_by INT NULL,
                INDEX idx_entity (entity_type, entity_id),
                INDEX idx_created (created_at),
                CONSTRAINT fk_audit_log_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

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
        $db->exec('DROP TABLE IF EXISTS audit_log');
    },
];
