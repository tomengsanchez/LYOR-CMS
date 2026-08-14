<?php
/**
 * Migration 038: Add soft-delete columns to deletable entities.
 */
return [
    'name' => 'migration_038_soft_delete_core_entities',
    'up' => function (\PDO $db): void {
        $tables = [
            'projects',
            'profiles',
            'structures',
            'grievances',
            'grievance_attachments',
            'grievance_vulnerabilities',
            'grievance_respondent_types',
            'grievance_grm_channels',
            'grievance_preferred_languages',
            'grievance_types',
            'grievance_categories',
            'grievance_progress_levels',
            'user_profiles',
        ];

        foreach ($tables as $table) {
            $stmt = $db->prepare('SHOW COLUMNS FROM `' . $table . '` LIKE ?');

            $stmt->execute(['is_deleted']);
            if (!$stmt->fetch(\PDO::FETCH_ASSOC)) {
                $db->exec('ALTER TABLE `' . $table . '` ADD COLUMN is_deleted TINYINT(1) NOT NULL DEFAULT 0');
            }

            $stmt->execute(['deleted_at']);
            if (!$stmt->fetch(\PDO::FETCH_ASSOC)) {
                $db->exec('ALTER TABLE `' . $table . '` ADD COLUMN deleted_at DATETIME NULL');
            }

            $stmt->execute(['deleted_by']);
            if (!$stmt->fetch(\PDO::FETCH_ASSOC)) {
                $db->exec('ALTER TABLE `' . $table . '` ADD COLUMN deleted_by INT NULL');
            }
        }
    },
    'down' => function (\PDO $db): void {
        $tables = [
            'projects',
            'profiles',
            'structures',
            'grievances',
            'grievance_attachments',
            'grievance_vulnerabilities',
            'grievance_respondent_types',
            'grievance_grm_channels',
            'grievance_preferred_languages',
            'grievance_types',
            'grievance_categories',
            'grievance_progress_levels',
            'user_profiles',
        ];

        foreach ($tables as $table) {
            $stmt = $db->prepare('SHOW COLUMNS FROM `' . $table . '` LIKE ?');

            $drops = [];
            foreach (['deleted_by', 'deleted_at', 'is_deleted'] as $col) {
                $stmt->execute([$col]);
                if ($stmt->fetch(\PDO::FETCH_ASSOC)) {
                    $drops[] = 'DROP COLUMN `' . $col . '`';
                }
            }
            if (!empty($drops)) {
                $db->exec('ALTER TABLE `' . $table . '` ' . implode(', ', $drops));
            }
        }
    },
];
