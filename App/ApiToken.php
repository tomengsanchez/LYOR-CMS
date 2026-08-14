<?php
namespace App;

use Core\Database;
use App\Models\AppSettings;

/**
 * API token management for REST Bearer authentication.
 * Tokens are stored hashed; raw token is returned only on create.
 *
 * Absolute expiry defaults to 7 days. Idle timeout (default 72 hours) revokes
 * tokens that have not been used recently (requires last_used_at column).
 */
class ApiToken
{
    private const DEFAULT_EXPIRY_DAYS = 7;
    private const DEFAULT_IDLE_MINUTES = 4320; // 72 hours
    private const LAST_USED_TOUCH_SECONDS = 300; // throttle DB writes

    /** Create a token for user; returns ['token' => raw, 'expires_at' => datetime]. */
    public static function create(int $userId, ?int $expiryDays = null): array
    {
        $expiryDays = $expiryDays ?? self::configuredExpiryDays();
        $expiryDays = max(1, min(90, $expiryDays));

        $raw = bin2hex(random_bytes(32));
        $hash = hash('sha256', $raw);
        $expiresAt = date('Y-m-d H:i:s', strtotime("+{$expiryDays} days"));
        $now = date('Y-m-d H:i:s');

        $db = Database::getInstance();
        if (self::hasLastUsedColumn($db)) {
            $stmt = $db->prepare(
                'INSERT INTO api_tokens (user_id, token_hash, expires_at, last_used_at) VALUES (?, ?, ?, ?)'
            );
            $stmt->execute([$userId, $hash, $expiresAt, $now]);
        } else {
            $stmt = $db->prepare('INSERT INTO api_tokens (user_id, token_hash, expires_at) VALUES (?, ?, ?)');
            $stmt->execute([$userId, $hash, $expiresAt]);
        }

        return [
            'token'      => $raw,
            'expires_at' => $expiresAt,
        ];
    }

    /** Validate token; returns user_id or null. Updates last_used_at and enforces idle timeout. */
    public static function validate(string $token): ?int
    {
        $token = trim($token);
        if ($token === '') {
            return null;
        }
        $hash = hash('sha256', $token);
        $db = Database::getInstance();

        if (self::hasLastUsedColumn($db)) {
            $stmt = $db->prepare(
                'SELECT id, user_id, last_used_at FROM api_tokens WHERE token_hash = ? AND expires_at > NOW()'
            );
            $stmt->execute([$hash]);
            $row = $stmt->fetch(\PDO::FETCH_OBJ);
            if (!$row) {
                return null;
            }

            $idleMinutes = self::configuredIdleMinutes();
            if ($idleMinutes > 0) {
                $lastUsed = $row->last_used_at ?? null;
                if ($lastUsed) {
                    $lastTs = strtotime((string) $lastUsed);
                    if ($lastTs !== false && (time() - $lastTs) > ($idleMinutes * 60)) {
                        $del = $db->prepare('DELETE FROM api_tokens WHERE id = ?');
                        $del->execute([(int) $row->id]);
                        return null;
                    }
                }
            }

            self::touchLastUsed($db, (int) $row->id, $row->last_used_at ?? null);
            return (int) $row->user_id;
        }

        $stmt = $db->prepare('SELECT user_id FROM api_tokens WHERE token_hash = ? AND expires_at > NOW()');
        $stmt->execute([$hash]);
        $row = $stmt->fetch(\PDO::FETCH_OBJ);
        return $row ? (int) $row->user_id : null;
    }

    /** Revoke token (logout). Returns true if revoked. */
    public static function revoke(string $token): bool
    {
        $token = trim($token);
        if ($token === '') {
            return false;
        }
        $hash = hash('sha256', $token);
        $db = Database::getInstance();
        $stmt = $db->prepare('DELETE FROM api_tokens WHERE token_hash = ?');
        $stmt->execute([$hash]);
        return $stmt->rowCount() > 0;
    }

