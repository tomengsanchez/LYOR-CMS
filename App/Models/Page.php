<?php
namespace App\Models;

use App\AuditLog;
use App\CmsSlug;
use App\ContentBlocks;
use App\ContentRevision;
use App\LayoutBuilder;
use App\PublicSeo;
use App\PublicTheme;
use Core\Auth;
use Core\Database;

class Page
{
    private static function selectSql(): string
    {
        return '
            SELECT p.*,
                   u.username AS author_name,
                   fm.mime_type AS featured_mime_type,
                   fm.alt_text AS featured_alt_text,
                   fm.width AS featured_width,
                   fm.height AS featured_height,
                   fm.original_name AS featured_original_name
            FROM cms_pages p
            LEFT JOIN users u ON u.id = p.author_id
            LEFT JOIN cms_media fm ON fm.id = p.featured_image_id AND fm.deleted_at IS NULL
        ';
    }

    public static function allActive(): array
    {
        $db = Database::getInstance();
        return $db->query(self::selectSql() . '
            WHERE p.deleted_at IS NULL
            ORDER BY p.updated_at DESC
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
        $sql = self::selectSql() . ' WHERE p.slug = ? AND p.deleted_at IS NULL';
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

    /** @return array<int, object> id => option rows for dropdowns */
    public static function publishedOptions(): array
    {
        return Database::getInstance()->query("
            SELECT id, title, slug FROM cms_pages
            WHERE deleted_at IS NULL AND status = 'published'
            ORDER BY title
        ")->fetchAll(\PDO::FETCH_OBJ);
    }

    /** @return array<int, object> for parent dropdown (excludes self/descendants) */
    public static function parentOptions(?int $excludeId = null): array
    {
        $rows = Database::getInstance()->query("
            SELECT id, title, parent_id FROM cms_pages
            WHERE deleted_at IS NULL
            ORDER BY title
        ")->fetchAll(\PDO::FETCH_OBJ);
        if ($excludeId === null) {
            return $rows;
        }
        $blocked = self::descendantIds($excludeId);
        $blocked[$excludeId] = true;
        return array_values(array_filter($rows, static fn ($r) => !isset($blocked[(int) $r->id])));
    }

    /** @return array<int, true> */
    private static function descendantIds(int $pageId): array
    {
        $all = Database::getInstance()->query('SELECT id, parent_id FROM cms_pages WHERE deleted_at IS NULL')->fetchAll(\PDO::FETCH_OBJ);
        $byParent = [];
        foreach ($all as $row) {
            $pid = (int) ($row->parent_id ?? 0);
            $byParent[$pid][] = (int) $row->id;
        }
        $out = [];
        $stack = $byParent[$pageId] ?? [];
        while ($stack !== []) {
            $id = array_pop($stack);
            if (isset($out[$id])) {
                continue;
            }
            $out[$id] = true;
            foreach ($byParent[$id] ?? [] as $child) {
                $stack[] = $child;
            }
        }
        return $out;
    }

    /** @return array<int, object> root → leaf */
    public static function ancestors(object $page): array
    {
        $chain = [];
        $current = $page;
        $guard = 0;
        while ($current && !empty($current->parent_id) && $guard < 20) {
            $parent = self::find((int) $current->parent_id);
            if (!$parent) {
                break;
            }
            array_unshift($chain, $parent);
            $current = $parent;
            $guard++;
        }
        return $chain;
    }

    public static function normalizeParentId(?int $parentId, ?int $pageId = null): ?int
    {
        if ($parentId === null || $parentId <= 0) {
            return null;
        }
        if ($pageId !== null && $parentId === $pageId) {
            return null;
        }
        if ($pageId !== null) {
            $blocked = self::descendantIds($pageId);
            if (isset($blocked[$parentId])) {
                return null;
            }
        }
        $parent = self::find($parentId);
        return $parent ? $parentId : null;
    }

    /** Published pages for sitemap (excludes noindex). */
    public static function publishedForSitemap(): array
    {
        return Database::getInstance()->query("
            SELECT id, slug, title, updated_at, robots_noindex
            FROM cms_pages
            WHERE deleted_at IS NULL AND status = 'published' AND robots_noindex = 0
            ORDER BY updated_at DESC
        ")->fetchAll(\PDO::FETCH_OBJ);
    }

    /** Published indexable pages for llms.txt / JSON catalogs. */
    public static function publishedForLlms(): array
    {
        return Database::getInstance()->query("
            SELECT id, slug, title, meta_description, llm_summary, citation_snippet, body, blocks_json, layout_json, updated_at
            FROM cms_pages
            WHERE deleted_at IS NULL AND status = 'published' AND robots_noindex = 0
            ORDER BY title ASC
        ")->fetchAll(\PDO::FETCH_OBJ);
    }

    public static function create(array $data): int
    {
        $db = Database::getInstance();
        $slug = CmsSlug::unique($db, 'cms_pages', CmsSlug::from($data['title'] ?? '', 'page'));
        if (CmsSlug::conflictsWithOtherContent($db, 'cms_pages', $slug)) {
            return 0;
        }
        $featuredId = Media::resolveImageId(
            !empty($data['featured_image_id']) ? (int) $data['featured_image_id'] : null
        );
        $stmt = $db->prepare('
            INSERT INTO cms_pages (title, slug, body, blocks_json, status, meta_title, meta_description, featured_image_id, llm_summary, citation_snippet, faq_json, robots_noindex, content_layout, parent_id, author_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ');
        $stmt->execute([
            trim($data['title'] ?? ''),
            $slug,
            $data['body'] ?? '',
            ContentBlocks::normalizeJson($data['blocks_json'] ?? null),
            in_array($data['status'] ?? '', ['published', 'draft'], true) ? $data['status'] : 'draft',
            PublicSeo::normalizeMetaTitle($data['meta_title'] ?? ''),
            PublicSeo::normalizeMetaDescription($data['meta_description'] ?? ''),
            $featuredId,
            PublicSeo::normalizeLlmSummary($data['llm_summary'] ?? ''),
            PublicSeo::normalizeCitationSnippet($data['citation_snippet'] ?? ''),
            PublicSeo::normalizeFaqJson($data['faq_json'] ?? null),
            !empty($data['robots_noindex']) ? 1 : 0,
            PublicTheme::normalizeContentLayout($data['content_layout'] ?? null),
            self::normalizeParentId(!empty($data['parent_id']) ? (int) $data['parent_id'] : null),
            Auth::id(),
        ]);
        $id = (int) $db->lastInsertId();
        AuditLog::record('page', $id, 'created');
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
            $slug = CmsSlug::from($data['title'] ?? $existing->title, 'page');
        }
        $slug = CmsSlug::unique($db, 'cms_pages', $slug, $id);
        if (CmsSlug::conflictsWithOtherContent($db, 'cms_pages', $slug, $id)) {
            return false;
        }
        ContentRevision::recordPage($existing, 'Before update');
        $featuredId = Media::resolveImageId(
            !empty($data['featured_image_id']) ? (int) $data['featured_image_id'] : null
        );
        $stmt = $db->prepare('
            UPDATE cms_pages SET title = ?, slug = ?, body = ?, blocks_json = ?, status = ?, meta_title = ?, meta_description = ?,
                featured_image_id = ?, llm_summary = ?, citation_snippet = ?, faq_json = ?, robots_noindex = ?, content_layout = ?, parent_id = ?
            WHERE id = ? AND deleted_at IS NULL
        ');
        $stmt->execute([
            trim($data['title'] ?? ''),
            $slug,
            $data['body'] ?? '',
            ContentBlocks::normalizeJson($data['blocks_json'] ?? null),
            in_array($data['status'] ?? '', ['published', 'draft'], true) ? $data['status'] : 'draft',
            PublicSeo::normalizeMetaTitle($data['meta_title'] ?? ''),
            PublicSeo::normalizeMetaDescription($data['meta_description'] ?? ''),
            $featuredId,
            PublicSeo::normalizeLlmSummary($data['llm_summary'] ?? ''),
            PublicSeo::normalizeCitationSnippet($data['citation_snippet'] ?? ''),
            PublicSeo::normalizeFaqJson($data['faq_json'] ?? null),
            !empty($data['robots_noindex']) ? 1 : 0,
            PublicTheme::normalizeContentLayout($data['content_layout'] ?? null),
            self::normalizeParentId(!empty($data['parent_id']) ? (int) $data['parent_id'] : null, $id),
            $id,
        ]);
        AuditLog::record('page', $id, 'updated');
        return true;
    }

    public static function saveLayoutJson(int $id, ?string $json): bool
    {
        $existing = self::find($id);
        if (!$existing) {
            return false;
        }
        ContentRevision::recordPage($existing, 'Before layout save');
        $normalized = LayoutBuilder::normalizeJson($json);
        $stmt = Database::getInstance()->prepare('
            UPDATE cms_pages SET layout_json = ? WHERE id = ? AND deleted_at IS NULL
        ');
        $stmt->execute([$normalized, $id]);
        AuditLog::record('page', $id, 'layout_updated');
        return true;
    }

    /** Restore a revision snapshot (records current state once, then applies). */
    public static function restoreRevision(int $id, int $revisionId): bool
    {
        $existing = self::find($id);
        $rev = ContentRevision::find($revisionId);
        if (!$existing || !$rev || (string) $rev->entity_type !== 'page' || (int) $rev->entity_id !== $id) {
            return false;
        }
        $snap = ContentRevision::decodeSnapshot($rev);
        if ($snap === null) {
            return false;
        }
        ContentRevision::recordPage($existing, 'Before restore to #' . (int) $rev->revision_no);

        $slug = trim((string) ($snap['slug'] ?? $existing->slug));
        if ($slug === '') {
            $slug = CmsSlug::from((string) ($snap['title'] ?? $existing->title), 'page');
        }
        $db = Database::getInstance();
        $slug = CmsSlug::unique($db, 'cms_pages', $slug, $id);
        if (CmsSlug::conflictsWithOtherContent($db, 'cms_pages', $slug, $id)) {
            return false;
        }
        $layout = $snap['layout_json'] ?? null;
        if (is_array($layout)) {
            $layout = json_encode($layout, JSON_UNESCAPED_UNICODE);
        }
        $layout = LayoutBuilder::normalizeJson(is_string($layout) ? $layout : null);
        $blocks = $snap['blocks_json'] ?? null;
        if (is_array($blocks)) {
            $blocks = json_encode($blocks, JSON_UNESCAPED_UNICODE);
        }
        $featuredId = Media::resolveImageId(
            !empty($snap['featured_image_id']) ? (int) $snap['featured_image_id'] : null
        );
        $stmt = $db->prepare('
            UPDATE cms_pages SET title = ?, slug = ?, body = ?, blocks_json = ?, layout_json = ?, status = ?,
                meta_title = ?, meta_description = ?, featured_image_id = ?, llm_summary = ?, citation_snippet = ?, faq_json = ?, robots_noindex = ?,
                content_layout = ?, parent_id = ?
            WHERE id = ? AND deleted_at IS NULL
        ');
        $stmt->execute([
            trim((string) ($snap['title'] ?? '')),
            $slug,
            (string) ($snap['body'] ?? ''),
            ContentBlocks::normalizeJson(is_string($blocks) ? $blocks : null),
            $layout,
            in_array((string) ($snap['status'] ?? ''), ['published', 'draft'], true) ? $snap['status'] : 'draft',
            PublicSeo::normalizeMetaTitle($snap['meta_title'] ?? ''),
            PublicSeo::normalizeMetaDescription($snap['meta_description'] ?? ''),
            $featuredId,
            PublicSeo::normalizeLlmSummary($snap['llm_summary'] ?? ''),
            PublicSeo::normalizeCitationSnippet($snap['citation_snippet'] ?? ''),
            PublicSeo::normalizeFaqJson($snap['faq_json'] ?? null),
            !empty($snap['robots_noindex']) ? 1 : 0,
            PublicTheme::normalizeContentLayout($snap['content_layout'] ?? null),
            self::normalizeParentId(!empty($snap['parent_id']) ? (int) $snap['parent_id'] : null, $id),
            $id,
        ]);
        AuditLog::record('page', $id, 'restored', ['revision_id' => $revisionId, 'revision_no' => (int) $rev->revision_no]);
        return true;
    }

    public static function softDelete(int $id): bool
    {
        $stmt = Database::getInstance()->prepare('UPDATE cms_pages SET deleted_at = NOW() WHERE id = ? AND deleted_at IS NULL');
        $stmt->execute([$id]);
        if ($stmt->rowCount() > 0) {
            AuditLog::record('page', $id, 'deleted');
            return true;
        }
        return false;
    }

    public static function countsByStatus(): array
    {
        $rows = Database::getInstance()->query("
            SELECT status, COUNT(*) AS cnt FROM cms_pages WHERE deleted_at IS NULL GROUP BY status
        ")->fetchAll(\PDO::FETCH_ASSOC);
        $out = ['draft' => 0, 'published' => 0];
        foreach ($rows as $r) {
            $out[$r['status']] = (int) $r['cnt'];
        }
        return $out;
    }

    public static function hasFeaturedImage(object $page): bool
    {
        return !empty($page->featured_image_id)
            && Media::isImageMime((string) ($page->featured_mime_type ?? ''));
    }

    public static function isHomepageSlug(string $slug): bool
    {
        return in_array($slug, ['welcome', 'home'], true);
    }

    /** @return array<int, object> */
    public static function recentDrafts(int $limit = 5): array
    {
        $limit = max(1, min(20, $limit));
        $stmt = Database::getInstance()->query("
            SELECT id, title, slug, updated_at
            FROM cms_pages
            WHERE deleted_at IS NULL AND status = 'draft'
            ORDER BY updated_at DESC
            LIMIT {$limit}
        ");
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }
}
