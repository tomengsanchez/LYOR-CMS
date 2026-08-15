<?php
/**
 * Migration 017: Content revisions + URL redirects.
 */
return [
    'name' => 'migration_017_revisions_redirects',
    'up' => function (\PDO $db): void {
        $db->exec("
            CREATE TABLE IF NOT EXISTS cms_content_revisions (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                entity_type VARCHAR(16) NOT NULL,
                entity_id INT UNSIGNED NOT NULL,
                revision_no INT UNSIGNED NOT NULL,
                snapshot_json MEDIUMTEXT NOT NULL,
                note VARCHAR(255) NULL DEFAULT NULL,
                created_by INT UNSIGNED NULL DEFAULT NULL,
                created_at DATETIME NOT NULL,
                UNIQUE KEY uq_cms_rev_entity_no (entity_type, entity_id, revision_no),
                KEY idx_cms_rev_entity (entity_type, entity_id, created_at),
                KEY idx_cms_rev_created_by (created_by)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        $db->exec("
            CREATE TABLE IF NOT EXISTS cms_redirects (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                from_path VARCHAR(500) NOT NULL,
                to_url VARCHAR(1000) NOT NULL,
                status_code SMALLINT UNSIGNED NOT NULL DEFAULT 301,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                hit_count INT UNSIGNED NOT NULL DEFAULT 0,
                note VARCHAR(255) NULL DEFAULT NULL,
                created_by INT UNSIGNED NULL DEFAULT NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                UNIQUE KEY uq_cms_redirect_from (from_path(191)),
                KEY idx_cms_redirect_active (is_active)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    },
    'down' => function (\PDO $db): void {
        $db->exec('DROP TABLE IF EXISTS cms_redirects');
        $db->exec('DROP TABLE IF EXISTS cms_content_revisions');
    },
];
