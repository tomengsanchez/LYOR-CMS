<?php
/**
 * Migration 089: SES → RAP field mapping (definitions + column maps) and capabilities.
 */
return [
    'name' => 'migration_089_ses_rap_mapping',
    'up' => function (\PDO $db): void {
        $db->exec("
            CREATE TABLE IF NOT EXISTS rap_field_definitions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                field_key VARCHAR(64) NOT NULL,
                label VARCHAR(255) NOT NULL,
                category VARCHAR(64) NOT NULL DEFAULT 'general',
                value_mode VARCHAR(32) NOT NULL DEFAULT 'first',
                sort_order INT NOT NULL DEFAULT 0,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                created_at DATETIME NOT NULL,
                UNIQUE KEY uq_rap_field_key (field_key),
                INDEX idx_rap_fields_sort (sort_order, id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $db->exec("
            CREATE TABLE IF NOT EXISTS ses_rap_column_maps (
                id INT AUTO_INCREMENT PRIMARY KEY,
                rap_field_id INT NOT NULL,
                section_key VARCHAR(255) NOT NULL DEFAULT '',
                ses_column VARCHAR(255) NOT NULL,
                sort_order INT NOT NULL DEFAULT 0,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                UNIQUE KEY uq_ses_rap_map (rap_field_id, section_key, ses_column),
                CONSTRAINT fk_ses_rap_maps_field
                    FOREIGN KEY (rap_field_id) REFERENCES rap_field_definitions(id) ON DELETE CASCADE,
                INDEX idx_ses_rap_maps_section (section_key),
                INDEX idx_ses_rap_maps_column (ses_column)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $now = date('Y-m-d H:i:s');
        $ins = $db->prepare('
            INSERT IGNORE INTO rap_field_definitions
                (field_key, label, category, value_mode, sort_order, is_active, created_at)
            VALUES (?, ?, ?, ?, ?, 1, ?)
        ');
        $seed = [
            ['household_head', 'Household head / respondent', 'household', 'first', 10],
            ['household_size', 'Household size', 'household', 'first', 20],
            ['vulnerable_status', 'Vulnerability status', 'vulnerability', 'list', 30],
            ['livelihood_primary', 'Primary livelihood', 'livelihood', 'first', 40],
            ['monthly_income', 'Monthly income', 'livelihood', 'sum', 50],
            ['ownership_status', 'Land / structure ownership', 'assets', 'first', 60],
            ['land_affected', 'Affected land / lot', 'assets', 'list', 70],
            ['structure_affected', 'Affected structure', 'assets', 'list', 80],
            ['resettlement_preference', 'Resettlement preference', 'preference', 'first', 90],
            ['ses_barangay', 'SES barangay', 'location', 'first', 100],
            ['ses_municipality', 'SES municipality / city', 'location', 'first', 110],
            ['rap_notes', 'RAP notes / remarks', 'other', 'list', 120],
        ];
        foreach ($seed as $row) {
            $ins->execute([$row[0], $row[1], $row[2], $row[3], $row[4], $now]);
        }

        $capIns = $db->prepare('INSERT IGNORE INTO role_capabilities (role_id, capability) VALUES (?, ?)');
        $admin = $db->query("SELECT id FROM roles WHERE name = 'Administrator' LIMIT 1")->fetchColumn();
        if ($admin) {
            foreach (['view_rap_mapping', 'manage_rap_mapping'] as $cap) {
                $capIns->execute([(int) $admin, $cap]);
            }
        }
    },
    'down' => function (\PDO $db): void {
        $db->exec('DROP TABLE IF EXISTS ses_rap_column_maps');
        $db->exec('DROP TABLE IF EXISTS rap_field_definitions');
        $db->exec("DELETE FROM role_capabilities WHERE capability IN (
            'view_rap_mapping', 'manage_rap_mapping'
        )");
    },
];
