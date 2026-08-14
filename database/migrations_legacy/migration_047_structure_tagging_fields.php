<?php
/**
 * Migration 047: Structure tagging / visit / GPS / classification fields.
 */
return [
    'name' => 'migration_047_structure_tagging_fields',
    'up' => function (\PDO $db): void {
        $add = static function (string $col, string $ddl) use ($db): void {
            $stmt = $db->prepare('SHOW COLUMNS FROM structures LIKE ?');
            $stmt->execute([$col]);
            if (!$stmt->fetch(\PDO::FETCH_ASSOC)) {
                $db->exec('ALTER TABLE structures ' . $ddl);
            }
        };
        $add('location_of_structure', 'ADD COLUMN location_of_structure VARCHAR(500) NULL AFTER structure_tag');
        $add('date_first_visit', 'ADD COLUMN date_first_visit DATE NULL AFTER location_of_structure');
        $add('remarks_first_visit', 'ADD COLUMN remarks_first_visit TEXT NULL AFTER date_first_visit');
        $add('date_second_visit', 'ADD COLUMN date_second_visit DATE NULL AFTER remarks_first_visit');
        $add('remarks_second_visit', 'ADD COLUMN remarks_second_visit TEXT NULL AFTER date_second_visit');
        $add('date_third_visit', 'ADD COLUMN date_third_visit DATE NULL AFTER remarks_second_visit');
        $add('remarks_third_visit', 'ADD COLUMN remarks_third_visit TEXT NULL AFTER date_third_visit');
        $add('structure_classification', 'ADD COLUMN structure_classification VARCHAR(20) NULL AFTER remarks_third_visit');
        $add('associated_primary_structure_id', 'ADD COLUMN associated_primary_structure_id INT NULL AFTER structure_classification');
        $add('actual_usage', 'ADD COLUMN actual_usage VARCHAR(500) NULL AFTER associated_primary_structure_id');
        $add('gps_latitude', 'ADD COLUMN gps_latitude DECIMAL(11,8) NULL AFTER actual_usage');
        $add('gps_longitude', 'ADD COLUMN gps_longitude DECIMAL(11,8) NULL AFTER gps_latitude');
        $add('tagging_status', 'ADD COLUMN tagging_status VARCHAR(40) NULL AFTER gps_longitude');
        $add('tagging_status_other', 'ADD COLUMN tagging_status_other VARCHAR(500) NULL AFTER tagging_status');
        $add('refusal_reason', 'ADD COLUMN refusal_reason TEXT NULL AFTER tagging_status_other');

        $idx = $db->query("SHOW INDEX FROM structures WHERE Key_name = 'idx_structures_assoc_primary'")->fetch(\PDO::FETCH_ASSOC);
        if (!$idx) {
            $db->exec('ALTER TABLE structures ADD INDEX idx_structures_assoc_primary (associated_primary_structure_id)');
        }
        $fk = $db->query("
            SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'structures' AND CONSTRAINT_NAME = 'fk_structures_assoc_primary'
        ")->fetchColumn();
        if (!$fk) {
            try {
                $db->exec('
                    ALTER TABLE structures
                    ADD CONSTRAINT fk_structures_assoc_primary
                    FOREIGN KEY (associated_primary_structure_id) REFERENCES structures(id) ON DELETE SET NULL
                ');
            } catch (\Throwable $e) {
                // Older engines / permissions: index only
            }
        }
    },
    'down' => function (\PDO $db): void {
        $dropFk = static function () use ($db): void {
            try {
                $db->exec('ALTER TABLE structures DROP FOREIGN KEY fk_structures_assoc_primary');
            } catch (\Throwable $e) {
                // ignore
            }
        };
        $dropFk();
        $cols = [
            'location_of_structure', 'date_first_visit', 'remarks_first_visit',
            'date_second_visit', 'remarks_second_visit', 'date_third_visit', 'remarks_third_visit',
            'structure_classification', 'associated_primary_structure_id', 'actual_usage',
            'gps_latitude', 'gps_longitude', 'tagging_status', 'tagging_status_other', 'refusal_reason',
        ];
        foreach ($cols as $col) {
            $stmt = $db->prepare('SHOW COLUMNS FROM structures LIKE ?');
            $stmt->execute([$col]);
            if ($stmt->fetch(\PDO::FETCH_ASSOC)) {
                try {
                    $db->exec("ALTER TABLE structures DROP COLUMN $col");
                } catch (\Throwable $e) {
                    // continue
                }
            }
        }
    },
];