    /** Get Authorization Bearer token from request. */
    public static function getBearerToken(): ?string
    {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
        if ($header === '' && function_exists('apache_request_headers')) {
            $headers = apache_request_headers();
            $header = $headers['Authorization'] ?? $headers['authorization'] ?? '';
        }
        if (preg_match('/^Bearer\s+(.+)$/i', trim($header), $m)) {
            return trim($m[1]);
        }
        return null;
    }

    public static function configuredExpiryDays(): int
    {
        $days = (int) AppSettings::get('api_token_expiry_days', (string) self::DEFAULT_EXPIRY_DAYS);
        return max(1, min(90, $days > 0 ? $days : self::DEFAULT_EXPIRY_DAYS));
    }

    public static function configuredIdleMinutes(): int
    {
        $mins = (int) AppSettings::get('api_token_idle_minutes', (string) self::DEFAULT_IDLE_MINUTES);
        return max(0, min(525600, $mins)); // 0 = idle check off
    }

    private static function hasLastUsedColumn(\PDO $db): bool
    {
        static $cached = null;
        if ($cached !== null) {
            return $cached;
        }
        try {
            $stmt = $db->query("SHOW COLUMNS FROM api_tokens LIKE 'last_used_at'");
            $cached = (bool) ($stmt && $stmt->fetch(\PDO::FETCH_ASSOC));
        } catch (\Throwable $e) {
            $cached = false;
        }
        return $cached;
    }

    private static function touchLastUsed(\PDO $db, int $id, ?string $lastUsedAt): void
    {
        $shouldTouch = true;
        if ($lastUsedAt) {
            $ts = strtotime($lastUsedAt);
            if ($ts !== false && (time() - $ts) < self::LAST_USED_TOUCH_SECONDS) {
                $shouldTouch = false;
            }
        }
        if (!$shouldTouch) {
            return;
        }
        $upd = $db->prepare('UPDATE api_tokens SET last_used_at = NOW() WHERE id = ?');
        $upd->execute([$id]);
    }

