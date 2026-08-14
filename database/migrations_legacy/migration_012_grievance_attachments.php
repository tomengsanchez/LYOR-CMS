<?php
/**
 * Migration 012: Grievance attachment cards (Title, Description, File per attachment).
 */
return [
    'name' => 'migration_012_grievance_attachments',
    'up' => function (\PDO $db): void {
        $db->exec("
            CREATE TABLE IF NOT EXISTS grievance_attachments (
                id INT AUTO_INCREMENT PRIMARY KEY,
                grievance_id INT NOT NULL,
                title VARCHAR(255) NOT NULL DEFAULT '',
                description TEXT NULL,
                file_path VARCHAR(512) NOT NULL,
                sort_order INT NOT NULL DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_grievance (grievance_id),
                CONSTRAINT fk_grievance_attachments_grievance FOREIGN KEY (grievance_id) REFERENCES grievances(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    },
    'down' => function (\PDO $db): void {
        $db->exec('DROP TABLE IF EXISTS grievance_attachments');
    },
];
