<?php
/**
 * Migration 020: Hide public Admin login links by default.
 */
return [
    'name' => 'migration_020_hide_public_admin_link',
    'up' => function (\PDO $db): void {
        $db->prepare("
            INSERT INTO app_settings (setting_key, setting_value)
            VALUES ('pub_theme_show_admin_link', '0')
            ON DUPLICATE KEY UPDATE setting_value = '0'
        ")->execute();
    },
    'down' => function (\PDO $db): void {
        $db->prepare("
            UPDATE app_settings SET setting_value = '1' WHERE setting_key = 'pub_theme_show_admin_link'
        ")->execute();
    },
];
