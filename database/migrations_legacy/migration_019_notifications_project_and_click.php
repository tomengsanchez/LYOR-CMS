<?php
/**
 * Migration 019: Add project and click metadata to notifications.
 */
return [
    'name' => 'migration_019_notifications_project_and_click',
    'up' => function (\PDO $db): void {
        $db->exec("ALTER TABLE notifications ADD COLUMN project_id INT NULL AFTER related_id");
        $db->exec("ALTER TABLE notifications ADD COLUMN clicked_at DATETIME NULL AFTER created_at");
        $db->exec("ALTER TABLE notifications ADD INDEX idx_user_project (user_id, project_id, created_at)");
    },
    'down' => function (\PDO $db): void {
        $db->exec("ALTER TABLE notifications DROP INDEX idx_user_project");
        $db->exec("ALTER TABLE notifications DROP COLUMN clicked_at");
        $db->exec("ALTER TABLE notifications DROP COLUMN project_id");
    },
];

