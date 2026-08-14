<?php
/**
 * Migration 048: Three visit date + remarks pairs on profiles (field engagement).
 */
return [
    'name' => 'migration_048_profile_three_visits',
    'up' => function (\PDO $db): void {
        $check = function (string $col) use ($db): bool {
            $stmt = $db->prepare('SHOW COLUMNS FROM profiles LIKE ?');
            $stmt->execute([$col]);

            return (bool) $stmt->fetch(\PDO::FETCH_ASSOC);
        };
        $add = function (string $sql) use ($db, $check): void {
            preg_match('/ADD COLUMN\s+`?([a-zA-Z0-9_]+)`?/i', $sql, $m);
            $col = $m[1] ?? '';
            if ($col !== '' && !$check($col)) {
                $db->exec($sql);
            }
        };
        $add('ALTER TABLE profiles ADD COLUMN visit_1_date DATE NULL AFTER date_of_invitation');
        $add('ALTER TABLE profiles ADD COLUMN visit_1_remarks TEXT NULL AFTER visit_1_date');
        $add('ALTER TABLE profiles ADD COLUMN visit_2_date DATE NULL AFTER visit_1_remarks');
        $add('ALTER TABLE profiles ADD COLUMN visit_2_remarks TEXT NULL AFTER visit_2_date');
        $add('ALTER TABLE profiles ADD COLUMN visit_3_date DATE NULL AFTER visit_2_remarks');
        $add('ALTER TABLE profiles ADD COLUMN visit_3_remarks TEXT NULL AFTER visit_3_date');
    },
    'down' => function (\PDO $db): void {
        foreach (['visit_3_remarks', 'visit_3_date', 'visit_2_remarks', 'visit_2_date', 'visit_1_remarks', 'visit_1_date'] as $col) {
            $stmt = $db->prepare('SHOW COLUMNS FROM profiles LIKE ?');
            $stmt->execute([$col]);
            if ($stmt->fetch(\PDO::FETCH_ASSOC)) {
                $db->exec('ALTER TABLE profiles DROP COLUMN `' . str_replace('`', '``', $col) . '`');
            }
        }
    },
];
