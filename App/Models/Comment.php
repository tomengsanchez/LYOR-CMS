<?php
namespace App\Models;

use App\AuditLog;
use App\CommentRateLimit;
use App\DiscussionSettings;
use Core\Database;

class Comment
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_SPAM = 'spam';
    public const STATUS_TRASH = 'trash';

    /** @return array<string, string> */
    public static function statuses(): array
    {
        return [
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_PENDING  => 'Pending',
            self::STATUS_SPAM     => 'Spam',
            self::STATUS_TRASH    => 'Trash',
        ];
    }

    /** @return array<int, object> */
    public static function forPost(int $postId, bool $publicOnly = true): array
    {
        $sql = '
            SELECT c.*, p.title AS post_title
            FROM cms_comments c
            INNER JOIN cms_posts p ON p.id = c.post_id
            WHERE c.post_id = ? AND c.parent_id IS NULL
        ';
        if ($publicOnly) {
            $sql .= " AND c.status = 'approved'";
        } else {
            $sql .= " AND c.status <> 'trash'";
        }
        $sql .= ' ORDER BY c.created_at ASC';
        $stmt = Database::getInstance()->prepare($sql);
        $stmt->execute([$postId]);
        $top = $stmt->fetchAll(\PDO::FETCH_OBJ);
        foreach ($top as $comment) {
            $comment->replies = self::repliesFor((int) $comment->id, $publicOnly);
        }
        return $top;
    }

    /** @return array<int, object> */
    public static function repliesFor(int $parentId, bool $publicOnly = true): array
    {
        $sql = 'SELECT * FROM cms_comments WHERE parent_id = ?';
        if ($publicOnly) {
            $sql .= " AND status = 'approved'";
        } else {
            $sql .= " AND status <> 'trash'";
        }
        $sql .= ' ORDER BY created_at ASC';
        $stmt = Database::getInstance()->prepare($sql);
        $stmt->execute([$parentId]);
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }

    /** @return array<int, object> */
    public static function allForAdmin(?string $status = null): array
    {
        $sql = '
            SELECT c.*, p.title AS post_title, p.slug AS post_slug
            FROM cms_comments c
            INNER JOIN cms_posts p ON p.id = c.post_id
            WHERE 1=1
        ';
        $params = [];
        if ($status !== null && $status !== '' && array_key_exists($status, self::statuses())) {
            $sql .= ' AND c.status = ?';
            $params[] = $status;
        } else {
            $sql .= " AND c.status <> 'trash'";
        }
        $sql .= ' ORDER BY c.created_at DESC LIMIT 500';
        $stmt = Database::getInstance()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }

    public static function find(int $id): ?object
    {
        $stmt = Database::getInstance()->prepare('SELECT * FROM cms_comments WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch(\PDO::FETCH_OBJ);
        return $row ?: null;
    }

    public static function createPublic(int $postId, array $data): ?int
    {
        if (!DiscussionSettings::get()->comments_enabled) {
            return null;
        }
        if (trim((string) ($data['website'] ?? '')) !== '') {
            return null;
        }
        $post = Post::findPublished($postId);
        if (!$post) {
            return null;
        }

        $name = trim((string) ($data['author_name'] ?? ''));
        $email = trim((string) ($data['author_email'] ?? ''));
        $content = trim((string) ($data['content'] ?? ''));
        if ($content === '') {
            return null;
        }
        if (DiscussionSettings::get()->require_name_email && ($name === '' || $email === '')) {
            return null;
        }
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        $ip = CommentRateLimit::clientIp();
        $rateLimit = DiscussionSettings::get()->comment_rate_limit_per_hour;
        if (CommentRateLimit::isLimited($ip, $rateLimit)) {
            return null;
        }

        $parentId = !empty($data['parent_id']) ? (int) $data['parent_id'] : null;
        if ($parentId) {
            $parent = self::find($parentId);
            if (!$parent || (int) $parent->post_id !== $postId) {
                $parentId = null;
            }
        }

        $status = DiscussionSettings::get()->moderation ? self::STATUS_PENDING : self::STATUS_APPROVED;
        $url = trim((string) ($data['author_url'] ?? ''));

        $stmt = Database::getInstance()->prepare('
            INSERT INTO cms_comments (post_id, parent_id, author_name, author_email, author_url, content, status, ip_address, user_agent)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ');
        $stmt->execute([
            $postId,
            $parentId,
            $name !== '' ? $name : 'Anonymous',
            $email !== '' ? $email : 'noreply@localhost',
            $url !== '' ? $url : null,
            $content,
            $status,
            substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45) ?: null,
            substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255) ?: null,
        ]);
        $id = (int) Database::getInstance()->lastInsertId();
        AuditLog::record('comment', $id, 'created');
        return $id;
    }

    public static function setStatus(int $id, string $status): bool
    {
        if (!array_key_exists($status, self::statuses())) {
            return false;
        }
        $stmt = Database::getInstance()->prepare('UPDATE cms_comments SET status = ? WHERE id = ?');
        $stmt->execute([$status, $id]);
        if ($stmt->rowCount() > 0) {
            AuditLog::record('comment', $id, 'status_' . $status);
            return true;
        }
        return false;
    }

    public static function delete(int $id): bool
    {
        return self::setStatus($id, self::STATUS_TRASH);
    }

    public static function pendingCount(): int
    {
        return (int) Database::getInstance()->query("
            SELECT COUNT(*) FROM cms_comments WHERE status = 'pending'
        ")->fetchColumn();
    }

    /** @return array<int, object> */
    public static function recentPending(int $limit = 5): array
    {
        $limit = max(1, min(20, $limit));
        $stmt = Database::getInstance()->prepare("
            SELECT c.*, p.title AS post_title, p.slug AS post_slug
            FROM cms_comments c
            INNER JOIN cms_posts p ON p.id = c.post_id
            WHERE c.status = 'pending'
            ORDER BY c.created_at DESC
            LIMIT {$limit}
        ");
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }
}
