<?php
/**
 * Migration 092: Structure Options Library — Actual Usage and Tagging Status lookups.
 * Soft-delete aware; seeds default tagging statuses (legacy hardcoded list).
 * Grants manage_structure_options to Administrator.
 */
return [
    'name' => 'migration_092_structure_options_library',
    'up' => function (\PDO $db): void {
        $db->exec("
            CREATE TABLE IF NOT EXISTS structure_tagging_statuses (
                id INT AUTO_INCREMENT PRIMARY KEY,
                code VARCHAR(64) NOT NULL,
                name VARCHAR(255) NOT NULL,
                description TEXT NULL,
                sort_order INT NOT NULL DEFAULT 0,
                requires_other_text TINYINT(1) NOT NULL DEFAULT 0,
                requires_refusal_reason TINYINT(1) NOT NULL DEFAULT 0,
                is_deleted TINYINT(1) NOT NULL DEFAULT 0,
                deleted_at DATETIME NULL,
                deleted_by INT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_structure_tagging_statuses_code (code),
                INDEX idx_structure_tagging_statuses_sort (sort_order, name)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $db->exec("
            CREATE TABLE IF NOT EXISTS structure_actual_usages (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                description TEXT NULL,
                sort_order INT NOT NULL DEFAULT 0,
                is_deleted TINYINT(1) NOT NULL DEFAULT 0,
                deleted_at DATETIME NULL,
                deleted_by INT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_structure_actual_usages_sort (sort_order, name)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // Seed default tagging statuses when empty (same as former Structure::taggingStatusOptions).
        $count = (int) $db->query('SELECT COUNT(*) FROM structure_tagging_statuses')->fetchColumn();
        if ($count === 0) {
            $ins = $db->prepare('
                INSERT INTO structure_tagging_statuses
                    (code, name, description, sort_order, requires_other_text, requires_refusal_reason)
                VALUES (?, ?, ?, ?, ?, ?)
            ');
            $defaults = [
                ['tagged', 'Tagged', '', 10, 0, 0],
                ['owner_refused', 'Owner Refused', '', 20, 0, 1],
                ['owner_not_around', 'Owner not Around', '', 30, 0, 0],
                ['vacant', 'No Occupant / Vacant', '', 40, 0, 0],
                ['abandoned', 'Abandoned', '', 50, 0, 0],
                ['under_construction', 'Under Construction', '', 60, 0, 0],
                ['temporary', 'Temporary', '', 70, 0, 0],
                ['exceeded_number_of_visits', 'Exceeded number of Visits', '', 80, 0, 0],
                ['other', 'Other (specify)', '', 90, 1, 0],
            ];
            foreach ($defaults as $row) {
                $ins->execute($row);
            }
        }

        // Promote existing free-text Actual Usage values into the lookup (upgrade path).
        $usageCount = (int) $db->query('SELECT COUNT(*) FROM structure_actual_usages')->fetchColumn();
        if ($usageCount === 0) {
            $hasStructures = (bool) $db->query("SHOW TABLES LIKE 'structures'")->fetchColumn();
            if ($hasStructures) {
                $hasCol = (bool) $db->query("SHOW COLUMNS FROM structures LIKE 'actual_usage'")->fetchColumn();
                if ($hasCol) {
                    $db->exec("
                        INSERT INTO structure_actual_usages (name, description, sort_order)
                        SELECT DISTINCT TRIM(actual_usage), '', 0
                        FROM structures
                        WHERE actual_usage IS NOT NULL
                          AND TRIM(actual_usage) <> ''
                          AND (is_deleted = 0 OR is_deleted IS NULL)
                        ORDER BY TRIM(actual_usage)
                    ");
                }
            }
        }

        $insCap = $db->prepare('INSERT IGNORE INTO role_capabilities (role_id, capability) VALUES (?, ?)');
        $admin = $db->query("SELECT id FROM roles WHERE name = 'Administrator' LIMIT 1")->fetchColumn();
        if ($admin) {
            $insCap->execute([(int) $admin, 'manage_structure_options']);
        }
    },
    'down' => function (\PDO $db): void {
        $db->exec("DELETE FROM role_capabilities WHERE capability = 'manage_structure_options'");
        $db->exec('DROP TABLE IF EXISTS structure_actual_usages');
        $db->exec('DROP TABLE IF EXISTS structure_tagging_statuses');
    },
];
