<?php
namespace App\Models;

use App\AuditLog;
use App\CmsSlug;
use App\ContentBlocks;
use App\ContentPassword;
use App\ContentRevision;
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
                   u.username AS author_username,
                   COALESCE(NULLIF(u.display_name, \'\'), u.username) AS author_name,
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

    /** Live public posts: published and not scheduled in the future. */
    public static function liveSql(string $alias = 'p'): string
    {
        $now = "'" . str_replace("'", "''", UserTime::nowSql()) . "'";
        return $alias . '.deleted_at IS NULL AND ' . $alias . ".status = 'published'"
            . ' AND (' . $alias . '.published_at IS NULL OR ' . $alias . '.published_at <= ' . $now . ')';
    }

    public static function liveOrderSql(string $alias = 'p'): string
    {
        return $alias . '.is_sticky DESC, ' . $alias . '.published_at DESC, ' . $alias . '.id DESC';
    }

    public static function isLive(object $post): bool
    {
        if ((string) ($post->status ?? '') !== 'published') {
            return false;
        }
        $at = trim((string) ($post->published_at ?? ''));
        return $at === '' || $at <= UserTime::nowSql();
    }

    public static function isScheduled(object $post): bool
    {
        if ((string) ($post->status ?? '') !== 'published') {
            return false;
        }
        $at = trim((string) ($post->published_at ?? ''));
        return $at !== '' && $at > UserTime::nowSql();
    }

    public static function publicStatusLabel(object $post): string
    {
        if (self::isScheduled($post)) {
            return 'scheduled';
        }
        return (string) ($post->status ?? '');
    }

    public static function authorPublicUrl(object $post): string
    {
        $user = trim((string) ($post->author_username ?? ''));
        if ($user === '') {
            return '';
        }
        return '/blog/author/' . rawurlencode($user);
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
        $sql = self::selectSql() . ' WHERE p.slug = ?';
        if ($publishedOnly) {
            $sql .= ' AND ' . self::liveSql('p');
        } else {
            $sql .= ' AND p.deleted_at IS NULL';
        }
        $stmt = Database::getInstance()->prepare($sql);
        $stmt->execute([$slug]);
        $row = $stmt->fetch(\PDO::FETCH_OBJ);
        return $row ?: null;
    }

    public static function findPublished(int $id): ?object
    {
        $stmt = Database::getInstance()->prepare(self::selectSql() . '
            WHERE p.id = ? AND ' . self::liveSql('p') . '
        ');
        $stmt->execute([$id]);
        $row = $stmt->fetch(\PDO::FETCH_OBJ);
        return $row ?: null;
    }

    /**
     * @param array{year?:int, month?:int, author_id?:int} $opts
     */
    public static function publishedCount(?int $categoryId = null, ?int $tagId = null, ?string $search = null, array $opts = []): int
    {
        $sql = 'SELECT COUNT(*) FROM cms_posts p WHERE ' . self::liveSql('p');
        $params = [];
        $sql .= self::archiveFilterSql($opts, $params);
        if ($categoryId !== null) {
            $sql .= ' AND p.category_id = ?';
            $params[] = $categoryId;
        }
        if ($tagId !== null) {
            $sql .= ' AND EXISTS (SELECT 1 FROM cms_post_tags pt WHERE pt.post_id = p.id AND pt.tag_id = ?)';
            $params[] = $tagId;
        }
        $sql .= self::searchWhereClause($search, $params);
        $stmt = Database::getInstance()->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    /**
     * @param array{year?:int, month?:int, author_id?:int} $opts
     * @return list<object>
     */
    public static function publishedList(int $limit = 20, int $offset = 0, ?int $categoryId = null, ?int $tagId = null, ?string $search = null, array $opts = []): array
    {
        $limit = max(1, min(100, $limit));
        $offset = max(0, $offset);
        $where = self::liveSql('p');
        $params = [];
        $where .= self::archiveFilterSql($opts, $params);
        if ($categoryId !== null) {
            $where .= ' AND p.category_id = ?';
            $params[] = $categoryId;
        }
        if ($tagId !== null) {
            $where .= ' AND EXISTS (SELECT 1 FROM cms_post_tags pt WHERE pt.post_id = p.id AND pt.tag_id = ?)';
            $params[] = $tagId;
        }
        $where .= self::searchWhereClause($search, $params);
        $order = self::liveOrderSql('p');
        $stmt = Database::getInstance()->prepare(self::selectSql() . "
            WHERE {$where}
            ORDER BY {$order}
            LIMIT {$limit} OFFSET {$offset}
        ");
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }

    /**
     * @param array{year?:int, month?:int, author_id?:int} $opts
     */
    private static function archiveFilterSql(array $opts, array &$params): string
    {
        $sql = '';
        $year = (int) ($opts['year'] ?? 0);
        $month = (int) ($opts['month'] ?? 0);
        $authorId = (int) ($opts['author_id'] ?? 0);
        if ($year >= 2000 && $year <= 2100) {
            $sql .= ' AND YEAR(p.published_at) = ?';
            $params[] = $year;
        }
        if ($month >= 1 && $month <= 12) {
            $sql .= ' AND MONTH(p.published_at) = ?';
            $params[] = $month;
        }
        if ($authorId > 0) {
            $sql .= ' AND p.author_id = ?';
            $params[] = $authorId;
        }
        return $sql;
    }

    /**
     * Other live posts, preferring the same category.
     *
     * @return list<object>
     */
    public static function related(object $post, int $limit = 3): array
    {
        $id = (int) ($post->id ?? 0);
        $limit = max(1, min(6, $limit));
        if ($id <= 0) {
            return [];
        }
        $cat = (int) ($post->category_id ?? 0);
        $seen = [$id => true];
        $out = [];
        $live = self::liveSql('p');
        if ($cat > 0) {
            $stmt = Database::getInstance()->prepare(self::selectSql() . '
                WHERE ' . $live . ' AND p.id != ? AND p.category_id = ?
                ORDER BY p.published_at DESC
                LIMIT ' . $limit);
            $stmt->execute([$id, $cat]);
            foreach ($stmt->fetchAll(\PDO::FETCH_OBJ) as $row) {
                $rid = (int) $row->id;
                $seen[$rid] = true;
                $out[] = $row;
            }
        }
        if (count($out) >= $limit) {
            return $out;
        }
        $stmt = Database::getInstance()->prepare(self::selectSql() . '
            WHERE ' . $live . ' AND p.id != ?
            ORDER BY p.published_at DESC
            LIMIT ' . max($limit, 12));
        $stmt->execute([$id]);
        foreach ($stmt->fetchAll(\PDO::FETCH_OBJ) as $row) {
            $rid = (int) $row->id;
            if (isset($seen[$rid])) {
                continue;
            }
            $seen[$rid] = true;
            $out[] = $row;
            if (count($out) >= $limit) {
                break;
            }
        }
        return $out;
    }

    /**
     * Chronological neighbors (not sticky-ordered).
     *
     * @return array{previous:?object, next:?object}
     */
    public static function neighbors(object $post): array
    {
        $id = (int) ($post->id ?? 0);
        $at = (string) ($post->published_at ?? '');
        $live = self::liveSql('p');
        $prev = null;
        $next = null;
        if ($id <= 0 || $at === '') {
            return ['previous' => null, 'next' => null];
        }
        $stmt = Database::getInstance()->prepare(self::selectSql() . '
            WHERE ' . $live . ' AND (p.published_at < ? OR (p.published_at = ? AND p.id < ?))
            ORDER BY p.published_at DESC, p.id DESC
            LIMIT 1');
        $stmt->execute([$at, $at, $id]);
        $row = $stmt->fetch(\PDO::FETCH_OBJ);
        if ($row) {
            $prev = $row;
        }
        $stmt = Database::getInstance()->prepare(self::selectSql() . '
            WHERE ' . $live . ' AND (p.published_at > ? OR (p.published_at = ? AND p.id > ?))
            ORDER BY p.published_at ASC, p.id ASC
            LIMIT 1');
        $stmt->execute([$at, $at, $id]);
        $row = $stmt->fetch(\PDO::FETCH_OBJ);
        if ($row) {
            $next = $row;
        }
        return ['previous' => $prev, 'next' => $next];
    }

    /** @return list<object> year, month, label, count, url */
    public static function archiveMonths(int $limit = 24): array
    {
        $limit = max(1, min(60, $limit));
        $stmt = Database::getInstance()->query('
            SELECT YEAR(p.published_at) AS yr, MONTH(p.published_at) AS mo, COUNT(*) AS cnt
            FROM cms_posts p
            WHERE ' . self::liveSql('p') . ' AND p.published_at IS NOT NULL
            GROUP BY YEAR(p.published_at), MONTH(p.published_at)
            ORDER BY yr DESC, mo DESC
            LIMIT ' . $limit . '
        ');
        $out = [];
        foreach ($stmt->fetchAll(\PDO::FETCH_OBJ) as $row) {
            $year = (int) $row->yr;
            $month = (int) $row->mo;
            if ($year < 2000 || $month < 1 || $month > 12) {
                continue;
            }
            $stamp = sprintf('%04d-%02d-01', $year, $month);
            $out[] = (object) [
                'year' => $year,
                'month' => $month,
                'label' => date('F Y', strtotime($stamp) ?: time()),
                'count' => (int) $row->cnt,
                'url' => sprintf('/blog/archive/%04d/%02d', $year, $month),
            ];
        }
        return $out;
    }

    public static function findAuthorByUsername(string $username): ?object
    {
        $username = trim($username);
        if ($username === '' || !preg_match('/^[A-Za-z0-9._-]{1,100}$/', $username)) {
            return null;
        }
        $stmt = Database::getInstance()->prepare('
            SELECT id, username, display_name FROM users WHERE username = ? LIMIT 1
        ');
        $stmt->execute([$username]);
        $row = $stmt->fetch(\PDO::FETCH_OBJ);
        return $row ?: null;
    }

    public static function readingMinutes(object $post): int
    {
        $text = ContentBlocks::plainTextFromEntity($post);
        $excerpt = trim((string) ($post->excerpt ?? ''));
        if ($excerpt !== '' && !str_contains($text, $excerpt)) {
            $text = $excerpt . ' ' . $text;
        }
        $text = trim(preg_replace('/\s+/u', ' ', $text) ?? $text);
        if ($text === '') {
            return 0;
        }
        $words = preg_match_all('/[\p{L}\p{N}\']+/u', $text);
        if (!is_int($words) || $words < 1) {
            $words = str_word_count($text);
        }
        if ($words < 1) {
            return 0;
        }
        return max(1, (int) ceil($words / 220));
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
        return ' AND ' . ContentPassword::openSql('p')
            . ' AND (p.title LIKE ? OR p.excerpt LIKE ? OR p.body LIKE ?)';
    }

    private static function normalizePublishedAt(array $data, string $status, ?string $existing): ?string
    {
        if ($status !== 'published') {
            return null;
        }
        $raw = trim((string) ($data['published_at'] ?? ''));
        $raw = str_replace('T', ' ', $raw);
        if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $raw)) {
            $raw .= ':00';
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $raw)) {
            return $raw;
        }
        if ($existing !== null && $existing !== '') {
            return $existing;
        }
        return UserTime::nowSql();
    }

    public static function create(array $data): int
    {
        $db = Database::getInstance();
        $slugSource = trim((string) ($data['slug'] ?? ''));
        if ($slugSource === '') {
            $slugSource = (string) ($data['title'] ?? '');
        }
        $slug = CmsSlug::unique($db, 'cms_posts', CmsSlug::from($slugSource, 'post'));
        if (CmsSlug::conflictsWithOtherContent($db, 'cms_posts', $slug)) {
            return 0;
        }
        $status = in_array($data['status'] ?? '', ['published', 'draft'], true) ? $data['status'] : 'draft';
        $publishedAt = self::normalizePublishedAt($data, $status, null);
        $featuredId = \App\Models\Media::resolveImageId(
            !empty($data['featured_image_id']) ? (int) $data['featured_image_id'] : null
        );
        $sticky = !empty($data['is_sticky']) ? 1 : 0;
        $authorId = (int) ($data['author_id'] ?? 0);
        if ($authorId <= 0) {
            $authorId = (int) Auth::id();
        }
        $stmt = $db->prepare('
            INSERT INTO cms_posts (title, slug, excerpt, body, blocks_json, category_id, featured_image_id, meta_title, meta_description, llm_summary, citation_snippet, faq_json, robots_noindex, content_layout, status, is_sticky, password_hash, published_at, author_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
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
            PublicSeo::normalizeCitationSnippet($data['citation_snippet'] ?? ''),
            PublicSeo::normalizeFaqJson($data['faq_json'] ?? null),
            !empty($data['robots_noindex']) ? 1 : 0,
            PublicTheme::normalizeContentLayout($data['content_layout'] ?? null),
            $status,
            $sticky,
            ContentPassword::hashFromWrite($data, null),
            $publishedAt,
            $authorId,
        ]);
        $id = (int) $db->lastInsertId();
        Tag::syncPostTags($id, $data['tags'] ?? '');
        AuditLog::record('post', $id, 'created');
        return $id;
    }

    public static function duplicate(int $id): int
    {
        $src = self::find($id);
        if (!$src) {
            return 0;
        }
        $newId = self::create([
            'title' => 'Copy of ' . (string) $src->title,
            'excerpt' => (string) ($src->excerpt ?? ''),
            'body' => (string) ($src->body ?? ''),
            'blocks_json' => $src->blocks_json ?? null,
            'category_id' => $src->category_id ?? null,
            'featured_image_id' => $src->featured_image_id ?? null,
            'meta_title' => (string) ($src->meta_title ?? ''),
            'meta_description' => (string) ($src->meta_description ?? ''),
            'llm_summary' => (string) ($src->llm_summary ?? ''),
            'citation_snippet' => (string) ($src->citation_snippet ?? ''),
            'faq_json' => $src->faq_json ?? null,
            'robots_noindex' => !empty($src->robots_noindex),
            'content_layout' => $src->content_layout ?? '',
            'status' => 'draft',
            'is_sticky' => false,
            'tags' => Tag::namesForPost($id),
        ]);
        if ($newId <= 0) {
            return 0;
        }
        if (!empty($src->layout_json)) {
            $stmt = Database::getInstance()->prepare('UPDATE cms_posts SET layout_json = ? WHERE id = ? AND deleted_at IS NULL');
            $stmt->execute([(string) $src->layout_json, $newId]);
        }
        if (ContentPassword::has($src)) {
            ContentPassword::persistHash('cms_posts', $newId, ContentPassword::storedHash($src));
        }
        AuditLog::record('post', $newId, 'duplicated', ['source_id' => $id]);
        return $newId;
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
        ContentRevision::recordPost($existing, 'Before update');
        $status = in_array($data['status'] ?? '', ['published', 'draft'], true) ? $data['status'] : 'draft';
        $publishedAt = self::normalizePublishedAt($data, $status, (string) ($existing->published_at ?? ''));
        $featuredId = \App\Models\Media::resolveImageId(
            !empty($data['featured_image_id']) ? (int) $data['featured_image_id'] : null
        );
        $sticky = !empty($data['is_sticky']) ? 1 : 0;
        $stmt = $db->prepare('
            UPDATE cms_posts SET title = ?, slug = ?, excerpt = ?, body = ?, blocks_json = ?, category_id = ?, featured_image_id = ?,
                meta_title = ?, meta_description = ?, llm_summary = ?, citation_snippet = ?, faq_json = ?, robots_noindex = ?, content_layout = ?, status = ?, is_sticky = ?, password_hash = ?, published_at = ?
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
            PublicSeo::normalizeCitationSnippet($data['citation_snippet'] ?? ''),
            PublicSeo::normalizeFaqJson($data['faq_json'] ?? null),
            !empty($data['robots_noindex']) ? 1 : 0,
            PublicTheme::normalizeContentLayout($data['content_layout'] ?? null),
            $status,
            $sticky,
            ContentPassword::hashFromWrite($data, (string) ($existing->password_hash ?? '')),
            $publishedAt,
            $id,
        ]);
        AuditLog::record('post', $id, 'updated');
        Tag::syncPostTags($id, $data['tags'] ?? '');
        return true;
    }

    public static function saveLayoutJson(int $id, ?string $json): bool
    {
        $existing = self::find($id);
        if (!$existing) {
            return false;
        }
        ContentRevision::recordPost($existing, 'Before layout save');
        $normalized = LayoutBuilder::normalizeJson($json);
        $stmt = Database::getInstance()->prepare('
            UPDATE cms_posts SET layout_json = ? WHERE id = ? AND deleted_at IS NULL
        ');
        $stmt->execute([$normalized, $id]);
        AuditLog::record('post', $id, 'layout_updated');
        return true;
    }

    public static function restoreRevision(int $id, int $revisionId): bool
    {
        $existing = self::find($id);
        $rev = ContentRevision::find($revisionId);
        if (!$existing || !$rev || (string) $rev->entity_type !== 'post' || (int) $rev->entity_id !== $id) {
            return false;
        }
        $snap = ContentRevision::decodeSnapshot($rev);
        if ($snap === null) {
            return false;
        }
        ContentRevision::recordPost($existing, 'Before restore to #' . (int) $rev->revision_no);

        $slug = trim((string) ($snap['slug'] ?? $existing->slug));
        if ($slug === '') {
            $slug = CmsSlug::from((string) ($snap['title'] ?? $existing->title), 'post');
        }
        $db = Database::getInstance();
        $slug = CmsSlug::unique($db, 'cms_posts', $slug, $id);
        if (CmsSlug::conflictsWithOtherContent($db, 'cms_posts', $slug, $id)) {
            return false;
        }
        $status = in_array((string) ($snap['status'] ?? ''), ['published', 'draft'], true) ? (string) $snap['status'] : 'draft';
        $publishedAt = self::normalizePublishedAt(
            ['published_at' => $snap['published_at'] ?? ''],
            $status,
            null
        );
        $sticky = !empty($snap['is_sticky']) ? 1 : 0;
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
        $catId = !empty($snap['category_id']) ? (int) $snap['category_id'] : null;
        $stmt = $db->prepare('
            UPDATE cms_posts SET title = ?, slug = ?, excerpt = ?, body = ?, blocks_json = ?, layout_json = ?,
                category_id = ?, featured_image_id = ?, meta_title = ?, meta_description = ?, llm_summary = ?,
                citation_snippet = ?, faq_json = ?, robots_noindex = ?, content_layout = ?, status = ?, is_sticky = ?, password_hash = ?, published_at = ?
            WHERE id = ? AND deleted_at IS NULL
        ');
        $stmt->execute([
            trim((string) ($snap['title'] ?? '')),
            $slug,
            trim((string) ($snap['excerpt'] ?? '')) ?: null,
            (string) ($snap['body'] ?? ''),
            ContentBlocks::normalizeJson(is_string($blocks) ? $blocks : null),
            $layout,
            $catId ?: null,
            $featuredId,
            PublicSeo::normalizeMetaTitle($snap['meta_title'] ?? ''),
            PublicSeo::normalizeMetaDescription($snap['meta_description'] ?? ''),
            PublicSeo::normalizeLlmSummary($snap['llm_summary'] ?? ''),
            PublicSeo::normalizeCitationSnippet($snap['citation_snippet'] ?? ''),
            PublicSeo::normalizeFaqJson($snap['faq_json'] ?? null),
            !empty($snap['robots_noindex']) ? 1 : 0,
            PublicTheme::normalizeContentLayout($snap['content_layout'] ?? null),
            $status,
            $sticky,
            ContentPassword::storedHash((object) ['password_hash' => $snap['password_hash'] ?? null]),
            $publishedAt,
            $id,
        ]);
        Tag::syncPostTags($id, (string) ($snap['tags'] ?? ''));
        AuditLog::record('post', $id, 'restored', ['revision_id' => $revisionId, 'revision_no' => (int) $rev->revision_no]);
        return true;
    }

    public static function bulkSetStatus(array $ids, string $status): int
    {
        $ids = array_values(array_filter(array_map('intval', $ids), static fn ($id) => $id > 0));
        if ($ids === [] || !in_array($status, ['published', 'draft'], true)) {
            return 0;
        }
        $in = implode(',', array_fill(0, count($ids), '?'));
        if ($status === 'published') {
            $params = array_merge([UserTime::nowSql()], $ids);
            $sql = 'UPDATE cms_posts SET status = \'published\', published_at = COALESCE(published_at, ?)
                WHERE id IN (' . $in . ') AND deleted_at IS NULL';
        } else {
            $params = $ids;
            $sql = 'UPDATE cms_posts SET status = \'draft\', published_at = NULL
                WHERE id IN (' . $in . ') AND deleted_at IS NULL';
        }
        $stmt = Database::getInstance()->prepare($sql);
        $stmt->execute($params);
        $n = $stmt->rowCount();
        if ($n > 0) {
            AuditLog::record('post', $ids[0], 'bulk_status', ['status' => $status, 'ids' => $ids, 'count' => $n]);
        }
        return $n;
    }

    /** @param list<int> $ids */
    public static function bulkSetSticky(array $ids, bool $sticky): int
    {
        $ids = array_values(array_filter(array_map('intval', $ids), static fn ($id) => $id > 0));
        if ($ids === []) {
            return 0;
        }
        $in = implode(',', array_fill(0, count($ids), '?'));
        $params = array_merge([$sticky ? 1 : 0], $ids);
        $stmt = Database::getInstance()->prepare(
            'UPDATE cms_posts SET is_sticky = ? WHERE id IN (' . $in . ') AND deleted_at IS NULL'
        );
        $stmt->execute($params);
        $n = $stmt->rowCount();
        if ($n > 0) {
            AuditLog::record('post', $ids[0], 'bulk_sticky', ['sticky' => $sticky ? 1 : 0, 'ids' => $ids, 'count' => $n]);
        }
        return $n;
    }

    /** @param list<int> $ids */
    public static function bulkSoftDelete(array $ids): int
    {
        $ids = array_values(array_filter(array_map('intval', $ids), static fn ($id) => $id > 0));
        if ($ids === []) {
            return 0;
        }
        $in = implode(',', array_fill(0, count($ids), '?'));
        $stmt = Database::getInstance()->prepare('UPDATE cms_posts SET deleted_at = NOW() WHERE id IN (' . $in . ') AND deleted_at IS NULL');
        $stmt->execute($ids);
        $n = $stmt->rowCount();
        if ($n > 0) {
            AuditLog::record('post', $ids[0], 'bulk_deleted', ['ids' => $ids, 'count' => $n]);
        }
        return $n;
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
        return Database::getInstance()->query('
            SELECT p.slug, p.updated_at, p.published_at, p.category_id, c.slug AS category_slug
            FROM cms_posts p
            LEFT JOIN cms_categories c ON c.id = p.category_id
            WHERE ' . self::liveSql('p') . ' AND p.robots_noindex = 0
            ORDER BY p.published_at DESC
        ')->fetchAll(\PDO::FETCH_OBJ);
    }

    /** @return array<int, object> */
    public static function publishedForLlms(int $limit = 30): array
    {
        $limit = max(1, min(100, $limit));
        $stmt = Database::getInstance()->query(self::selectSql() . '
            WHERE ' . self::liveSql('p') . ' AND p.robots_noindex = 0
              AND ' . ContentPassword::openSql('p') . '
            ORDER BY p.published_at DESC
            LIMIT ' . $limit . '
        ');
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }

    /** @return array<int, object> */
    public static function publishedForFeed(int $limit = 20): array
    {
        $limit = max(1, min(50, $limit));
        $stmt = Database::getInstance()->query(self::selectSql() . '
            WHERE ' . self::liveSql('p') . ' AND p.robots_noindex = 0
            ORDER BY p.published_at DESC
            LIMIT ' . $limit . '
        ');
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
