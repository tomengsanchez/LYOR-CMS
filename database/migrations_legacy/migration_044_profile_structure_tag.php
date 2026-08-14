<?php
/**
 * Migration 044: Structure Tag # on profile (links to Structure module; may auto-create structure row).
 */
return [
    'name' => 'migration_044_profile_structure_tag',
    'up' => function (\PDO $db): void {
        $stmt = $db->prepare('SHOW COLUMNS FROM profiles LIKE ?');
        $stmt->execute(['profile_structure_tag']);
        if (!$stmt->fetch(\PDO::FETCH_ASSOC)) {
            $db->exec('ALTER TABLE profiles ADD COLUMN profile_structure_tag VARCHAR(255) NULL AFTER control_number');
        }
    },
    'down' => function (\PDO $db): void {
        $stmt = $db->prepare('SHOW COLUMNS FROM profiles LIKE ?');
        $stmt->execute(['profile_structure_tag']);
        if ($stmt->fetch(\PDO::FETCH_ASSOC)) {
            $db->exec('ALTER TABLE profiles DROP COLUMN profile_structure_tag');
        }
    },
];
