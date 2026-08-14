<?php
/**
 * Migration 082: Person vs Business profile identity.
 * entity_type: person (default) | business
 * registered_business_name: used when entity_type = business; full_name is synced to it on save.
 */
return [
    'name' => 'migration_082_profile_entity_type',
    'up' => function (\PDO $db): void {
        $hasEntity = false;
        $hasBiz = false;
        try {
            $stmt = $db->query("SHOW COLUMNS FROM profiles LIKE 'entity_type'");
            $hasEntity = (bool) $stmt->fetch(\PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            $hasEntity = false;
        }
        try {
            $stmt = $db->query("SHOW COLUMNS FROM profiles LIKE 'registered_business_name'");
            $hasBiz = (bool) $stmt->fetch(\PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            $hasBiz = false;
        }

        if (!$hasEntity) {
            $db->exec("
                ALTER TABLE profiles
                ADD COLUMN entity_type VARCHAR(20) NOT NULL DEFAULT 'person'
                    COMMENT 'person|business' AFTER full_name
            ");
        }
        if (!$hasBiz) {
            $db->exec("
                ALTER TABLE profiles
                ADD COLUMN registered_business_name VARCHAR(255) NULL
                    COMMENT 'Identity when entity_type=business' AFTER entity_type
            ");
        }

        try {
            $db->exec('CREATE INDEX idx_profiles_entity_type ON profiles (entity_type)');
        } catch (\Throwable $e) {
            // Index may already exist
        }
        try {
            $db->exec('CREATE INDEX idx_profiles_registered_business_name ON profiles (registered_business_name)');
        } catch (\Throwable $e) {
            // Index may already exist
        }
    },
];
