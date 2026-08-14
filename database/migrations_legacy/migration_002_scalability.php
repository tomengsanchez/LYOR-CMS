<?php
/**
 * Migration 002: Scalability improvements for millions of records
 *
 * - Add structure_count to profiles (denormalized for fast list)
 * - Add FULLTEXT indexes for search
 */
return [
    'name' => 'migration_002_scalability',
    'up' => function (\PDO $db): void {
        // 1. Add structure_count to profiles
        $db->exec('ALTER TABLE profiles ADD COLUMN structure_count INT NOT NULL DEFAULT 0 AFTER project_id');
        $db->exec('UPDATE profiles p SET p.structure_count = (SELECT COUNT(*) FROM structures s WHERE s.owner_id = p.id)');

        // 2. FULLTEXT indexes for search (requires MySQL 5.6+, InnoDB)
        $db->exec('ALTER TABLE profiles ADD FULLTEXT INDEX ft_profiles_search (full_name, papsid, control_number, contact_number)');
        $db->exec('ALTER TABLE structures ADD FULLTEXT INDEX ft_structures_search (strid, structure_tag, description)');
    },
    'down' => function (\PDO $db): void {
        $db->exec('ALTER TABLE profiles DROP INDEX ft_profiles_search');
        $db->exec('ALTER TABLE structures DROP INDEX ft_structures_search');
        $db->exec('ALTER TABLE profiles DROP COLUMN structure_count');
    },
];
