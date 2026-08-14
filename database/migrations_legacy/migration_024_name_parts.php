<?php
/**
 * Migration 024: Split full names into first/middle/last parts.
 */
return [
    'name' => 'migration_024_name_parts',
    'up' => function (\PDO $db): void {
        // Profiles: add name parts
        $db->exec("ALTER TABLE profiles ADD COLUMN first_name VARCHAR(120) NOT NULL DEFAULT '' AFTER control_number");
        $db->exec("ALTER TABLE profiles ADD COLUMN middle_name VARCHAR(120) NOT NULL DEFAULT '' AFTER first_name");
        $db->exec("ALTER TABLE profiles ADD COLUMN last_name VARCHAR(120) NOT NULL DEFAULT '' AFTER middle_name");

        // Grievances: add respondent name parts
        $db->exec("ALTER TABLE grievances ADD COLUMN respondent_first_name VARCHAR(120) NOT NULL DEFAULT '' AFTER profile_id");
        $db->exec("ALTER TABLE grievances ADD COLUMN respondent_middle_name VARCHAR(120) NOT NULL DEFAULT '' AFTER respondent_first_name");
        $db->exec("ALTER TABLE grievances ADD COLUMN respondent_last_name VARCHAR(120) NOT NULL DEFAULT '' AFTER respondent_middle_name");

        // Best-effort backfill from current full_name / respondent_full_name for existing rows.
        // Keeps compatibility if there is pre-existing data.
        $db->exec("
            UPDATE profiles
            SET
                first_name = TRIM(SUBSTRING_INDEX(full_name, ' ', 1)),
                last_name = TRIM(
                    CASE
                        WHEN INSTR(TRIM(full_name), ' ') > 0 THEN SUBSTRING_INDEX(TRIM(full_name), ' ', -1)
                        ELSE ''
                    END
                ),
                middle_name = TRIM(
                    CASE
                        WHEN (LENGTH(TRIM(full_name)) - LENGTH(REPLACE(TRIM(full_name), ' ', ''))) >= 2
                            THEN TRIM(SUBSTRING(TRIM(full_name), LENGTH(SUBSTRING_INDEX(TRIM(full_name), ' ', 1)) + 2, LENGTH(TRIM(full_name)) - LENGTH(SUBSTRING_INDEX(TRIM(full_name), ' ', -1)) - LENGTH(SUBSTRING_INDEX(TRIM(full_name), ' ', 1)) - 2))
                        ELSE ''
                    END
                )
            WHERE TRIM(COALESCE(full_name, '')) <> ''
        ");
        $db->exec("
            UPDATE grievances
            SET
                respondent_first_name = TRIM(SUBSTRING_INDEX(respondent_full_name, ' ', 1)),
                respondent_last_name = TRIM(
                    CASE
                        WHEN INSTR(TRIM(respondent_full_name), ' ') > 0 THEN SUBSTRING_INDEX(TRIM(respondent_full_name), ' ', -1)
                        ELSE ''
                    END
                ),
                respondent_middle_name = TRIM(
                    CASE
                        WHEN (LENGTH(TRIM(respondent_full_name)) - LENGTH(REPLACE(TRIM(respondent_full_name), ' ', ''))) >= 2
                            THEN TRIM(SUBSTRING(TRIM(respondent_full_name), LENGTH(SUBSTRING_INDEX(TRIM(respondent_full_name), ' ', 1)) + 2, LENGTH(TRIM(respondent_full_name)) - LENGTH(SUBSTRING_INDEX(TRIM(respondent_full_name), ' ', -1)) - LENGTH(SUBSTRING_INDEX(TRIM(respondent_full_name), ' ', 1)) - 2))
                        ELSE ''
                    END
                )
            WHERE TRIM(COALESCE(respondent_full_name, '')) <> ''
        ");
    },
    'down' => null,
];
