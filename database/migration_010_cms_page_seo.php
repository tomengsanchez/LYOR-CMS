<?php
/**
 * Migration 010: Page SEO, featured image, LLM summary, robots.
 */
return [
    'name' => 'migration_010_cms_page_seo',
    'up' => function (\PDO $db): void {
        $db->exec("
            ALTER TABLE cms_pages
                ADD COLUMN featured_image_id INT NULL AFTER meta_description,
                ADD COLUMN llm_summary TEXT NULL AFTER featured_image_id,
                ADD COLUMN robots_noindex TINYINT(1) NOT NULL DEFAULT 0 AFTER llm_summary,
                ADD INDEX idx_cms_pages_featured (featured_image_id),
                ADD CONSTRAINT fk_cms_pages_featured_image
                    FOREIGN KEY (featured_image_id) REFERENCES cms_media(id) ON DELETE SET NULL
        ");
    },
    'down' => function (\PDO $db): void {
        $db->exec('ALTER TABLE cms_pages DROP FOREIGN KEY fk_cms_pages_featured_image');
        $db->exec('ALTER TABLE cms_pages DROP COLUMN featured_image_id, DROP COLUMN llm_summary, DROP COLUMN robots_noindex');
    },
];
