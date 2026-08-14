<?php
/**
 * Migration 003: Add Relevant Information and Additional Information fields to profiles
 *
 * Historical: these columns are removed on current databases by migration_037_remove_profile_questionnaire_fields.php.
 * The file remains so migration order is unchanged for existing installs.
 */
return [
    'name' => 'migration_003_profile_fields',
    'up' => function (\PDO $db): void {
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
    },
    'down' => function (\PDO $db): void {
        $db->exec("
            ALTER TABLE profiles
            DROP COLUMN residing_in_project_affected,
            DROP COLUMN residing_in_project_affected_note,
            DROP COLUMN residing_in_project_affected_attachments,
            DROP COLUMN structure_owners,
            DROP COLUMN structure_owners_note,
            DROP COLUMN structure_owners_attachments,
            DROP COLUMN if_not_structure_owner_what,
            DROP COLUMN if_not_structure_owner_attachments,
            DROP COLUMN own_property_elsewhere,
            DROP COLUMN own_property_elsewhere_note,
            DROP COLUMN own_property_elsewhere_attachments,
            DROP COLUMN availed_government_housing,
            DROP COLUMN availed_government_housing_note,
            DROP COLUMN availed_government_housing_attachments,
            DROP COLUMN hh_income
        ");
    },
];
