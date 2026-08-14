<?php
/**
 * Migration 056: API 2FA challenges
 *
 * Stores short-lived OTP challenges issued by POST /api/auth/login when
 * email 2FA is enabled. Verified via POST /api/auth/2fa/verify which then
 * issues an api_tokens row. challenge_id is the client-facing handle;
 * code_hash is sha256 of the 6-digit code (raw code is emailed only).
 */
return [
    'name' => 'migration_056_api_2fa_challenges',
    'up' => function (\PDO $db): void {
        $db->exec("
            CREATE TABLE IF NOT EXISTS api_2fa_challenges (
                id INT AUTO_INCREMENT PRIMARY KEY,
                challenge_id VARCHAR(64) NOT NULL,
                user_id INT NOT NULL,
                code_hash VARCHAR(64) NOT NULL,
                expires_at DATETIME NOT NULL,
                attempts INT NOT NULL DEFAULT 0,
                max_attempts INT NOT NULL DEFAULT 5,
                consumed_at DATETIME NULL,
                ip_address VARCHAR(45) NULL,
                user_agent VARCHAR(255) NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uk_api_2fa_challenge_id (challenge_id),
                INDEX idx_api_2fa_user (user_id),
                INDEX idx_api_2fa_expires (expires_at),
                CONSTRAINT fk_api_2fa_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    },
    'down' => function (\PDO $db): void {
        $db->exec('DROP TABLE IF EXISTS api_2fa_challenges');
    },
];
