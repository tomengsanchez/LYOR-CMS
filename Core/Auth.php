<?php
namespace Core;

class Auth
{
    private static ?object $user = null;
    private static ?array $userCapabilities = null;

    public static function init(): void
    {
        $params = [
            'lifetime' => 0,
            'path' => '/',
            'domain' => '',
            'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            'httponly' => true,
            'samesite' => 'Lax',
        ];
        if (PHP_VERSION_ID >= 70300) {
            session_set_cookie_params($params);
        } else {
            session_set_cookie_params(
                $params['lifetime'],
                $params['path'] . '; samesite=' . $params['samesite'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }
        session_start();
        if (isset($_SESSION['user_id'])) {
            $uri = $_SERVER['REQUEST_URI'] ?? '';
            $isApi = strpos($uri, '/api/') !== false;
            $logoutMins = (int) \App\Models\AppSettings::get('user_logout_after_minutes', 30);
            if ($logoutMins > 0 && !$isApi) {
                $lastActivity = (int) ($_SESSION['last_activity'] ?? 0);
                if ($lastActivity && (time() - $lastActivity) > $logoutMins * 60) {
                    session_unset();
                    session_destroy();
                    header('Location: ' . \App\AdminPath::url('login') . '?timeout=1');
                    exit;
                }
            }
            $_SESSION['last_activity'] = time();

            $db = Database::getInstance();
            $stmt = $db->prepare('SELECT u.*, r.name as role_name FROM users u 
                LEFT JOIN roles r ON u.role_id = r.id 
                WHERE u.id = ?');
            $stmt->execute([$_SESSION['user_id']]);
            self::$user = $stmt->fetch(\PDO::FETCH_OBJ);
        }
        if (self::$user !== null) {
            // Track and enforce user sessions for "see where you logged in" / remote logout.
            \App\UserSession::touchOrEnforceForCurrent();
        }
        // Bearer token for REST API (when no session)
        if (self::$user === null) {
            $bearer = \App\ApiToken::getBearerToken();
            if ($bearer !== null) {
                $userId = \App\ApiToken::validate($bearer);
                if ($userId !== null) {
                    $db = Database::getInstance();
                    $stmt = $db->prepare('SELECT u.*, r.name as role_name FROM users u 
                        LEFT JOIN roles r ON u.role_id = r.id 
                        WHERE u.id = ?');
                    $stmt->execute([$userId]);
                    self::$user = $stmt->fetch(\PDO::FETCH_OBJ);
                }
            }
        }
    }

    public static function can(string $capability): bool
    {
        if (!self::check()) return false;
        if (self::isAdmin()) return true;
        if (self::$userCapabilities === null) {
            $db = Database::getInstance();
            $stmt = $db->prepare('SELECT capability FROM role_capabilities WHERE role_id = ?');
            $stmt->execute([self::$user->role_id]);
            self::$userCapabilities = $stmt->fetchAll(\PDO::FETCH_COLUMN);
        }
        return in_array($capability, self::$userCapabilities, true);
    }

    public static function canAny(array $capabilities): bool
    {
        foreach ($capabilities as $cap) {
            if (self::can($cap)) return true;
        }
        return false;
    }

    public static function check(): bool
    {
        return isset($_SESSION['user_id']) || self::$user !== null;
    }

    public static function user(): ?object
    {
        return self::$user;
    }

    public static function id(): ?int
    {
        $sid = $_SESSION['user_id'] ?? null;
        if ($sid !== null) {
            return (int) $sid;
        }
        return self::$user ? (int) self::$user->id : null;
    }

    public static function login(int $userId): void
    {
        $_SESSION['user_id'] = $userId;
        $_SESSION['last_activity'] = time();
        // Mitigate session fixation: new id after privilege elevation (password or 2FA path).
        if (session_status() === \PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }

    public static function logout(): void
    {
        // Mark current device/browser session as revoked if possible.
        \App\UserSession::revokeCurrent();
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }

    public static function hasRole(string $role): bool
    {
        return self::$user && self::$user->role_name === $role;
    }

    public static function isAdmin(): bool
    {
        return self::hasRole('Administrator');
    }

    public static function isCoordinator(): bool
    {
        return self::hasRole('Coordinator');
    }

    /** @return list<string> Capability keys for the current user (empty if guest). Administrator: all keys from Capabilities. */
    public static function capabilitiesForCurrentUser(): array
    {
        if (!self::check()) {
            return [];
        }
        if (self::isAdmin()) {
            return array_keys(\App\Capabilities::all());
        }
        if (self::$userCapabilities === null) {
            $db = Database::getInstance();
            $stmt = $db->prepare('SELECT capability FROM role_capabilities WHERE role_id = ?');
            $stmt->execute([self::$user->role_id]);
            self::$userCapabilities = $stmt->fetchAll(\PDO::FETCH_COLUMN) ?: [];
        }
        return array_values(self::$userCapabilities);
    }
}
