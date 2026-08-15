<?php
/**
 * Migration 021: AI citation / BLUF snippet and FAQ pairs for pages and posts.
 * MySQL and MariaDB compatible (additive nullable columns).
 */
return [
    'name' => 'migration_021_ai_citation_bluf',
    'up' => function (\PDO $db): void {
        $add = static function (\PDO $db, string $table, string $column, string $ddl): void {
            $exists = $db->query('SHOW COLUMNS FROM ' . $table . ' LIKE ' . $db->quote($column))->fetchAll();
            if (!$exists) {
                $db->exec('ALTER TABLE ' . $table . ' ADD COLUMN ' . $ddl);
            }
        };
        $add($db, 'cms_posts', 'citation_snippet', 'citation_snippet VARCHAR(500) NULL DEFAULT NULL AFTER llm_summary');
        $add($db, 'cms_posts', 'faq_json', 'faq_json MEDIUMTEXT NULL DEFAULT NULL AFTER citation_snippet');
        $add($db, 'cms_pages', 'citation_snippet', 'citation_snippet VARCHAR(500) NULL DEFAULT NULL AFTER llm_summary');
        $add($db, 'cms_pages', 'faq_json', 'faq_json MEDIUMTEXT NULL DEFAULT NULL AFTER citation_snippet');
    },
    'down' => function (\PDO $db): void {
        foreach (['cms_posts', 'cms_pages'] as $table) {
            foreach (['faq_json', 'citation_snippet'] as $column) {
                $exists = $db->query('SHOW COLUMNS FROM ' . $table . ' LIKE ' . $db->quote($column))->fetchAll();
                if ($exists) {
                    $db->exec('ALTER TABLE ' . $table . ' DROP COLUMN ' . $column);
                }
            }
        }
    },
];
