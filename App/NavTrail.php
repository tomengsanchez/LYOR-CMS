<?php
namespace App;

class NavTrail
{
    public static function resolve(string $currentPage, string $helpPage = '', ?array $override = null, ?string $leafLabel = null): array
    {
        if (is_array($override) && $override !== []) {
            return self::normalize($override);
        }
        $trail = self::trailFor($currentPage);
        if ($leafLabel !== null && $leafLabel !== '' && $trail !== []) {
            $last = count($trail) - 1;
            $trail[$last]['label'] = $leafLabel;
            $trail[$last]['url'] = null;
        }
        return $trail;
    }

    public static function trailFor(string $pageKey): array
    {
        $home = ['label' => 'Home', 'url' => AdminPath::url()];
        $map = [
            'dashboard' => [$home, ['label' => 'Dashboard', 'url' => null]],
            'pages' => [$home, ['label' => 'Content', 'url' => null], ['label' => 'Pages', 'url' => AdminPath::url('pages')]],
            'posts' => [$home, ['label' => 'Content', 'url' => null], ['label' => 'Posts', 'url' => AdminPath::url('posts')]],
            'categories' => [$home, ['label' => 'Content', 'url' => null], ['label' => 'Categories', 'url' => AdminPath::url('categories')]],
            'tags' => [$home, ['label' => 'Content', 'url' => null], ['label' => 'Tags', 'url' => AdminPath::url('tags')]],
            'menus' => [$home, ['label' => 'Content', 'url' => null], ['label' => 'Menus', 'url' => AdminPath::url('menus')]],
            'comments' => [$home, ['label' => 'Content', 'url' => null], ['label' => 'Comments', 'url' => AdminPath::url('comments')]],
            'widgets' => [$home, ['label' => 'Appearance', 'url' => null], ['label' => 'Widgets', 'url' => AdminPath::url('widgets')]],
            'media' => [$home, ['label' => 'Content', 'url' => null], ['label' => 'Media', 'url' => AdminPath::url('media')]],
            'settings' => [$home, ['label' => 'Settings', 'url' => AdminPath::url('settings')]],
            'email-settings' => [$home, ['label' => 'Settings', 'url' => AdminPath::url('settings')], ['label' => 'Email', 'url' => null]],
            'security-settings' => [$home, ['label' => 'Settings', 'url' => AdminPath::url('settings')], ['label' => 'Security', 'url' => null]],
            'general' => [$home, ['label' => 'System', 'url' => null], ['label' => 'General', 'url' => AdminPath::url('system/general')]],
            'backup-restore' => [$home, ['label' => 'System', 'url' => null], ['label' => 'Backup & Restore', 'url' => null]],
            'audit-trail' => [$home, ['label' => 'System', 'url' => null], ['label' => 'Audit Trail', 'url' => null]],
            'users' => [$home, ['label' => 'User Management', 'url' => null], ['label' => 'Users', 'url' => AdminPath::url('users')]],
            'user-roles' => [$home, ['label' => 'User Management', 'url' => null], ['label' => 'Roles', 'url' => AdminPath::url('users/roles')]],
            'help' => [$home, ['label' => 'Help', 'url' => null]],
        ];
        $raw = $map[$pageKey] ?? [];
        if ($raw === [] && $pageKey !== '') {
            return [$home, ['label' => PresencePageMap::labelFor($pageKey), 'url' => null]];
        }
        return self::normalize($raw);
    }

    private static function normalize(array $items): array
    {
        $out = [];
        foreach ($items as $item) {
            if (!is_array($item) || !isset($item['label'])) {
                continue;
            }
            $url = array_key_exists('url', $item) ? $item['url'] : null;
            $out[] = ['label' => trim((string) $item['label']), 'url' => $url !== '' ? $url : null];
        }
        return $out;
    }
}
