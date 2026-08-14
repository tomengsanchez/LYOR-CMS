<?php
/** Shared help topic titles for Simple CMS. */
function help_pages_dir(): string
{
    return __DIR__ . '/pages';
}

function help_page_partial(string $key): ?string
{
    if ($key === '' || !preg_match('/^[a-z0-9\-]+$/', $key)) {
        return null;
    }
    $path = help_pages_dir() . '/' . $key . '.php';
    return is_file($path) ? $path : null;
}

function help_module_title(string $module): string
{
    $titles = [
        'dashboard' => 'Dashboard',
        'pages' => 'Pages',
        'posts' => 'Posts',
        'categories' => 'Categories',
        'tags' => 'Tags',
        'menus' => 'Menus',
        'comments' => 'Comments',
        'widgets' => 'Widgets',
        'media' => 'Media',
        'settings' => 'Settings',
        'email-settings' => 'Email Settings',
        'security-settings' => 'Security Settings',
        'general' => 'General Settings',
        'backup-restore' => 'Backup & Restore',
        'audit-trail' => 'Audit Trail',
        'users' => 'Users',
        'user-roles' => 'Roles & Capabilities',
        'account' => 'My Account',
    ];
    return $titles[$module] ?? 'Simple CMS';
}

function help_resolve_module_key(string $from): string
{
    if ($from === '') {
        return 'general';
    }
    $map = [
        'dashboard' => 'dashboard',
        'pages' => 'pages',
        'posts' => 'posts',
        'categories' => 'categories',
        'tags' => 'tags',
        'menus' => 'menus',
        'comments' => 'comments',
        'widgets' => 'widgets',
        'media' => 'media',
        'settings' => 'settings',
        'email-settings' => 'email-settings',
        'security-settings' => 'security-settings',
        'general' => 'general',
        'backup-restore' => 'backup-restore',
        'audit-trail' => 'audit-trail',
        'users' => 'users',
        'user-roles' => 'user-roles',
        'account' => 'account',
    ];
    return $map[$from] ?? 'general';
}

function help_resolve_content_key(string $from): string
{
    if ($from !== '' && help_page_partial($from) !== null) {
        return $from;
    }
    return help_resolve_module_key($from);
}
