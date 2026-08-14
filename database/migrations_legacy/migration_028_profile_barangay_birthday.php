<?php
/**
 * Migration 028: Barangay (custom column) and birthday on profiles.
 */
return [
    'name' => 'migration_028_profile_barangay_birthday',
    'up' => function (\PDO $db): void {
        $colExists = static function (string $col) use ($db): bool {
            $stmt = $db->prepare('SHOW COLUMNS FROM profiles LIKE ?');
            $stmt->execute([$col]);
            return (bool) $stmt->fetch(\PDO::FETCH_ASSOC);
        };

        if (!$colExists('custom_barangay')) {
            $db->exec("ALTER TABLE profiles ADD COLUMN custom_barangay VARCHAR(255) NOT NULL DEFAULT '' AFTER last_name");
        }
        if (!$colExists('birthday')) {
            $db->exec('ALTER TABLE profiles ADD COLUMN birthday DATE NULL AFTER age');
        }
    },
    'down' => function (\PDO $db): void {
        $colExists = static function (string $col) use ($db): bool {
            $stmt = $db->prepare('SHOW COLUMNS FROM profiles LIKE ?');
            $stmt->execute([$col]);
            return (bool) $stmt->fetch(\PDO::FETCH_ASSOC);
        };

        if ($colExists('birthday')) {
            $db->exec('ALTER TABLE profiles DROP COLUMN birthday');
        }
        if ($colExists('custom_barangay')) {
            $db->exec('ALTER TABLE profiles DROP COLUMN custom_barangay');
        }
    },
];
