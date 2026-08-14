<?php
/**
 * Migration 064: Invitation card fields (RSVP, reasons, visit personnel, distribution status).
 */
return [
    'name' => 'migration_064_profile_invitation_fields',
    'up' => function (\PDO $db): void {
        $check = function (string $col) use ($db): bool {
            $stmt = $db->prepare('SHOW COLUMNS FROM profiles LIKE ?');
            $stmt->execute([$col]);

            return (bool) $stmt->fetch(\PDO::FETCH_ASSOC);
        };
        $add = function (string $col, string $ddl) use ($check, $db): void {
            if (!$check($col)) {
                $db->exec($ddl);
            }
        };

        $after = 'representative_contact_number_3';
        $add('invitation_rsvp', "ALTER TABLE profiles ADD COLUMN invitation_rsvp VARCHAR(255) NULL AFTER {$after}");
        $add('invitation_reason_not_accepting', 'ALTER TABLE profiles ADD COLUMN invitation_reason_not_accepting VARCHAR(255) NULL AFTER invitation_rsvp');
        $add('invitation_reason_not_attending', 'ALTER TABLE profiles ADD COLUMN invitation_reason_not_attending VARCHAR(255) NULL AFTER invitation_reason_not_accepting');
        $add('invitation_specific_needs_specify', 'ALTER TABLE profiles ADD COLUMN invitation_specific_needs_specify VARCHAR(255) NULL AFTER invitation_reason_not_attending');
        $add('invitation_date_received_visit', 'ALTER TABLE profiles ADD COLUMN invitation_date_received_visit VARCHAR(32) NULL AFTER invitation_specific_needs_specify');
        $add('invitation_first_visit_name', 'ALTER TABLE profiles ADD COLUMN invitation_first_visit_name VARCHAR(255) NULL AFTER invitation_date_received_visit');
        $add('invitation_first_visit_position_org', 'ALTER TABLE profiles ADD COLUMN invitation_first_visit_position_org VARCHAR(255) NULL AFTER invitation_first_visit_name');
        $add('invitation_first_visit_name_2', 'ALTER TABLE profiles ADD COLUMN invitation_first_visit_name_2 VARCHAR(255) NULL AFTER invitation_first_visit_position_org');
        $add('invitation_first_visit_position_org_2', 'ALTER TABLE profiles ADD COLUMN invitation_first_visit_position_org_2 VARCHAR(255) NULL AFTER invitation_first_visit_name_2');
        $add('invitation_first_visit_date_of_invitation', 'ALTER TABLE profiles ADD COLUMN invitation_first_visit_date_of_invitation DATE NULL AFTER invitation_first_visit_position_org_2');
        $add('invitation_second_visit_name', 'ALTER TABLE profiles ADD COLUMN invitation_second_visit_name VARCHAR(255) NULL AFTER invitation_first_visit_date_of_invitation');
        $add('invitation_second_visit_position_org', 'ALTER TABLE profiles ADD COLUMN invitation_second_visit_position_org VARCHAR(255) NULL AFTER invitation_second_visit_name');
        $add('invitation_second_visit_name_2', 'ALTER TABLE profiles ADD COLUMN invitation_second_visit_name_2 VARCHAR(255) NULL AFTER invitation_second_visit_position_org');
        $add('invitation_second_visit_position_org_2', 'ALTER TABLE profiles ADD COLUMN invitation_second_visit_position_org_2 VARCHAR(255) NULL AFTER invitation_second_visit_name_2');
        $add('invitation_second_visit_date_of_invitation', 'ALTER TABLE profiles ADD COLUMN invitation_second_visit_date_of_invitation DATE NULL AFTER invitation_second_visit_position_org_2');
        $add('invitation_distribution_status', 'ALTER TABLE profiles ADD COLUMN invitation_distribution_status VARCHAR(100) NULL AFTER invitation_second_visit_date_of_invitation');
        $add('invitation_distribution_status_other', 'ALTER TABLE profiles ADD COLUMN invitation_distribution_status_other VARCHAR(255) NULL AFTER invitation_distribution_status');
        $add('invitation_first_visit_status', 'ALTER TABLE profiles ADD COLUMN invitation_first_visit_status VARCHAR(255) NULL AFTER invitation_distribution_status_other');
        $add('invitation_second_visit_status', 'ALTER TABLE profiles ADD COLUMN invitation_second_visit_status VARCHAR(255) NULL AFTER invitation_first_visit_status');
    },
    'down' => function (\PDO $db): void {
        foreach ([
            'invitation_second_visit_status',
            'invitation_first_visit_status',
            'invitation_distribution_status_other',
            'invitation_distribution_status',
            'invitation_second_visit_date_of_invitation',
            'invitation_second_visit_position_org_2',
            'invitation_second_visit_name_2',
            'invitation_second_visit_position_org',
            'invitation_second_visit_name',
            'invitation_first_visit_date_of_invitation',
            'invitation_first_visit_position_org_2',
            'invitation_first_visit_name_2',
            'invitation_first_visit_position_org',
            'invitation_first_visit_name',
            'invitation_date_received_visit',
            'invitation_specific_needs_specify',
            'invitation_reason_not_attending',
            'invitation_reason_not_accepting',
            'invitation_rsvp',
        ] as $col) {
            $stmt = $db->prepare('SHOW COLUMNS FROM profiles LIKE ?');
            $stmt->execute([$col]);
            if ($stmt->fetch(\PDO::FETCH_ASSOC)) {
                $db->exec('ALTER TABLE profiles DROP COLUMN `' . str_replace('`', '``', $col) . '`');
            }
        }
    },
];
