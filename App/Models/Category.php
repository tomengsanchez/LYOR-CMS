<?php
namespace App\Models;

use App\AuditLog;
use App\CmsSlug;
use Core\Database;

class Category
{
    public static function all(): array
    {
        return Database::getInstance()->query('SELECT * FROM cms_categories ORDER BY name')->fetchAll(\PDO::FETCH_OBJ);
    }

    public static function find(int $id): ?object
    {
        $stmt = Database::getInstance()->prepare('SELECT * FROM cms_categories WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch(\PDO::FETCH_OBJ);
        return $row ?: null;
    }

    public static function findBySlug(string $slug): ?object
    {
        $stmt = Database::getInstance()->prepare('SELECT * FROM cms_categories WHERE slug = ?');
        $stmt->execute([$slug]);
        $row = $stmt->fetch(\PDO::FETCH_OBJ);
        return $row ?: null;
    }

    public static function create(array $data): int
    {
        $db = Database::getInstance();
        $slug = CmsSlug::unique($db, 'cms_categories', CmsSlug::from($data['name'] ?? '', 'category'));
        $stmt = $db->prepare('INSERT INTO cms_categories (name, slug, description) VALUES (?, ?, ?)');
        $stmt->execute([
            trim($data['name'] ?? ''),
            $slug,
            trim($data['description'] ?? '') ?: null,
        ]);
        $id = (int) $db->lastInsertId();
        AuditLog::record('category', $id, 'created');
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
            $slug = CmsSlug::from($data['name'] ?? $existing->name, 'category');
        }
        $slug = CmsSlug::unique($db, 'cms_categories', $slug, $id);
        $stmt = $db->prepare('UPDATE cms_categories SET name = ?, slug = ?, description = ? WHERE id = ?');
        $stmt->execute([
            trim($data['name'] ?? ''),
            $slug,
            trim($data['description'] ?? '') ?: null,
            $id,
        ]);
        AuditLog::record('category', $id, 'updated');
        return true;
    }

    /** Categories that have at least one published post. */
    public static function publishedForSitemap(): array
    {
        return Database::getInstance()->query("
            SELECT DISTINCT c.id, c.slug, c.name, c.description
            FROM cms_categories c
            INNER JOIN cms_posts p ON p.category_id = c.id AND p.deleted_at IS NULL AND p.status = 'published'
            ORDER BY c.name
        ")->fetchAll(\PDO::FETCH_OBJ);
    }

    public static function delete(int $id): bool
    {
        $db = Database::getInstance();
        $stmt = $db->prepare('DELETE FROM cms_categories WHERE id = ?');
        $stmt->execute([$id]);
        if ($stmt->rowCount() > 0) {
            AuditLog::record('category', $id, 'deleted');
            return true;
        }
        return false;
    }
}
