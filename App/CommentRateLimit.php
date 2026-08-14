<?php
namespace App;

use Core\Database;

/**
 * Per-IP comment submission rate limit (MySQL/MariaDB compatible).
 */
class CommentRateLimit
{
    public static function isLimited(string $ip, int $maxPerHour): bool
    {
        $maxPerHour = max(0, $maxPerHour);
        if ($maxPerHour === 0 || $ip === '') {
            return false;
        }
        $stmt = Database::getInstance()->prepare('
            SELECT COUNT(*) FROM cms_comments
            WHERE ip_address = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
        ');
        $stmt->execute([$ip]);
        return (int) $stmt->fetchColumn() >= $maxPerHour;
    }

    public static function clientIp(): string
    {
        return substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45);
    }
}
