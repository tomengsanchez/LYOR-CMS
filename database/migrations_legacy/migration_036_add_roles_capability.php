<?php
/**
 * Migration 036: Grant add_roles capability to Administrator role.
 */
return [
    'name' => 'migration_036_add_roles_capability',
    'up' => function (\PDO $db): void {
        $stmt = $db->query("SELECT id FROM roles WHERE name = 'Administrator' LIMIT 1");
        $adminRoleId = $stmt ? (int) $stmt->fetchColumn() : 0;
        if ($adminRoleId <= 0) {
            return;
        }
        $ins = $db->prepare('INSERT IGNORE INTO role_capabilities (role_id, capability) VALUES (?, ?)');
        $ins->execute([$adminRoleId, 'add_roles']);
    },
    'down' => function (\PDO $db): void {
        $stmt = $db->query("SELECT id FROM roles WHERE name = 'Administrator' LIMIT 1");
        $adminRoleId = $stmt ? (int) $stmt->fetchColumn() : 0;
        if ($adminRoleId <= 0) {
            return;
        }
        $del = $db->prepare('DELETE FROM role_capabilities WHERE role_id = ? AND capability = ?');
        $del->execute([$adminRoleId, 'add_roles']);
    },
];
