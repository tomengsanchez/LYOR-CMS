<?php
/**
 * Migration 061: Representative name (distinct from PAPS profile name).
 * Drops mistaken profiles.suffix from migration 060 when present.
 */
return [
    'name' => 'migration_061_profiles_representative_name',
    'up' => function (\PDO $db): void {
        $check = function (string $col) use ($db): bool {
            $stmt = $db->prepare('SHOW COLUMNS FROM profiles LIKE ?');
            $stmt->execute([$col]);

            return (bool) $stmt->fetch(\PDO::FETCH_ASSOC);
        };

        if (!$check('representative_last_name')) {
            $db->exec('ALTER TABLE profiles ADD COLUMN representative_last_name VARCHAR(255) NULL AFTER city_municipality');
        }
        if (!$check('representative_first_name')) {
            $db->exec('ALTER TABLE profiles ADD COLUMN representative_first_name VARCHAR(255) NULL AFTER representative_last_name');
        }
        if (!$check('representative_middle_name')) {
            $db->exec('ALTER TABLE profiles ADD COLUMN representative_middle_name VARCHAR(255) NULL AFTER representative_first_name');
        }
        if (!$check('representative_suffix')) {
            $db->exec('ALTER TABLE profiles ADD COLUMN representative_suffix VARCHAR(32) NULL AFTER representative_middle_name');
        }

        if ($check('suffix') && $check('representative_suffix')) {
            $db->exec(
                'UPDATE profiles SET representative_suffix = suffix
                 WHERE representative_suffix IS NULL AND suffix IS NOT NULL AND TRIM(suffix) <> \'\''
            );
            $db->exec('ALTER TABLE profiles DROP COLUMN suffix');
        }
    },
    'down' => function (\PDO $db): void {
        $check = function (string $col) use ($db): bool {
            $stmt = $db->prepare('SHOW COLUMNS FROM profiles LIKE ?');
            $stmt->execute([$col]);

            return (bool) $stmt->fetch(\PDO::FETCH_ASSOC);
        };

        if (!$check('suffix')) {
            $db->exec('ALTER TABLE profiles ADD COLUMN suffix VARCHAR(32) NULL AFTER middle_name');
        }
        if ($check('suffix') && $check('representative_suffix')) {
            $db->exec(
                'UPDATE profiles SET suffix = representative_suffix
                 WHERE suffix IS NULL AND representative_suffix IS NOT NULL AND TRIM(representative_suffix) <> \'\''
            );
        }

        foreach ([
            'representative_suffix',
            'representative_middle_name',
            'representative_first_name',
            'representative_last_name',
        ] as $col) {
            if ($check($col)) {
                $db->exec('ALTER TABLE profiles DROP COLUMN `' . str_replace('`', '``', $col) . '`');
            }
        }
    },
];
