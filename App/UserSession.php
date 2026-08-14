<?php
namespace App;

use Core\Auth;
use Core\Database;

class UserSession
{
    /** Minutes without presence/activity before a session is considered offline. */
    public const ACTIVE_WINDOW_MINUTES = 10;

    private static function db(): \PDO
    {
        return Database::getInstance();
    }

    private static function currentSessionId(): ?string
    {
        $sid = session_id();
        return $sid !== '' ? $sid : null;
    }

    private static function clientIp(): ?string
    {
        // Reuse LoginThrottle logic? For now, basic REMOTE_ADDR (no proxy handling).
        return $_SERVER['REMOTE_ADDR'] ?? null;
    }

    /**
    * Called on successful login to register the current browser/device session.
    */
    public static function onLogin(int $userId): void
    {
        $sid = self::currentSessionId();
        if ($sid === null) {
            return;
        }

        $db = self::db();
        $stmt = $db->prepare('SELECT id FROM user_sessions WHERE user_id = ? AND session_id = ? LIMIT 1');
        $stmt->execute([$userId, $sid]);
        $existingId = $stmt->fetchColumn();

        $ua = substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 500);
        $ip = substr((string) (self::clientIp() ?? ''), 0, 45);

        if ($existingId) {
            $upd = $db->prepare('UPDATE user_sessions SET user_agent = ?, ip_address = ?, last_activity_at = NOW(), revoked_at = NULL WHERE id = ?');
            $upd->execute([$ua, $ip, $existingId]);
        } else {
            $ins = $db->prepare('INSERT INTO user_sessions (user_id, session_id, user_agent, ip_address, created_at, last_activity_at) VALUES (?, ?, ?, ?, NOW(), NOW())');
            $ins->execute([$userId, $sid, $ua, $ip]);
        }
    }

    /**
    * Called on each authenticated request to:
    * - ensure a session row exists
    * - update last_activity_at
    * - force logout if revoked.
    */
    public static function touchOrEnforceForCurrent(): void
    {
        $userId = Auth::id();
        $sid = self::currentSessionId();
        if ($userId === null || $sid === null) {
            return;
        }

        $db = self::db();
        $stmt = $db->prepare('SELECT id, revoked_at FROM user_sessions WHERE user_id = ? AND session_id = ? LIMIT 1');
        $stmt->execute([$userId, $sid]);
        $row = $stmt->fetch(\PDO::FETCH_OBJ);

        if ($row && $row->revoked_at !== null) {
            // This session has been revoked from another device/action.
            Auth::logout();
            header('Location: ' . \App\AdminPath::url('login') . '?logged_out=1');
            exit;
        }

        $ua = substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 500);
        $ip = substr((string) (self::clientIp() ?? ''), 0, 45);

        if ($row) {
            $upd = $db->prepare('UPDATE user_sessions SET last_activity_at = NOW(), user_agent = ?, ip_address = ? WHERE id = ?');
            $upd->execute([$ua, $ip, $row->id]);
        } else {
            $ins = $db->prepare('INSERT INTO user_sessions (user_id, session_id, user_agent, ip_address, created_at, last_activity_at) VALUES (?, ?, ?, ?, NOW(), NOW())');
            $ins->execute([$userId, $sid, $ua, $ip]);
        }
    }

    /**
    * Mark the current browser/device session as revoked (used on logout).
    */
    public static function revokeCurrent(): void
    {
        $userId = Auth::id();
        $sid = self::currentSessionId();
        if ($userId === null || $sid === null) {
            return;
        }
        $db = self::db();
        $stmt = $db->prepare('UPDATE user_sessions SET revoked_at = NOW() WHERE user_id = ? AND session_id = ? AND revoked_at IS NULL');
        $stmt->execute([$userId, $sid]);
    }

    /**
    * Revoke all other active sessions for the current user (leave this one).
    */
    public static function revokeOthers(): void
    {
        $userId = Auth::id();
        $sid = self::currentSessionId();
        if ($userId === null) {
            return;
        }
        $db = self::db();
        if ($sid === null) {
            $stmt = $db->prepare('UPDATE user_sessions SET revoked_at = NOW() WHERE user_id = ? AND revoked_at IS NULL');
            $stmt->execute([$userId]);
        } else {
            $stmt = $db->prepare('UPDATE user_sessions SET revoked_at = NOW() WHERE user_id = ? AND session_id <> ? AND revoked_at IS NULL');
            $stmt->execute([$userId, $sid]);
        }
    }

    /**
    * Revoke a specific session (by ID) owned by current user.
    */
    public static function revokeById(int $sessionId): void
    {
        $userId = Auth::id();
        if ($userId === null) {
            return;
        }
        $db = self::db();
        $stmt = $db->prepare('UPDATE user_sessions SET revoked_at = NOW() WHERE id = ? AND user_id = ? AND revoked_at IS NULL');
        $stmt->execute([$sessionId, $userId]);
    }

    /**
    * List sessions for current user for the UI.
    *
    * @return array<int,object>
    */
    public static function listForCurrentUser(): array
    {
        $userId = Auth::id();
        if ($userId === null) {
            return [];
        }

        return self::listForUser((int) $userId, true);
    }

    /**
     * List sessions for a user (Users view / Account access dashboard).
     *
     * @return array<int,object>
     */
    public static function listForUser(int $userId, bool $markCurrent = false): array
    {
        $userId = (int) $userId;
        if ($userId <= 0) {
            return [];
        }
        $sid = ($markCurrent || Auth::id() === $userId) ? self::currentSessionId() : null;

        $db = self::db();
        try {
            $stmt = $db->prepare('
                SELECT id, session_id, user_agent, ip_address, created_at, last_activity_at, revoked_at,
                       current_path, page_key, page_label, presence_updated_at
                FROM user_sessions
                WHERE user_id = ?
                ORDER BY revoked_at IS NULL DESC, COALESCE(presence_updated_at, last_activity_at) DESC, created_at DESC
            ');
            $stmt->execute([$userId]);
            $rows = $stmt->fetchAll(\PDO::FETCH_OBJ) ?: [];
        } catch (\Throwable $e) {
            $stmt = $db->prepare('
                SELECT id, session_id, user_agent, ip_address, created_at, last_activity_at, revoked_at
                FROM user_sessions
                WHERE user_id = ?
                ORDER BY revoked_at IS NULL DESC, last_activity_at DESC, created_at DESC
            ');
            $stmt->execute([$userId]);
            $rows = $stmt->fetchAll(\PDO::FETCH_OBJ) ?: [];
        }

        foreach ($rows as $row) {
            $row->is_current = ($sid !== null && (string) $row->session_id === (string) $sid);
            $row->is_active = ($row->revoked_at === null);
            $presence = $row->presence_updated_at ?? null;
            $row->last_seen_at = $presence ?: ($row->last_activity_at ?? null);
            if (!isset($row->current_path)) {
                $row->current_path = null;
            }
            if (!isset($row->page_key)) {
                $row->page_key = null;
            }
            if (!isset($row->page_label)) {
                $row->page_label = null;
            }
        }

        return $rows;
    }

    /**
     * Revoke a session only if it belongs to the given user.
     */
    public static function revokeByIdForUser(int $sessionId, int $userId): bool
    {
        $sessionId = (int) $sessionId;
        $userId = (int) $userId;
        if ($sessionId <= 0 || $userId <= 0) {
            return false;
        }
        $db = self::db();
        $stmt = $db->prepare('UPDATE user_sessions SET revoked_at = NOW() WHERE id = ? AND user_id = ? AND revoked_at IS NULL');
        $stmt->execute([$sessionId, $userId]);

        return $stmt->rowCount() > 0;
    }

    /**
     * Best-effort presence update for the current browser session only.
     * Failures are swallowed so heartbeats never break the app.
     */
    public static function updatePresenceForCurrent(string $pageKey, string $path, string $pageLabel): void
    {
        try {
            $userId = Auth::id();
            $sid = self::currentSessionId();
            if ($userId === null || $sid === null) {
                return;
            }

            $pageKey = substr(trim($pageKey), 0, 100);
            $path = substr(trim($path), 0, 500);
            $pageLabel = substr(trim($pageLabel), 0, 200);

            $db = self::db();
            $stmt = $db->prepare('
                UPDATE user_sessions
                SET current_path = ?, page_key = ?, page_label = ?, presence_updated_at = NOW(), last_activity_at = NOW()
                WHERE user_id = ? AND session_id = ? AND revoked_at IS NULL
            ');
            $stmt->execute([$path !== '' ? $path : null, $pageKey !== '' ? $pageKey : null, $pageLabel !== '' ? $pageLabel : null, $userId, $sid]);

            if ($stmt->rowCount() === 0) {
                // Ensure a row exists (e.g. first heartbeat before touch ran).
                self::touchOrEnforceForCurrent();
                $stmt->execute([$path !== '' ? $path : null, $pageKey !== '' ? $pageKey : null, $pageLabel !== '' ? $pageLabel : null, $userId, $sid]);
            }
        } catch (\Throwable $e) {
            // Presence is best-effort.
        }
    }

    /**
     * Active (non-revoked) sessions with recent presence/activity for admin dashboard.
     *
     * @return array<int,object>
     */
    public static function listActiveSessions(int $windowMinutes = self::ACTIVE_WINDOW_MINUTES): array
    {
        $windowMinutes = max(1, min(1440, $windowMinutes));
        $sid = self::currentSessionId();

        $db = self::db();
        $stmt = $db->prepare("
            SELECT
                us.id,
                us.user_id,
                us.session_id,
                us.user_agent,
                us.ip_address,
                us.created_at,
                us.last_activity_at,
                us.revoked_at,
                us.current_path,
                us.page_key,
                us.page_label,
                us.presence_updated_at,
                u.username,
                u.display_name,
                r.name AS role_name
            FROM user_sessions us
            INNER JOIN users u ON u.id = us.user_id
            LEFT JOIN roles r ON r.id = u.role_id
            WHERE us.revoked_at IS NULL
              AND COALESCE(us.presence_updated_at, us.last_activity_at) >= (NOW() - INTERVAL {$windowMinutes} MINUTE)
            ORDER BY COALESCE(us.presence_updated_at, us.last_activity_at) DESC, us.id DESC
        ");
        $stmt->execute();
        $rows = $stmt->fetchAll(\PDO::FETCH_OBJ) ?: [];

        foreach ($rows as $row) {
            $row->is_current = ($sid !== null && (string) $row->session_id === $sid);
            $row->last_seen_at = $row->presence_updated_at ?: $row->last_activity_at;
        }

        return $rows;
    }

    /**
     * Admin force-end of any session by id (not limited to current user).
     */
    public static function revokeByIdAsAdmin(int $sessionId): bool
    {
        if ($sessionId <= 0) {
            return false;
        }
        $db = self::db();
        $stmt = $db->prepare('UPDATE user_sessions SET revoked_at = NOW() WHERE id = ? AND revoked_at IS NULL');
        $stmt->execute([$sessionId]);
        return $stmt->rowCount() > 0;
    }
}

