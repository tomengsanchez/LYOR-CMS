<?php
/**
 * Migration 025: Newsletter subscribers (double opt-in tokens).
 * MySQL and MariaDB compatible (InnoDB, utf8mb4, VARCHAR(191) unique email).
 */
return [
    'name' => 'migration_025_newsletter_subscribers',
    'up' => function (\PDO $db): void {
        $db->exec("
            CREATE TABLE IF NOT EXISTS cms_newsletter_subscribers (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                email VARCHAR(191) NOT NULL,
                status VARCHAR(20) NOT NULL DEFAULT 'pending',
                confirm_token CHAR(64) NULL DEFAULT NULL,
                unsub_token CHAR(64) NULL DEFAULT NULL,
                ip_address VARCHAR(45) NULL DEFAULT NULL,
                user_agent VARCHAR(255) NULL DEFAULT NULL,
                consent_at DATETIME NULL DEFAULT NULL,
                confirm_sent_at DATETIME NULL DEFAULT NULL,
                confirmed_at DATETIME NULL DEFAULT NULL,
                unsubscribed_at DATETIME NULL DEFAULT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uq_nl_email (email),
                UNIQUE KEY uq_nl_confirm (confirm_token),
                UNIQUE KEY uq_nl_unsub (unsub_token),
                KEY idx_nl_status (status),
                KEY idx_nl_ip_created (ip_address, created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    },
    'down' => function (\PDO $db): void {
        $db->exec('DROP TABLE IF EXISTS cms_newsletter_subscribers');
    },
];
