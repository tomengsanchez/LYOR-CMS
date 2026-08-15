<?php
/**
 * Migration 022: Media source_url + caption for external/free images (no re-upload required).
 * MySQL and MariaDB compatible.
 */
return [
    'name' => 'migration_022_media_source_caption',
    'up' => function (\PDO $db): void {
        $add = static function (\PDO $db, string $column, string $ddl): void {
            $exists = $db->query('SHOW COLUMNS FROM cms_media LIKE ' . $db->quote($column))->fetchAll();
            if (!$exists) {
                $db->exec('ALTER TABLE cms_media ADD COLUMN ' . $ddl);
            }
        };
        $add($db, 'source_url', 'source_url VARCHAR(500) NULL DEFAULT NULL AFTER alt_text');
        $add($db, 'caption', 'caption VARCHAR(1000) NULL DEFAULT NULL AFTER source_url');
    },
    'down' => function (\PDO $db): void {
        foreach (['caption', 'source_url'] as $column) {
            $exists = $db->query('SHOW COLUMNS FROM cms_media LIKE ' . $db->quote($column))->fetchAll();
            if ($exists) {
                $db->exec('ALTER TABLE cms_media DROP COLUMN ' . $column);
            }
        }
    },
];
