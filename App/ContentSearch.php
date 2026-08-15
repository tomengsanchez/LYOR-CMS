<?php
namespace App;

use App\Models\Media;
use Core\Database;

/**
 * Cross-module admin content library search (pages, posts, media).
 */
class ContentSearch
{
    /**
     * @return array{pages: array<int, object>, posts: array<int, object>, media: array<int, object>, q: string}
     */
    public static function query(string $q, int $limit = 20): array
    {
        $q = trim($q);
        $limit = max(5, min(50, $limit));
        $empty = ['pages' => [], 'posts' => [], 'media' => [], 'q' => $q];
        if ($q === '' || mb_strlen($q) < 2) {
            return $empty;
        }
        $db = Database::getInstance();
        $like = '%' . self::escapeLike($q) . '%';

        $pages = $db->prepare("
            SELECT id, title, slug, status, updated_at
            FROM cms_pages
            WHERE deleted_at IS NULL
              AND (title LIKE ? OR slug LIKE ? OR meta_title LIKE ? OR meta_description LIKE ? OR llm_summary LIKE ?)
            ORDER BY updated_at DESC
            LIMIT {$limit}
        ");
        $pages->execute([$like, $like, $like, $like, $like]);

        $posts = $db->prepare("
            SELECT p.id, p.title, p.slug, p.status, p.updated_at, c.name AS category_name
            FROM cms_posts p
            LEFT JOIN cms_categories c ON c.id = p.category_id
            WHERE p.deleted_at IS NULL
              AND (
                p.title LIKE ? OR p.slug LIKE ? OR p.excerpt LIKE ?
                OR p.meta_title LIKE ? OR p.meta_description LIKE ? OR p.llm_summary LIKE ?
                OR EXISTS (
                    SELECT 1 FROM cms_post_tags pt
                    INNER JOIN cms_tags t ON t.id = pt.tag_id
                    WHERE pt.post_id = p.id AND t.name LIKE ?
                )
              )
            ORDER BY p.updated_at DESC
            LIMIT {$limit}
        ");
        $posts->execute([$like, $like, $like, $like, $like, $like, $like]);

        $media = $db->prepare("
            SELECT id, original_name, alt_text, mime_type, width, height, created_at
            FROM cms_media
            WHERE original_name LIKE ? OR alt_text LIKE ? OR mime_type LIKE ?
            ORDER BY created_at DESC
            LIMIT {$limit}
        ");
        $media->execute([$like, $like, $like]);

        return [
            'pages' => $pages->fetchAll(\PDO::FETCH_OBJ),
            'posts' => $posts->fetchAll(\PDO::FETCH_OBJ),
            'media' => $media->fetchAll(\PDO::FETCH_OBJ),
            'q' => $q,
        ];
    }

    private static function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }
}
