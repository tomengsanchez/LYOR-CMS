<?php
namespace Core;

class Logger
{
    private static string $logDir;
    private static bool $initialized = false;

    public static function init(): void
    {
        if (self::$initialized) return;
        self::$logDir = ROOT . '/logs';
        if (!is_dir(self::$logDir)) {
            mkdir(self::$logDir, 0755, true);
        }
        self::$initialized = true;
    }

    public static function log(string $file, string $message, array $context = []): void
    {
        self::init();
        $path = self::$logDir . '/' . $file;
        $timestamp = date('Y-m-d H:i:s');
        $contextStr = empty($context) ? '' : ' ' . json_encode($context);
        file_put_contents($path, "[{$timestamp}] {$message}{$contextStr}\n", FILE_APPEND | LOCK_EX);
    }

    public static function php(string $message, array $context = []): void
    {
        self::log('php_error.log', $message, $context);
    }

    public static function database(string $message, array $context = []): void
    {
        self::log('database_error.log', $message, $context);
    }

    public static function auth(string $message, array $context = []): void
    {
        self::log('auth.log', $message, $context);
    }

    public static function app(string $message, array $context = []): void
    {
        self::log('app.log', $message, $context);
    }

    public static function email(string $message, array $context = []): void
    {
        self::log('email.log', $message, $context);
    }
}
