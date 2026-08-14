<?php
/**
 * Migration 055: Optional Library project + municipality + barangay on structures (web create/edit).
 */
return [
    'name' => 'migration_055_structures_project_location',
    'up' => function (\PDO $db): void {
        $stmt = $db->prepare('SHOW COLUMNS FROM structures LIKE ?');
        $stmt->execute(['project_id']);
        if (!$stmt->fetch(\PDO::FETCH_ASSOC)) {
            $db->exec('ALTER TABLE structures ADD COLUMN project_id INT NULL AFTER tagged_by_profile_id');
            $db->exec('ALTER TABLE structures ADD INDEX idx_structures_project (project_id)');
            $db->exec('ALTER TABLE structures ADD CONSTRAINT fk_structures_project FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE SET NULL');
        }
        $stmt = $db->prepare('SHOW COLUMNS FROM structures LIKE ?');
        $stmt->execute(['municipality_id']);
        if (!$stmt->fetch(\PDO::FETCH_ASSOC)) {
            $db->exec('ALTER TABLE structures ADD COLUMN municipality_id INT NULL AFTER project_id');
            $db->exec('ALTER TABLE structures ADD INDEX idx_structures_municipality (municipality_id)');
            $db->exec('ALTER TABLE structures ADD CONSTRAINT fk_structures_municipality FOREIGN KEY (municipality_id) REFERENCES municipalities(id) ON DELETE SET NULL');
        }
        $stmt = $db->prepare('SHOW COLUMNS FROM structures LIKE ?');
        $stmt->execute(['barangay_id']);
        if (!$stmt->fetch(\PDO::FETCH_ASSOC)) {
            $db->exec('ALTER TABLE structures ADD COLUMN barangay_id INT NULL AFTER municipality_id');
            $db->exec('ALTER TABLE structures ADD INDEX idx_structures_barangay (barangay_id)');
            $db->exec('ALTER TABLE structures ADD CONSTRAINT fk_structures_barangay FOREIGN KEY (barangay_id) REFERENCES barangays(id) ON DELETE SET NULL');
        }
    },
    'down' => function (\PDO $db): void {
        foreach (['barangay_id', 'municipality_id', 'project_id'] as $col) {
            $stmt = $db->prepare('SHOW COLUMNS FROM structures LIKE ?');
            $stmt->execute([$col]);
            if (!$stmt->fetch(\PDO::FETCH_ASSOC)) {
                continue;
            }
            $fk = match ($col) {
                'project_id' => 'fk_structures_project',
                'municipality_id' => 'fk_structures_municipality',
                'barangay_id' => 'fk_structures_barangay',
            };
            $idx = match ($col) {
                'project_id' => 'idx_structures_project',
                'municipality_id' => 'idx_structures_municipality',
                'barangay_id' => 'idx_structures_barangay',
            };
            $db->exec("ALTER TABLE structures DROP FOREIGN KEY {$fk}");
            $db->exec("ALTER TABLE structures DROP INDEX {$idx}");
            $db->exec("ALTER TABLE structures DROP COLUMN {$col}");
        }
    },
];
