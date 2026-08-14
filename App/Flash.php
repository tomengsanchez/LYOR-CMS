<?php
namespace App;

/**
 * One-request flash messages (PRG success / error feedback).
 */
final class Flash
{
    private const SESSION_KEY = '_flash_messages';

    public static function success(string $message): void
    {
        self::push('success', $message);
    }

    public static function error(string $message): void
    {
        self::push('danger', $message);
    }

    public static function warning(string $message): void
    {
        self::push('warning', $message);
    }

    /** @return list<array{type: string, message: string}> */
    public static function pull(): array
    {
        $items = $_SESSION[self::SESSION_KEY] ?? [];
        unset($_SESSION[self::SESSION_KEY]);

        return is_array($items) ? $items : [];
    }

    private static function push(string $type, string $message): void
    {
        $message = trim($message);
        if ($message === '') {
            return;
        }
        if (!isset($_SESSION[self::SESSION_KEY]) || !is_array($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = [];
        }
        $_SESSION[self::SESSION_KEY][] = ['type' => $type, 'message' => $message];
    }
}
