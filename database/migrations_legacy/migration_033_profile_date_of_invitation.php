<?php
/**
 * Migration 033: Add profiles.date_of_invitation (and repair earlier typo column if present).
 */
return [
    'name' => 'migration_033_profile_date_of_invitation',
    'up' => function (\PDO $db): void {
        $hasInvitation = false;
        $stmt = $db->prepare('SHOW COLUMNS FROM profiles LIKE ?');
        $stmt->execute(['date_of_invitation']);
        $hasInvitation = (bool) $stmt->fetch(\PDO::FETCH_ASSOC);

        $hasTypo = false;
        $stmt = $db->prepare('SHOW COLUMNS FROM profiles LIKE ?');
        $stmt->execute(['date_of_invidation']);
        $hasTypo = (bool) $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$hasInvitation && $hasTypo) {
            $db->exec('ALTER TABLE profiles CHANGE COLUMN date_of_invidation date_of_invitation DATE NULL AFTER birthday');
            return;
        }

        if (!$hasInvitation) {
            $db->exec('ALTER TABLE profiles ADD COLUMN date_of_invitation DATE NULL AFTER birthday');
        }
    },
    'down' => function (\PDO $db): void {
        $stmt = $db->prepare('SHOW COLUMNS FROM profiles LIKE ?');
        $stmt->execute(['date_of_invitation']);
        if ($stmt->fetch(\PDO::FETCH_ASSOC)) {
            $db->exec('ALTER TABLE profiles DROP COLUMN date_of_invitation');
        }
    },
];
