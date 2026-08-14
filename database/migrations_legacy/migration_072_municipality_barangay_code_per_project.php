<?php
/**
 * Migration 072: Municipality and barangay codes are unique per project (app-enforced), not globally.
 */
return [
    'name' => 'migration_072_municipality_barangay_code_per_project',
    'up' => function (\PDO $db): void {
        $mun = (bool) $db->query("SHOW TABLES LIKE 'municipalities'")->fetch();
        if ($mun) {
            try {
                $db->exec('ALTER TABLE municipalities DROP INDEX uq_municipalities_code');
            } catch (\Throwable $e) {
                // Index may already be absent.
            }
        }
        $br = (bool) $db->query("SHOW TABLES LIKE 'barangays'")->fetch();
        if ($br) {
            try {
                $db->exec('ALTER TABLE barangays DROP INDEX uq_barangays_code');
            } catch (\Throwable $e) {
                // Index may already be absent.
            }
        }
    },
    'down' => function (\PDO $db): void {
        $mun = (bool) $db->query("SHOW TABLES LIKE 'municipalities'")->fetch();
        if ($mun) {
            try {
                $db->exec('ALTER TABLE municipalities ADD UNIQUE KEY uq_municipalities_code (code)');
            } catch (\Throwable $e) {
                // May fail if duplicate codes exist after per-project data entry.
            }
        }
        $br = (bool) $db->query("SHOW TABLES LIKE 'barangays'")->fetch();
        if ($br) {
            try {
                $db->exec('ALTER TABLE barangays ADD UNIQUE KEY uq_barangays_code (code)');
            } catch (\Throwable $e) {
                // May fail if duplicate codes exist after per-project data entry.
            }
        }
    },
];
