<?php
/**
 * Migration 049: Normalized project areas (municipality + barangay) and profile.custom_municipality.
 * Migrates legacy projects.affected_barangays (one barangay per line) into rows with municipality "Unassigned".
 */
return [
    'name' => 'migration_049_project_affected_barangays_normalized',
    'up' => function (\PDO $db): void {
        $db->exec("
            CREATE TABLE IF NOT EXISTS project_affected_barangays (
                id INT NOT NULL AUTO_INCREMENT,
                project_id INT NOT NULL,
                municipality VARCHAR(255) NOT NULL,
                barangay VARCHAR(255) NOT NULL,
                PRIMARY KEY (id),
                UNIQUE KEY uq_project_mun_brgy (project_id, municipality, barangay),
                KEY idx_project_municipality (project_id, municipality),
                CONSTRAINT fk_pab_project FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $legacyMun = 'Unassigned';

        $stmt = $db->query('SELECT id, COALESCE(affected_barangays, \'\') AS raw FROM projects WHERE is_deleted = 0');
        $projects = $stmt ? $stmt->fetchAll(\PDO::FETCH_ASSOC) : [];
        $ins = $db->prepare(
            'INSERT IGNORE INTO project_affected_barangays (project_id, municipality, barangay) VALUES (?, ?, ?)'
        );
        foreach ($projects as $row) {
            $pid = (int) ($row['id'] ?? 0);
            if ($pid <= 0) {
                continue;
            }
            $raw = (string) ($row['raw'] ?? '');
            if ($raw === '') {
                continue;
            }
            $lines = preg_split('/\r\n|\r|\n/', $raw);
            foreach ($lines as $line) {
                $br = trim((string) $line);
                if ($br === '') {
                    continue;
                }
                $ins->execute([$pid, $legacyMun, $br]);
            }
        }

        $checkCol = function (string $col) use ($db): bool {
            $s = $db->prepare('SHOW COLUMNS FROM profiles LIKE ?');
            $s->execute([$col]);

            return (bool) $s->fetch(\PDO::FETCH_ASSOC);
        };
        if (!$checkCol('custom_municipality')) {
            $db->exec("ALTER TABLE profiles ADD COLUMN custom_municipality VARCHAR(255) NOT NULL DEFAULT '' AFTER full_name");
        }

        $qMun = $db->quote($legacyMun);
        $db->exec("
            UPDATE profiles p
            INNER JOIN project_affected_barangays a
                ON a.project_id = p.project_id AND a.barangay = p.custom_barangay AND a.municipality = {$qMun}
            SET p.custom_municipality = {$qMun}
            WHERE p.custom_barangay <> '' AND COALESCE(NULLIF(TRIM(p.custom_municipality), ''), '') = ''
        ");
    },
    'down' => function (\PDO $db): void {
        $stmt = $db->query("SHOW TABLES LIKE 'project_affected_barangays'");
        if ($stmt && $stmt->fetch()) {
            $db->exec('DROP TABLE project_affected_barangays');
        }
        $s = $db->prepare('SHOW COLUMNS FROM profiles LIKE ?');
        $s->execute(['custom_municipality']);
        if ($s->fetch(\PDO::FETCH_ASSOC)) {
            $db->exec('ALTER TABLE profiles DROP COLUMN custom_municipality');
        }
    },
];
