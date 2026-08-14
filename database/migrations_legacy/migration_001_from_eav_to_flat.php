<?php
/**
 * Migration 001: Refactor from EAV to flat tables
 *
 * Creates flat tables (projects, profiles, structures, user_profiles) with standard
 * created_at/updated_at timestamps for performant pagination at scale.
 * Migrates existing EAV data and drops EAV tables.
 *
 * Format: return array with 'name', 'up', and optional 'down' callables.
 * This format will be used for all future migrations.
 */
return [
    'name' => 'migration_001_from_eav_to_flat',
    'up' => function (\PDO $db): void {
        $db->exec('SET FOREIGN_KEY_CHECKS = 0');

        // 1. Create flat projects table
        $db->exec("
            CREATE TABLE IF NOT EXISTS projects (
                id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL DEFAULT '',
                description TEXT,
                coordinator_id INT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_coordinator (coordinator_id),
                INDEX idx_created (created_at),
                CONSTRAINT fk_projects_coordinator FOREIGN KEY (coordinator_id) REFERENCES users(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // 2. Create flat profiles table
        $db->exec("
            CREATE TABLE IF NOT EXISTS profiles (
                id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                papsid VARCHAR(50) NOT NULL,
                control_number VARCHAR(100) DEFAULT '',
                full_name VARCHAR(255) DEFAULT '',
                age INT NULL,
                contact_number VARCHAR(50) DEFAULT '',
                project_id INT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uk_papsid (papsid),
                INDEX idx_project (project_id),
                INDEX idx_full_name (full_name),
                INDEX idx_created (created_at),
                CONSTRAINT fk_profiles_project FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // 3. Create flat structures table
        $db->exec("
            CREATE TABLE IF NOT EXISTS structures (
                id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                strid VARCHAR(50) NOT NULL,
                owner_id INT NULL,
                structure_tag VARCHAR(255) DEFAULT '',
                description TEXT,
                tagging_images TEXT,
                structure_images TEXT,
                other_details TEXT,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uk_strid (strid),
                INDEX idx_owner (owner_id),
                INDEX idx_structure_tag (structure_tag),
                INDEX idx_created (created_at),
                CONSTRAINT fk_structures_owner FOREIGN KEY (owner_id) REFERENCES profiles(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // 4. Create flat user_profiles table
        $db->exec("
            CREATE TABLE IF NOT EXISTS user_profiles (
                id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL DEFAULT '',
                user_id INT NULL,
                role_id INT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uk_user_id (user_id),
                INDEX idx_role (role_id),
                INDEX idx_created (created_at),
                CONSTRAINT fk_user_profiles_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
                CONSTRAINT fk_user_profiles_role FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // 5. Migrate data from EAV - need attribute IDs from eav_attributes
        $getAttr = function (string $entityType, string $name) use ($db): ?int {
            $stmt = $db->prepare('SELECT id FROM eav_attributes WHERE entity_type = ? AND name = ?');
            $stmt->execute([$entityType, $name]);
            $v = $stmt->fetchColumn();
            return $v ? (int) $v : null;
        };
        $getValue = function (int $entityId, ?int $attrId) use ($db): ?string {
            if (!$attrId) return null;
            $stmt = $db->prepare('SELECT value FROM eav_values WHERE entity_id = ? AND attribute_id = ?');
            $stmt->execute([$entityId, $attrId]);
            return $stmt->fetchColumn() ?: null;
        };

        // Check if EAV tables exist and have data
        $hasEav = $db->query("SHOW TABLES LIKE 'eav_entities'")->rowCount() > 0;
        if ($hasEav) {
            // Migrate projects
            $projName = $getAttr('project', 'name');
            $projDesc = $getAttr('project', 'description');
            $projCoord = $getAttr('project', 'coordinator_id');
            $projRows = $db->query("SELECT id, created_at, updated_at FROM eav_entities WHERE entity_type = 'project'")->fetchAll(\PDO::FETCH_ASSOC);
            $insProj = $db->prepare('INSERT INTO projects (id, name, description, coordinator_id, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?)');
            foreach ($projRows as $r) {
                $coord = $getValue((int) $r['id'], $projCoord);
                $insProj->execute([
                    $r['id'],
                    $getValue((int) $r['id'], $projName) ?? '',
                    $getValue((int) $r['id'], $projDesc),
                    $coord !== null && $coord !== '' ? (int) $coord : null,
                    $r['created_at'],
                    $r['updated_at'],
                ]);
            }

            // Migrate profiles
            $profPapsid = $getAttr('profile', 'papsid');
            $profCtrl = $getAttr('profile', 'control_number');
            $profName = $getAttr('profile', 'full_name');
            $profAge = $getAttr('profile', 'age');
            $profContact = $getAttr('profile', 'contact_number');
            $profProj = $getAttr('profile', 'project_id');
            $profRows = $db->query("SELECT id, created_at, updated_at FROM eav_entities WHERE entity_type = 'profile'")->fetchAll(\PDO::FETCH_ASSOC);
            $insProf = $db->prepare('INSERT INTO profiles (id, papsid, control_number, full_name, age, contact_number, project_id, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
            foreach ($profRows as $r) {
                $proj = $getValue((int) $r['id'], $profProj);
                $insProf->execute([
                    $r['id'],
                    $getValue((int) $r['id'], $profPapsid) ?? 'PAPS-UNKNOWN',
                    $getValue((int) $r['id'], $profCtrl) ?? '',
                    $getValue((int) $r['id'], $profName) ?? '',
                    ($a = $getValue((int) $r['id'], $profAge)) !== null && $a !== '' ? (int) $a : null,
                    $getValue((int) $r['id'], $profContact) ?? '',
                    $proj !== null && $proj !== '' ? (int) $proj : null,
                    $r['created_at'],
                    $r['updated_at'],
                ]);
            }

            // Migrate structures
            $strStrid = $getAttr('structure', 'strid');
            $strOwner = $getAttr('structure', 'owner_id');
            $strTag = $getAttr('structure', 'structure_tag');
            $strDesc = $getAttr('structure', 'description');
            $strTimg = $getAttr('structure', 'tagging_images');
            $strSimg = $getAttr('structure', 'structure_images');
            $strOther = $getAttr('structure', 'other_details');
            $strRows = $db->query("SELECT id, created_at, updated_at FROM eav_entities WHERE entity_type = 'structure'")->fetchAll(\PDO::FETCH_ASSOC);
            $insStr = $db->prepare('INSERT INTO structures (id, strid, owner_id, structure_tag, description, tagging_images, structure_images, other_details, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
            foreach ($strRows as $r) {
                $owner = $getValue((int) $r['id'], $strOwner);
                $insStr->execute([
                    $r['id'],
                    $getValue((int) $r['id'], $strStrid) ?? 'STRID-UNKNOWN',
                    $owner !== null && $owner !== '' ? (int) $owner : null,
                    $getValue((int) $r['id'], $strTag) ?? '',
                    $getValue((int) $r['id'], $strDesc),
                    $getValue((int) $r['id'], $strTimg),
                    $getValue((int) $r['id'], $strSimg),
                    $getValue((int) $r['id'], $strOther),
                    $r['created_at'],
                    $r['updated_at'],
                ]);
            }

            // Migrate user_profiles
            $upName = $getAttr('user_profile', 'name');
            $upUser = $getAttr('user_profile', 'user_id');
            $upRole = $getAttr('user_profile', 'role_id');
            $upRows = $db->query("SELECT id, created_at, updated_at FROM eav_entities WHERE entity_type = 'user_profile'")->fetchAll(\PDO::FETCH_ASSOC);
            $insUp = $db->prepare('INSERT INTO user_profiles (id, name, user_id, role_id, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?)');
            foreach ($upRows as $r) {
                $uid = $getValue((int) $r['id'], $upUser);
                $rid = $getValue((int) $r['id'], $upRole);
                $insUp->execute([
                    $r['id'],
                    $getValue((int) $r['id'], $upName) ?? '',
                    $uid !== null && $uid !== '' ? (int) $uid : null,
                    $rid !== null && $rid !== '' ? (int) $rid : null,
                    $r['created_at'],
                    $r['updated_at'],
                ]);
            }

            // Drop EAV tables (order matters for FK)
            $db->exec('DROP TABLE IF EXISTS eav_values');
            $db->exec('DROP TABLE IF EXISTS eav_attributes');
            $db->exec('DROP TABLE IF EXISTS eav_entities');
        }

        $db->exec('SET FOREIGN_KEY_CHECKS = 1');
    },
    'down' => function (\PDO $db): void {
        // Reverse: would need to recreate EAV and migrate back - typically not used
        $db->exec('SET FOREIGN_KEY_CHECKS = 0');
        $db->exec('DROP TABLE IF EXISTS structures');
        $db->exec('DROP TABLE IF EXISTS profiles');
        $db->exec('DROP TABLE IF EXISTS user_profiles');
        $db->exec('DROP TABLE IF EXISTS projects');
        $db->exec('SET FOREIGN_KEY_CHECKS = 1');
    },
];
