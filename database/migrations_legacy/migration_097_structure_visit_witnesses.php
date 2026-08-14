<?php
/**
 * Migration 097: Structure visitation witness fields (name / position-org / date × 2 per visit).
 * Keeps legacy date_first_visit / date_second_visit / date_third_visit (synced from witness 1 date on write).
 */
return [
    'name' => 'migration_097_structure_visit_witnesses',
    'up' => function (\PDO $db): void {
        $add = static function (string $col, string $ddl) use ($db): void {
            $stmt = $db->prepare('SHOW COLUMNS FROM structures LIKE ?');
            $stmt->execute([$col]);
            if (!$stmt->fetch(\PDO::FETCH_ASSOC)) {
                $db->exec('ALTER TABLE structures ' . $ddl);
            }
        };

        // After each visit's remarks, before the next visit date / classification.
        $add('first_visit_witness_1_name', 'ADD COLUMN first_visit_witness_1_name VARCHAR(255) NULL AFTER remarks_first_visit');
        $add('first_visit_witness_1_position_org', 'ADD COLUMN first_visit_witness_1_position_org VARCHAR(255) NULL AFTER first_visit_witness_1_name');
        $add('first_visit_witness_1_date', 'ADD COLUMN first_visit_witness_1_date DATE NULL AFTER first_visit_witness_1_position_org');
        $add('first_visit_witness_2_name', 'ADD COLUMN first_visit_witness_2_name VARCHAR(255) NULL AFTER first_visit_witness_1_date');
        $add('first_visit_witness_2_position_org', 'ADD COLUMN first_visit_witness_2_position_org VARCHAR(255) NULL AFTER first_visit_witness_2_name');
        $add('first_visit_witness_2_date', 'ADD COLUMN first_visit_witness_2_date DATE NULL AFTER first_visit_witness_2_position_org');

        $add('second_visit_witness_1_name', 'ADD COLUMN second_visit_witness_1_name VARCHAR(255) NULL AFTER remarks_second_visit');
        $add('second_visit_witness_1_position_org', 'ADD COLUMN second_visit_witness_1_position_org VARCHAR(255) NULL AFTER second_visit_witness_1_name');
        $add('second_visit_witness_1_date', 'ADD COLUMN second_visit_witness_1_date DATE NULL AFTER second_visit_witness_1_position_org');
        $add('second_visit_witness_2_name', 'ADD COLUMN second_visit_witness_2_name VARCHAR(255) NULL AFTER second_visit_witness_1_date');
        $add('second_visit_witness_2_position_org', 'ADD COLUMN second_visit_witness_2_position_org VARCHAR(255) NULL AFTER second_visit_witness_2_name');
        $add('second_visit_witness_2_date', 'ADD COLUMN second_visit_witness_2_date DATE NULL AFTER second_visit_witness_2_position_org');

        $add('third_visit_witness_1_name', 'ADD COLUMN third_visit_witness_1_name VARCHAR(255) NULL AFTER remarks_third_visit');
        $add('third_visit_witness_1_position_org', 'ADD COLUMN third_visit_witness_1_position_org VARCHAR(255) NULL AFTER third_visit_witness_1_name');
        $add('third_visit_witness_1_date', 'ADD COLUMN third_visit_witness_1_date DATE NULL AFTER third_visit_witness_1_position_org');
        $add('third_visit_witness_2_name', 'ADD COLUMN third_visit_witness_2_name VARCHAR(255) NULL AFTER third_visit_witness_1_date');
        $add('third_visit_witness_2_position_org', 'ADD COLUMN third_visit_witness_2_position_org VARCHAR(255) NULL AFTER third_visit_witness_2_name');
        $add('third_visit_witness_2_date', 'ADD COLUMN third_visit_witness_2_date DATE NULL AFTER third_visit_witness_2_position_org');
    },
    'down' => function (\PDO $db): void {
        $cols = [
            'first_visit_witness_1_name',
            'first_visit_witness_1_position_org',
            'first_visit_witness_1_date',
            'first_visit_witness_2_name',
            'first_visit_witness_2_position_org',
            'first_visit_witness_2_date',
            'second_visit_witness_1_name',
            'second_visit_witness_1_position_org',
            'second_visit_witness_1_date',
            'second_visit_witness_2_name',
            'second_visit_witness_2_position_org',
            'second_visit_witness_2_date',
            'third_visit_witness_1_name',
            'third_visit_witness_1_position_org',
            'third_visit_witness_1_date',
            'third_visit_witness_2_name',
            'third_visit_witness_2_position_org',
            'third_visit_witness_2_date',
        ];
        foreach ($cols as $col) {
            $stmt = $db->prepare('SHOW COLUMNS FROM structures LIKE ?');
            $stmt->execute([$col]);
            if ($stmt->fetch(\PDO::FETCH_ASSOC)) {
                try {
                    $db->exec('ALTER TABLE structures DROP COLUMN `' . $col . '`');
                } catch (\Throwable $e) {
                    // continue
                }
            }
        }
    },
];
