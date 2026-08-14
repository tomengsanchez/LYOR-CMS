<?php
/**
 * Migration 041: Single-select structure ownership type on profiles (Owner, Renter, etc.).
 */
return [
    'name' => 'migration_041_profile_structure_ownership_type',
    'up' => function (\PDO $db): void {
        $stmt = $db->prepare('SHOW COLUMNS FROM profiles LIKE ?');
        $stmt->execute(['structure_ownership_type']);
        if (!$stmt->fetch(\PDO::FETCH_ASSOC)) {
            $db->exec('ALTER TABLE profiles ADD COLUMN structure_ownership_type VARCHAR(40) NULL AFTER contacts_json');
        }
    },
    'down' => function (\PDO $db): void {
        $stmt = $db->prepare('SHOW COLUMNS FROM profiles LIKE ?');
        $stmt->execute(['structure_ownership_type']);
        if ($stmt->fetch(\PDO::FETCH_ASSOC)) {
            $db->exec('ALTER TABLE profiles DROP COLUMN structure_ownership_type');
        }
    },
];
