<?php
namespace App;

/**
 * Remember the last list URI (page + filters) per module so View/Edit "Back"
 * returns to the same list context. Session-scoped; allowlisted relative paths only.
 */
final class ListReturn
{
    private const SESSION_KEY = '_list_return';

    private const MAX_URI_LEN = 2048;

    /** @var array<string, string> module key => list path prefix (exact root) */
    private const ALLOWED = [
        'profile' => '/profile',
        'structure' => '/structure',
        'grievance' => '/grievance/list',
    ];

    public static function remember(string $module): void
    {
        $default = self::ALLOWED[$module] ?? null;
        if ($default === null) {
            return;
        }
        $uri = self::currentRequestUri();
        $safe = self::sanitize($module, $uri);
        if ($safe === null) {
            return;
        }
        if (!isset($_SESSION[self::SESSION_KEY]) || !is_array($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = [];
        }
        $_SESSION[self::SESSION_KEY][$module] = $safe;
    }

    /** Last remembered list URL for the module, or $fallback (default = list root). */
    public static function url(string $module, ?string $fallback = null): string
    {
        $default = self::ALLOWED[$module] ?? '/';
        $fallback = $fallback ?? $default;
        $stored = $_SESSION[self::SESSION_KEY][$module] ?? null;
        if (!is_string($stored) || $stored === '') {
            return $fallback;
        }
        $safe = self::sanitize($module, $stored);
        return $safe ?? $fallback;
    }

    private static function currentRequestUri(): string
    {
        $uri = (string) ($_SERVER['REQUEST_URI'] ?? '');
        $path = parse_url($uri, PHP_URL_PATH);
        $query = parse_url($uri, PHP_URL_QUERY);
        $path = is_string($path) ? $path : '';
        $path = rtrim($path, '/') ?: '/';
        if (is_string($query) && $query !== '') {
            return $path . '?' . $query;
        }
        return $path;
    }

    /**
     * Allow only same-app relative list URLs for the given module.
     */
    public static function sanitize(string $module, string $uri): ?string
    {
        $root = self::ALLOWED[$module] ?? null;
        if ($root === null) {
            return null;
        }
        $uri = trim($uri);
        if ($uri === '' || strlen($uri) > self::MAX_URI_LEN) {
            return null;
        }
        if ($uri[0] !== '/' || str_starts_with($uri, '//') || str_contains($uri, '\\')) {
            return null;
        }
        if (preg_match('#^[a-z][a-z0-9+.-]*:#i', $uri)) {
            return null;
        }
        $path = parse_url($uri, PHP_URL_PATH);
        if (!is_string($path)) {
            return null;
        }
        $path = rtrim($path, '/') ?: '/';
        if ($path !== $root) {
            return null;
        }
        $query = parse_url($uri, PHP_URL_QUERY);
        if (is_string($query) && $query !== '') {
            // Drop cursor hints; page + filters are enough and stay stable.
            parse_str($query, $params);
            if (!is_array($params)) {
                return $path;
            }
            unset($params['after_id'], $params['before_id']);
            $qs = http_build_query($params);
            return $qs !== '' ? ($path . '?' . $qs) : $path;
        }
        return $path;
    }
}
