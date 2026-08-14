<?php
/**
 * Migration 074: Allow users without a password (reference / data-only accounts).
 */
return [
    'name' => 'migration_074_users_optional_password',
    'up' => function (\PDO $db): void {
        $stmt = $db->query("SHOW COLUMNS FROM users LIKE 'password_hash'");
        $col = $stmt->fetch(\PDO::FETCH_ASSOC);
        if ($col && strtoupper((string) ($col['Null'] ?? '')) === 'NO') {
            $db->exec('ALTER TABLE users MODIFY COLUMN password_hash VARCHAR(255) NULL');
        }
    },
    'down' => function (\PDO $db): void {
        $db->exec("UPDATE users SET password_hash = '' WHERE password_hash IS NULL");
        $db->exec('ALTER TABLE users MODIFY COLUMN password_hash VARCHAR(255) NOT NULL');
    },
];
