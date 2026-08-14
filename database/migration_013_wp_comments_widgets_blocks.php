<?php
/**
 * Migration 013: Comments, widgets, block builder JSON on pages/posts.
 */
return [
    'name' => 'migration_013_wp_comments_widgets_blocks',
    'up' => function (\PDO $db): void {
        $db->exec("
            CREATE TABLE IF NOT EXISTS cms_comments (
                id INT AUTO_INCREMENT PRIMARY KEY,
                post_id INT NOT NULL,
                parent_id INT NULL DEFAULT NULL,
                author_name VARCHAR(100) NOT NULL,
                author_email VARCHAR(190) NOT NULL,
                author_url VARCHAR(500) NULL DEFAULT NULL,
                content TEXT NOT NULL,
                status VARCHAR(20) NOT NULL DEFAULT 'pending',
                ip_address VARCHAR(45) NULL DEFAULT NULL,
                user_agent VARCHAR(255) NULL DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_cms_comments_post (post_id),
                INDEX idx_cms_comments_status (status),
                CONSTRAINT fk_cms_comments_post FOREIGN KEY (post_id) REFERENCES cms_posts(id) ON DELETE CASCADE,
                CONSTRAINT fk_cms_comments_parent FOREIGN KEY (parent_id) REFERENCES cms_comments(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $db->exec("
            CREATE TABLE IF NOT EXISTS cms_widgets (
                id INT AUTO_INCREMENT PRIMARY KEY,
                area VARCHAR(50) NOT NULL,
                widget_type VARCHAR(50) NOT NULL,
                title VARCHAR(150) NULL DEFAULT NULL,
                config_json TEXT NULL,
                sort_order INT NOT NULL DEFAULT 0,
                is_enabled TINYINT(1) NOT NULL DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_cms_widgets_area (area, sort_order)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $pageCol = $db->query("SHOW COLUMNS FROM cms_pages LIKE 'blocks_json'")->fetchAll();
        if (!$pageCol) {
            $db->exec('ALTER TABLE cms_pages ADD COLUMN blocks_json MEDIUMTEXT NULL DEFAULT NULL AFTER body');
        }
        $postCol = $db->query("SHOW COLUMNS FROM cms_posts LIKE 'blocks_json'")->fetchAll();
        if (!$postCol) {
            $db->exec('ALTER TABLE cms_posts ADD COLUMN blocks_json MEDIUMTEXT NULL DEFAULT NULL AFTER body');
        }
    },
    'down' => function (\PDO $db): void {
        $db->exec('DROP TABLE IF EXISTS cms_widgets');
        $db->exec('DROP TABLE IF EXISTS cms_comments');
        $db->exec('ALTER TABLE cms_pages DROP COLUMN blocks_json');
        $db->exec('ALTER TABLE cms_posts DROP COLUMN blocks_json');
    },
];
