<?php
namespace App\Models;

use App\CmsSlug;
use Core\Database;

class NavMenu
{
    public const LOCATION_PRIMARY = 'primary';

    public const TYPE_HOME = 'home';
    public const TYPE_BLOG = 'blog';
    public const TYPE_PAGE = 'page';
    public const TYPE_POST = 'post';
    public const TYPE_CATEGORY = 'category';
    public const TYPE_CUSTOM = 'custom';

    /** @return array<string, string> */
    public static function itemTypes(): array
    {
        return [
            self::TYPE_HOME => 'Homepage',
            self::TYPE_BLOG => 'Blog',
            self::TYPE_PAGE => 'Page',
            self::TYPE_POST => 'Post',
            self::TYPE_CATEGORY => 'Category',
            self::TYPE_CUSTOM => 'Custom URL',
        ];
    }

    public static function findByLocation(string $location): ?object
    {
        $stmt = Database::getInstance()->prepare('SELECT * FROM cms_menus WHERE location = ? LIMIT 1');
        $stmt->execute([$location]);
        $row = $stmt->fetch(\PDO::FETCH_OBJ);
        return $row ?: null;
    }

    /** @return array<int, object> */
    public static function itemsForLocation(string $location): array
    {
        $menu = self::findByLocation($location);
        if (!$menu) {
            return self::fallbackPrimaryItems();
        }
        return self::itemsForMenu((int) $menu->id);
    }

    /** @return array<int, object> */
    public static function itemsForMenu(int $menuId): array
    {
        $stmt = Database::getInstance()->prepare('
            SELECT * FROM cms_menu_items
            WHERE menu_id = ? AND parent_id IS NULL
            ORDER BY sort_order ASC, id ASC
        ');
        $stmt->execute([$menuId]);
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }

    /** @return array<int, object> */
    public static function allItemsForMenu(int $menuId): array
    {
        $stmt = Database::getInstance()->prepare('
            SELECT * FROM cms_menu_items WHERE menu_id = ?
            ORDER BY sort_order ASC, id ASC
        ');
        $stmt->execute([$menuId]);
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }

    /** @return array<int, object> */
    private static function fallbackPrimaryItems(): array
    {
        return [
            (object) ['id' => 0, 'label' => 'Home', 'item_type' => self::TYPE_HOME, 'object_id' => null, 'custom_url' => '/', 'open_in_new_tab' => 0],
            (object) ['id' => 0, 'label' => 'Blog', 'item_type' => self::TYPE_BLOG, 'object_id' => null, 'custom_url' => '/blog', 'open_in_new_tab' => 0],
        ];
    }

    public static function resolveItemUrl(object $item): string
    {
        $type = (string) ($item->item_type ?? self::TYPE_CUSTOM);
        switch ($type) {
            case self::TYPE_HOME:
                return '/';
            case self::TYPE_BLOG:
                return '/blog';
            case self::TYPE_PAGE:
                $page = Page::findPublished((int) ($item->object_id ?? 0));
                return $page ? \App\Permalink::urlForPage($page) : '#';
            case self::TYPE_POST:
                $post = Post::findPublished((int) ($item->object_id ?? 0));
                return $post ? \App\Permalink::urlForPost($post) : '#';
            case self::TYPE_CATEGORY:
                $cat = Category::find((int) ($item->object_id ?? 0));
                return $cat ? '/blog/category/' . rawurlencode($cat->slug) : '#';
            default:
                $url = trim((string) ($item->custom_url ?? ''));
                return $url !== '' ? $url : '#';
        }
    }

    /** @param array<int, array<string, mixed>> $rows */
    public static function savePrimaryItems(array $rows): void
    {
        $menu = self::findByLocation(self::LOCATION_PRIMARY);
        if (!$menu) {
            $db = Database::getInstance();
            $db->exec("INSERT INTO cms_menus (name, location) VALUES ('Primary Menu', 'primary')");
            $menu = self::findByLocation(self::LOCATION_PRIMARY);
        }
        if (!$menu) {
            return;
        }
        $menuId = (int) $menu->id;
        $db = Database::getInstance();
        $db->prepare('DELETE FROM cms_menu_items WHERE menu_id = ?')->execute([$menuId]);

        $sort = 0;
        foreach ($rows as $row) {
            $label = trim((string) ($row['label'] ?? ''));
            if ($label === '') {
                continue;
            }
            $type = (string) ($row['item_type'] ?? self::TYPE_CUSTOM);
            if (!array_key_exists($type, self::itemTypes())) {
                $type = self::TYPE_CUSTOM;
            }
            $objectId = !empty($row['object_id']) ? (int) $row['object_id'] : null;
            $customUrl = trim((string) ($row['custom_url'] ?? ''));
            $sort += 10;
            $stmt = $db->prepare('
                INSERT INTO cms_menu_items (menu_id, label, item_type, object_id, custom_url, sort_order, open_in_new_tab)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ');
            $stmt->execute([
                $menuId,
                $label,
                $type,
                $objectId,
                $customUrl !== '' ? $customUrl : null,
                $sort,
                !empty($row['open_in_new_tab']) ? 1 : 0,
            ]);
        }
    }
}
