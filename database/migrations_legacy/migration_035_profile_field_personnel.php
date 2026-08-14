<?php
/**
 * Migration 035: Add profiles.field_personnel_id (linked user for Field Personnel).
 */
return [
    'name' => 'migration_035_profile_field_personnel',
    'up' => function (\PDO $db): void {
        $stmt = $db->prepare('SHOW COLUMNS FROM profiles LIKE ?');
        $stmt->execute(['field_personnel_id']);
        if (!$stmt->fetch(\PDO::FETCH_ASSOC)) {
            $db->exec('ALTER TABLE profiles ADD COLUMN field_personnel_id INT NULL AFTER project_id');
            $db->exec('ALTER TABLE profiles ADD INDEX idx_profiles_field_personnel_id (field_personnel_id)');
            $db->exec('ALTER TABLE profiles ADD CONSTRAINT fk_profiles_field_personnel FOREIGN KEY (field_personnel_id) REFERENCES users(id) ON DELETE SET NULL');
        }
    },
    'down' => function (\PDO $db): void {
        $stmt = $db->prepare('SHOW COLUMNS FROM profiles LIKE ?');
        $stmt->execute(['field_personnel_id']);
        if ($stmt->fetch(\PDO::FETCH_ASSOC)) {
            $db->exec('ALTER TABLE profiles DROP FOREIGN KEY fk_profiles_field_personnel');
            $db->exec('ALTER TABLE profiles DROP INDEX idx_profiles_field_personnel_id');
            $db->exec('ALTER TABLE profiles DROP COLUMN field_personnel_id');
        }
    },
];
