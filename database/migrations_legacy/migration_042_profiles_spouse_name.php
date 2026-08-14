<?php
/**
 * Migration 042: Spouse name on profiles (shown when structure ownership is Owner).
 */
return [
    'name' => 'migration_042_profiles_spouse_name',
    'up' => function (\PDO $db): void {
        $stmt = $db->prepare('SHOW COLUMNS FROM profiles LIKE ?');
        $stmt->execute(['spouse_name']);
        if (!$stmt->fetch(\PDO::FETCH_ASSOC)) {
            $db->exec('ALTER TABLE profiles ADD COLUMN spouse_name VARCHAR(255) NULL AFTER structure_ownership_type');
        }
    },
    'down' => function (\PDO $db): void {
        $stmt = $db->prepare('SHOW COLUMNS FROM profiles LIKE ?');
        $stmt->execute(['spouse_name']);
        if ($stmt->fetch(\PDO::FETCH_ASSOC)) {
            $db->exec('ALTER TABLE profiles DROP COLUMN spouse_name');
        }
    },
];
