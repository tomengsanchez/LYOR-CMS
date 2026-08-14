<?php
/**
 * Migration 065: Preserve original GPS coordinate text (N15°58.209, E120°9.687) alongside decimals.
 */
return [
    'name' => 'migration_065_structure_gps_text',
    'up' => function (\PDO $db): void {
        $add = static function (string $col, string $ddl) use ($db): void {
            $stmt = $db->prepare('SHOW COLUMNS FROM structures LIKE ?');
            $stmt->execute([$col]);
            if (!$stmt->fetch(\PDO::FETCH_ASSOC)) {
                $db->exec('ALTER TABLE structures ' . $ddl);
            }
        };
        $add('gps_latitude_text', 'ADD COLUMN gps_latitude_text VARCHAR(64) NULL AFTER gps_longitude');
        $add('gps_longitude_text', 'ADD COLUMN gps_longitude_text VARCHAR(64) NULL AFTER gps_latitude_text');
    },
    'down' => function (\PDO $db): void {
        foreach (['gps_longitude_text', 'gps_latitude_text'] as $col) {
            $stmt = $db->prepare('SHOW COLUMNS FROM structures LIKE ?');
            $stmt->execute([$col]);
            if ($stmt->fetch(\PDO::FETCH_ASSOC)) {
                $db->exec('ALTER TABLE structures DROP COLUMN `' . str_replace('`', '``', $col) . '`');
            }
        }
    },
];
