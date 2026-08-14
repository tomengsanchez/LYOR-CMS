<?php
/**
 * Migration 004: CMS pages
 */
return [
    'name' => 'migration_004_cms_pages',
    'up' => function (\PDO $db): void {
        $db->exec("
            CREATE TABLE IF NOT EXISTS cms_pages (
                id INT AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(255) NOT NULL,
                slug VARCHAR(255) NOT NULL,
                body MEDIUMTEXT NULL,
                status VARCHAR(20) NOT NULL DEFAULT 'draft',
                meta_title VARCHAR(255) NULL,
                meta_description VARCHAR(500) NULL,
                author_id INT NULL,
                deleted_at DATETIME NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uk_cms_pages_slug (slug),
                INDEX idx_cms_pages_status (status),
                INDEX idx_cms_pages_deleted (deleted_at),
                CONSTRAINT fk_cms_pages_author FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    },
    'down' => function (\PDO $db): void {
        $db->exec('DROP TABLE IF EXISTS cms_pages');
    },
];
