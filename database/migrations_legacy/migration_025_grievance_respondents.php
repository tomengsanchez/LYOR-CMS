<?php
/**
 * Migration 025: Normalize grievance respondents into a dedicated table.
 */
return [
    'name' => 'migration_025_grievance_respondents',
    'up' => function (\PDO $db): void {
        $db->exec("
            CREATE TABLE IF NOT EXISTS grievance_respondents (
                id INT AUTO_INCREMENT PRIMARY KEY,
                is_paps TINYINT(1) NOT NULL DEFAULT 0,
                profile_id INT NULL,
                first_name VARCHAR(120) DEFAULT '',
                middle_name VARCHAR(120) DEFAULT '',
                last_name VARCHAR(120) DEFAULT '',
                full_name VARCHAR(255) DEFAULT '',
                gender VARCHAR(50) DEFAULT '',
                gender_specify VARCHAR(255) DEFAULT '',
                valid_id_philippines VARCHAR(255) DEFAULT '',
                id_number VARCHAR(100) DEFAULT '',
                vulnerability_ids TEXT NULL,
                respondent_type_ids TEXT NULL,
                respondent_type_other_specify VARCHAR(255) DEFAULT '',
                home_business_address TEXT NULL,
                mobile_number VARCHAR(50) DEFAULT '',
                email VARCHAR(255) DEFAULT '',
                contact_others_specify TEXT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_gr_rsp_is_paps (is_paps),
                INDEX idx_gr_rsp_profile (profile_id),
                INDEX idx_gr_rsp_name (first_name, middle_name, last_name),
                CONSTRAINT fk_gr_rsp_profile FOREIGN KEY (profile_id) REFERENCES profiles(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // Add respondent reference to grievances (safe if column already exists).
        try {
            $db->exec("ALTER TABLE grievances ADD COLUMN respondent_id INT NULL AFTER profile_id");
        } catch (\Throwable $e) {
            // Ignore if already added.
        }
        try {
            $db->exec("ALTER TABLE grievances ADD INDEX idx_grievance_respondent (respondent_id)");
        } catch (\Throwable $e) {
            // Ignore if already exists.
        }
        try {
            $db->exec("ALTER TABLE grievances ADD CONSTRAINT fk_grievances_respondent FOREIGN KEY (respondent_id) REFERENCES grievance_respondents(id) ON DELETE SET NULL");
        } catch (\Throwable $e) {
            // Ignore if already exists.
        }

        // Backfill respondent rows for existing grievances that do not yet have respondent_id.
        $rows = $db->query("
            SELECT id, is_paps, profile_id,
                   respondent_first_name, respondent_middle_name, respondent_last_name, respondent_full_name,
                   gender, gender_specify, valid_id_philippines, id_number,
                   vulnerability_ids, respondent_type_ids, respondent_type_other_specify,
                   home_business_address, mobile_number, email, contact_others_specify
            FROM grievances
            WHERE respondent_id IS NULL
            ORDER BY id ASC
        ")->fetchAll(\PDO::FETCH_ASSOC);

        $insert = $db->prepare("
            INSERT INTO grievance_respondents (
                is_paps, profile_id, first_name, middle_name, last_name, full_name,
                gender, gender_specify, valid_id_philippines, id_number,
                vulnerability_ids, respondent_type_ids, respondent_type_other_specify,
                home_business_address, mobile_number, email, contact_others_specify
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $update = $db->prepare("UPDATE grievances SET respondent_id = ? WHERE id = ?");

        foreach ($rows as $r) {
            $insert->execute([
                !empty($r['is_paps']) ? 1 : 0,
                !empty($r['profile_id']) ? (int) $r['profile_id'] : null,
                trim((string) ($r['respondent_first_name'] ?? '')),
                trim((string) ($r['respondent_middle_name'] ?? '')),
                trim((string) ($r['respondent_last_name'] ?? '')),
                trim((string) ($r['respondent_full_name'] ?? '')),
                trim((string) ($r['gender'] ?? '')),
                trim((string) ($r['gender_specify'] ?? '')),
                trim((string) ($r['valid_id_philippines'] ?? '')),
                trim((string) ($r['id_number'] ?? '')),
                (string) ($r['vulnerability_ids'] ?? '[]'),
                (string) ($r['respondent_type_ids'] ?? '[]'),
                trim((string) ($r['respondent_type_other_specify'] ?? '')),
                (string) ($r['home_business_address'] ?? ''),
                trim((string) ($r['mobile_number'] ?? '')),
                trim((string) ($r['email'] ?? '')),
                (string) ($r['contact_others_specify'] ?? ''),
            ]);
            $rid = (int) $db->lastInsertId();
            $update->execute([$rid, (int) $r['id']]);
        }
    },
    'down' => null,
];
