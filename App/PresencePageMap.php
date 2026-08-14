<?php
namespace App;

class PresencePageMap
{
    private static array $labels = [
        'dashboard' => 'Dashboard',
        'pages' => 'Pages',
        'posts' => 'Posts',
        'categories' => 'Categories',
        'media' => 'Media',
        'settings' => 'Settings',
        'email-settings' => 'Email Settings',
        'security-settings' => 'Security',
        'general' => 'General',
        'backup-restore' => 'Backup & Restore',
        'audit-trail' => 'Audit Trail',
        'users' => 'Users',
        'user-roles' => 'Roles',
        'help' => 'Help',
    ];

    public static function labelFor(string $pageKey): string
    {
        return self::$labels[$pageKey] ?? ucfirst(str_replace('-', ' ', $pageKey));
    }
}
