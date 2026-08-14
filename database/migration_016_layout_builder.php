<?php
/**
 * Migration 016: Divi-style visual layout JSON on pages/posts.
 */
return [
    'name' => 'migration_016_layout_builder',
    'up' => function (\PDO $db): void {
        $add = static function (\PDO $db, string $table, string $column, string $ddl): void {
            $exists = $db->query('SHOW COLUMNS FROM ' . $table . ' LIKE ' . $db->quote($column))->fetchAll();
            if (!$exists) {
                $db->exec('ALTER TABLE ' . $table . ' ADD COLUMN ' . $ddl);
            }
        };
        $add($db, 'cms_pages', 'layout_json', 'layout_json MEDIUMTEXT NULL DEFAULT NULL AFTER blocks_json');
        $add($db, 'cms_posts', 'layout_json', 'layout_json MEDIUMTEXT NULL DEFAULT NULL AFTER blocks_json');
    },
    'down' => function (\PDO $db): void {
        foreach (['cms_pages', 'cms_posts'] as $table) {
            $exists = $db->query('SHOW COLUMNS FROM ' . $table . ' LIKE ' . $db->quote('layout_json'))->fetchAll();
            if ($exists) {
                $db->exec('ALTER TABLE ' . $table . ' DROP COLUMN layout_json');
            }
        }
    },
];
