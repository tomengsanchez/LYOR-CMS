<?php
/**
 * Migration 095: API client security/usage event log.
 */
return [
    'name' => 'migration_095_api_client_events',
    'up' => function (\PDO $db): void {
        $db->exec("
            CREATE TABLE IF NOT EXISTS api_client_events (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                created_at DATETIME NOT NULL,
                event_type VARCHAR(32) NOT NULL,
                client_id VARCHAR(64) NULL,
                user_id INT NULL,
                project_id INT NULL,
                method VARCHAR(16) NULL,
                path VARCHAR(512) NULL,
                status_code SMALLINT NULL,
                ip VARCHAR(45) NULL,
                user_agent VARCHAR(512) NULL,
                message VARCHAR(255) NULL,
                INDEX idx_api_client_events_created (created_at),
                INDEX idx_api_client_events_type_created (event_type, created_at),
                INDEX idx_api_client_events_client (client_id, created_at),
                INDEX idx_api_client_events_user (user_id, created_at),
                INDEX idx_api_client_events_project (project_id, created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        // Logging on by default once table exists
        $stmt = $db->prepare('SELECT setting_value FROM app_settings WHERE setting_key = ?');
        $stmt->execute(['api_clients_logging_enabled']);
        if ($stmt->fetchColumn() === false) {
            $ins = $db->prepare('INSERT INTO app_settings (setting_key, setting_value) VALUES (?, ?)');
            $ins->execute(['api_clients_logging_enabled', '1']);
            $ins->execute(['api_clients_log_retention_days', '90']);
        }
    },
    'down' => function (\PDO $db): void {
        $db->exec('DROP TABLE IF EXISTS api_client_events');
        $db->exec("DELETE FROM app_settings WHERE setting_key IN ('api_clients_logging_enabled', 'api_clients_log_retention_days')");
    },
];
