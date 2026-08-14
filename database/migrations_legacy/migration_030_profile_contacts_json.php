<?php
/**
 * Migration 030: Multiple named contact numbers per profile (JSON + denormalized search column).
 */
return [
    'name' => 'migration_030_profile_contacts_json',
    'up' => function (\PDO $db): void {
        $stmt = $db->prepare('SHOW COLUMNS FROM profiles LIKE ?');
        $stmt->execute(['contacts_json']);
        if (!$stmt->fetch(\PDO::FETCH_ASSOC)) {
            $db->exec('ALTER TABLE profiles ADD COLUMN contacts_json TEXT NULL AFTER contact_number');
        }

        $sel = $db->query('SELECT id, contact_number FROM profiles');
        while ($row = $sel->fetch(\PDO::FETCH_ASSOC)) {
            $id = (int) ($row['id'] ?? 0);
            $cn = trim((string) ($row['contact_number'] ?? ''));
            if ($id <= 0 || $cn === '') {
                continue;
            }
            $json = json_encode([['person' => '', 'number' => $cn]], JSON_UNESCAPED_UNICODE);
            $u = $db->prepare('UPDATE profiles SET contacts_json = ? WHERE id = ? AND (contacts_json IS NULL OR contacts_json = \'\' OR contacts_json = \'[]\')');
            $u->execute([$json, $id]);
        }
    },
    'down' => function (\PDO $db): void {
        $stmt = $db->prepare('SHOW COLUMNS FROM profiles LIKE ?');
        $stmt->execute(['contacts_json']);
        if ($stmt->fetch(\PDO::FETCH_ASSOC)) {
            $db->exec('ALTER TABLE profiles DROP COLUMN contacts_json');
        }
    },
];
