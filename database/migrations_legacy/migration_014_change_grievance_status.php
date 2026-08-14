<?php
/**
 * Migration 014: Add change_grievance_status capability and assign to Administrator.
 *
 * Allows roles to be configured so that changing grievance status is separate from edit_grievance.
 */
return [
    'name' => 'migration_014_change_grievance_status',
    'up' => function (\PDO $db): void {
        $adminId = $db->query("SELECT id FROM roles WHERE name = 'Administrator' LIMIT 1")->fetchColumn();
        if ($adminId) {
            $db->prepare("INSERT IGNORE INTO role_capabilities (role_id, capability) VALUES (?, 'change_grievance_status')")
                ->execute([$adminId]);
        }
    },
    'down' => function (\PDO $db): void {
        $db->prepare('DELETE FROM role_capabilities WHERE capability = ?')->execute(['change_grievance_status']);
    },
];
