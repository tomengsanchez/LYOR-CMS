<?php
namespace App;

class Capabilities
{
    private static array $entities = [
        'Pages' => [
            'view_pages'   => 'View List',
            'add_pages'    => 'Add',
            'edit_pages'   => 'Edit',
            'delete_pages' => 'Delete',
        ],
        'Posts' => [
            'view_posts'   => 'View List',
            'add_posts'    => 'Add',
            'edit_posts'   => 'Edit',
            'delete_posts' => 'Delete',
            'moderate_comments' => 'Moderate comments',
        ],
        'Newsletter' => [
            'view_subscribers' => 'View List',
            'manage_subscribers' => 'Manage',
            'export_subscribers' => 'Export',
        ],
        'Categories' => [
            'view_categories'    => 'View List',
            'manage_categories'  => 'Manage',
        ],
        'Media' => [
            'view_media'    => 'View Library',
            'upload_media'  => 'Upload',
            'delete_media'  => 'Delete',
        ],
        'Settings' => [
            'view_settings'   => 'View',
            'manage_settings' => 'Manage',
        ],
        'Email Settings' => [
            'view_email_settings'   => 'View',
            'manage_email_settings' => 'Manage',
        ],
        'Security' => [
            'view_security_settings'   => 'View',
            'manage_security_settings' => 'Manage',
        ],
        'Users' => [
            'view_users'   => 'View List',
            'add_users'    => 'Add',
            'edit_users'   => 'Edit',
            'delete_users' => 'Delete',
            'export_users' => 'Export',
        ],
        'User Roles & Capabilities' => [
            'view_roles' => 'View List',
            'add_roles'  => 'Add',
            'edit_roles' => 'Edit',
        ],
    ];

    private static array $menuCapability = [
        'dashboard'       => 'view_pages',
        'pages'           => 'view_pages',
        'posts'           => 'view_posts',
        'comments'        => 'moderate_comments',
        'subscribers'     => 'view_subscribers',
        'widgets'         => 'view_settings',
        'customize'       => 'view_settings',
        'categories'      => 'view_categories',
        'tags'            => 'manage_categories',
        'menus'           => 'view_settings',
        'media'           => 'view_media',
        'search'          => 'view_pages',
        'redirects'       => 'manage_settings',
        'settings'        => 'view_settings',
        'email-settings'  => 'view_email_settings',
        'security-settings' => 'view_security_settings',
        'general'         => 'view_settings',
        'backup-restore'  => 'view_settings',
        'audit-trail'     => 'view_settings',
        'users'           => 'view_users',
        'user-roles'      => 'view_roles',
    ];

    public static function entities(): array
    {
        return self::$entities;
    }

    public static function all(): array
    {
        $flat = [];
        foreach (self::$entities as $caps) {
            $flat = array_merge($flat, $caps);
        }
        return $flat;
    }

    public static function getLabel(string $capKey): string
    {
        foreach (self::$entities as $caps) {
            if (isset($caps[$capKey])) {
                return $caps[$capKey];
            }
        }
        return $capKey;
    }

    public static function menuCapability(string $pageKey): ?string
    {
        return self::$menuCapability[$pageKey] ?? null;
    }

    public static function canSeeMenu(string $pageKey): bool
    {
        $cap = self::menuCapability($pageKey);
        if ($cap === null) {
            return true;
        }
        return \Core\Auth::can($cap) || \Core\Auth::isAdmin();
    }
}
