<?php
/**
 * Migration 052: Add code and description fields to municipalities.
 */
return [
    'name' => 'migration_052_municipalities_code_description',
    'up' => function (\PDO $db): void {
        $hasCode = (bool) $db->query("SHOW COLUMNS FROM municipalities LIKE 'code'")->fetch();
        if (!$hasCode) {
            $db->exec('ALTER TABLE municipalities ADD COLUMN code VARCHAR(50) NULL AFTER name');
        }

        $hasDescription = (bool) $db->query("SHOW COLUMNS FROM municipalities LIKE 'description'")->fetch();
        if (!$hasDescription) {
            $db->exec('ALTER TABLE municipalities ADD COLUMN description TEXT NULL AFTER code');
        }

        $db->exec("UPDATE municipalities SET code = CONCAT('MUN-', LPAD(id, 4, '0')) WHERE code IS NULL OR TRIM(code) = ''");
        try {
            $db->exec('ALTER TABLE municipalities MODIFY code VARCHAR(50) NOT NULL');
        } catch (\Throwable) {
        }
        try {
            $db->exec('ALTER TABLE municipalities ADD UNIQUE KEY uq_municipalities_code (code)');
        } catch (\Throwable) {
        }
    },
    'down' => function (\PDO $db): void {
        try {
            $db->exec('ALTER TABLE municipalities DROP INDEX uq_municipalities_code');
        } catch (\Throwable) {
        }
        try {
            $db->exec('ALTER TABLE municipalities DROP COLUMN description');
        } catch (\Throwable) {
        }
        try {
            $db->exec('ALTER TABLE municipalities DROP COLUMN code');
        } catch (\Throwable) {
        }
    },
];
