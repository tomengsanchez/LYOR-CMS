<?php
/**
 * Migration 006: Add respondent_type_other_specify to grievances
 */
return [
    'name' => 'migration_006_respondent_type_other_specify',
    'up' => function (\PDO $db): void {
        $db->exec("ALTER TABLE grievances ADD COLUMN respondent_type_other_specify VARCHAR(255) DEFAULT '' AFTER respondent_type_ids");
    },
    'down' => function (\PDO $db): void {
        $db->exec('ALTER TABLE grievances DROP COLUMN respondent_type_other_specify');
    },
];
