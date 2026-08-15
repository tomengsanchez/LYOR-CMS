<?php
/**
 * Migration 018: Reusable visual layout templates.
 */
return [
    'name' => 'migration_018_layout_templates',
    'up' => function (\PDO $db): void {
        $db->exec("
            CREATE TABLE IF NOT EXISTS cms_layout_templates (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(120) NOT NULL,
                layout_json MEDIUMTEXT NOT NULL,
                created_by INT UNSIGNED NULL DEFAULT NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                KEY idx_cms_layout_tpl_name (name),
                KEY idx_cms_layout_tpl_created_by (created_by)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    },
    'down' => function (\PDO $db): void {
        $db->exec('DROP TABLE IF EXISTS cms_layout_templates');
    },
];
