<?php
/**
 * Migration 005: CMS categories and posts
 */
return [
    'name' => 'migration_005_cms_categories_posts',
    'up' => function (\PDO $db): void {
        $db->exec("
            CREATE TABLE IF NOT EXISTS cms_categories (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                slug VARCHAR(100) NOT NULL,
                description VARCHAR(500) NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uk_cms_categories_slug (slug)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $db->exec("
            CREATE TABLE IF NOT EXISTS cms_posts (
                id INT AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(255) NOT NULL,
                slug VARCHAR(255) NOT NULL,
                excerpt TEXT NULL,
                body MEDIUMTEXT NULL,
                category_id INT NULL,
                status VARCHAR(20) NOT NULL DEFAULT 'draft',
                published_at DATETIME NULL,
                author_id INT NULL,
                deleted_at DATETIME NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uk_cms_posts_slug (slug),
                INDEX idx_cms_posts_status (status),
                INDEX idx_cms_posts_category (category_id),
                INDEX idx_cms_posts_published (published_at),
                INDEX idx_cms_posts_deleted (deleted_at),
                CONSTRAINT fk_cms_posts_category FOREIGN KEY (category_id) REFERENCES cms_categories(id) ON DELETE SET NULL,
                CONSTRAINT fk_cms_posts_author FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    },
    'down' => function (\PDO $db): void {
        $db->exec('DROP TABLE IF EXISTS cms_posts');
        $db->exec('DROP TABLE IF EXISTS cms_categories');
    },
];
