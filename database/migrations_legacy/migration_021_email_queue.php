<?php
/**
 * Migration 021: Email queue for background sending (notification emails).
 * Web requests enqueue only; CLI/cron sends via cli/send_queued_emails.php.
 */
return [
    'name' => 'migration_021_email_queue',
    'up' => function (\PDO $db): void {
        $db->exec("
            CREATE TABLE IF NOT EXISTS email_queue (
                id INT AUTO_INCREMENT PRIMARY KEY,
                to_email VARCHAR(255) NOT NULL,
                subject VARCHAR(500) NOT NULL,
                body TEXT NOT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                sent_at DATETIME NULL DEFAULT NULL,
                status VARCHAR(20) NOT NULL DEFAULT 'pending',
                error_message VARCHAR(500) NULL DEFAULT NULL,
                INDEX idx_status_created (status, created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    },
    'down' => function (\PDO $db): void {
        $db->exec('DROP TABLE IF EXISTS email_queue');
    },
];
