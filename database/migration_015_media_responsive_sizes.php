<?php
/**
 * Migration 015: Responsive image size variants (WordPress-style).
 */
return [
    'name' => 'migration_015_media_responsive_sizes',
    'up' => function (\PDO $db): void {
        $db->exec("
            CREATE TABLE IF NOT EXISTS cms_media_sizes (
                id INT AUTO_INCREMENT PRIMARY KEY,
                media_id INT NOT NULL,
                size_name VARCHAR(50) NOT NULL,
                file_path VARCHAR(500) NOT NULL,
                mime_type VARCHAR(100) NOT NULL,
                width INT UNSIGNED NOT NULL,
                height INT UNSIGNED NOT NULL,
                file_size INT UNSIGNED NOT NULL DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uq_cms_media_sizes_media_name (media_id, size_name),
                INDEX idx_cms_media_sizes_media (media_id),
                CONSTRAINT fk_cms_media_sizes_media
                    FOREIGN KEY (media_id) REFERENCES cms_media(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    },
    'down' => function (\PDO $db): void {
        $db->exec('DROP TABLE IF EXISTS cms_media_sizes');
    },
];
