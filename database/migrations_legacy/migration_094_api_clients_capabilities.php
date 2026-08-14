<?php
/**
 * Migration 094: API Clients module capabilities (view/manage).
 * Grants to Administrator and any role that already has manage_security_settings.
 */
return [
    'name' => 'migration_094_api_clients_capabilities',
    'up' => function (\PDO $db): void {
        $caps = ['view_api_clients', 'manage_api_clients'];
        $ins = $db->prepare('INSERT IGNORE INTO role_capabilities (role_id, capability) VALUES (?, ?)');

        $adminId = (int) ($db->query("SELECT id FROM roles WHERE name = 'Administrator' LIMIT 1")->fetchColumn() ?: 0);
        if ($adminId > 0) {
            foreach ($caps as $cap) {
                $ins->execute([$adminId, $cap]);
            }
        }

        // Continuity: roles that managed security settings can manage API clients.
        $roleIds = $db->query("
            SELECT DISTINCT role_id FROM role_capabilities
            WHERE capability IN ('manage_security_settings', 'view_security_settings')
        ")->fetchAll(\PDO::FETCH_COLUMN);
        foreach ($roleIds as $rid) {
            $rid = (int) $rid;
            if ($rid <= 0) {
                continue;
            }
            $ins->execute([$rid, 'view_api_clients']);
            $hasManage = $db->prepare('SELECT 1 FROM role_capabilities WHERE role_id = ? AND capability = ? LIMIT 1');
            $hasManage->execute([$rid, 'manage_security_settings']);
            if ($hasManage->fetchColumn()) {
                $ins->execute([$rid, 'manage_api_clients']);
            }
        }
    },
    'down' => function (\PDO $db): void {
        $db->exec("DELETE FROM role_capabilities WHERE capability IN ('view_api_clients', 'manage_api_clients')");
    },
];
