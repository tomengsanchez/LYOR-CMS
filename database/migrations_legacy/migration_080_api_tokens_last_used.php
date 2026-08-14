<?php
/**
 * Migration 080: Track API token last use for idle timeout.
 *
 * Adds last_used_at so Bearer tokens can expire after inactivity
 * (see App\ApiToken), in addition to absolute expires_at.
 */
return [
    'name' => 'migration_080_api_tokens_last_used',
    'up' => function (\PDO $db): void {
        $stmt = $db->query("SHOW COLUMNS FROM api_tokens LIKE 'last_used_at'");
        if (!$stmt || !$stmt->fetch(\PDO::FETCH_ASSOC)) {
            $db->exec('ALTER TABLE api_tokens ADD COLUMN last_used_at DATETIME NULL AFTER expires_at');
            $db->exec('UPDATE api_tokens SET last_used_at = COALESCE(created_at, NOW()) WHERE last_used_at IS NULL');
            $db->exec('ALTER TABLE api_tokens ADD INDEX idx_api_tokens_last_used (last_used_at)');
        }
    },
    'down' => function (\PDO $db): void {
        $stmt = $db->prepare('SHOW INDEX FROM api_tokens WHERE Key_name = ?');
        $stmt->execute(['idx_api_tokens_last_used']);
        if ($stmt->fetch(\PDO::FETCH_ASSOC)) {
            $db->exec('ALTER TABLE api_tokens DROP INDEX idx_api_tokens_last_used');
        }
        $col = $db->query("SHOW COLUMNS FROM api_tokens LIKE 'last_used_at'");
        if ($col && $col->fetch(\PDO::FETCH_ASSOC)) {
            $db->exec('ALTER TABLE api_tokens DROP COLUMN last_used_at');
        }
    },
];
