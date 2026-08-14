<?php
/**
 * Migration 079: Many structure tags per profile (junction table).
 * Migrates legacy profiles.profile_structure_tag into profile_structure_tags.
 */
return [
    'name' => 'migration_079_profile_structure_tags',
    'up' => function (\PDO $db): void {
        $db->exec('
            CREATE TABLE IF NOT EXISTS profile_structure_tags (
                profile_id INT NOT NULL,
                structure_tag VARCHAR(255) NOT NULL,
                sort_order INT NOT NULL DEFAULT 0,
                PRIMARY KEY (profile_id, structure_tag),
                INDEX idx_profile_structure_tags_tag (structure_tag),
                CONSTRAINT fk_profile_structure_tags_profile
                    FOREIGN KEY (profile_id) REFERENCES profiles(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ');

        $db->exec('
            INSERT IGNORE INTO profile_structure_tags (profile_id, structure_tag, sort_order)
            SELECT p.id, TRIM(p.profile_structure_tag), 0
            FROM profiles p
            WHERE TRIM(COALESCE(p.profile_structure_tag, \'\')) <> \'\'
        ');
    },
    'down' => function (\PDO $db): void {
        $db->exec('DROP TABLE IF EXISTS profile_structure_tags');
    },
];
