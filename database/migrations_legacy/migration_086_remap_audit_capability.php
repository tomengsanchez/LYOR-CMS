<?php
/**
 * Migration 086: Remap Audit capabilities (System guide for GRM / language / progress-level remap).
 */
return [
    'name' => 'migration_086_remap_audit_capability',
    'up' => function (\PDO $db): void {
        $ins = $db->prepare('INSERT IGNORE INTO role_capabilities (role_id, capability) VALUES (?, ?)');
        $admin = $db->query("SELECT id FROM roles WHERE name = 'Administrator' LIMIT 1")->fetchColumn();
        if ($admin) {
            foreach (['view_remap_audit', 'run_remap_audit'] as $cap) {
                $ins->execute([(int) $admin, $cap]);
            }
        }
    },
    'down' => function (\PDO $db): void {
        $db->exec("DELETE FROM role_capabilities WHERE capability IN (
            'view_remap_audit', 'run_remap_audit'
        )");
    },
];
