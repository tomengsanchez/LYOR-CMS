<?php
/**
 * Migration 032: Add profile structure ownership types (multi-select JSON).
 *
 * Historical: column dropped by migration_037_remove_profile_questionnaire_fields.php on current schemas.
 */
return [
    'name' => 'migration_032_profile_structure_ownership_types',
    'up' => function (\PDO $db): void {
        $stmt = $db->prepare('SHOW COLUMNS FROM profiles LIKE ?');
        $stmt->execute(['structure_ownership_types']);
        if (!$stmt->fetch(\PDO::FETCH_ASSOC)) {
            $db->exec("ALTER TABLE profiles ADD COLUMN structure_ownership_types TEXT NULL AFTER structure_owners");
        }
    },
    'down' => function (\PDO $db): void {
        $stmt = $db->prepare('SHOW COLUMNS FROM profiles LIKE ?');
        $stmt->execute(['structure_ownership_types']);
        if ($stmt->fetch(\PDO::FETCH_ASSOC)) {
            $db->exec('ALTER TABLE profiles DROP COLUMN structure_ownership_types');
        }
    },
];
