<?php
/**
 * Migration 012: WordPress-like features — menus, tags, page hierarchy.
 */
return [
    'name' => 'migration_012_wp_features',
    'up' => function (\PDO $db): void {
        $db->exec("
            ALTER TABLE cms_pages
                ADD COLUMN parent_id INT NULL DEFAULT NULL AFTER content_layout,
                ADD INDEX idx_cms_pages_parent (parent_id),
                ADD CONSTRAINT fk_cms_pages_parent
                    FOREIGN KEY (parent_id) REFERENCES cms_pages(id) ON DELETE SET NULL
        ");

        $db->exec("
            CREATE TABLE IF NOT EXISTS cms_tags (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                slug VARCHAR(100) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uk_cms_tags_slug (slug)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $db->exec("
            CREATE TABLE IF NOT EXISTS cms_post_tags (
                post_id INT NOT NULL,
                tag_id INT NOT NULL,
                PRIMARY KEY (post_id, tag_id),
                CONSTRAINT fk_cms_post_tags_post FOREIGN KEY (post_id) REFERENCES cms_posts(id) ON DELETE CASCADE,
                CONSTRAINT fk_cms_post_tags_tag FOREIGN KEY (tag_id) REFERENCES cms_tags(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $db->exec("
            CREATE TABLE IF NOT EXISTS cms_menus (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                location VARCHAR(50) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uk_cms_menus_location (location)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $db->exec("
            CREATE TABLE IF NOT EXISTS cms_menu_items (
                id INT AUTO_INCREMENT PRIMARY KEY,
                menu_id INT NOT NULL,
                parent_id INT NULL DEFAULT NULL,
                label VARCHAR(150) NOT NULL,
                item_type VARCHAR(20) NOT NULL DEFAULT 'custom',
                object_id INT NULL DEFAULT NULL,
                custom_url VARCHAR(500) NULL DEFAULT NULL,
                sort_order INT NOT NULL DEFAULT 0,
                open_in_new_tab TINYINT(1) NOT NULL DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_cms_menu_items_menu (menu_id),
                INDEX idx_cms_menu_items_sort (menu_id, sort_order),
                CONSTRAINT fk_cms_menu_items_menu FOREIGN KEY (menu_id) REFERENCES cms_menus(id) ON DELETE CASCADE,
                CONSTRAINT fk_cms_menu_items_parent FOREIGN KEY (parent_id) REFERENCES cms_menu_items(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $db->exec("INSERT IGNORE INTO cms_menus (id, name, location) VALUES (1, 'Primary Menu', 'primary')");
        $count = (int) $db->query('SELECT COUNT(*) FROM cms_menu_items WHERE menu_id = 1')->fetchColumn();
        if ($count === 0) {
            $db->exec("
                INSERT INTO cms_menu_items (menu_id, label, item_type, custom_url, sort_order) VALUES
                (1, 'Home', 'home', '/', 10),
                (1, 'Blog', 'blog', '/blog', 20)
            ");
        }
    },
    'down' => function (\PDO $db): void {
        $db->exec('DROP TABLE IF EXISTS cms_menu_items');
        $db->exec('DROP TABLE IF EXISTS cms_menus');
        $db->exec('DROP TABLE IF EXISTS cms_post_tags');
        $db->exec('DROP TABLE IF EXISTS cms_tags');
        $db->exec('ALTER TABLE cms_pages DROP FOREIGN KEY fk_cms_pages_parent');
        $db->exec('ALTER TABLE cms_pages DROP COLUMN parent_id');
    },
];
