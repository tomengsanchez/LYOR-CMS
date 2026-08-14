<?php
namespace App;

use App\Models\NavMenu;

/**
 * Render public navigation from cms_menus (WordPress-style menus).
 */
class PublicNav
{
    /** @return array<int, object> */
    public static function primaryItems(): array
    {
        return NavMenu::itemsForLocation(NavMenu::LOCATION_PRIMARY);
    }

    public static function urlForItem(object $item): string
    {
        return NavMenu::resolveItemUrl($item);
    }

    public static function isItemActive(object $item, string $publicNavActive): bool
    {
        $type = (string) ($item->item_type ?? '');
        if ($type === 'home' && $publicNavActive === 'home') {
            return true;
        }
        if ($type === 'blog' && $publicNavActive === 'blog') {
            return true;
        }
        if ($type === 'page' && $publicNavActive === 'page-' . (int) ($item->object_id ?? 0)) {
            return true;
        }
        return false;
    }
}
