<?php
/**
 * Migration 093: Structure PN number (optional text after location of structure).
 */
return [
    'name' => 'migration_093_structure_pn_number',
    'up' => function (\PDO $db): void {
        $stmt = $db->prepare('SHOW COLUMNS FROM structures LIKE ?');
        $stmt->execute(['pn_number']);
        if (!$stmt->fetch(\PDO::FETCH_ASSOC)) {
            $db->exec('ALTER TABLE structures ADD COLUMN pn_number VARCHAR(255) NULL AFTER location_of_structure');
        }
    },
    'down' => function (\PDO $db): void {
        $stmt = $db->prepare('SHOW COLUMNS FROM structures LIKE ?');
        $stmt->execute(['pn_number']);
        if ($stmt->fetch(\PDO::FETCH_ASSOC)) {
            $db->exec('ALTER TABLE structures DROP COLUMN `pn_number`');
        }
    },
];
