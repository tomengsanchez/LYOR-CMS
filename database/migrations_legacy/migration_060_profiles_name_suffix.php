<?php
/**
 * Migration 060: Name suffix on profiles (e.g. Jr., Sr., III).
 */
return [
    'name' => 'migration_060_profiles_name_suffix',
    'up' => function (\PDO $db): void {
        $stmt = $db->prepare('SHOW COLUMNS FROM profiles LIKE ?');
        $stmt->execute(['suffix']);
        if (!$stmt->fetch(\PDO::FETCH_ASSOC)) {
            $db->exec('ALTER TABLE profiles ADD COLUMN suffix VARCHAR(32) NULL AFTER middle_name');
        }
    },
    'down' => function (\PDO $db): void {
        $stmt = $db->prepare('SHOW COLUMNS FROM profiles LIKE ?');
        $stmt->execute(['suffix']);
        if ($stmt->fetch(\PDO::FETCH_ASSOC)) {
            $db->exec('ALTER TABLE profiles DROP COLUMN suffix');
        }
    },
];
