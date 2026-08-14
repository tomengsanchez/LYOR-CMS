<?php
/**
 * Migration 018: Generic audit log for entity history
 *
 * Stores creation and edit history for profiles, structures, grievances, and future modules.
 */
return [
    'name' => 'migration_018_audit_log',
    'up' => function (\PDO $db): void {
        $db->exec("
            CREATE TABLE IF NOT EXISTS audit_log (
                id INT AUTO_INCREMENT PRIMARY KEY,
                entity_type VARCHAR(50) NOT NULL,
                entity_id INT NOT NULL,
                action VARCHAR(50) NOT NULL,
                changes JSON NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                created_by INT NULL,
                INDEX idx_entity (entity_type, entity_id),
                INDEX idx_created (created_at),
                CONSTRAINT fk_audit_log_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    },
    'down' => function (\PDO $db): void {
        $db->exec('DROP TABLE IF EXISTS audit_log');
    },
];

