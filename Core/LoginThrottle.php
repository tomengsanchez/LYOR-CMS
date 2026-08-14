<?php
namespace Core;

use App\Models\AppSettings;

class LoginThrottle
{
    private const STORAGE_FILE = ROOT . '/logs/login_throttle.json';

    private static function getConfig(): object
    {
        return AppSettings::getSecurityConfig();
    }

    /**
     * Runtime throttle values.
     * Realtime Security threshold overrides max attempts when enabled + auto-block is on.
     */
    private static function getThrottleRuntime(): array
    {
        $security = self::getConfig();
        $enabled = !empty($security->login_throttle_enabled);

        $maxAttempts = (int) ($security->login_throttle_max_attempts ?? 5);
        $maxAttempts = max(1, min(50, $maxAttempts));

        $lockoutMinutes = (int) ($security->login_throttle_lockout_minutes ?? 15);
        $lockoutMinutes = max(1, min(1440, $lockoutMinutes));

        $realtimeEnabled = AppSettings::get('realtime_security_enabled', '1') === '1';
        $realtimeBlockIps = AppSettings::get('realtime_security_block_suspicious_ips', '1') === '1';
        if ($enabled && $realtimeEnabled && $realtimeBlockIps) {
            $realtimeThreshold = (int) AppSettings::get('realtime_security_failed_login_threshold', (string) $maxAttempts);
            $maxAttempts = max(3, min(50, $realtimeThreshold));
        }

        return [
            'enabled' => $enabled,
            'max_attempts' => $maxAttempts,
            'lockout_minutes' => $lockoutMinutes,
        ];
    }

    /**
     * Client IP for throttling.
     * Uses X-Forwarded-For / Client-IP only when REMOTE_ADDR is in config trusted_proxies.
     * Otherwise REMOTE_ADDR alone (prevents clients from spoofing lockout keys).
     */
    public static function getClientIp(): string
    {
        $remote = self::normalizeIp((string) ($_SERVER['REMOTE_ADDR'] ?? ''));
        if ($remote === null) {
            return '0.0.0.0';
        }

        if (!self::remoteIsTrustedProxy($remote)) {
            return $remote;
        }

        $forwarded = self::firstForwardedIp((string) ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? ''));
        if ($forwarded !== null) {
            return $forwarded;
        }

        $clientIp = self::normalizeIp((string) ($_SERVER['HTTP_CLIENT_IP'] ?? ''));
        if ($clientIp !== null) {
            return $clientIp;
        }

        return $remote;
    }

    /** @return list<string> */
    public static function trustedProxies(): array
    {
        if (defined('TRUSTED_PROXIES') && is_array(TRUSTED_PROXIES)) {
            return TRUSTED_PROXIES;
        }
        return [];
    }

    private static function remoteIsTrustedProxy(string $remote): bool
    {
        $trusted = self::trustedProxies();
        return $trusted !== [] && in_array($remote, $trusted, true);
    }

    private static function firstForwardedIp(string $header): ?string
    {
        if (trim($header) === '') {
            return null;
        }
        $parts = explode(',', $header);
        return self::normalizeIp(trim((string) ($parts[0] ?? '')));
    }

    private static function normalizeIp(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }
        return filter_var($value, FILTER_VALIDATE_IP) ? $value : null;
    }

    public static function isBlocked(string $ip): bool
    {
        $runtime = self::getThrottleRuntime();
        if (empty($runtime['enabled'])) {
            return false;
        }

        $state = self::loadState();
        if (!isset($state[$ip])) {
            return false;
        }

        $blockedUntil = isset($state[$ip]['blocked_until']) ? (int) $state[$ip]['blocked_until'] : 0;
        if ($blockedUntil > time()) {
            return true;
        }

        // Lockout period has expired – clear entry.
        unset($state[$ip]);
        self::saveState($state);
        return false;
    }

    public static function recordFailure(string $ip): void
    {
        $runtime = self::getThrottleRuntime();
        if (empty($runtime['enabled'])) {
            return;
        }

        $state = self::loadState();
        $now = time();

        $maxAttempts = (int) ($runtime['max_attempts'] ?? 5);
        $lockoutMinutes = (int) ($runtime['lockout_minutes'] ?? 15);

        $entry = $state[$ip] ?? [
            'count' => 0,
            'blocked_until' => 0,
        ];

        // If currently blocked and still within lockout window, keep as-is.
        if (!empty($entry['blocked_until']) && (int) $entry['blocked_until'] > $now) {
            return;
        }

        $entry['count'] = (int) ($entry['count'] ?? 0) + 1;

        if ($entry['count'] >= $maxAttempts) {
            $entry['blocked_until'] = $now + ($lockoutMinutes * 60);
            $entry['count'] = 0;
            Logger::auth('Too many login attempts. IP blocked.', [
                'ip' => $ip,
                'lockout_minutes' => $lockoutMinutes,
                'threshold' => $maxAttempts,
            ]);
        }

        $state[$ip] = $entry;
        self::saveState($state);
    }

    public static function clear(string $ip): void
    {
        $runtime = self::getThrottleRuntime();
        if (empty($runtime['enabled'])) {
            return;
        }

        $state = self::loadState();
        if (isset($state[$ip])) {
            unset($state[$ip]);
            self::saveState($state);
        }
    }

    private static function loadState(): array
    {
        $file = self::STORAGE_FILE;
        if (!is_file($file)) {
            return [];
        }
        $json = @file_get_contents($file);
        if ($json === false || $json === '') {
            return [];
        }
        $data = json_decode($json, true);
        return is_array($data) ? $data : [];
    }

    private static function saveState(array $state): void
    {
        $file = self::STORAGE_FILE;
        $dir = dirname($file);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        @file_put_contents($file, json_encode($state), LOCK_EX);
    }
}

