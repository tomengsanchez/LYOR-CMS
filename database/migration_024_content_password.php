<?php
/**
 * Migration 024: Optional password on published pages and posts.
 * MySQL and MariaDB compatible (additive nullable VARCHAR).
 */
return [
    'name' => 'migration_024_content_password',
    'up' => function (\PDO $db): void {
        $pageCol = $db->query("SHOW COLUMNS FROM cms_pages LIKE 'password_hash'")->fetchAll();
        if (!$pageCol) {
            $db->exec('ALTER TABLE cms_pages ADD COLUMN password_hash VARCHAR(255) NULL AFTER status');
        }
        $postCol = $db->query("SHOW COLUMNS FROM cms_posts LIKE 'password_hash'")->fetchAll();
        if (!$postCol) {
            $db->exec('ALTER TABLE cms_posts ADD COLUMN password_hash VARCHAR(255) NULL AFTER is_sticky');
        }
    },
    'down' => function (\PDO $db): void {
        $pageCol = $db->query("SHOW COLUMNS FROM cms_pages LIKE 'password_hash'")->fetchAll();
        if ($pageCol) {
            $db->exec('ALTER TABLE cms_pages DROP COLUMN password_hash');
        }
        $postCol = $db->query("SHOW COLUMNS FROM cms_posts LIKE 'password_hash'")->fetchAll();
        if ($postCol) {
            $db->exec('ALTER TABLE cms_posts DROP COLUMN password_hash');
        }
    },
];
