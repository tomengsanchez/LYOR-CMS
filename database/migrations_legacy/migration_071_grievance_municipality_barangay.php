<?php
/**
 * Migration 071: Municipality and barangay on grievances (respondent location).
 */
return [
    'name' => 'migration_071_grievance_municipality_barangay',
    'up' => function (\PDO $db): void {
        $cols = [
            'municipality_id' => "ADD COLUMN municipality_id INT NULL AFTER project_id",
            'barangay_id' => "ADD COLUMN barangay_id INT NULL AFTER municipality_id",
        ];
        foreach ($cols as $col => $ddl) {
            $stmt = $db->prepare('SHOW COLUMNS FROM grievances LIKE ?');
            $stmt->execute([$col]);
            if (!$stmt->fetch(\PDO::FETCH_ASSOC)) {
                $db->exec("ALTER TABLE grievances {$ddl}");
            }
        }
        try {
            $db->exec('ALTER TABLE grievances ADD INDEX idx_grievance_municipality (municipality_id)');
        } catch (\Throwable $e) {
            // Ignore if exists.
        }
        try {
            $db->exec('ALTER TABLE grievances ADD INDEX idx_grievance_barangay (barangay_id)');
        } catch (\Throwable $e) {
            // Ignore if exists.
        }
        try {
            $db->exec('ALTER TABLE grievances ADD CONSTRAINT fk_grievances_municipality FOREIGN KEY (municipality_id) REFERENCES municipalities(id) ON DELETE SET NULL');
        } catch (\Throwable $e) {
            // Ignore if exists.
        }
        try {
            $db->exec('ALTER TABLE grievances ADD CONSTRAINT fk_grievances_barangay FOREIGN KEY (barangay_id) REFERENCES barangays(id) ON DELETE SET NULL');
        } catch (\Throwable $e) {
            // Ignore if exists.
        }
    },
    'down' => function (\PDO $db): void {
        try {
            $db->exec('ALTER TABLE grievances DROP FOREIGN KEY fk_grievances_barangay');
        } catch (\Throwable $e) {
        }
        try {
            $db->exec('ALTER TABLE grievances DROP FOREIGN KEY fk_grievances_municipality');
        } catch (\Throwable $e) {
        }
        foreach (['barangay_id', 'municipality_id'] as $col) {
            $stmt = $db->prepare('SHOW COLUMNS FROM grievances LIKE ?');
            $stmt->execute([$col]);
            if ($stmt->fetch(\PDO::FETCH_ASSOC)) {
                $db->exec("ALTER TABLE grievances DROP COLUMN {$col}");
            }
        }
    },
];
