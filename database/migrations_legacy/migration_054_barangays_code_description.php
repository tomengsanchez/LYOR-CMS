<?php
/**
 * Migration 054: Add code and description to barangays (Library CRUD).
 */
return [
    'name' => 'migration_054_barangays_code_description',
    'up' => function (\PDO $db): void {
        $tbl = (bool) $db->query("SHOW TABLES LIKE 'barangays'")->fetch();
        if (!$tbl) {
            return;
        }
        $hasCode = (bool) $db->query("SHOW COLUMNS FROM barangays LIKE 'code'")->fetch();
        if (!$hasCode) {
            $db->exec('ALTER TABLE barangays ADD COLUMN code VARCHAR(50) NULL AFTER name');
        }
        $hasDescription = (bool) $db->query("SHOW COLUMNS FROM barangays LIKE 'description'")->fetch();
        if (!$hasDescription) {
            $db->exec('ALTER TABLE barangays ADD COLUMN description TEXT NULL AFTER code');
        }
        $db->exec("UPDATE barangays SET code = CONCAT('BRGY-', LPAD(id, 5, '0')) WHERE code IS NULL OR TRIM(code) = ''");
        try {
            $db->exec('ALTER TABLE barangays MODIFY code VARCHAR(50) NOT NULL');
        } catch (\Throwable) {
        }
        try {
            $db->exec('ALTER TABLE barangays ADD UNIQUE KEY uq_barangays_code (code)');
        } catch (\Throwable) {
        }
    },
    'down' => function (\PDO $db): void {
        $tbl = (bool) $db->query("SHOW TABLES LIKE 'barangays'")->fetch();
        if (!$tbl) {
            return;
        }
        try {
            $db->exec('ALTER TABLE barangays DROP INDEX uq_barangays_code');
        } catch (\Throwable) {
        }
        try {
            $db->exec('ALTER TABLE barangays DROP COLUMN description');
        } catch (\Throwable) {
        }
        try {
            $db->exec('ALTER TABLE barangays DROP COLUMN code');
        } catch (\Throwable) {
        }
    },
];
