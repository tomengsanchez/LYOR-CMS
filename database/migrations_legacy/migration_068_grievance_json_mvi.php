<?php
/**
 * Migration 068: Multi-valued indexes for grievance type/category JSON filters.
 *
 * Supports MEMBER OF() in dashboard aggregates and respondent history (MySQL 8.0.17+).
 */
return [
    'name' => 'migration_068_grievance_json_mvi',
    'up' => function (\PDO $db): void {
        $version = (string) $db->query('SELECT VERSION()')->fetchColumn();
        $normalized = preg_replace('/[^0-9.].*$/', '', $version) ?: $version;
        if (stripos($version, 'mariadb') !== false || version_compare($normalized, '8.0.17', '<')) {
            return;
        }

        $has = static function (string $name) use ($db): bool {
            $stmt = $db->prepare('SHOW INDEX FROM grievances WHERE Key_name = ?');
            $stmt->execute([$name]);

            return (bool) $stmt->fetch(\PDO::FETCH_ASSOC);
        };

        if (!$has('idx_grievance_type_ids_mvi')) {
            $db->exec(
                'ALTER TABLE grievances ADD INDEX idx_grievance_type_ids_mvi ((CAST(grievance_type_ids AS UNSIGNED ARRAY)))'
            );
        }
        if (!$has('idx_grievance_category_ids_mvi')) {
            $db->exec(
                'ALTER TABLE grievances ADD INDEX idx_grievance_category_ids_mvi ((CAST(grievance_category_ids AS UNSIGNED ARRAY)))'
            );
        }
    },
    'down' => function (\PDO $db): void {
        $has = static function (string $name) use ($db): bool {
            $stmt = $db->prepare('SHOW INDEX FROM grievances WHERE Key_name = ?');
            $stmt->execute([$name]);

            return (bool) $stmt->fetch(\PDO::FETCH_ASSOC);
        };

        if ($has('idx_grievance_type_ids_mvi')) {
            $db->exec('ALTER TABLE grievances DROP INDEX idx_grievance_type_ids_mvi');
        }
        if ($has('idx_grievance_category_ids_mvi')) {
            $db->exec('ALTER TABLE grievances DROP INDEX idx_grievance_category_ids_mvi');
        }
    },
];
