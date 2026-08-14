<?php
/**
 * Migration 090: RAP field mode params (range ops) + multi-entity column maps.
 *
 * - mode_params_json on rap_field_definitions (min/max for count_in_range / sum_in_range)
 * - source_entity on ses_rap_column_maps (ses | structure | grievance)
 */
return [
    'name' => 'migration_090_rap_field_ops_multisource',
    'up' => function (\PDO $db): void {
        $hasCol = static function (\PDO $db, string $table, string $column): bool {
            $stmt = $db->prepare('
                SELECT COUNT(*) FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?
            ');
            $stmt->execute([$table, $column]);
            return (int) $stmt->fetchColumn() > 0;
        };

        if (!$hasCol($db, 'rap_field_definitions', 'mode_params_json')) {
            $db->exec('
                ALTER TABLE rap_field_definitions
                ADD COLUMN mode_params_json TEXT NULL AFTER value_mode
            ');
        }

        if (!$hasCol($db, 'ses_rap_column_maps', 'source_entity')) {
            $db->exec("
                ALTER TABLE ses_rap_column_maps
                ADD COLUMN source_entity VARCHAR(32) NOT NULL DEFAULT 'ses' AFTER rap_field_id
            ");
        }

        // InnoDB may use uq_ses_rap_map for the FK on rap_field_id — drop FK first.
        $fkStmt = $db->query("
            SELECT CONSTRAINT_NAME
            FROM information_schema.TABLE_CONSTRAINTS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'ses_rap_column_maps'
              AND CONSTRAINT_TYPE = 'FOREIGN KEY'
              AND CONSTRAINT_NAME = 'fk_ses_rap_maps_field'
        ");
        if ($fkStmt && $fkStmt->fetch()) {
            $db->exec('ALTER TABLE ses_rap_column_maps DROP FOREIGN KEY fk_ses_rap_maps_field');
        }

        $idx = $db->query("SHOW INDEX FROM ses_rap_column_maps WHERE Key_name = 'uq_ses_rap_map'")->fetchAll();
        if ($idx) {
            $db->exec('ALTER TABLE ses_rap_column_maps DROP INDEX uq_ses_rap_map');
        }

        $idx2 = $db->query("SHOW INDEX FROM ses_rap_column_maps WHERE Key_name = 'uq_ses_rap_map_entity'")->fetchAll();
        if (!$idx2) {
            $db->exec('
                ALTER TABLE ses_rap_column_maps
                ADD UNIQUE KEY uq_ses_rap_map_entity (rap_field_id, source_entity, section_key, ses_column)
            ');
        }

        // Re-add FK (needs an index on rap_field_id — unique key covers it).
        $fkAgain = $db->query("
            SELECT CONSTRAINT_NAME
            FROM information_schema.TABLE_CONSTRAINTS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'ses_rap_column_maps'
              AND CONSTRAINT_TYPE = 'FOREIGN KEY'
              AND CONSTRAINT_NAME = 'fk_ses_rap_maps_field'
        ");
        if (!$fkAgain || !$fkAgain->fetch()) {
            $db->exec('
                ALTER TABLE ses_rap_column_maps
                ADD CONSTRAINT fk_ses_rap_maps_field
                    FOREIGN KEY (rap_field_id) REFERENCES rap_field_definitions(id) ON DELETE CASCADE
            ');
        }

        $entIdx = $db->query("SHOW INDEX FROM ses_rap_column_maps WHERE Key_name = 'idx_ses_rap_maps_entity'")->fetchAll();
        if (!$entIdx) {
            $db->exec('CREATE INDEX idx_ses_rap_maps_entity ON ses_rap_column_maps (source_entity)');
        }
    },
    'down' => function (\PDO $db): void {
        try {
            $db->exec('ALTER TABLE ses_rap_column_maps DROP INDEX idx_ses_rap_maps_entity');
        } catch (\Throwable $e) {
            // ignore
        }
        try {
            $db->exec('ALTER TABLE ses_rap_column_maps DROP FOREIGN KEY fk_ses_rap_maps_field');
        } catch (\Throwable $e) {
            // ignore
        }
        try {
            $db->exec('ALTER TABLE ses_rap_column_maps DROP INDEX uq_ses_rap_map_entity');
        } catch (\Throwable $e) {
            // ignore
        }
        try {
            $db->exec('
                ALTER TABLE ses_rap_column_maps
                ADD UNIQUE KEY uq_ses_rap_map (rap_field_id, section_key, ses_column)
            ');
        } catch (\Throwable $e) {
            // ignore
        }
        try {
            $db->exec('
                ALTER TABLE ses_rap_column_maps
                ADD CONSTRAINT fk_ses_rap_maps_field
                    FOREIGN KEY (rap_field_id) REFERENCES rap_field_definitions(id) ON DELETE CASCADE
            ');
        } catch (\Throwable $e) {
            // ignore
        }
        try {
            $db->exec('ALTER TABLE ses_rap_column_maps DROP COLUMN source_entity');
        } catch (\Throwable $e) {
            // ignore
        }
        try {
            $db->exec('ALTER TABLE rap_field_definitions DROP COLUMN mode_params_json');
        } catch (\Throwable $e) {
            // ignore
        }
    },
];
