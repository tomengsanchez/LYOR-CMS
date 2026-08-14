<?php
/**
 * Migration 045: Link structures created from a profile (e.g. non-owner) to that profile for project scope and listing.
 */
return [
    'name' => 'migration_045_structure_tagged_by_profile',
    'up' => function (\PDO $db): void {
        $stmt = $db->prepare('SHOW COLUMNS FROM structures LIKE ?');
        $stmt->execute(['tagged_by_profile_id']);
        if (!$stmt->fetch(\PDO::FETCH_ASSOC)) {
            $db->exec('ALTER TABLE structures ADD COLUMN tagged_by_profile_id INT NULL AFTER owner_id');
            $db->exec('ALTER TABLE structures ADD INDEX idx_tagged_by_profile (tagged_by_profile_id)');
            $db->exec('ALTER TABLE structures ADD CONSTRAINT fk_structures_tagged_by_profile FOREIGN KEY (tagged_by_profile_id) REFERENCES profiles(id) ON DELETE SET NULL');
        }
    },
    'down' => function (\PDO $db): void {
        $stmt = $db->prepare('SHOW COLUMNS FROM structures LIKE ?');
        $stmt->execute(['tagged_by_profile_id']);
        if ($stmt->fetch(\PDO::FETCH_ASSOC)) {
            $db->exec('ALTER TABLE structures DROP FOREIGN KEY fk_structures_tagged_by_profile');
            $db->exec('ALTER TABLE structures DROP INDEX idx_tagged_by_profile');
            $db->exec('ALTER TABLE structures DROP COLUMN tagged_by_profile_id');
        }
    },
];
