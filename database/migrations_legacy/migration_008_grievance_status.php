<?php
/**
 * Migration 008: Grievance ticketing status system
 * Status: open, in_progress, closed. In Progress has levels 1, 2, 3.
 * Each status/stage can have notes and attachments via grievance_status_log.
 */
return [
    'name' => 'migration_008_grievance_status',
    'up' => function (\PDO $db): void {
        $db->exec("ALTER TABLE grievances ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT 'open' AFTER desired_resolution");
        $db->exec("ALTER TABLE grievances ADD COLUMN progress_level TINYINT NULL COMMENT '1,2,3 when status=in_progress' AFTER status");
        $db->exec("
            CREATE TABLE IF NOT EXISTS grievance_status_log (
                id INT AUTO_INCREMENT PRIMARY KEY,
                grievance_id INT NOT NULL,
                status VARCHAR(20) NOT NULL,
                progress_level TINYINT NULL,
                note TEXT,
                attachments JSON,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                created_by INT NULL,
                INDEX idx_grievance (grievance_id),
                INDEX idx_created (created_at),
                CONSTRAINT fk_grievance_status_log_grievance FOREIGN KEY (grievance_id) REFERENCES grievances(id) ON DELETE CASCADE,
                CONSTRAINT fk_grievance_status_log_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    },
    'down' => function (\PDO $db): void {
        $db->exec('DROP TABLE IF EXISTS grievance_status_log');
        $db->exec('ALTER TABLE grievances DROP COLUMN status, DROP COLUMN progress_level');
    },
];