    /**
     * Active Bearer tokens for Realtime Dashboard (recent last_used_at within window).
     *
     * @return list<object{
     *   token_id:int,user_id:int,username:string,display_name:?string,role_name:?string,
     *   last_seen_at:string,device_model:?string,os_version:?string,client_id:?string,
     *   path:?string,ip:?string,user_agent:?string
     * }>
     */
    public static function listActiveForDashboard(int $windowMinutes = 10): array
    {
        $windowMinutes = max(1, min(1440, $windowMinutes));
        $db = Database::getInstance();
        if (!self::hasLastUsedColumn($db)) {
            return [];
        }

        $hasEvents = false;
        $hasDeviceCols = false;
        try {
            $hasEvents = (bool) $db->query("SHOW TABLES LIKE 'api_client_events'")->fetchColumn();
            if ($hasEvents) {
                $hasDeviceCols = (bool) $db->query("SHOW COLUMNS FROM api_client_events LIKE 'device_model'")->fetchColumn();
            }
        } catch (\Throwable $e) {
            $hasEvents = false;
            $hasDeviceCols = false;
        }

        $params = [];
        $joinEvents = '';
        if ($hasEvents) {
            $eventCols = $hasDeviceCols
                ? 'e1.user_id, e1.device_model, e1.os_version, e1.client_id, e1.path, e1.ip, e1.user_agent'
                : 'e1.user_id, e1.client_id, e1.path, e1.ip, e1.user_agent';
            $joinEvents = "
                LEFT JOIN (
                    SELECT {$eventCols}
                    FROM api_client_events e1
                    INNER JOIN (
                        SELECT user_id, MAX(id) AS max_id
                        FROM api_client_events
                        WHERE event_type = 'request'
                          AND user_id IS NOT NULL
                          AND created_at >= DATE_SUB(NOW(), INTERVAL ? MINUTE)
                        GROUP BY user_id
                    ) latest ON latest.max_id = e1.id
                ) e ON e.user_id = t.user_id
            ";
            $params[] = $windowMinutes;
        }

        if ($hasDeviceCols) {
            $deviceSelect = 'e.device_model, e.os_version, e.client_id, e.path, e.ip, e.user_agent';
        } elseif ($hasEvents) {
            $deviceSelect = 'NULL AS device_model, NULL AS os_version, e.client_id, e.path, e.ip, e.user_agent';
        } else {
            $deviceSelect = 'NULL AS device_model, NULL AS os_version, NULL AS client_id, NULL AS path, NULL AS ip, NULL AS user_agent';
        }
        $params[] = $windowMinutes;

        $sql = "
            SELECT
                t.id AS token_id,
                t.user_id,
                t.last_used_at AS last_seen_at,
                u.username,
                u.display_name,
                r.name AS role_name,
                {$deviceSelect}
            FROM api_tokens t
            INNER JOIN users u ON u.id = t.user_id
            LEFT JOIN roles r ON r.id = u.role_id
            {$joinEvents}
            WHERE t.expires_at > NOW()
              AND t.last_used_at IS NOT NULL
              AND t.last_used_at >= DATE_SUB(NOW(), INTERVAL ? MINUTE)
            ORDER BY t.last_used_at DESC, t.id DESC
        ";
        try {
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(\PDO::FETCH_OBJ) ?: [];
        } catch (\Throwable $e) {
            return [];
        }

        $out = [];
        foreach ($rows as $row) {
            $out[] = (object) [
                'token_id' => (int) ($row->token_id ?? 0),
                'user_id' => (int) ($row->user_id ?? 0),
                'username' => (string) ($row->username ?? ''),
                'display_name' => $row->display_name !== null ? (string) $row->display_name : null,
                'role_name' => $row->role_name !== null ? (string) $row->role_name : null,
                'last_seen_at' => (string) ($row->last_seen_at ?? ''),
                'device_model' => isset($row->device_model) && $row->device_model !== null && $row->device_model !== '' ? (string) $row->device_model : null,
                'os_version' => isset($row->os_version) && $row->os_version !== null && $row->os_version !== '' ? (string) $row->os_version : null,
                'client_id' => isset($row->client_id) && $row->client_id !== null && $row->client_id !== '' ? (string) $row->client_id : null,
                'path' => isset($row->path) && $row->path !== null ? (string) $row->path : null,
                'ip' => isset($row->ip) && $row->ip !== null ? (string) $row->ip : null,
                'user_agent' => isset($row->user_agent) && $row->user_agent !== null ? (string) $row->user_agent : null,
            ];
        }

        return $out;
    }

    /** Admin revoke of a Bearer token by id. */
    public static function revokeByIdAsAdmin(int $id): bool
    {
        $id = (int) $id;
        if ($id <= 0) {
            return false;
        }
        $stmt = Database::getInstance()->prepare('DELETE FROM api_tokens WHERE id = ?');
        $stmt->execute([$id]);

        return $stmt->rowCount() > 0;
    }

