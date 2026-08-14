<?php
/**
 * Migration 029: Affected barangays (one per line) on projects for profile barangay dropdown.
 */
return [
    'name' => 'migration_029_projects_affected_barangays',
    'up' => function (\PDO $db): void {
        $stmt = $db->prepare('SHOW COLUMNS FROM projects LIKE ?');
        $stmt->execute(['affected_barangays']);
        if (!$stmt->fetch(\PDO::FETCH_ASSOC)) {
            $db->exec('ALTER TABLE projects ADD COLUMN affected_barangays TEXT NULL AFTER description');
        }
    },
    'down' => function (\PDO $db): void {
        $stmt = $db->prepare('SHOW COLUMNS FROM projects LIKE ?');
        $stmt->execute(['affected_barangays']);
        if ($stmt->fetch(\PDO::FETCH_ASSOC)) {
            $db->exec('ALTER TABLE projects DROP COLUMN affected_barangays');
        }
    },
];
