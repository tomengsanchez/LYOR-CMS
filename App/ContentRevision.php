<?php
namespace App;

use Core\Auth;
use Core\Database;

/**
 * Snapshot/restore history for pages and posts (before overwrite).
 */
class ContentRevision
{
    public const MAX_PER_ENTITY = 50;

    /** @return array<string, mixed> */
    public static function snapshotFromPage(object $page): array
    {
        return [
            'title' => (string) ($page->title ?? ''),
            'slug' => (string) ($page->slug ?? ''),
            'body' => (string) ($page->body ?? ''),
            'blocks_json' => $page->blocks_json ?? null,
            'layout_json' => $page->layout_json ?? null,
            'status' => (string) ($page->status ?? 'draft'),
            'meta_title' => (string) ($page->meta_title ?? ''),
            'meta_description' => (string) ($page->meta_description ?? ''),
            'featured_image_id' => !empty($page->featured_image_id) ? (int) $page->featured_image_id : null,
            'llm_summary' => (string) ($page->llm_summary ?? ''),
            'citation_snippet' => (string) ($page->citation_snippet ?? ''),
            'faq_json' => (string) ($page->faq_json ?? ''),
            'robots_noindex' => !empty($page->robots_noindex) ? 1 : 0,
            'content_layout' => (string) ($page->content_layout ?? ''),
            'parent_id' => !empty($page->parent_id) ? (int) $page->parent_id : null,
        ];
    }

    /** @return array<string, mixed> */
    public static function snapshotFromPost(object $post): array
    {
        return [
            'title' => (string) ($post->title ?? ''),
            'slug' => (string) ($post->slug ?? ''),
            'excerpt' => (string) ($post->excerpt ?? ''),
            'body' => (string) ($post->body ?? ''),
            'blocks_json' => $post->blocks_json ?? null,
            'layout_json' => $post->layout_json ?? null,
            'status' => (string) ($post->status ?? 'draft'),
            'category_id' => !empty($post->category_id) ? (int) $post->category_id : null,
            'featured_image_id' => !empty($post->featured_image_id) ? (int) $post->featured_image_id : null,
            'meta_title' => (string) ($post->meta_title ?? ''),
            'meta_description' => (string) ($post->meta_description ?? ''),
            'llm_summary' => (string) ($post->llm_summary ?? ''),
            'citation_snippet' => (string) ($post->citation_snippet ?? ''),
            'faq_json' => (string) ($post->faq_json ?? ''),
            'robots_noindex' => !empty($post->robots_noindex) ? 1 : 0,
            'content_layout' => (string) ($post->content_layout ?? ''),
            'published_at' => $post->published_at ?? null,
            'tags' => \App\Models\Tag::namesForPost((int) ($post->id ?? 0)),
        ];
    }

    public static function record(string $entityType, int $entityId, array $snapshot, ?string $note = null): int
    {
        if (!in_array($entityType, ['page', 'post'], true) || $entityId <= 0) {
            return 0;
        }
        $db = Database::getInstance();
        $stmtMax = $db->prepare('SELECT COALESCE(MAX(revision_no), 0) FROM cms_content_revisions WHERE entity_type = ? AND entity_id = ?');
        $stmtMax->execute([$entityType, $entityId]);
        $next = ((int) $stmtMax->fetchColumn()) + 1;

        $json = json_encode($snapshot, JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            return 0;
        }
        $stmt = $db->prepare('
            INSERT INTO cms_content_revisions (entity_type, entity_id, revision_no, snapshot_json, note, created_by, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ');
        $stmt->execute([
            $entityType,
            $entityId,
            $next,
            $json,
            $note !== null ? mb_substr(trim($note), 0, 255) : null,
            Auth::id(),
            UserTime::nowSql(),
        ]);
        $id = (int) $db->lastInsertId();
        self::prune($entityType, $entityId);
        return $id;
    }

    public static function recordPage(object $page, ?string $note = null): int
    {
        return self::record('page', (int) $page->id, self::snapshotFromPage($page), $note);
    }

    public static function recordPost(object $post, ?string $note = null): int
    {
        return self::record('post', (int) $post->id, self::snapshotFromPost($post), $note);
    }

    private static function prune(string $entityType, int $entityId): void
    {
        $db = Database::getInstance();
        $stmt = $db->prepare('
            SELECT id FROM cms_content_revisions
            WHERE entity_type = ? AND entity_id = ?
            ORDER BY revision_no DESC
        ');
        $stmt->execute([$entityType, $entityId]);
        $ids = $stmt->fetchAll(\PDO::FETCH_COLUMN);
        if (count($ids) <= self::MAX_PER_ENTITY) {
            return;
        }
        $drop = array_slice($ids, self::MAX_PER_ENTITY);
        $in = implode(',', array_map('intval', $drop));
        if ($in !== '') {
            $db->exec("DELETE FROM cms_content_revisions WHERE id IN ({$in})");
        }
    }

    /** @return array<int, object> */
    public static function listFor(string $entityType, int $entityId, int $limit = 30): array
    {
        $limit = max(1, min(100, $limit));
        $stmt = Database::getInstance()->prepare("
            SELECT r.*, u.username AS created_by_name
            FROM cms_content_revisions r
            LEFT JOIN users u ON u.id = r.created_by
            WHERE r.entity_type = ? AND r.entity_id = ?
            ORDER BY r.revision_no DESC
            LIMIT {$limit}
        ");
        $stmt->execute([$entityType, $entityId]);
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }

    public static function find(int $id): ?object
    {
        $stmt = Database::getInstance()->prepare('
            SELECT r.*, u.username AS created_by_name
            FROM cms_content_revisions r
            LEFT JOIN users u ON u.id = r.created_by
            WHERE r.id = ?
        ');
        $stmt->execute([$id]);
        $row = $stmt->fetch(\PDO::FETCH_OBJ);
        return $row ?: null;
    }

    /** @return array<string, mixed>|null */
    public static function decodeSnapshot(object $revision): ?array
    {
        $data = json_decode((string) ($revision->snapshot_json ?? ''), true);
        return is_array($data) ? $data : null;
    }
}
