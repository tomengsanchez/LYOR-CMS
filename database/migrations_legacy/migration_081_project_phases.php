<?php
/**
 * Migration 081: Project-scoped phases (Name, Description) with soft delete.
 * Adds phase_id on structures, profiles, grievances; seeds "Unassigned" per project;
 * backfills structure/profile phase_id to Unassigned.
 */
return [
    'name' => 'migration_081_project_phases',
    'up' => function (\PDO $db): void {
        $db->exec("
            CREATE TABLE IF NOT EXISTS project_phases (
                id INT AUTO_INCREMENT PRIMARY KEY,
                project_id INT NOT NULL,
                name VARCHAR(255) NOT NULL,
                description TEXT NULL,
                is_deleted TINYINT(1) NOT NULL DEFAULT 0,
                deleted_at DATETIME NULL,
                deleted_by INT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_project_phases_project (project_id),
                INDEX idx_project_phases_active (project_id, is_deleted),
                CONSTRAINT fk_project_phases_project FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
                CONSTRAINT fk_project_phases_deleted_by FOREIGN KEY (deleted_by) REFERENCES users(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // Unique active name per project (NULL key when soft-deleted allows reuse after delete).
        try {
            $stmt = $db->query("SHOW COLUMNS FROM project_phases LIKE 'name_active_key'");
            if (!$stmt->fetch(\PDO::FETCH_ASSOC)) {
                $db->exec("
                    ALTER TABLE project_phases
                    ADD COLUMN name_active_key VARCHAR(255)
                        GENERATED ALWAYS AS (IF(is_deleted = 0, name, NULL)) STORED,
                    ADD UNIQUE KEY uq_project_phases_project_name_active (project_id, name_active_key)
                ");
            }
        } catch (\Throwable $e) {
            // App-level uniqueness still enforced in ProjectPhase::validate()
        }

        $addPhaseCol = static function (\PDO $db, string $table, string $afterCol, string $fkName, string $idxName): void {
            $stmt = $db->prepare("SHOW COLUMNS FROM `{$table}` LIKE ?");
            $stmt->execute(['phase_id']);
            if ($stmt->fetch(\PDO::FETCH_ASSOC)) {
                return;
            }
            $db->exec("ALTER TABLE `{$table}` ADD COLUMN phase_id INT NULL AFTER `{$afterCol}`");
            $db->exec("ALTER TABLE `{$table}` ADD INDEX `{$idxName}` (phase_id)");
            $db->exec("
                ALTER TABLE `{$table}`
                ADD CONSTRAINT `{$fkName}` FOREIGN KEY (phase_id) REFERENCES project_phases(id) ON DELETE SET NULL
            ");
        };

        $addPhaseCol($db, 'structures', 'barangay_id', 'fk_structures_phase', 'idx_structures_phase');
        $addPhaseCol($db, 'profiles', 'custom_barangay_id', 'fk_profiles_phase', 'idx_profiles_phase');
        $addPhaseCol($db, 'grievances', 'barangay_id', 'fk_grievances_phase', 'idx_grievances_phase');

        $projects = $db->query('SELECT id FROM projects')->fetchAll(\PDO::FETCH_COLUMN);
        $findPhase = $db->prepare('
            SELECT id FROM project_phases
            WHERE project_id = ? AND name = ? AND is_deleted = 0
            LIMIT 1
        ');
        $insertPhase = $db->prepare('
            INSERT INTO project_phases (project_id, name, description)
            VALUES (?, ?, ?)
        ');
        foreach ($projects as $projectId) {
            $pid = (int) $projectId;
            if ($pid <= 0) {
                continue;
            }
            $findPhase->execute([$pid, 'Unassigned']);
            if ($findPhase->fetchColumn()) {
                continue;
            }
            $insertPhase->execute([$pid, 'Unassigned', 'Default phase for legacy and unscoped records.']);
        }

        $db->exec("
            UPDATE structures s
            INNER JOIN project_phases pp
                ON pp.project_id = s.project_id AND pp.name = 'Unassigned' AND pp.is_deleted = 0
            SET s.phase_id = pp.id
            WHERE s.project_id IS NOT NULL AND s.project_id > 0
              AND (s.phase_id IS NULL OR s.phase_id = 0)
        ");
        $db->exec("
            UPDATE profiles p
            INNER JOIN project_phases pp
                ON pp.project_id = p.project_id AND pp.name = 'Unassigned' AND pp.is_deleted = 0
            SET p.phase_id = pp.id
            WHERE p.project_id IS NOT NULL AND p.project_id > 0
              AND (p.phase_id IS NULL OR p.phase_id = 0)
        ");
    },
    'down' => function (\PDO $db): void {
        $dropPhaseCol = static function (\PDO $db, string $table, string $fkName, string $idxName): void {
            $stmt = $db->prepare("SHOW COLUMNS FROM `{$table}` LIKE ?");
            $stmt->execute(['phase_id']);
            if (!$stmt->fetch(\PDO::FETCH_ASSOC)) {
                return;
            }
            try {
                $db->exec("ALTER TABLE `{$table}` DROP FOREIGN KEY `{$fkName}`");
            } catch (\Throwable $e) {
            }
            try {
                $db->exec("ALTER TABLE `{$table}` DROP INDEX `{$idxName}`");
            } catch (\Throwable $e) {
            }
            $db->exec("ALTER TABLE `{$table}` DROP COLUMN phase_id");
        };

        $dropPhaseCol($db, 'grievances', 'fk_grievances_phase', 'idx_grievances_phase');
        $dropPhaseCol($db, 'profiles', 'fk_profiles_phase', 'idx_profiles_phase');
        $dropPhaseCol($db, 'structures', 'fk_structures_phase', 'idx_structures_phase');

        $db->exec('DROP TABLE IF EXISTS project_phases');
    },
];
