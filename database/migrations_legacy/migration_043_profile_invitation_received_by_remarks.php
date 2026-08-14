<?php
/**
 * Migration 043: Invitation received by and remarks on profiles.
 */
return [
    'name' => 'migration_043_profile_invitation_received_by_remarks',
    'up' => function (\PDO $db): void {
        $check = function (string $col) use ($db): bool {
            $stmt = $db->prepare('SHOW COLUMNS FROM profiles LIKE ?');
            $stmt->execute([$col]);

            return (bool) $stmt->fetch(\PDO::FETCH_ASSOC);
        };
        if (!$check('invitation_received_by')) {
            $db->exec('ALTER TABLE profiles ADD COLUMN invitation_received_by VARCHAR(255) NULL AFTER spouse_name');
        }
        if (!$check('remarks')) {
            $db->exec('ALTER TABLE profiles ADD COLUMN remarks TEXT NULL AFTER invitation_received_by');
        }
    },
    'down' => function (\PDO $db): void {
        foreach (['remarks', 'invitation_received_by'] as $col) {
            $stmt = $db->prepare('SHOW COLUMNS FROM profiles LIKE ?');
            $stmt->execute([$col]);
            if ($stmt->fetch(\PDO::FETCH_ASSOC)) {
                $db->exec('ALTER TABLE profiles DROP COLUMN `' . str_replace('`', '``', $col) . '`');
            }
        }
    },
];
