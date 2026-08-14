<?php
/**
 * Migration 031: Widen profiles.contact_number for denormalized multi-contact search text.
 */
return [
    'name' => 'migration_031_profile_contact_number_widen',
    'up' => function (\PDO $db): void {
        $db->exec('ALTER TABLE profiles MODIFY COLUMN contact_number VARCHAR(2000) NOT NULL DEFAULT \'\'');
    },
    'down' => function (\PDO $db): void {
        $db->exec('ALTER TABLE profiles MODIFY COLUMN contact_number VARCHAR(50) NOT NULL DEFAULT \'\'');
    },
];
