<?php
namespace App;

class CmsSlug
{
    public static function from(string $title, ?string $fallback = null): string
    {
        $slug = strtolower(trim($title));
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug) ?? '';
        $slug = trim($slug, '-');
        if ($slug === '') {
            $slug = $fallback ?? 'item';
        }
        return substr($slug, 0, 200);
    }

    public static function unique(\PDO $db, string $table, string $slug, ?int $excludeId = null): string
    {
        $base = $slug;
        $n = 1;
        while (self::exists($db, $table, $slug, $excludeId)) {
            $slug = $base . '-' . $n;
            $n++;
        }
        return $slug;
    }

    private static function exists(\PDO $db, string $table, string $slug, ?int $excludeId, bool $includeDeleted = true): bool
    {
        $allowed = ['cms_pages', 'cms_posts', 'cms_categories', 'cms_tags'];
        if (!in_array($table, $allowed, true)) {
            return false;
        }
        $sql = "SELECT id FROM {$table} WHERE slug = ?";
        $params = [$slug];
        $hasSoftDelete = !in_array($table, ['cms_categories', 'cms_tags'], true);
        if ($hasSoftDelete && !$includeDeleted) {
            $sql .= ' AND deleted_at IS NULL';
        }
        if ($excludeId !== null) {
            $sql .= ' AND id <> ?';
            $params[] = $excludeId;
        }
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return (bool) $stmt->fetchColumn();
    }

    /** True when slug is used by the other content table (pages vs posts). */
    public static function conflictsWithOtherContent(\PDO $db, string $ownTable, string $slug, ?int $excludeId = null): bool
    {
        if (!in_array($ownTable, ['cms_pages', 'cms_posts'], true)) {
            return false;
        }
        $other = $ownTable === 'cms_pages' ? 'cms_posts' : 'cms_pages';
        return self::exists($db, $other, $slug, null, false);
    }
}
