<?php
/**
 * Migration 073: GRM attendant (user with linked projects) on grievances.
 */
return [
    'name' => 'migration_073_grievance_attendant',
    'up' => function (\PDO $db): void {
        $stmt = $db->prepare('SHOW COLUMNS FROM grievances LIKE ?');
        $stmt->execute(['attendant_id']);
        if (!$stmt->fetch(\PDO::FETCH_ASSOC)) {
            $db->exec('ALTER TABLE grievances ADD COLUMN attendant_id INT NULL AFTER grm_channel_ids');
        }
        try {
            $db->exec('ALTER TABLE grievances ADD INDEX idx_grievance_attendant (attendant_id)');
        } catch (\Throwable $e) {
            // Ignore if exists.
        }
        try {
            $db->exec('ALTER TABLE grievances ADD CONSTRAINT fk_grievances_attendant FOREIGN KEY (attendant_id) REFERENCES users(id) ON DELETE SET NULL');
        } catch (\Throwable $e) {
            // Ignore if exists.
        }
    },
    'down' => function (\PDO $db): void {
        try {
            $db->exec('ALTER TABLE grievances DROP FOREIGN KEY fk_grievances_attendant');
        } catch (\Throwable $e) {
        }
        try {
            $db->exec('ALTER TABLE grievances DROP INDEX idx_grievance_attendant');
        } catch (\Throwable $e) {
        }
        $stmt = $db->prepare('SHOW COLUMNS FROM grievances LIKE ?');
        $stmt->execute(['attendant_id']);
        if ($stmt->fetch(\PDO::FETCH_ASSOC)) {
            $db->exec('ALTER TABLE grievances DROP COLUMN attendant_id');
        }
    },
];
