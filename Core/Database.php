<?php
namespace Core;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $instance = null;
    private static array $config;
    private static bool $sessionTimezoneApplied = false;

    public static function init(array $config): void
    {
        self::$config = $config;
    }

    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            $dsn = sprintf(
                    'mysql:host=%s;dbname=%s;charset=%s',
                    self::$config['host'],
                    self::$config['dbname'],
                    self::$config['charset']
                );
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ];
            try {
                if (SystemDebug::isCollecting()) {
                    self::$instance = new LoggingPDO(
                        $dsn,
                        self::$config['username'],
                        self::$config['password'],
                        $options
                    );
                } else {
                    self::$instance = new PDO(
                        $dsn,
                        self::$config['username'],
                        self::$config['password'],
                        $options
                    );
                }
                self::applySessionTimezone(self::$instance);
            } catch (PDOException $e) {
                Logger::database($e->getMessage(), [
                    'dsn' => $dsn ?? '',
                    'code' => $e->getCode(),
                ]);
                die('Database connection failed: ' . $e->getMessage());
            }
        }
        return self::$instance;
    }

    /**
     * Align MySQL/MariaDB session time_zone with the app timezone (org System → General).
     * Uses offset form (+08:00) so zone tables are not required.
     * Does not ALTER tables or convert business DATETIME columns.
     */
    private static function applySessionTimezone(PDO $pdo): void
    {
        if (self::$sessionTimezoneApplied) {
            return;
        }
        try {
            $offset = \App\UserTime::mysqlOffset();
            if (!preg_match('/^[+-]\d{2}:\d{2}$/', $offset)) {
                $offset = '+00:00';
            }
            $pdo->exec('SET time_zone = ' . $pdo->quote($offset));
            self::$sessionTimezoneApplied = true;
        } catch (\Throwable $e) {
            // Keep connection usable if SET fails (e.g. restricted host).
            Logger::database('Failed to set session time_zone: ' . $e->getMessage(), []);
        }
    }

    /** Re-apply session TZ after org timezone is changed in the same request (optional). */
    public static function refreshSessionTimezone(): void
    {
        if (self::$instance === null) {
            return;
        }
        self::$sessionTimezoneApplied = false;
        self::applySessionTimezone(self::$instance);
    }
}
