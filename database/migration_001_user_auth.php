<?php
/**
 * Migration 001: User auth extensions (sessions, password policy, API tokens, 2FA)
 */
return [
    'name' => 'migration_001_user_auth',
    'up' => function (\PDO $db): void {
        $db->exec("
            ALTER TABLE users
            ADD COLUMN password_changed_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP AFTER password_hash
        ");

        $db->exec("
            CREATE TABLE IF NOT EXISTS user_password_history (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                password_hash VARCHAR(255) NOT NULL,
                changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                INDEX idx_user_password_history_user (user_id, changed_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $db->exec("
            CREATE TABLE IF NOT EXISTS user_sessions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                session_id VARCHAR(128) NOT NULL,
                user_agent VARCHAR(500) DEFAULT NULL,
                ip_address VARCHAR(45) DEFAULT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                last_activity_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                revoked_at DATETIME NULL DEFAULT NULL,
                INDEX idx_user_session (user_id, session_id),
                INDEX idx_user_revoked (user_id, revoked_at, last_activity_at),
                CONSTRAINT fk_user_sessions_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $db->exec("
            CREATE TABLE IF NOT EXISTS api_tokens (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                token_hash VARCHAR(64) NOT NULL,
                expires_at DATETIME NOT NULL,
                last_used_at DATETIME NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uk_token_hash (token_hash),
                INDEX idx_user (user_id),
                INDEX idx_expires (expires_at),
                INDEX idx_api_tokens_last_used (last_used_at),
                CONSTRAINT fk_api_tokens_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

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
        $db->exec('DROP TABLE IF EXISTS api_tokens');
        $db->exec('DROP TABLE IF EXISTS user_sessions');
        $db->exec('DROP TABLE IF EXISTS user_password_history');
        $db->exec('ALTER TABLE users DROP COLUMN password_changed_at');
    },
];
