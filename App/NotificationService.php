<?php
namespace App;

use Core\Database;
use App\Models\AppSettings;

/**
 * In-app and email notifications for Simple CMS events.
 */
class NotificationService
{
    public const TYPE_PAGE_PUBLISHED = 'page_published';
    public const TYPE_POST_PUBLISHED = 'post_published';
    public const TYPE_MEDIA_UPLOADED = 'media_uploaded';

    public const RELATED_PAGE = 'page';
    public const RELATED_POST = 'post';
    public const RELATED_MEDIA = 'media';

    public static function notifyAdmins(string $type, string $relatedType, int $relatedId, string $message): void
    {
        $db = Database::getInstance();
        $users = $db->query("
            SELECT u.id, u.email FROM users u
            INNER JOIN roles r ON r.id = u.role_id
            WHERE r.name = 'Administrator'
        ")->fetchAll(\PDO::FETCH_OBJ);

        if ($users === []) {
            return;
        }

        $ins = $db->prepare('INSERT INTO notifications (user_id, type, related_type, related_id, message) VALUES (?, ?, ?, ?, ?)');
        $emailConfig = AppSettings::getEmailConfig();
        $sendEmail = !empty($emailConfig->enable_notification_emails);
        $baseUrl = defined('BASE_URL') && BASE_URL ? rtrim(BASE_URL, '/') : '';

        foreach ($users as $u) {
            $uid = (int) $u->id;
            $prefs = UserNotificationSettings::getForUserId($uid);
            if (empty($prefs[UserNotificationSettings::prefKeyForType($type)])) {
                continue;
            }
            $ins->execute([$uid, $type, $relatedType, $relatedId, $message]);
            $notificationId = (int) $db->lastInsertId();
            if ($sendEmail && $notificationId && !empty(trim($u->email ?? ''))) {
                $clickUrl = $baseUrl . AdminPath::url('notifications/click/' . $notificationId);
                $subject = 'Simple CMS: ' . $message;
                $body = '<p>' . htmlspecialchars($message) . '</p><p><a href="' . htmlspecialchars($clickUrl) . '">Open in admin</a></p>';
                $db->prepare('INSERT INTO email_queue (to_email, subject, body) VALUES (?, ?, ?)')
                    ->execute([trim($u->email), $subject, $body]);
            }
        }
    }

    public static function getForUser(int $userId, int $limit = 20): array
    {
        $db = Database::getInstance();
        $limit = max(1, min(100, $limit));
        $stmt = $db->prepare('
            SELECT id, type, related_type, related_id, message, created_at, clicked_at
            FROM notifications
            WHERE user_id = ? AND clicked_at IS NULL
            ORDER BY created_at DESC
            LIMIT ' . $limit
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }

    /** @return array{items: array<int,object>, total:int, page:int, per_page:int, total_pages:int} */
    public static function listForUser(int $userId, array $filters, int $page = 1, int $perPage = 20): array
    {
        $db = Database::getInstance();
        $page = max(1, $page);
        $perPage = max(1, min(100, $perPage));
        $where = ['n.user_id = :uid'];
        $params = ['uid' => $userId];

        $module = $filters['module'] ?? '';
        if (in_array($module, [self::RELATED_PAGE, self::RELATED_POST, self::RELATED_MEDIA], true)) {
            $where[] = 'n.related_type = :rtype';
            $params['rtype'] = $module;
        }
        if (!empty($filters['from'])) {
            $where[] = 'n.created_at >= :from';
            $params['from'] = $filters['from'] . ' 00:00:00';
        }
        if (!empty($filters['to'])) {
            $where[] = 'n.created_at <= :to';
            $params['to'] = $filters['to'] . ' 23:59:59';
        }

        $whereSql = implode(' AND ', $where);
        $offset = ($page - 1) * $perPage;
        $stmt = $db->prepare("
            SELECT n.id, n.type, n.related_type, n.related_id, n.message, n.created_at, n.clicked_at
            FROM notifications n WHERE {$whereSql}
            ORDER BY n.created_at DESC, n.id DESC
            LIMIT {$perPage} OFFSET {$offset}
        ");
        $stmt->execute($params);
        $items = $stmt->fetchAll(\PDO::FETCH_OBJ);

        $stmtCount = $db->prepare("SELECT COUNT(*) FROM notifications n WHERE {$whereSql}");
        $stmtCount->execute($params);
        $total = (int) $stmtCount->fetchColumn();

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => (int) ceil(max(1, $total) / $perPage),
        ];
    }

    /** Build admin URL for a notification target without marking it clicked. */
    public static function urlForRelated(string $relatedType, int $relatedId): string
    {
        return match ($relatedType) {
            self::RELATED_PAGE => AdminPath::url('pages/view/' . $relatedId),
            self::RELATED_POST => AdminPath::url('posts/view/' . $relatedId),
            self::RELATED_MEDIA => AdminPath::url('media'),
            default => AdminPath::url(),
        };
    }

    public static function clickAndGetUrl(int $notificationId, int $userId): ?string
    {
        $db = Database::getInstance();
        $stmt = $db->prepare('SELECT id, related_type, related_id FROM notifications WHERE id = ? AND user_id = ?');
        $stmt->execute([$notificationId, $userId]);
        $row = $stmt->fetch(\PDO::FETCH_OBJ);
        if (!$row) {
            return null;
        }
        $db->prepare('UPDATE notifications SET clicked_at = NOW() WHERE id = ?')->execute([$notificationId]);

        return self::urlForRelated((string) $row->related_type, (int) $row->related_id);
    }
}
