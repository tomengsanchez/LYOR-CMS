<?php
/**
 * Migration 019: Default public content width is full (1320px).
 * Updates stored theme width from the former narrow default only.
 */
return [
    'name' => 'migration_019_content_width_full_default',
    'up' => function (\PDO $db): void {
        $stmt = $db->prepare("SELECT setting_value FROM app_settings WHERE setting_key = 'pub_theme_width' LIMIT 1");
        $stmt->execute();
        $current = $stmt->fetchColumn();
        if ($current === false || $current === null || $current === '' || $current === 'narrow') {
            $up = $db->prepare("
                INSERT INTO app_settings (setting_key, setting_value)
                VALUES ('pub_theme_width', 'full')
                ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
            ");
            $up->execute();
        }
    },
    'down' => function (\PDO $db): void {
        $stmt = $db->prepare("SELECT setting_value FROM app_settings WHERE setting_key = 'pub_theme_width' LIMIT 1");
        $stmt->execute();
        $current = $stmt->fetchColumn();
        if ($current === 'full') {
            $db->prepare("
                UPDATE app_settings SET setting_value = 'narrow' WHERE setting_key = 'pub_theme_width'
            ")->execute();
        }
    },
];
