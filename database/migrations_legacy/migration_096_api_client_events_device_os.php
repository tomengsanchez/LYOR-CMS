<?php
/**
 * Migration 096: Device model + OS version on API client event logs (not client CRUD).
 */
return [
    'name' => 'migration_096_api_client_events_device_os',
    'up' => function (\PDO $db): void {
        $has = static function (string $col) use ($db): bool {
            try {
                return (bool) $db->query("SHOW COLUMNS FROM api_client_events LIKE " . $db->quote($col))->fetchColumn();
            } catch (\Throwable $e) {
                return false;
            }
        };
        if (!$has('device_model')) {
            $db->exec('ALTER TABLE api_client_events ADD COLUMN device_model VARCHAR(128) NULL AFTER user_agent');
        }
        if (!$has('os_version')) {
            $db->exec('ALTER TABLE api_client_events ADD COLUMN os_version VARCHAR(64) NULL AFTER device_model');
        }
        // Drop short-lived single "device" column if a partial prior attempt created it
        if ($has('device')) {
            try {
                $db->exec('ALTER TABLE api_client_events DROP INDEX idx_api_client_events_device');
            } catch (\Throwable $e) {
                // ignore
            }
            try {
                $db->exec('ALTER TABLE api_client_events DROP COLUMN device');
            } catch (\Throwable $e) {
                // ignore
            }
        }
    },
    'down' => function (\PDO $db): void {
        foreach (['os_version', 'device_model'] as $col) {
            try {
                $db->exec('ALTER TABLE api_client_events DROP COLUMN `' . $col . '`');
            } catch (\Throwable $e) {
                // ignore
            }
        }
    },
];
