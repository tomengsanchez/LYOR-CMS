<?php
/**
 * Migration 058: view_contacts capability for roles that can view profiles or users.
 */
return [
    'name' => 'migration_058_view_contacts_capability',
    'up' => function (\PDO $db): void {
        $ins = $db->prepare('INSERT IGNORE INTO role_capabilities (role_id, capability) VALUES (?, ?)');
        $roles = $db->query("
            SELECT DISTINCT role_id FROM role_capabilities
            WHERE capability IN ('view_profiles', 'view_users')
        ")->fetchAll(\PDO::FETCH_COLUMN);
        foreach ($roles as $roleId) {
            $ins->execute([(int) $roleId, 'view_contacts']);
        }
        $admin = $db->query("SELECT id FROM roles WHERE name = 'Administrator' LIMIT 1")->fetchColumn();
        if ($admin) {
            $ins->execute([(int) $admin, 'view_contacts']);
        }
    },
    'down' => function (\PDO $db): void {
        $db->exec("DELETE FROM role_capabilities WHERE capability = 'view_contacts'");
    },
];
