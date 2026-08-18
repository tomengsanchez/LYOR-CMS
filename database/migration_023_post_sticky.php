<?php
/**
 * Migration 023: Sticky flag for live posts (listed first on blog / featured widgets).
 * MySQL and MariaDB compatible (additive tinyint + composite index).
 */
return [
    'name' => 'migration_023_post_sticky',
    'up' => function (\PDO $db): void {
        $exists = $db->query("SHOW COLUMNS FROM cms_posts LIKE 'is_sticky'")->fetchAll();
        if (!$exists) {
            $db->exec('ALTER TABLE cms_posts ADD COLUMN is_sticky TINYINT(1) NOT NULL DEFAULT 0 AFTER status');
        }
        $hasIdx = false;
        foreach ($db->query('SHOW INDEX FROM cms_posts')->fetchAll() as $row) {
            if (($row['Key_name'] ?? '') === 'idx_cms_posts_sticky_pub') {
                $hasIdx = true;
                break;
            }
        }
        if (!$hasIdx) {
            $db->exec('ALTER TABLE cms_posts ADD INDEX idx_cms_posts_sticky_pub (is_sticky, published_at)');
        }
    },
    'down' => function (\PDO $db): void {
        $hasIdx = false;
        foreach ($db->query('SHOW INDEX FROM cms_posts')->fetchAll() as $row) {
            if (($row['Key_name'] ?? '') === 'idx_cms_posts_sticky_pub') {
                $hasIdx = true;
                break;
            }
        }
        if ($hasIdx) {
            $db->exec('ALTER TABLE cms_posts DROP INDEX idx_cms_posts_sticky_pub');
        }
        $exists = $db->query("SHOW COLUMNS FROM cms_posts LIKE 'is_sticky'")->fetchAll();
        if ($exists) {
            $db->exec('ALTER TABLE cms_posts DROP COLUMN is_sticky');
        }
    },
];