    /**
     * Non-expired Bearer tokens for one user (Account / Users access dashboard).
     *
     * @return list<object{
     *   token_id:int,user_id:int,expires_at:string,last_seen_at:?string,
     *   device_model:?string,os_version:?string,client_id:?string,
     *   path:?string,ip:?string,user_agent:?string,is_recent:bool
     * }>
     */
    public static function listForUser(int $userId, int $recentWindowMinutes = 10): array
    {
        $userId = (int) $userId;
        $recentWindowMinutes = max(1, min(1440, $recentWindowMinutes));
        if ($userId <= 0) {
            return [];
        }
        $db = Database::getInstance();
        $hasLastUsed = self::hasLastUsedColumn($db);

        $hasEvents = false;
        $hasDeviceCols = false;
        try {
            $hasEvents = (bool) $db->query("SHOW TABLES LIKE 'api_client_events'")->fetchColumn();
            if ($hasEvents) {
                $hasDeviceCols = (bool) $db->query("SHOW COLUMNS FROM api_client_events LIKE 'device_model'")->fetchColumn();
            }
        } catch (\Throwable $e) {
            $hasEvents = false;
            $hasDeviceCols = false;
        }

        $params = [];
        $joinEvents = '';
        if ($hasEvents) {
            $eventCols = $hasDeviceCols
                ? 'e1.user_id, e1.device_model, e1.os_version, e1.client_id, e1.path, e1.ip, e1.user_agent'
                : 'e1.user_id, e1.client_id, e1.path, e1.ip, e1.user_agent';
            $joinEvents = "
                LEFT JOIN (
                    SELECT {$eventCols}
                    FROM api_client_events e1
                    INNER JOIN (
                        SELECT user_id, MAX(id) AS max_id
                        FROM api_client_events
                        WHERE event_type = 'request'
                          AND user_id = ?
                        GROUP BY user_id
                    ) latest ON latest.max_id = e1.id
                ) e ON e.user_id = t.user_id
            ";
            $params[] = $userId;
        }

        if ($hasDeviceCols) {
            $deviceSelect = 'e.device_model, e.os_version, e.client_id, e.path, e.ip, e.user_agent';
        } elseif ($hasEvents) {
            $deviceSelect = 'NULL AS device_model, NULL AS os_version, e.client_id, e.path, e.ip, e.user_agent';
        } else {
            $deviceSelect = 'NULL AS device_model, NULL AS os_version, NULL AS client_id, NULL AS path, NULL AS ip, NULL AS user_agent';
        }

        $lastUsedSelect = $hasLastUsed ? 't.last_used_at' : 'NULL AS last_used_at';
        $params[] = $userId;

        $sql = "
            SELECT
                t.id AS token_id,
                t.user_id,
                t.expires_at,
                {$lastUsedSelect},
                {$deviceSelect}
            FROM api_tokens t
            {$joinEvents}
            WHERE t.user_id = ?
              AND t.expires_at > NOW()
            ORDER BY " . ($hasLastUsed ? 't.last_used_at IS NULL ASC, t.last_used_at DESC,' : '') . " t.id DESC
        ";
        try {
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(\PDO::FETCH_OBJ) ?: [];
        } catch (\Throwable $e) {
            return [];
        }

        $cutoff = time() - ($recentWindowMinutes * 60);
        $out = [];
        foreach ($rows as $row) {
            $last = isset($row->last_used_at) && $row->last_used_at !== null ? (string) $row->last_used_at : null;
            $lastTs = $last ? strtotime($last) : false;
            $isRecent = $lastTs !== false && $lastTs >= $cutoff;
            $out[] = (object) [
                'token_id' => (int) ($row->token_id ?? 0),
                'user_id' => (int) ($row->user_id ?? 0),
                'expires_at' => (string) ($row->expires_at ?? ''),
                'last_seen_at' => $last,
                'device_model' => isset($row->device_model) && $row->device_model !== null && $row->device_model !== '' ? (string) $row->device_model : null,
                'os_version' => isset($row->os_version) && $row->os_version !== null && $row->os_version !== '' ? (string) $row->os_version : null,
                'client_id' => isset($row->client_id) && $row->client_id !== null && $row->client_id !== '' ? (string) $row->client_id : null,
                'path' => isset($row->path) && $row->path !== null ? (string) $row->path : null,
                'ip' => isset($row->ip) && $row->ip !== null ? (string) $row->ip : null,
                'user_agent' => isset($row->user_agent) && $row->user_agent !== null ? (string) $row->user_agent : null,
                'is_recent' => $isRecent,
            ];
        }

        return $out;
    }

    /** Revoke a Bearer token only if it belongs to the given user. */
    public static function revokeByIdForUser(int $id, int $userId): bool
    {
        $id = (int) $id;
        $userId = (int) $userId;
        if ($id <= 0 || $userId <= 0) {
            return false;
        }
        $stmt = Database::getInstance()->prepare('DELETE FROM api_tokens WHERE id = ? AND user_id = ?');
        $stmt->execute([$id, $userId]);

        return $stmt->rowCount() > 0;
    }
}
