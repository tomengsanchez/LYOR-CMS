<?php
/**
 * Migration 005: Grievance module tables and options library
 *
 * Creates grievances main table, lookup tables for options library,
 * and assigns manage_grievance_options to Administrator.
 */
return [
    'name' => 'migration_005_grievance',
    'up' => function (\PDO $db): void {
        // Options Library lookup tables
        $db->exec("
            CREATE TABLE IF NOT EXISTS grievance_vulnerabilities (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                description TEXT,
                sort_order INT NOT NULL DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        $db->exec("
            CREATE TABLE IF NOT EXISTS grievance_respondent_types (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                type ENUM('Directly Affected','Indirectly Affected','Others') NOT NULL,
                type_specify VARCHAR(255) NULL,
                guide TEXT,
                description TEXT,
                sort_order INT NOT NULL DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        $db->exec("
            CREATE TABLE IF NOT EXISTS grievance_grm_channels (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                description TEXT,
                sort_order INT NOT NULL DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        $db->exec("
            CREATE TABLE IF NOT EXISTS grievance_preferred_languages (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                description TEXT,
                sort_order INT NOT NULL DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        $db->exec("
            CREATE TABLE IF NOT EXISTS grievance_types (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                description TEXT,
                sort_order INT NOT NULL DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        $db->exec("
            CREATE TABLE IF NOT EXISTS grievance_categories (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                description TEXT,
                sort_order INT NOT NULL DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // Main grievances table
        $db->exec("
            CREATE TABLE IF NOT EXISTS grievances (
                id INT AUTO_INCREMENT PRIMARY KEY,
                date_recorded DATETIME NULL,
                grievance_case_number VARCHAR(100) DEFAULT '',
                is_paps TINYINT(1) NOT NULL DEFAULT 0,
                profile_id INT NULL,
                respondent_full_name VARCHAR(255) DEFAULT '',
                gender VARCHAR(50) DEFAULT '',
                gender_specify VARCHAR(255) DEFAULT '',
                valid_id_philippines VARCHAR(255) DEFAULT '',
                id_number VARCHAR(100) DEFAULT '',
                vulnerability_ids JSON,
                respondent_type_ids JSON,
                home_business_address TEXT,
                mobile_number VARCHAR(50) DEFAULT '',
                email VARCHAR(255) DEFAULT '',
                contact_others_specify TEXT,
                grm_channel_ids JSON,
                preferred_language_ids JSON,
                grievance_type_ids JSON,
                grievance_category_ids JSON,
                location_same_as_address TINYINT(1) NOT NULL DEFAULT 1,
                location_specify TEXT,
                incident_one_time TINYINT(1) NOT NULL DEFAULT 0,
                incident_date DATE NULL,
                incident_multiple TINYINT(1) NOT NULL DEFAULT 0,
                incident_dates TEXT,
                incident_ongoing TINYINT(1) NOT NULL DEFAULT 0,
                description_complaint TEXT,
                desired_resolution TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_date_recorded (date_recorded),
                INDEX idx_case_number (grievance_case_number),
                INDEX idx_profile (profile_id),
                INDEX idx_created (created_at),
                CONSTRAINT fk_grievances_profile FOREIGN KEY (profile_id) REFERENCES profiles(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // Add manage_grievance_options capability and assign to Administrator
        $adminId = $db->query("SELECT id FROM roles WHERE name = 'Administrator'")->fetchColumn();
        if ($adminId) {
            $db->prepare("INSERT IGNORE INTO role_capabilities (role_id, capability) VALUES (?, 'manage_grievance_options')")
                ->execute([$adminId]);
        }
    },
    'down' => function (\PDO $db): void {
        $db->exec('SET FOREIGN_KEY_CHECKS = 0');
        $db->exec('DROP TABLE IF EXISTS grievances');
        $db->exec('DROP TABLE IF EXISTS grievance_categories');
        $db->exec('DROP TABLE IF EXISTS grievance_types');
        $db->exec('DROP TABLE IF EXISTS grievance_preferred_languages');
        $db->exec('DROP TABLE IF EXISTS grievance_grm_channels');
        $db->exec('DROP TABLE IF EXISTS grievance_respondent_types');
        $db->exec('DROP TABLE IF EXISTS grievance_vulnerabilities');
        $db->prepare('DELETE FROM role_capabilities WHERE capability = ?')->execute(['manage_grievance_options']);
        $db->exec('SET FOREIGN_KEY_CHECKS = 1');
    },
];
