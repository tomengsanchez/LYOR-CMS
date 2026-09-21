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

    public static function findByName(string $name): ?object
    {
        $name = trim($name);
        if ($name === '') {
            return null;
        }
        $stmt = Database::getInstance()->prepare('SELECT * FROM cms_tags WHERE LOWER(name) = LOWER(?) LIMIT 1');
        $stmt->execute([$name]);
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
        $seen = [];
        foreach ($parts as $part) {
            $name = trim($part);
            if ($name === '') {
                continue;
            }
            $key = mb_strtolower($name);
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $out[] = $name;
        }
        return $out;
    }

    public static function findOrCreateByName(string $name): int
    {
        $name = trim($name);
        if ($name === '') {
            return 0;
        }
        $existing = self::findByName($name);
        if ($existing) {
            return (int) $existing->id;
        }
        $db = Database::getInstance();
        $baseSlug = CmsSlug::from($name, 'tag');
        $bySlug = self::findBySlug($baseSlug);
        if ($bySlug) {
            return (int) $bySlug->id;
        }
        $slug = CmsSlug::unique($db, 'cms_tags', $baseSlug);
        $stmt = $db->prepare('INSERT INTO cms_tags (name, slug) VALUES (?, ?)');
        $stmt->execute([$name, $slug]);
        $id = (int) $db->lastInsertId();
        AuditLog::record('tag', $id, 'created');
        return $id;
    }

    public static function create(array $data): int
    {
        $name = trim($data['name'] ?? '');
        if ($name === '') {
            return 0;
        }
        $existing = self::findByName($name);
        if ($existing) {
            return (int) $existing->id;
        }
        $db = Database::getInstance();
        $requested = trim((string) ($data['slug'] ?? ''));
        $base = $requested !== '' ? CmsSlug::from($requested, 'tag') : CmsSlug::from($name, 'tag');
        $slug = CmsSlug::unique($db, 'cms_tags', $base);
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
        if ($name === '') {
            return false;
        }
        $other = self::findByName($name);
        if ($other && (int) $other->id !== $id) {
            return false;
        }
        $slug = trim($data['slug'] ?? $existing->slug);
        if ($slug === '') {
            $slug = CmsSlug::from($name, 'tag');
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

    /**
     * Merge tags that share the same name (case-insensitive).
     * Keeps the row whose slug matches the base name when possible, else the lowest id.
     *
     * @return array{groups: int, remapped: int, deleted: int}
     */
    public static function mergeDuplicatesByName(): array
    {
        $db = Database::getInstance();
        $rows = $db->query('SELECT id, name, slug FROM cms_tags ORDER BY id ASC')->fetchAll(\PDO::FETCH_OBJ);
        $groups = [];
        foreach ($rows as $row) {
            $key = mb_strtolower(trim((string) $row->name));
            if ($key === '') {
                continue;
            }
            $groups[$key][] = $row;
        }

        $groupCount = 0;
        $remapped = 0;
        $deleted = 0;
        foreach ($groups as $key => $list) {
            if (count($list) < 2) {
                continue;
            }
            $groupCount++;
            $baseSlug = CmsSlug::from((string) $list[0]->name, 'tag');
            $canonical = $list[0];
            foreach ($list as $row) {
                if ((string) $row->slug === $baseSlug) {
                    $canonical = $row;
                    break;
                }
            }
            $canonicalId = (int) $canonical->id;
            if ((string) $canonical->slug !== $baseSlug) {
                $free = CmsSlug::unique($db, 'cms_tags', $baseSlug, $canonicalId);
                $db->prepare('UPDATE cms_tags SET slug = ? WHERE id = ?')->execute([$free, $canonicalId]);
            }
            foreach ($list as $row) {
                $dupId = (int) $row->id;
                if ($dupId === $canonicalId) {
                    continue;
                }
                $links = $db->prepare('SELECT post_id FROM cms_post_tags WHERE tag_id = ?');
                $links->execute([$dupId]);
                foreach ($links->fetchAll(\PDO::FETCH_COLUMN) as $postId) {
                    $db->prepare('INSERT IGNORE INTO cms_post_tags (post_id, tag_id) VALUES (?, ?)')
                        ->execute([(int) $postId, $canonicalId]);
                    $remapped++;
                }
                $db->prepare('DELETE FROM cms_post_tags WHERE tag_id = ?')->execute([$dupId]);
                $db->prepare('DELETE FROM cms_tags WHERE id = ?')->execute([$dupId]);
                $deleted++;
                AuditLog::record('tag', $dupId, 'merged_into_' . $canonicalId);
            }
        }

        return ['groups' => $groupCount, 'remapped' => $remapped, 'deleted' => $deleted];
    }

    /** Tags used on at least one published post. */
    public static function publishedForSitemap(): array
    {
        return Database::getInstance()->query('
            SELECT DISTINCT t.id, t.slug, t.name
            FROM cms_tags t
            INNER JOIN cms_post_tags pt ON pt.tag_id = t.id
            INNER JOIN cms_posts p ON p.id = pt.post_id AND ' . \App\Models\Post::liveSql('p') . '
            ORDER BY t.name
        ')->fetchAll(\PDO::FETCH_OBJ);
    }
}
