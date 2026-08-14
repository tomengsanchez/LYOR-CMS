<?php
/**
 * Migration 050: Master municipalities table; project_affected_barangays + profiles use municipality_id FK.
 */
return [
    'name' => 'migration_050_municipalities_table',
    'up' => function (\PDO $db): void {
        $db->exec("
            CREATE TABLE IF NOT EXISTS municipalities (
                id INT NOT NULL AUTO_INCREMENT,
                name VARCHAR(255) NOT NULL,
                PRIMARY KEY (id),
                KEY idx_municipalities_name (name)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $pabExists = (bool) $db->query("SHOW TABLES LIKE 'project_affected_barangays'")->fetch();
        if ($pabExists) {
            $hasVarcharMun = (bool) $db->query("SHOW COLUMNS FROM project_affected_barangays LIKE 'municipality'")->fetch();
            $hasMidCol = (bool) $db->query("SHOW COLUMNS FROM project_affected_barangays LIKE 'municipality_id'")->fetch();

            if ($hasVarcharMun) {
                $stmt = $db->query("SELECT DISTINCT TRIM(municipality) AS n FROM project_affected_barangays WHERE TRIM(municipality) <> ''");
                $names = $stmt ? $stmt->fetchAll(\PDO::FETCH_COLUMN) : [];
                $insMun = $db->prepare('INSERT INTO municipalities (name) SELECT ? WHERE NOT EXISTS (SELECT 1 FROM municipalities m WHERE m.name = ?)');
                foreach ($names as $n) {
                    $n = (string) $n;
                    if ($n === '') {
                        continue;
                    }
                    $insMun->execute([$n, $n]);
                }

                if (!$hasMidCol) {
                    $db->exec('ALTER TABLE project_affected_barangays ADD COLUMN municipality_id INT NULL AFTER project_id');
                    $db->exec('
                        UPDATE project_affected_barangays pab
                        INNER JOIN municipalities m ON m.name = TRIM(pab.municipality)
                        SET pab.municipality_id = m.id
                    ');
                }

                $bad = (int) $db->query('SELECT COUNT(*) FROM project_affected_barangays WHERE municipality_id IS NULL')->fetchColumn();
                if ($bad > 0) {
                    throw new \RuntimeException('migration_050: project_affected_barangays rows could not be mapped to municipalities (NULL municipality_id).');
                }

                // fk_pab_project (project_id → projects) may use idx_project_municipality; drop FK before altering indexes.
                try {
                    $db->exec('ALTER TABLE project_affected_barangays DROP FOREIGN KEY fk_pab_project');
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

                $db->exec('ALTER TABLE project_affected_barangays DROP COLUMN municipality');
                $db->exec('ALTER TABLE project_affected_barangays MODIFY municipality_id INT NOT NULL');

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
        }

        $checkProfileCol = static function (string $col) use ($db): bool {
            $s = $db->prepare('SHOW COLUMNS FROM profiles LIKE ?');
            $s->execute([$col]);

            return (bool) $s->fetch(\PDO::FETCH_ASSOC);
        };

        if (!$checkProfileCol('custom_municipality_id') && $checkProfileCol('custom_municipality')) {
            $db->exec('ALTER TABLE profiles ADD COLUMN custom_municipality_id INT NULL AFTER full_name');
            $db->exec('
                UPDATE profiles p
                INNER JOIN project_affected_barangays a
                    ON a.project_id = p.project_id AND a.barangay = p.custom_barangay
                SET p.custom_municipality_id = a.municipality_id
                WHERE p.project_id IS NOT NULL AND p.custom_barangay <> \'\'
            ');
            $db->exec('
                UPDATE profiles p
                INNER JOIN municipalities m ON m.name = TRIM(p.custom_municipality)
                SET p.custom_municipality_id = m.id
                WHERE p.custom_municipality_id IS NULL AND TRIM(COALESCE(p.custom_municipality, \'\')) <> \'\'
            ');
            $db->exec('ALTER TABLE profiles DROP COLUMN custom_municipality');
        }

        if ($checkProfileCol('custom_municipality_id')) {
            $fkStmt = $db->query("
                SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS
                WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'profiles' AND CONSTRAINT_TYPE = 'FOREIGN KEY'
                AND CONSTRAINT_NAME = 'fk_profiles_custom_municipality'
            ");
            if (!$fkStmt || !$fkStmt->fetch()) {
                try {
                    $db->exec('
                        ALTER TABLE profiles
                        ADD CONSTRAINT fk_profiles_custom_municipality FOREIGN KEY (custom_municipality_id) REFERENCES municipalities(id) ON DELETE SET NULL
                    ');
                } catch (\Throwable) {
                }
            }
        }
    },
    'down' => function (\PDO $db): void {
        $pabExists = (bool) $db->query("SHOW TABLES LIKE 'project_affected_barangays'")->fetch();
        $hasMid = false;
        if ($pabExists) {
            $hasMid = (bool) $db->query("SHOW COLUMNS FROM project_affected_barangays LIKE 'municipality_id'")->fetch();
        }

        if ($hasMid) {
            $db->exec('ALTER TABLE project_affected_barangays DROP FOREIGN KEY fk_pab_municipality');
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
                $db->exec('ALTER TABLE project_affected_barangays DROP INDEX `' . str_replace('`', '``', $keyName) . '`');
            }
            $db->exec('ALTER TABLE project_affected_barangays ADD COLUMN municipality VARCHAR(255) NOT NULL DEFAULT \'\' AFTER project_id');
            $db->exec('
                UPDATE project_affected_barangays pab
                INNER JOIN municipalities m ON m.id = pab.municipality_id
                SET pab.municipality = m.name
            ');
            $db->exec('ALTER TABLE project_affected_barangays DROP COLUMN municipality_id');
            $db->exec('ALTER TABLE project_affected_barangays ADD UNIQUE KEY uq_project_mun_brgy (project_id, municipality, barangay)');
            $db->exec('ALTER TABLE project_affected_barangays ADD KEY idx_project_municipality (project_id, municipality)');
        }

        $profHasMid = (bool) $db->query("SHOW COLUMNS FROM profiles LIKE 'custom_municipality_id'")->fetch();
        if ($profHasMid) {
            try {
                $db->exec('ALTER TABLE profiles DROP FOREIGN KEY fk_profiles_custom_municipality');
            } catch (\Throwable) {
            }
            $db->exec("ALTER TABLE profiles ADD COLUMN custom_municipality VARCHAR(255) NOT NULL DEFAULT '' AFTER full_name");
            $db->exec('
                UPDATE profiles p
                INNER JOIN municipalities m ON m.id = p.custom_municipality_id
                SET p.custom_municipality = m.name
                WHERE p.custom_municipality_id IS NOT NULL
            ');
            $db->exec('ALTER TABLE profiles DROP COLUMN custom_municipality_id');
        }

        $db->exec('DROP TABLE IF EXISTS municipalities');
    },
];
