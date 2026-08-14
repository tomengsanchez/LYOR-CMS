<?php
/**
 * Migration 059: Add household and location text fields to profiles.
 */
return [
    'name' => 'migration_059_profile_household_location_fields',
    'up' => function (\PDO $db): void {
        $check = function (string $col) use ($db): bool {
            $stmt = $db->prepare('SHOW COLUMNS FROM profiles LIKE ?');
            $stmt->execute([$col]);

            return (bool) $stmt->fetch(\PDO::FETCH_ASSOC);
        };

        if (!$check('total_household')) {
            $db->exec('ALTER TABLE profiles ADD COLUMN total_household INT NULL AFTER spouse_name');
        }
        if (!$check('household_number')) {
            $db->exec('ALTER TABLE profiles ADD COLUMN household_number VARCHAR(255) NULL AFTER total_household');
        }
        if (!$check('structure_number')) {
            $db->exec('ALTER TABLE profiles ADD COLUMN structure_number VARCHAR(255) NULL AFTER household_number');
        }
        if (!$check('house_number')) {
            $db->exec('ALTER TABLE profiles ADD COLUMN house_number VARCHAR(255) NULL AFTER structure_number');
        }
        if (!$check('building_number')) {
            $db->exec('ALTER TABLE profiles ADD COLUMN building_number VARCHAR(255) NULL AFTER house_number');
        }
        if (!$check('lot_block_number')) {
            $db->exec('ALTER TABLE profiles ADD COLUMN lot_block_number VARCHAR(255) NULL AFTER building_number');
        }
        if (!$check('street_name')) {
            $db->exec('ALTER TABLE profiles ADD COLUMN street_name VARCHAR(255) NULL AFTER lot_block_number');
        }
        if (!$check('village_subd')) {
            $db->exec('ALTER TABLE profiles ADD COLUMN village_subd VARCHAR(255) NULL AFTER street_name');
        }
        if (!$check('barangay_text')) {
            $db->exec('ALTER TABLE profiles ADD COLUMN barangay_text VARCHAR(255) NULL AFTER village_subd');
        }
        if (!$check('city_municipality')) {
            $db->exec('ALTER TABLE profiles ADD COLUMN city_municipality VARCHAR(255) NULL AFTER barangay_text');
        }
    },
    'down' => function (\PDO $db): void {
        foreach ([
            'city_municipality',
            'barangay_text',
            'village_subd',
            'street_name',
            'lot_block_number',
            'building_number',
            'house_number',
            'structure_number',
            'household_number',
            'total_household',
        ] as $col) {
            $stmt = $db->prepare('SHOW COLUMNS FROM profiles LIKE ?');
            $stmt->execute([$col]);
            if ($stmt->fetch(\PDO::FETCH_ASSOC)) {
                $db->exec('ALTER TABLE profiles DROP COLUMN `' . str_replace('`', '``', $col) . '`');
            }
        }
    },
];
