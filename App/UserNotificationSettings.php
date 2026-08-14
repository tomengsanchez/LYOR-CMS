<?php
namespace App;

use Core\Auth;
use Core\Database;

class UserNotificationSettings
{
    public const MODULE = 'notification_preferences';

    public const NOTIFY_PAGE_PUBLISHED = 'notify_page_published';
    public const NOTIFY_POST_PUBLISHED = 'notify_post_published';
    public const NOTIFY_MEDIA_UPLOADED = 'notify_media_uploaded';

    public static function defaultConfig(): array
    {
        return [
            self::NOTIFY_PAGE_PUBLISHED => true,
            self::NOTIFY_POST_PUBLISHED => true,
            self::NOTIFY_MEDIA_UPLOADED => false,
        ];
    }

    public static function prefKeyForType(string $type): string
    {
        return match ($type) {
            NotificationService::TYPE_PAGE_PUBLISHED => self::NOTIFY_PAGE_PUBLISHED,
            NotificationService::TYPE_POST_PUBLISHED => self::NOTIFY_POST_PUBLISHED,
            NotificationService::TYPE_MEDIA_UPLOADED => self::NOTIFY_MEDIA_UPLOADED,
            default => self::NOTIFY_PAGE_PUBLISHED,
        };
    }

    public static function get(): array
    {
        $userId = Auth::id();
        return $userId ? self::getForUserId((int) $userId) : self::defaultConfig();
    }

    public static function getForUserId(int $userId): array
    {
        $db = Database::getInstance();
        try {
            $stmt = $db->prepare('SELECT config FROM user_dashboard_config WHERE user_id = ? AND module = ?');
            $stmt->execute([$userId, self::MODULE]);
            $row = $stmt->fetch(\PDO::FETCH_OBJ);
        } catch (\Throwable $e) {
            return self::defaultConfig();
        }
        if (!$row || !$row->config) {
            return self::defaultConfig();
        }
        $decoded = json_decode($row->config, true);
        if (!is_array($decoded)) {
            return self::defaultConfig();
        }
        $merged = self::defaultConfig();
        foreach ($merged as $key => $default) {
            if (array_key_exists($key, $decoded)) {
                $merged[$key] = !empty($decoded[$key]);
            }
        }
        return $merged;
    }

    public static function save(array $config): void
    {
        $userId = Auth::id();
        if (!$userId) {
            return;
        }
        $json = json_encode([
            self::NOTIFY_PAGE_PUBLISHED => !empty($config[self::NOTIFY_PAGE_PUBLISHED]),
            self::NOTIFY_POST_PUBLISHED => !empty($config[self::NOTIFY_POST_PUBLISHED]),
            self::NOTIFY_MEDIA_UPLOADED => !empty($config[self::NOTIFY_MEDIA_UPLOADED]),
        ]);
        $db = Database::getInstance();
        $stmt = $db->prepare('INSERT INTO user_dashboard_config (user_id, module, config) VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE config = VALUES(config)');
        $stmt->execute([$userId, self::MODULE, $json]);
    }
}
