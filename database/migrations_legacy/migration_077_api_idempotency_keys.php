<?php
/**
 * Migration 077: API idempotency keys for safe POST retries.
 */
return [
    'name' => 'migration_077_api_idempotency_keys',
    'up' => function (\PDO $db): void {
        $db->exec("
            CREATE TABLE IF NOT EXISTS api_idempotency_keys (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                scope VARCHAR(191) NOT NULL,
                key_hash CHAR(64) NOT NULL,
                http_status SMALLINT UNSIGNED NOT NULL,
                response_json LONGTEXT NOT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                expires_at DATETIME NOT NULL,
                UNIQUE KEY uq_api_idempotency (user_id, scope, key_hash),
                INDEX idx_api_idempotency_expires (expires_at),
                CONSTRAINT fk_api_idempotency_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    },
    'down' => function (\PDO $db): void {
        $db->exec('DROP TABLE IF EXISTS api_idempotency_keys');
    },
];
