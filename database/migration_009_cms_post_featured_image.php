<?php
/**
 * Migration 009: Post featured images + media dimensions for social sharing.
 */
return [
    'name' => 'migration_009_cms_post_featured_image',
    'up' => function (\PDO $db): void {
        $db->exec("
            ALTER TABLE cms_media
                ADD COLUMN width INT UNSIGNED NULL AFTER file_size,
                ADD COLUMN height INT UNSIGNED NULL AFTER width
        ");

        $db->exec("
            ALTER TABLE cms_posts
                ADD COLUMN featured_image_id INT NULL AFTER category_id,
                ADD INDEX idx_cms_posts_featured (featured_image_id),
                ADD CONSTRAINT fk_cms_posts_featured_image
                    FOREIGN KEY (featured_image_id) REFERENCES cms_media(id) ON DELETE SET NULL
        ");
    },
    'down' => function (\PDO $db): void {
        $db->exec('ALTER TABLE cms_posts DROP FOREIGN KEY fk_cms_posts_featured_image');
        $db->exec('ALTER TABLE cms_posts DROP COLUMN featured_image_id');
        $db->exec('ALTER TABLE cms_media DROP COLUMN width, DROP COLUMN height');
    },
];
