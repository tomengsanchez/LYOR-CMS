<?php
/**
 * Migration 057: Central contacts table (profile PAPS + users) with backfill from profiles.contacts_json.
 */
return [
    'name' => 'migration_057_contacts_table',
    'up' => function (\PDO $db): void {
        $db->exec("
            CREATE TABLE IF NOT EXISTS contacts (
                id INT AUTO_INCREMENT PRIMARY KEY,
                entity_type VARCHAR(20) NOT NULL,
                entity_id INT NOT NULL,
                person_label VARCHAR(120) NOT NULL DEFAULT '',
                number VARCHAR(50) NOT NULL DEFAULT '',
                sort_order INT NOT NULL DEFAULT 0,
                is_primary TINYINT(1) NOT NULL DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_contacts_entity (entity_type, entity_id),
                INDEX idx_contacts_number (number)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $sel = $db->query('SELECT id, contacts_json, contact_number FROM profiles');
        $ins = $db->prepare('
            INSERT INTO contacts (entity_type, entity_id, person_label, number, sort_order, is_primary)
            VALUES (\'profile\', ?, ?, ?, ?, ?)
        ');
        while ($row = $sel->fetch(\PDO::FETCH_ASSOC)) {
            $profileId = (int) ($row['id'] ?? 0);
            if ($profileId <= 0) {
                continue;
            }
            $parsed = \App\Models\Profile::parseContacts($row['contacts_json'] ?? null);
            if ($parsed === [] && trim((string) ($row['contact_number'] ?? '')) !== '') {
                $parsed = [['person' => '', 'number' => trim((string) $row['contact_number'])]];
            }
            $order = 0;
            foreach ($parsed as $item) {
                $person = trim((string) ($item['person'] ?? ''));
                $number = trim((string) ($item['number'] ?? ''));
                if ($person === '' && $number === '') {
                    continue;
                }
                $ins->execute([
                    $profileId,
                    $person,
                    $number,
                    $order,
                    $order === 0 ? 1 : 0,
                ]);
                $order++;
            }
        }
    },
    'down' => function (\PDO $db): void {
        $db->exec('DROP TABLE IF EXISTS contacts');
    },
];
