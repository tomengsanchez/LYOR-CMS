<?php
namespace App\Models;

use App\AuditLog;
use App\CmsSlug;
use Core\Database;

class Tag
{
    public static function all(): array
    {
        return Database::getInstance()->query('
            SELECT t.*, COUNT(pt.post_id) AS post_count
            FROM cms_tags t
            LEFT JOIN cms_post_tags pt ON pt.tag_id = t.id
            LEFT JOIN cms_posts p ON p.id = pt.post_id AND p.deleted_at IS NULL
            GROUP BY t.id
            ORDER BY t.name
        ')->fetchAll(\PDO::FETCH_OBJ);
    }

    public static function find(int $id): ?object
    {
        $stmt = Database::getInstance()->prepare('SELECT * FROM cms_tags WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch(\PDO::FETCH_OBJ);
        return $row ?: null;
    }

    public static function findBySlug(string $slug): ?object
    {
        $stmt = Database::getInstance()->prepare('SELECT * FROM cms_tags WHERE slug = ?');
        $stmt->execute([$slug]);
        $row = $stmt->fetch(\PDO::FETCH_OBJ);
        return $row ?: null;
    }

    /** @return array<int, object> */
    public static function forPost(int $postId): array
    {
        $stmt = Database::getInstance()->prepare('
            SELECT t.* FROM cms_tags t
            INNER JOIN cms_post_tags pt ON pt.tag_id = t.id
            WHERE pt.post_id = ?
            ORDER BY t.name
        ');
        $stmt->execute([$postId]);
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }

    /** @return string Comma-separated tag names for admin form */
    public static function namesForPost(int $postId): string
    {
        $tags = self::forPost($postId);
        return implode(', ', array_map(static fn ($t) => (string) $t->name, $tags));
    }

    /** @param array<int, string>|string $input */
    public static function syncPostTags(int $postId, $input): void
    {
        $names = is_array($input) ? $input : self::parseNames((string) $input);
        $db = Database::getInstance();
        $db->prepare('DELETE FROM cms_post_tags WHERE post_id = ?')->execute([$postId]);

        foreach ($names as $name) {
            $tagId = self::findOrCreateByName($name);
            if ($tagId > 0) {
                $db->prepare('INSERT IGNORE INTO cms_post_tags (post_id, tag_id) VALUES (?, ?)')->execute([$postId, $tagId]);
            }
        }
    }

    /** @return array<int, string> */
    public static function parseNames(string $csv): array
    {
        $parts = preg_split('/\s*,\s*/', trim($csv)) ?: [];
        $out = [];
        foreach ($parts as $part) {
            $name = trim($part);
            if ($name !== '' && !in_array($name, $out, true)) {
                $out[] = $name;
            }
        }
        return $out;
    }

    public static function findOrCreateByName(string $name): int
    {
        $name = trim($name);
        if ($name === '') {
            return 0;
        }
        $db = Database::getInstance();
        $slug = CmsSlug::unique($db, 'cms_tags', CmsSlug::from($name, 'tag'));
        $stmt = $db->prepare('SELECT id FROM cms_tags WHERE slug = ?');
        $stmt->execute([$slug]);
        $existing = $stmt->fetchColumn();
        if ($existing) {
            return (int) $existing;
        }
        $stmt = $db->prepare('INSERT INTO cms_tags (name, slug) VALUES (?, ?)');
        $stmt->execute([$name, $slug]);
        $id = (int) $db->lastInsertId();
        AuditLog::record('tag', $id, 'created');
        return $id;
    }

    public static function create(array $data): int
    {
        $db = Database::getInstance();
        $name = trim($data['name'] ?? '');
        $slug = CmsSlug::unique($db, 'cms_tags', CmsSlug::from($name !== '' ? $name : ($data['slug'] ?? ''), 'tag'));
        $stmt = $db->prepare('INSERT INTO cms_tags (name, slug) VALUES (?, ?)');
        $stmt->execute([$name, $slug]);
        $id = (int) $db->lastInsertId();
        AuditLog::record('tag', $id, 'created');
        return $id;
    }

    public static function update(int $id, array $data): bool
    {
        $db = Database::getInstance();
        $existing = self::find($id);
        if (!$existing) {
            return false;
        }
        $name = trim($data['name'] ?? '');
        $slug = trim($data['slug'] ?? $existing->slug);
        if ($slug === '') {
            $slug = CmsSlug::from($name !== '' ? $name : $existing->name, 'tag');
        }
        $slug = CmsSlug::unique($db, 'cms_tags', $slug, $id);
        $stmt = $db->prepare('UPDATE cms_tags SET name = ?, slug = ? WHERE id = ?');
        $stmt->execute([$name, $slug, $id]);
        AuditLog::record('tag', $id, 'updated');
        return true;
    }

    public static function delete(int $id): bool
    {
        $stmt = Database::getInstance()->prepare('DELETE FROM cms_tags WHERE id = ?');
        $stmt->execute([$id]);
        if ($stmt->rowCount() > 0) {
            AuditLog::record('tag', $id, 'deleted');
            return true;
        }
        return false;
    }

    /** Tags used on at least one published post. */
    public static function publishedForSitemap(): array
    {
        return Database::getInstance()->query("
            SELECT DISTINCT t.id, t.slug, t.name
            FROM cms_tags t
            INNER JOIN cms_post_tags pt ON pt.tag_id = t.id
            INNER JOIN cms_posts p ON p.id = pt.post_id AND p.deleted_at IS NULL AND p.status = 'published'
            ORDER BY t.name
        ")->fetchAll(\PDO::FETCH_OBJ);
    }
}
