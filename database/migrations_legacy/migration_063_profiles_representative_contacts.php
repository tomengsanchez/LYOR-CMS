<?php
/**
 * Migration 063: Three contact numbers on representative information.
 */
return [
    'name' => 'migration_063_profiles_representative_contacts',
    'up' => function (\PDO $db): void {
        $check = function (string $col) use ($db): bool {
            $stmt = $db->prepare('SHOW COLUMNS FROM profiles LIKE ?');
            $stmt->execute([$col]);

            return (bool) $stmt->fetch(\PDO::FETCH_ASSOC);
        };

        if (!$check('representative_contact_number_1')) {
            $db->exec('ALTER TABLE profiles ADD COLUMN representative_contact_number_1 VARCHAR(64) NULL AFTER representative_suffix');
        }
        if (!$check('representative_contact_number_2')) {
            $db->exec('ALTER TABLE profiles ADD COLUMN representative_contact_number_2 VARCHAR(64) NULL AFTER representative_contact_number_1');
        }
        if (!$check('representative_contact_number_3')) {
            $db->exec('ALTER TABLE profiles ADD COLUMN representative_contact_number_3 VARCHAR(64) NULL AFTER representative_contact_number_2');
        }
    },
    'down' => function (\PDO $db): void {
        foreach ([
            'representative_contact_number_3',
            'representative_contact_number_2',
            'representative_contact_number_1',
        ] as $col) {
            $stmt = $db->prepare('SHOW COLUMNS FROM profiles LIKE ?');
            $stmt->execute([$col]);
            if ($stmt->fetch(\PDO::FETCH_ASSOC)) {
                $db->exec('ALTER TABLE profiles DROP COLUMN `' . str_replace('`', '``', $col) . '`');
            }
        }
    },
];
