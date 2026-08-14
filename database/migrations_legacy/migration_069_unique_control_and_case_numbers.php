<?php
/**
 * Migration 069: Unique active control numbers and grievance case numbers.
 */
return [
    'name' => 'migration_069_unique_control_and_case_numbers',
    'up' => function (\PDO $db): void {
        $dedupe = static function (\PDO $db, string $table, string $column, string $deletedClause): void {
            $sql = "SELECT {$column}, MIN(id) AS keep_id
                    FROM {$table}
                    WHERE {$deletedClause} AND {$column} IS NOT NULL AND TRIM({$column}) <> ''
                    GROUP BY {$column}
                    HAVING COUNT(*) > 1";
            foreach ($db->query($sql)->fetchAll(\PDO::FETCH_ASSOC) as $row) {
                $value = $row[$column];
                $keepId = (int) $row['keep_id'];
                $stmt = $db->prepare(
                    "UPDATE {$table} SET {$column} = CONCAT({$column}, '-dup-', id)
                     WHERE {$deletedClause} AND {$column} = ? AND id <> ?"
                );
                $stmt->execute([$value, $keepId]);
            }
        };

        $hasIndex = static function (\PDO $db, string $table, string $name): bool {
            $stmt = $db->prepare("SHOW INDEX FROM {$table} WHERE Key_name = ?");
            $stmt->execute([$name]);

            return (bool) $stmt->fetch(\PDO::FETCH_ASSOC);
        };

        $hasColumn = static function (\PDO $db, string $table, string $name): bool {
            $stmt = $db->prepare("SHOW COLUMNS FROM {$table} LIKE ?");
            $stmt->execute([$name]);

            return (bool) $stmt->fetch(\PDO::FETCH_ASSOC);
        };

        $db->exec("UPDATE profiles SET control_number = NULL WHERE control_number IS NOT NULL AND TRIM(control_number) = ''");
        $db->exec(
            "UPDATE grievances SET grievance_case_number = NULL
             WHERE is_deleted = 0 AND grievance_case_number IS NOT NULL AND TRIM(grievance_case_number) = ''"
        );

        $dedupe($db, 'profiles', 'control_number', 'is_deleted = 0');
        $dedupe($db, 'grievances', 'grievance_case_number', 'is_deleted = 0');

        if (!$hasColumn($db, 'profiles', 'control_number_unique')) {
            $db->exec("ALTER TABLE profiles ADD COLUMN control_number_unique VARCHAR(100)
                GENERATED ALWAYS AS (
                    IF(
                        is_deleted = 0
                        AND control_number IS NOT NULL
                        AND CHAR_LENGTH(TRIM(control_number)) > 0,
                        TRIM(control_number),
                        NULL
                    )
                ) STORED");
        }
        if (!$hasIndex($db, 'profiles', 'uk_profiles_control_number_active')) {
            $db->exec('ALTER TABLE profiles ADD UNIQUE INDEX uk_profiles_control_number_active (control_number_unique)');
        }

        if (!$hasColumn($db, 'grievances', 'grievance_case_number_unique')) {
            $db->exec("ALTER TABLE grievances ADD COLUMN grievance_case_number_unique VARCHAR(100)
                GENERATED ALWAYS AS (
                    IF(
                        is_deleted = 0
                        AND grievance_case_number IS NOT NULL
                        AND CHAR_LENGTH(TRIM(grievance_case_number)) > 0,
                        TRIM(grievance_case_number),
                        NULL
                    )
                ) STORED");
        }
        if (!$hasIndex($db, 'grievances', 'uk_grievance_case_number_active')) {
            $db->exec('ALTER TABLE grievances ADD UNIQUE INDEX uk_grievance_case_number_active (grievance_case_number_unique)');
        }
    },
    'down' => function (\PDO $db): void {
        $hasIndex = static function (\PDO $db, string $table, string $name): bool {
            $stmt = $db->prepare("SHOW INDEX FROM {$table} WHERE Key_name = ?");
            $stmt->execute([$name]);

            return (bool) $stmt->fetch(\PDO::FETCH_ASSOC);
        };
        $hasColumn = static function (\PDO $db, string $table, string $name): bool {
            $stmt = $db->prepare("SHOW COLUMNS FROM {$table} LIKE ?");
            $stmt->execute([$name]);

            return (bool) $stmt->fetch(\PDO::FETCH_ASSOC);
        };

        if ($hasIndex($db, 'grievances', 'uk_grievance_case_number_active')) {
            $db->exec('ALTER TABLE grievances DROP INDEX uk_grievance_case_number_active');
        }
        if ($hasColumn($db, 'grievances', 'grievance_case_number_unique')) {
            $db->exec('ALTER TABLE grievances DROP COLUMN grievance_case_number_unique');
        }
        if ($hasIndex($db, 'profiles', 'uk_profiles_control_number_active')) {
            $db->exec('ALTER TABLE profiles DROP INDEX uk_profiles_control_number_active');
        }
        if ($hasColumn($db, 'profiles', 'control_number_unique')) {
            $db->exec('ALTER TABLE profiles DROP COLUMN control_number_unique');
        }
    },
];
