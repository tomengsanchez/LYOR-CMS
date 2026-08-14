<?php
/**
 * Migration 034: Grant operational settings capabilities to Administrator role.
 */
return [
    'name' => 'migration_034_operational_capabilities',
    'up' => function (\PDO $db): void {
        $stmt = $db->query("SELECT id FROM roles WHERE name = 'Administrator' LIMIT 1");
        $adminRoleId = $stmt ? (int) $stmt->fetchColumn() : 0;
        if ($adminRoleId <= 0) {
            return;
        }

        $caps = [
            'view_operational_settings',
            'manage_operational_settings',
        ];
        $ins = $db->prepare('INSERT IGNORE INTO role_capabilities (role_id, capability) VALUES (?, ?)');
        foreach ($caps as $cap) {
            $ins->execute([$adminRoleId, $cap]);
        }
    },
    'down' => function (\PDO $db): void {
        $stmt = $db->query("SELECT id FROM roles WHERE name = 'Administrator' LIMIT 1");
        $adminRoleId = $stmt ? (int) $stmt->fetchColumn() : 0;
        if ($adminRoleId <= 0) {
            return;
        }
        $del = $db->prepare('DELETE FROM role_capabilities WHERE role_id = ? AND capability = ?');
        $del->execute([$adminRoleId, 'view_operational_settings']);
        $del->execute([$adminRoleId, 'manage_operational_settings']);
    },
];
