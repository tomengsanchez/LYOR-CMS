<?php
namespace App\Models;

use App\AuditLog;
use App\CmsSlug;
use App\ContentBlocks;
use App\LayoutBuilder;
use App\PublicSeo;
use App\PublicTheme;
use App\UserTime;
use Core\Auth;
use Core\Database;

class Post
{
    private static function selectSql(): string
    {
        return '
            SELECT p.*,
                   c.name AS category_name,
                   c.slug AS category_slug,
                   u.username AS author_name,
                   fm.mime_type AS featured_mime_type,
                   fm.alt_text AS featured_alt_text,
                   fm.width AS featured_width,
                   fm.height AS featured_height,
                   fm.original_name AS featured_original_name
            FROM cms_posts p
            LEFT JOIN cms_categories c ON c.id = p.category_id
            LEFT JOIN users u ON u.id = p.author_id
            LEFT JOIN cms_media fm ON fm.id = p.featured_image_id AND fm.deleted_at IS NULL
        ';
    }

    public static function allActive(): array
    {
        $db = Database::getInstance();
        return $db->query(self::selectSql() . '
            WHERE p.deleted_at IS NULL
            ORDER BY COALESCE(p.published_at, p.created_at) DESC
        ')->fetchAll(\PDO::FETCH_OBJ);
    }

    public static function find(int $id): ?object
    {
        $stmt = Database::getInstance()->prepare(self::selectSql() . '
            WHERE p.id = ? AND p.deleted_at IS NULL
        ');
        $stmt->execute([$id]);
        $row = $stmt->fetch(\PDO::FETCH_OBJ);
        return $row ?: null;
    }

    public static function findBySlug(string $slug, bool $publishedOnly = false): ?object
    {
        $sql = self::selectSql() . '
            WHERE p.slug = ? AND p.deleted_at IS NULL
        ';
        if ($publishedOnly) {
            $sql .= " AND p.status = 'published'";
        }
        $stmt = Database::getInstance()->prepare($sql);
        $stmt->execute([$slug]);
        $row = $stmt->fetch(\PDO::FETCH_OBJ);
        return $row ?: null;
    }

    public static function findPublished(int $id): ?object
    {
        $stmt = Database::getInstance()->prepare(self::selectSql() . "
            WHERE p.id = ? AND p.deleted_at IS NULL AND p.status = 'published'
        ");
        $stmt->execute([$id]);
        $row = $stmt->fetch(\PDO::FETCH_OBJ);
        return $row ?: null;
    }

    public static function publishedCount(?int $categoryId = null, ?int $tagId = null, ?string $search = null): int
    {
        $sql = "SELECT COUNT(*) FROM cms_posts p WHERE p.deleted_at IS NULL AND p.status = 'published'";
        $params = [];
        if ($categoryId !== null) {
            $sql .= ' AND p.category_id = ?';
            $params[] = $categoryId;
        }
        if ($tagId !== null) {
            $sql .= ' AND EXISTS (SELECT 1 FROM cms_post_tags pt WHERE pt.post_id = p.id AND pt.tag_id = ?)';
            $params[] = $tagId;
        }
        $searchSql = self::searchWhereClause($search, $params);
        $sql .= $searchSql;
        $stmt = Database::getInstance()->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    public static function publishedList(int $limit = 20, int $offset = 0, ?int $categoryId = null, ?int $tagId = null, ?string $search = null): array
    {
        $limit = max(1, min(100, $limit));
        $offset = max(0, $offset);
        $where = "p.deleted_at IS NULL AND p.status = 'published'";
        $params = [];
        if ($categoryId !== null) {
            $where .= ' AND p.category_id = ?';
            $params[] = $categoryId;
        }
        if ($tagId !== null) {
            $where .= ' AND EXISTS (SELECT 1 FROM cms_post_tags pt WHERE pt.post_id = p.id AND pt.tag_id = ?)';
            $params[] = $tagId;
        }
        $where .= self::searchWhereClause($search, $params);
        $stmt = Database::getInstance()->prepare(self::selectSql() . "
            WHERE {$where}
            ORDER BY p.published_at DESC
            LIMIT {$limit} OFFSET {$offset}
        ");
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }

    /** @param array<int, mixed> $params */
    private static function searchWhereClause(?string $search, array &$params): string
    {
        $search = trim((string) $search);
        if ($search === '') {
            return '';
        }
        $params[] = '%' . $search . '%';
        $params[] = '%' . $search . '%';
        $params[] = '%' . $search . '%';
        return ' AND (p.title LIKE ? OR p.excerpt LIKE ? OR p.body LIKE ?)';
    }

    public static function create(array $data): int
    {
        $db = Database::getInstance();
        $slug = CmsSlug::unique($db, 'cms_posts', CmsSlug::from($data['title'] ?? '', 'post'));
        if (CmsSlug::conflictsWithOtherContent($db, 'cms_posts', $slug)) {
            return 0;
        }
        $status = in_array($data['status'] ?? '', ['published', 'draft'], true) ? $data['status'] : 'draft';
        $publishedAt = ($status === 'published') ? UserTime::nowSql() : null;
        $featuredId = \App\Models\Media::resolveImageId(
            !empty($data['featured_image_id']) ? (int) $data['featured_image_id'] : null
        );
        $stmt = $db->prepare('
            INSERT INTO cms_posts (title, slug, excerpt, body, blocks_json, category_id, featured_image_id, meta_title, meta_description, llm_summary, robots_noindex, content_layout, status, published_at, author_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ');
        $catId = !empty($data['category_id']) ? (int) $data['category_id'] : null;
        $stmt->execute([
            trim($data['title'] ?? ''),
            $slug,
            trim($data['excerpt'] ?? '') ?: null,
            $data['body'] ?? '',
            ContentBlocks::normalizeJson($data['blocks_json'] ?? null),
            $catId ?: null,
            $featuredId,
            PublicSeo::normalizeMetaTitle($data['meta_title'] ?? ''),
            PublicSeo::normalizeMetaDescription($data['meta_description'] ?? ''),
            PublicSeo::normalizeLlmSummary($data['llm_summary'] ?? ''),
            !empty($data['robots_noindex']) ? 1 : 0,
            PublicTheme::normalizeContentLayout($data['content_layout'] ?? null),
            $status,
            $publishedAt,
            Auth::id(),
        ]);
        $id = (int) $db->lastInsertId();
        Tag::syncPostTags($id, $data['tags'] ?? '');
        AuditLog::record('post', $id, 'created');
        return $id;
    }

    public static function update(int $id, array $data): bool
    {
        $db = Database::getInstance();
        $existing = self::find($id);
        if (!$existing) {
            return false;
        }
        $slug = trim($data['slug'] ?? $existing->slug);
        if ($slug === '') {
            $slug = CmsSlug::from($data['title'] ?? $existing->title, 'post');
        }
        $slug = CmsSlug::unique($db, 'cms_posts', $slug, $id);
        if (CmsSlug::conflictsWithOtherContent($db, 'cms_posts', $slug, $id)) {
            return false;
        }
        $status = in_array($data['status'] ?? '', ['published', 'draft'], true) ? $data['status'] : 'draft';
        $publishedAt = $existing->published_at;
        if ($status === 'published' && !$publishedAt) {
            $publishedAt = UserTime::nowSql();
        }
        if ($status === 'draft') {
            $publishedAt = null;
        }
        $featuredId = \App\Models\Media::resolveImageId(
            !empty($data['featured_image_id']) ? (int) $data['featured_image_id'] : null
        );
        $stmt = $db->prepare('
            UPDATE cms_posts SET title = ?, slug = ?, excerpt = ?, body = ?, blocks_json = ?, category_id = ?, featured_image_id = ?,
                meta_title = ?, meta_description = ?, llm_summary = ?, robots_noindex = ?, content_layout = ?, status = ?, published_at = ?
            WHERE id = ? AND deleted_at IS NULL
        ');
        $catId = !empty($data['category_id']) ? (int) $data['category_id'] : null;
        $stmt->execute([
            trim($data['title'] ?? ''),
            $slug,
            trim($data['excerpt'] ?? '') ?: null,
            $data['body'] ?? '',
            ContentBlocks::normalizeJson($data['blocks_json'] ?? null),
            $catId ?: null,
            $featuredId,
            PublicSeo::normalizeMetaTitle($data['meta_title'] ?? ''),
            PublicSeo::normalizeMetaDescription($data['meta_description'] ?? ''),
            PublicSeo::normalizeLlmSummary($data['llm_summary'] ?? ''),
            !empty($data['robots_noindex']) ? 1 : 0,
            PublicTheme::normalizeContentLayout($data['content_layout'] ?? null),
            $status,
            $publishedAt,
            $id,
        ]);
        AuditLog::record('post', $id, 'updated');
        Tag::syncPostTags($id, $data['tags'] ?? '');
        return true;
    }

    public static function saveLayoutJson(int $id, ?string $json): bool
    {
        if (!self::find($id)) {
            return false;
        }
        $normalized = LayoutBuilder::normalizeJson($json);
        $stmt = Database::getInstance()->prepare('
            UPDATE cms_posts SET layout_json = ? WHERE id = ? AND deleted_at IS NULL
        ');
        $stmt->execute([$normalized, $id]);
        AuditLog::record('post', $id, 'layout_updated');
        return true;
    }

    public static function softDelete(int $id): bool
    {
        $stmt = Database::getInstance()->prepare('UPDATE cms_posts SET deleted_at = NOW() WHERE id = ? AND deleted_at IS NULL');
        $stmt->execute([$id]);
        if ($stmt->rowCount() > 0) {
            AuditLog::record('post', $id, 'deleted');
            return true;
        }
        return false;
    }

    public static function countsByStatus(): array
    {
        $rows = Database::getInstance()->query("
            SELECT status, COUNT(*) AS cnt FROM cms_posts WHERE deleted_at IS NULL GROUP BY status
        ")->fetchAll(\PDO::FETCH_ASSOC);
        $out = ['draft' => 0, 'published' => 0];
        foreach ($rows as $r) {
            $out[$r['status']] = (int) $r['cnt'];
        }
        return $out;
    }

    public static function hasFeaturedImage(object $post): bool
    {
        return !empty($post->featured_image_id)
            && \App\Models\Media::isImageMime((string) ($post->featured_mime_type ?? ''));
    }

    public static function publishedForSitemap(): array
    {
        return Database::getInstance()->query("
            SELECT p.slug, p.updated_at, p.published_at, p.category_id, c.slug AS category_slug
            FROM cms_posts p
            LEFT JOIN cms_categories c ON c.id = p.category_id
            WHERE p.deleted_at IS NULL AND p.status = 'published' AND p.robots_noindex = 0
            ORDER BY p.published_at DESC
        ")->fetchAll(\PDO::FETCH_OBJ);
    }

    /** @return array<int, object> */
    public static function publishedForLlms(int $limit = 30): array
    {
        $limit = max(1, min(100, $limit));
        $stmt = Database::getInstance()->query(self::selectSql() . "
            WHERE p.deleted_at IS NULL AND p.status = 'published' AND p.robots_noindex = 0
            ORDER BY p.published_at DESC
            LIMIT {$limit}
        ");
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }

    /** @return array<int, object> */
    public static function publishedForFeed(int $limit = 20): array
    {
        $limit = max(1, min(50, $limit));
        $stmt = Database::getInstance()->query(self::selectSql() . "
            WHERE p.deleted_at IS NULL AND p.status = 'published' AND p.robots_noindex = 0
            ORDER BY p.published_at DESC
            LIMIT {$limit}
        ");
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }

    /** @return array<int, object> */
    public static function recentDrafts(int $limit = 5): array
    {
        $limit = max(1, min(20, $limit));
        $stmt = Database::getInstance()->query(self::selectSql() . "
            WHERE p.deleted_at IS NULL AND p.status = 'draft'
            ORDER BY p.updated_at DESC
            LIMIT {$limit}
        ");
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }
}
