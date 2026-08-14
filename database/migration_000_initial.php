<?php
/**
 * Migration 000: Initial base schema (roles, users, app_settings, role_capabilities)
 */
return [
    'name' => 'migration_000_initial',
    'up' => function (\PDO $db): void {
        $db->exec("
            CREATE TABLE IF NOT EXISTS roles (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(50) NOT NULL UNIQUE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        $db->exec("
            CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(100) NOT NULL UNIQUE,
                email VARCHAR(255) DEFAULT NULL,
                display_name VARCHAR(255) NULL DEFAULT NULL,
                password_hash VARCHAR(255) NOT NULL,
                role_id INT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (role_id) REFERENCES roles(id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        $db->exec("
            CREATE TABLE IF NOT EXISTS app_settings (
                setting_key VARCHAR(100) PRIMARY KEY,
                setting_value TEXT,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        $db->exec("
            CREATE TABLE IF NOT EXISTS role_capabilities (
                id INT AUTO_INCREMENT PRIMARY KEY,
                role_id INT NOT NULL,
                capability VARCHAR(100) NOT NULL,
                FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
                UNIQUE KEY uk_role_cap (role_id, capability)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $db->exec("INSERT IGNORE INTO roles (name) VALUES ('Administrator'), ('Editor'), ('Viewer')");
        $adminRoleId = $db->query("SELECT id FROM roles WHERE name = 'Administrator' LIMIT 1")->fetchColumn();
        if ($adminRoleId) {
            $db->prepare("INSERT INTO users (username, password_hash, role_id) VALUES ('admin', ?, ?) ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash)")
                ->execute(['$2y$10$rBrZEQ7IKxJRkXZ2fzRdpO4MHdYqOd.wERK/VbgIFLxfx6JEowMGS', $adminRoleId]);
        }

        $adminId = $db->query("SELECT id FROM roles WHERE name = 'Administrator'")->fetchColumn();
        $editorId = $db->query("SELECT id FROM roles WHERE name = 'Editor'")->fetchColumn();
        $viewerId = $db->query("SELECT id FROM roles WHERE name = 'Viewer'")->fetchColumn();

        $adminCaps = [
            'view_pages', 'add_pages', 'edit_pages', 'delete_pages',
            'view_posts', 'add_posts', 'edit_posts', 'delete_posts',
            'view_categories', 'manage_categories',
            'view_media', 'upload_media', 'delete_media',
            'view_settings', 'manage_settings',
            'view_email_settings', 'manage_email_settings',
            'view_security_settings', 'manage_security_settings',
            'view_users', 'add_users', 'edit_users', 'delete_users', 'export_users',
            'view_roles', 'add_roles', 'edit_roles',
            'view_audit_trail',
            'manage_backup_restore',
        ];
        $editorCaps = [
            'view_pages', 'add_pages', 'edit_pages',
            'view_posts', 'add_posts', 'edit_posts',
            'view_categories', 'manage_categories',
            'view_media', 'upload_media',
        ];
        $viewerCaps = [
            'view_pages', 'view_posts', 'view_categories', 'view_media',
        ];

        $ins = $db->prepare('INSERT IGNORE INTO role_capabilities (role_id, capability) VALUES (?, ?)');
        if ($adminId) {
            foreach ($adminCaps as $c) {
                $ins->execute([$adminId, $c]);
            }
        }
        if ($editorId) {
            foreach ($editorCaps as $c) {
                $ins->execute([$editorId, $c]);
            }
        }
        if ($viewerId) {
            foreach ($viewerCaps as $c) {
                $ins->execute([$viewerId, $c]);
            }
        }

        $db->prepare('INSERT IGNORE INTO app_settings (setting_key, setting_value) VALUES (?, ?)')
            ->execute(['app_name', 'Simple CMS']);
    },
    'down' => null,
];
