<?php
/**
 * Migration 084: Socio Economic Survey (SES) zip import, current sections, version snapshots, capabilities.
 */
return [
    'name' => 'migration_084_socio_economic',
    'up' => function (\PDO $db): void {
        $db->exec("
            CREATE TABLE IF NOT EXISTS socio_import_batches (
                id INT AUTO_INCREMENT PRIMARY KEY,
                uploaded_by INT NULL,
                original_filename VARCHAR(255) NOT NULL,
                stored_path VARCHAR(500) NULL,
                status VARCHAR(32) NOT NULL DEFAULT 'completed',
                file_count INT NOT NULL DEFAULT 0,
                csv_filenames TEXT NULL,
                summary_json LONGTEXT NULL,
                profiles_affected INT NOT NULL DEFAULT 0,
                sections_created INT NOT NULL DEFAULT 0,
                sections_updated INT NOT NULL DEFAULT 0,
                profiles_with_new_sections INT NOT NULL DEFAULT 0,
                profiles_with_updated_sections INT NOT NULL DEFAULT 0,
                rows_skipped_empty_control INT NOT NULL DEFAULT 0,
                rows_unmatched INT NOT NULL DEFAULT 0,
                error_message TEXT NULL,
                created_at DATETIME NOT NULL,
                INDEX idx_socio_batches_created (created_at),
                INDEX idx_socio_batches_uploaded_by (uploaded_by)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $db->exec("
            CREATE TABLE IF NOT EXISTS socio_import_batch_projects (
                id INT AUTO_INCREMENT PRIMARY KEY,
                batch_id INT NOT NULL,
                project_id INT NOT NULL,
                project_name VARCHAR(255) NOT NULL DEFAULT '',
                profiles_affected INT NOT NULL DEFAULT 0,
                sections_created INT NOT NULL DEFAULT 0,
                sections_updated INT NOT NULL DEFAULT 0,
                CONSTRAINT fk_socio_batch_projects_batch
                    FOREIGN KEY (batch_id) REFERENCES socio_import_batches(id) ON DELETE CASCADE,
                INDEX idx_socio_batch_projects_batch (batch_id),
                INDEX idx_socio_batch_projects_project (project_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $db->exec("
            CREATE TABLE IF NOT EXISTS profile_socio_sections (
                id INT AUTO_INCREMENT PRIMARY KEY,
                profile_id INT NOT NULL,
                section_key VARCHAR(255) NOT NULL,
                source_filename VARCHAR(255) NOT NULL,
                rows_json LONGTEXT NOT NULL,
                batch_id INT NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                UNIQUE KEY uq_profile_socio_section (profile_id, section_key),
                CONSTRAINT fk_profile_socio_sections_profile
                    FOREIGN KEY (profile_id) REFERENCES profiles(id) ON DELETE CASCADE,
                INDEX idx_profile_socio_sections_batch (batch_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $db->exec("
            CREATE TABLE IF NOT EXISTS profile_socio_versions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                profile_id INT NOT NULL,
                batch_id INT NOT NULL,
                version_no INT NOT NULL,
                effect_summary_json LONGTEXT NULL,
                created_at DATETIME NOT NULL,
                UNIQUE KEY uq_profile_socio_version_no (profile_id, version_no),
                UNIQUE KEY uq_profile_socio_version_batch (profile_id, batch_id),
                CONSTRAINT fk_profile_socio_versions_profile
                    FOREIGN KEY (profile_id) REFERENCES profiles(id) ON DELETE CASCADE,
                CONSTRAINT fk_profile_socio_versions_batch
                    FOREIGN KEY (batch_id) REFERENCES socio_import_batches(id) ON DELETE CASCADE,
                INDEX idx_profile_socio_versions_profile (profile_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $db->exec("
            CREATE TABLE IF NOT EXISTS profile_socio_version_sections (
                id INT AUTO_INCREMENT PRIMARY KEY,
                version_id INT NOT NULL,
                section_key VARCHAR(255) NOT NULL,
                source_filename VARCHAR(255) NOT NULL,
                rows_json LONGTEXT NOT NULL,
                UNIQUE KEY uq_socio_version_section (version_id, section_key),
                CONSTRAINT fk_socio_version_sections_version
                    FOREIGN KEY (version_id) REFERENCES profile_socio_versions(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $ins = $db->prepare('INSERT IGNORE INTO role_capabilities (role_id, capability) VALUES (?, ?)');
        $admin = $db->query("SELECT id FROM roles WHERE name = 'Administrator' LIMIT 1")->fetchColumn();
        if ($admin) {
            foreach (['import_socio_economic', 'view_socio_economic_audit', 'view_socio_economic'] as $cap) {
                $ins->execute([(int) $admin, $cap]);
            }
        }
    },
    'down' => function (\PDO $db): void {
        $db->exec('DROP TABLE IF EXISTS profile_socio_version_sections');
        $db->exec('DROP TABLE IF EXISTS profile_socio_versions');
        $db->exec('DROP TABLE IF EXISTS profile_socio_sections');
        $db->exec('DROP TABLE IF EXISTS socio_import_batch_projects');
        $db->exec('DROP TABLE IF EXISTS socio_import_batches');
        $db->exec("DELETE FROM role_capabilities WHERE capability IN (
            'import_socio_economic', 'view_socio_economic_audit', 'view_socio_economic'
        )");
    },
];
