<?php
/**
 * Migration 014: Post SEO fields (parity with pages) for search and LLM discovery.
 */
return [
    'name' => 'migration_014_post_seo_llm',
    'up' => function (\PDO $db): void {
        $add = static function (\PDO $db, string $column, string $ddl): void {
            $exists = $db->query("SHOW COLUMNS FROM cms_posts LIKE " . $db->quote($column))->fetchAll();
            if (!$exists) {
                $db->exec("ALTER TABLE cms_posts ADD COLUMN {$ddl}");
            }
        };
        $add($db, 'meta_title', 'meta_title VARCHAR(255) NULL DEFAULT NULL AFTER excerpt');
        $add($db, 'meta_description', 'meta_description VARCHAR(500) NULL DEFAULT NULL AFTER meta_title');
        $add($db, 'llm_summary', 'llm_summary TEXT NULL DEFAULT NULL AFTER featured_image_id');
        $add($db, 'robots_noindex', 'robots_noindex TINYINT(1) NOT NULL DEFAULT 0 AFTER llm_summary');
    },
    'down' => function (\PDO $db): void {
        foreach (['robots_noindex', 'llm_summary', 'meta_description', 'meta_title'] as $column) {
            $exists = $db->query("SHOW COLUMNS FROM cms_posts LIKE " . $db->quote($column))->fetchAll();
            if ($exists) {
                $db->exec("ALTER TABLE cms_posts DROP COLUMN {$column}");
            }
        }
    },
];
