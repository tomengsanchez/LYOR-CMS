<?php
namespace Core;

class Csrf
{
    private const SESSION_KEY = 'csrf_token';

    public static function token(): string
    {
        if (empty($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(32));
        }
        return $_SESSION[self::SESSION_KEY];
    }

    public static function validate(?string $token = null): bool
    {
        $token = $token ?? ($_POST['csrf_token'] ?? '');
        $valid = hash_equals(self::token(), $token);
        if ($valid) {
            self::regenerate();
        }
        return $valid;
    }

    /**
     * Verify token matches session without rotating.
     * Use for quiet AJAX (e.g. Ask Help) so open forms keep working.
     */
    public static function check(?string $token = null): bool
    {
        $token = $token ?? ($_POST['csrf_token'] ?? '');
        if (!is_string($token) || $token === '') {
            return false;
        }
        return hash_equals(self::token(), $token);
    }

    public static function regenerate(): void
    {
        $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(32));
    }

    /** Returns HTML for a hidden input to include in forms. */
    public static function field(): string
    {
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(self::token(), ENT_QUOTES, 'UTF-8') . '">';
    }
}
