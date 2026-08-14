<?php
/**
 * Migration 011: Per-page/post content layout + stored in app_settings via PublicTheme (blog width).
 */
return [
    'name' => 'migration_011_content_layout',
    'up' => function (\PDO $db): void {
        $db->exec("
            ALTER TABLE cms_pages
                ADD COLUMN content_layout VARCHAR(10) NULL DEFAULT NULL AFTER robots_noindex
        ");
        $db->exec("
            ALTER TABLE cms_posts
                ADD COLUMN content_layout VARCHAR(10) NULL DEFAULT NULL AFTER featured_image_id
        ");
    },
    'down' => function (\PDO $db): void {
        $db->exec('ALTER TABLE cms_pages DROP COLUMN content_layout');
        $db->exec('ALTER TABLE cms_posts DROP COLUMN content_layout');
    },
];
