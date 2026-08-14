<?php
/**
 * Migration 037: Remove profile questionnaire fields (relevant/additional information,
 * structure ownership types, HH income) — superseded until product needs them again.
 */
return [
    'name' => 'migration_037_remove_profile_questionnaire_fields',
    'up' => function (\PDO $db): void {
        $cols = [];
        $stmt = $db->query('SHOW COLUMNS FROM profiles');
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            if (!empty($row['Field'])) {
                $cols[$row['Field']] = true;
            }
        }
        $drop = array_values(array_filter([
            'structure_ownership_types',
            'residing_in_project_affected',
            'residing_in_project_affected_note',
            'residing_in_project_affected_attachments',
            'structure_owners',
            'structure_owners_note',
            'structure_owners_attachments',
            'if_not_structure_owner_what',
            'if_not_structure_owner_attachments',
            'own_property_elsewhere',
            'own_property_elsewhere_note',
            'own_property_elsewhere_attachments',
            'availed_government_housing',
            'availed_government_housing_note',
            'availed_government_housing_attachments',
            'hh_income',
        ], static fn (string $c) => isset($cols[$c])));
        if ($drop === []) {
            return;
        }
        $parts = array_map(static fn (string $c) => 'DROP COLUMN `' . str_replace('`', '``', $c) . '`', $drop);
        $db->exec('ALTER TABLE profiles ' . implode(', ', $parts));
    },
    'down' => function (\PDO $db): void {
        $db->exec("
            ALTER TABLE profiles
            ADD COLUMN residing_in_project_affected TINYINT(1) NOT NULL DEFAULT 0,
            ADD COLUMN residing_in_project_affected_note TEXT,
            ADD COLUMN residing_in_project_affected_attachments TEXT,
            ADD COLUMN structure_owners TINYINT(1) NOT NULL DEFAULT 0,
            ADD COLUMN structure_owners_note TEXT,
            ADD COLUMN structure_owners_attachments TEXT,
            ADD COLUMN if_not_structure_owner_what TEXT,
            ADD COLUMN if_not_structure_owner_attachments TEXT,
            ADD COLUMN own_property_elsewhere TINYINT(1) NOT NULL DEFAULT 0,
            ADD COLUMN own_property_elsewhere_note TEXT,
            ADD COLUMN own_property_elsewhere_attachments TEXT,
            ADD COLUMN availed_government_housing TINYINT(1) NOT NULL DEFAULT 0,
            ADD COLUMN availed_government_housing_note TEXT,
            ADD COLUMN availed_government_housing_attachments TEXT,
            ADD COLUMN hh_income DECIMAL(15,2) NULL
        ");
        $stmt = $db->prepare('SHOW COLUMNS FROM profiles LIKE ?');
        $stmt->execute(['structure_ownership_types']);
        if (!$stmt->fetch(\PDO::FETCH_ASSOC)) {
            $db->exec('ALTER TABLE profiles ADD COLUMN structure_ownership_types TEXT NULL AFTER structure_owners');
        }
    },
];
