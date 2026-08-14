<?php
/**
 * Migration 083: Presence columns on user_sessions for admin realtime dashboard.
 *
 * Stores current screen (path/key/label) and last presence heartbeat time so
 * admins can see who is online and which page they are on.
 */
return [
    'name' => 'migration_083_user_session_presence',
    'up' => function (\PDO $db): void {
        $cols = [
            'current_path' => "ALTER TABLE user_sessions ADD COLUMN current_path VARCHAR(500) NULL DEFAULT NULL AFTER revoked_at",
            'page_key' => "ALTER TABLE user_sessions ADD COLUMN page_key VARCHAR(100) NULL DEFAULT NULL AFTER current_path",
            'page_label' => "ALTER TABLE user_sessions ADD COLUMN page_label VARCHAR(200) NULL DEFAULT NULL AFTER page_key",
            'presence_updated_at' => "ALTER TABLE user_sessions ADD COLUMN presence_updated_at DATETIME NULL DEFAULT NULL AFTER page_label",
        ];
        foreach ($cols as $name => $sql) {
            $stmt = $db->query("SHOW COLUMNS FROM user_sessions LIKE " . $db->quote($name));
            if (!$stmt || !$stmt->fetch(\PDO::FETCH_ASSOC)) {
                $db->exec($sql);
            }
        }

        $idx = $db->prepare('SHOW INDEX FROM user_sessions WHERE Key_name = ?');
        $idx->execute(['idx_sessions_active_presence']);
        if (!$idx->fetch(\PDO::FETCH_ASSOC)) {
            $db->exec('ALTER TABLE user_sessions ADD INDEX idx_sessions_active_presence (revoked_at, presence_updated_at)');
        }
    },
    'down' => function (\PDO $db): void {
        $idx = $db->prepare('SHOW INDEX FROM user_sessions WHERE Key_name = ?');
        $idx->execute(['idx_sessions_active_presence']);
        if ($idx->fetch(\PDO::FETCH_ASSOC)) {
            $db->exec('ALTER TABLE user_sessions DROP INDEX idx_sessions_active_presence');
        }

        foreach (['presence_updated_at', 'page_label', 'page_key', 'current_path'] as $name) {
            $col = $db->query("SHOW COLUMNS FROM user_sessions LIKE " . $db->quote($name));
            if ($col && $col->fetch(\PDO::FETCH_ASSOC)) {
                $db->exec('ALTER TABLE user_sessions DROP COLUMN ' . $name);
            }
        }
    },
];
