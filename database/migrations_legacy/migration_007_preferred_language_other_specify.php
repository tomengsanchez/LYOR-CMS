<?php
/**
 * Migration 007: Add preferred_language_other_specify to grievances
 */
return [
    'name' => 'migration_007_preferred_language_other_specify',
    'up' => function (\PDO $db): void {
        $db->exec("ALTER TABLE grievances ADD COLUMN preferred_language_other_specify VARCHAR(255) DEFAULT '' AFTER preferred_language_ids");
    },
    'down' => function (\PDO $db): void {
        $db->exec('ALTER TABLE grievances DROP COLUMN preferred_language_other_specify');
    },
];
