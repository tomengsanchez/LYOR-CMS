<?php
/**
 * Migration 087: Live Traffic tables + capabilities.
 *
 * Stores per-request HTTP traffic (humans, bots/crawlers, login attempts, 404s, blocks),
 * site-wide IP blocklist, and Geo/DNS cache. Grants Administrator view/manage capabilities.
 */
return [
    'name' => 'migration_087_live_traffic',
    'up' => function (\PDO $db): void {
        $db->exec("
            CREATE TABLE IF NOT EXISTS traffic_events (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                ip_address VARCHAR(45) NOT NULL,
                hostname VARCHAR(255) NULL,
                country_code CHAR(2) NULL,
                city VARCHAR(120) NULL,
                region VARCHAR(120) NULL,
                method VARCHAR(10) NOT NULL DEFAULT 'GET',
                path VARCHAR(512) NOT NULL,
                query_string VARCHAR(512) NULL,
                status_code SMALLINT NOT NULL DEFAULT 200,
                user_agent VARCHAR(512) NULL,
                referer VARCHAR(512) NULL,
                visitor_type VARCHAR(20) NOT NULL DEFAULT 'human',
                event_type VARCHAR(40) NOT NULL DEFAULT 'hit',
                user_id INT NULL,
                username VARCHAR(100) NULL,
                message VARCHAR(512) NULL,
                INDEX idx_traffic_created (created_at),
                INDEX idx_traffic_ip_created (ip_address, created_at),
                INDEX idx_traffic_visitor_created (visitor_type, created_at),
                INDEX idx_traffic_event_created (event_type, created_at),
                CONSTRAINT fk_traffic_events_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $db->exec("
            CREATE TABLE IF NOT EXISTS traffic_ip_blocks (
                id INT AUTO_INCREMENT PRIMARY KEY,
                ip_address VARCHAR(45) NOT NULL,
                reason VARCHAR(255) NULL,
                created_by INT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                expires_at DATETIME NULL,
                UNIQUE KEY uq_traffic_ip_blocks_ip (ip_address),
                INDEX idx_traffic_blocks_expires (expires_at),
                CONSTRAINT fk_traffic_ip_blocks_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $db->exec("
            CREATE TABLE IF NOT EXISTS traffic_geo_cache (
                ip_address VARCHAR(45) NOT NULL PRIMARY KEY,
                hostname VARCHAR(255) NULL,
                country_code CHAR(2) NULL,
                city VARCHAR(120) NULL,
                region VARCHAR(120) NULL,
                raw_json TEXT NULL,
                looked_up_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $insSetting = $db->prepare(
            'INSERT IGNORE INTO app_settings (setting_key, setting_value) VALUES (?, ?)'
        );
        $insSetting->execute(['live_traffic_enabled', '1']);
        $insSetting->execute(['live_traffic_retention_days', '30']);

        $ins = $db->prepare('INSERT IGNORE INTO role_capabilities (role_id, capability) VALUES (?, ?)');
        $admin = $db->query("SELECT id FROM roles WHERE name = 'Administrator' LIMIT 1")->fetchColumn();
        if ($admin) {
            foreach (['view_live_traffic', 'manage_live_traffic'] as $cap) {
                $ins->execute([(int) $admin, $cap]);
            }
        }
    },
    'down' => function (\PDO $db): void {
        $db->exec("DELETE FROM role_capabilities WHERE capability IN (
            'view_live_traffic', 'manage_live_traffic'
        )");
        $db->exec("DELETE FROM app_settings WHERE setting_key IN (
            'live_traffic_enabled', 'live_traffic_retention_days'
        )");
        $db->exec('DROP TABLE IF EXISTS traffic_events');
        $db->exec('DROP TABLE IF EXISTS traffic_ip_blocks');
        $db->exec('DROP TABLE IF EXISTS traffic_geo_cache');
    },
];
