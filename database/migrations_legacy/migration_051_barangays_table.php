<?php
/**
 * Migration 051: Master barangays table; project_affected_barangays + profiles use barangay_id FK.
 * Keeps project_affected_barangays.municipality_id (must match barangays.municipality_id for each row).
 */
return [
    'name' => 'migration_051_barangays_table',
    'up' => function (\PDO $db): void {
        $db->exec("
            CREATE TABLE IF NOT EXISTS barangays (
                id INT NOT NULL AUTO_INCREMENT,
                municipality_id INT NOT NULL,
                name VARCHAR(255) NOT NULL,
                PRIMARY KEY (id),
                UNIQUE KEY uq_barangay_mun_name (municipality_id, name),
                KEY idx_barangays_municipality (municipality_id),
                CONSTRAINT fk_barangays_municipality FOREIGN KEY (municipality_id) REFERENCES municipalities(id) ON DELETE RESTRICT
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $pabExists = (bool) $db->query("SHOW TABLES LIKE 'project_affected_barangays'")->fetch();
        $hasLegacyBarangayCol = $pabExists
            && (bool) $db->query("SHOW COLUMNS FROM project_affected_barangays LIKE 'barangay'")->fetch();
        if (!$hasLegacyBarangayCol) {
            return;
        }

        $db->exec("
            INSERT IGNORE INTO barangays (municipality_id, name)
            SELECT DISTINCT municipality_id, TRIM(barangay) FROM project_affected_barangays
            WHERE TRIM(COALESCE(barangay, '')) <> ''
        ");

        $hasProfBrgy = (bool) $db->query("SHOW COLUMNS FROM profiles LIKE 'custom_barangay'")->fetch();
        if ($hasProfBrgy) {
            $db->exec("
                INSERT IGNORE INTO barangays (municipality_id, name)
                SELECT DISTINCT p.custom_municipality_id, TRIM(p.custom_barangay)
                FROM profiles p
                WHERE p.custom_municipality_id IS NOT NULL
                  AND TRIM(COALESCE(p.custom_barangay, '')) <> ''
                  AND NOT EXISTS (
                      SELECT 1 FROM barangays b
                      WHERE b.municipality_id = p.custom_municipality_id AND b.name = TRIM(p.custom_barangay)
                  )
            ");
        }

        $db->exec('ALTER TABLE project_affected_barangays ADD COLUMN barangay_id INT NULL AFTER municipality_id');
        $db->exec("
            UPDATE project_affected_barangays pab
            INNER JOIN barangays b ON b.municipality_id = pab.municipality_id AND b.name = TRIM(pab.barangay)
            SET pab.barangay_id = b.id
        ");
        $badPab = (int) $db->query('SELECT COUNT(*) FROM project_affected_barangays WHERE barangay_id IS NULL')->fetchColumn();
        if ($badPab > 0) {
            throw new \RuntimeException('migration_051: project_affected_barangays rows could not be mapped to barangays (NULL barangay_id).');
        }

        foreach (['fk_pab_project', 'fk_pab_municipality'] as $fk) {
            try {
                $db->exec('ALTER TABLE project_affected_barangays DROP FOREIGN KEY `' . str_replace('`', '``', $fk) . '`');
            } catch (\Throwable) {
            }
        }
        $idxStmt = $db->query("SHOW INDEX FROM project_affected_barangays WHERE Key_name <> 'PRIMARY'");
        $indexes = $idxStmt ? $idxStmt->fetchAll(\PDO::FETCH_ASSOC) : [];
        $toDrop = [];
        foreach ($indexes as $row) {
            $kn = (string) ($row['Key_name'] ?? '');
            if ($kn !== '' && !isset($toDrop[$kn])) {
                $toDrop[$kn] = true;
            }
        }
        foreach (array_keys($toDrop) as $keyName) {
            try {
                $db->exec('ALTER TABLE project_affected_barangays DROP INDEX `' . str_replace('`', '``', $keyName) . '`');
            } catch (\Throwable) {
            }
        }

        $db->exec('ALTER TABLE project_affected_barangays DROP COLUMN barangay');
        $db->exec('ALTER TABLE project_affected_barangays MODIFY barangay_id INT NOT NULL');
        try {
            $db->exec('ALTER TABLE project_affected_barangays ADD UNIQUE KEY uq_project_barangay (project_id, barangay_id)');
        } catch (\Throwable) {
        }
        try {
            $db->exec('ALTER TABLE project_affected_barangays ADD KEY idx_project_municipality (project_id, municipality_id)');
        } catch (\Throwable) {
        }
        try {
            $db->exec('
                ALTER TABLE project_affected_barangays
                ADD CONSTRAINT fk_pab_barangay FOREIGN KEY (barangay_id) REFERENCES barangays(id) ON DELETE RESTRICT
            ');
        } catch (\Throwable) {
        }
        try {
            $db->exec('
                ALTER TABLE project_affected_barangays
                ADD CONSTRAINT fk_pab_municipality FOREIGN KEY (municipality_id) REFERENCES municipalities(id) ON DELETE RESTRICT
            ');
        } catch (\Throwable) {
        }
        try {
            $db->exec('
                ALTER TABLE project_affected_barangays
                ADD CONSTRAINT fk_pab_project FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
            ');
        } catch (\Throwable) {
        }

        if ($hasProfBrgy) {
            $hasBidCol = (bool) $db->query("SHOW COLUMNS FROM profiles LIKE 'custom_barangay_id'")->fetch();
            if (!$hasBidCol) {
                $db->exec('ALTER TABLE profiles ADD COLUMN custom_barangay_id INT NULL AFTER custom_municipality_id');
            }
            $db->exec("
                UPDATE profiles p
                INNER JOIN barangays b ON b.municipality_id = p.custom_municipality_id AND b.name = TRIM(p.custom_barangay)
                SET p.custom_barangay_id = b.id
                WHERE p.custom_municipality_id IS NOT NULL AND TRIM(COALESCE(p.custom_barangay, '')) <> ''
            ");
            $db->exec("
                INSERT IGNORE INTO barangays (municipality_id, name)
                SELECT DISTINCT p.custom_municipality_id, TRIM(p.custom_barangay)
                FROM profiles p
                WHERE p.custom_municipality_id IS NOT NULL
                  AND TRIM(COALESCE(p.custom_barangay, '')) <> ''
                  AND p.custom_barangay_id IS NULL
            ");
            $db->exec("
                UPDATE profiles p
                INNER JOIN barangays b ON b.municipality_id = p.custom_municipality_id AND b.name = TRIM(p.custom_barangay)
                SET p.custom_barangay_id = b.id
                WHERE p.custom_municipality_id IS NOT NULL AND TRIM(COALESCE(p.custom_barangay, '')) <> '' AND p.custom_barangay_id IS NULL
            ");
            $orphan = (int) $db->query("
                SELECT COUNT(*) FROM profiles
                WHERE TRIM(COALESCE(custom_barangay, '')) <> ''
                  AND custom_municipality_id IS NOT NULL
                  AND custom_barangay_id IS NULL
            ")->fetchColumn();
            if ($orphan > 0) {
                throw new \RuntimeException('migration_051: profiles rows could not be mapped to barangays (custom_barangay_id NULL).');
            }

            $db->exec('ALTER TABLE profiles DROP COLUMN custom_barangay');
            try {
                $db->exec('
                    ALTER TABLE profiles
                    ADD CONSTRAINT fk_profiles_custom_barangay FOREIGN KEY (custom_barangay_id) REFERENCES barangays(id) ON DELETE SET NULL
                ');
            } catch (\Throwable) {
            }
        }
    },
    'down' => function (\PDO $db): void {
        $hasBidOnPab = (bool) $db->query("SHOW COLUMNS FROM project_affected_barangays LIKE 'barangay_id'")->fetch();
        if ($hasBidOnPab) {
            try {
                $db->exec('ALTER TABLE project_affected_barangays DROP FOREIGN KEY fk_pab_barangay');
            } catch (\Throwable) {
            }
            try {
                $db->exec('ALTER TABLE project_affected_barangays DROP FOREIGN KEY fk_pab_project');
            } catch (\Throwable) {
            }
            try {
                $db->exec('ALTER TABLE project_affected_barangays DROP FOREIGN KEY fk_pab_municipality');
            } catch (\Throwable) {
            }
            $idxStmt = $db->query("SHOW INDEX FROM project_affected_barangays WHERE Key_name <> 'PRIMARY'");
            $indexes = $idxStmt ? $idxStmt->fetchAll(\PDO::FETCH_ASSOC) : [];
            $toDrop = [];
            foreach ($indexes as $row) {
                $kn = (string) ($row['Key_name'] ?? '');
                if ($kn !== '' && !isset($toDrop[$kn])) {
                    $toDrop[$kn] = true;
                }
            }
            foreach (array_keys($toDrop) as $keyName) {
                try {
                    $db->exec('ALTER TABLE project_affected_barangays DROP INDEX `' . str_replace('`', '``', $keyName) . '`');
                } catch (\Throwable) {
                }
            }
            $db->exec("ALTER TABLE project_affected_barangays ADD COLUMN barangay VARCHAR(255) NOT NULL DEFAULT '' AFTER municipality_id");
            $db->exec("
                UPDATE project_affected_barangays pab
                INNER JOIN barangays b ON b.id = pab.barangay_id
                SET pab.barangay = b.name
            ");
            $db->exec('ALTER TABLE project_affected_barangays DROP COLUMN barangay_id');
            try {
                $db->exec('ALTER TABLE project_affected_barangays ADD UNIQUE KEY uq_project_mun_brgy (project_id, municipality_id, barangay)');
            } catch (\Throwable) {
            }
            try {
                $db->exec('ALTER TABLE project_affected_barangays ADD KEY idx_project_municipality (project_id, municipality_id)');
            } catch (\Throwable) {
            }
            try {
                $db->exec('
                    ALTER TABLE project_affected_barangays
                    ADD CONSTRAINT fk_pab_municipality FOREIGN KEY (municipality_id) REFERENCES municipalities(id) ON DELETE RESTRICT
                ');
            } catch (\Throwable) {
            }
            try {
                $db->exec('
                    ALTER TABLE project_affected_barangays
                    ADD CONSTRAINT fk_pab_project FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
                ');
            } catch (\Throwable) {
            }
        }

        $hasProfBid = (bool) $db->query("SHOW COLUMNS FROM profiles LIKE 'custom_barangay_id'")->fetch();
        if ($hasProfBid) {
            try {
                $db->exec('ALTER TABLE profiles DROP FOREIGN KEY fk_profiles_custom_barangay');
            } catch (\Throwable) {
            }
            $hasLegacy = (bool) $db->query("SHOW COLUMNS FROM profiles LIKE 'custom_barangay'")->fetch();
            if (!$hasLegacy) {
                $db->exec("ALTER TABLE profiles ADD COLUMN custom_barangay VARCHAR(255) NOT NULL DEFAULT '' AFTER custom_municipality_id");
            }
            $db->exec("
                UPDATE profiles p
                LEFT JOIN barangays b ON b.id = p.custom_barangay_id
                SET p.custom_barangay = COALESCE(b.name, '')
                WHERE p.custom_barangay_id IS NOT NULL
            ");
            try {
                $db->exec('ALTER TABLE profiles DROP COLUMN custom_barangay_id');
            } catch (\Throwable) {
            }
        }

        $stmt = $db->query("SHOW TABLES LIKE 'barangays'");
        if ($stmt && $stmt->fetch()) {
            $db->exec('DROP TABLE IF EXISTS barangays');
        }
    },
];
