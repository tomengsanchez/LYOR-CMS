<?php
/**
 * Migration 017: Enable notification preferences for all users
 *
 * - Sets notify_new_profile, notify_new_grievance, notify_grievance_status_change
 *   to true for every user (existing).
 * - Safe to re-run: uses INSERT ... SELECT ... ON DUPLICATE KEY UPDATE.
 */
return [
    'name' => 'migration_017_notification_defaults_all_users',
    'up' => function (\PDO $db): void {
        $json = json_encode([
            'notify_new_profile' => true,
            'notify_new_grievance' => true,
            'notify_grievance_status_change' => true,
        ]);
        $stmt = $db->prepare("
            INSERT INTO user_dashboard_config (user_id, module, config)
            SELECT id, 'notification_preferences', :cfg
            FROM users
            ON DUPLICATE KEY UPDATE config = VALUES(config)
        ");
        $stmt->execute([':cfg' => $json]);
    },
    'down' => function (\PDO $db): void {
        // Optional rollback: remove notification_preferences for all users
        $db->prepare('DELETE FROM user_dashboard_config WHERE module = ?')
            ->execute(['notification_preferences']);
    },
];

