<?php
/**
 * Migration 091: Optional free-text attendant on grievances (alongside attendant_id user link).
 *
 * MySQL and MariaDB: VARCHAR NULL is portable; no generated columns.
 */
return [
    'name' => 'migration_091_grievance_attendant_text',
    'up' => function (\PDO $db): void {
        $stmt = $db->prepare('SHOW COLUMNS FROM grievances LIKE ?');
        $stmt->execute(['attendant_text']);
        if (!$stmt->fetch(\PDO::FETCH_ASSOC)) {
            $db->exec('ALTER TABLE grievances ADD COLUMN attendant_text VARCHAR(255) NULL AFTER attendant_id');
        }
    },
    'down' => function (\PDO $db): void {
        $stmt = $db->prepare('SHOW COLUMNS FROM grievances LIKE ?');
        $stmt->execute(['attendant_text']);
        if ($stmt->fetch(\PDO::FETCH_ASSOC)) {
            $db->exec('ALTER TABLE grievances DROP COLUMN attendant_text');
        }
    },
];
