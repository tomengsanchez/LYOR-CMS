<?php
namespace App;

/**
 * Column configuration for list tables.
 */
class ListConfig
{
    private static array $configs = [
        'pages' => [
            ['key' => 'title', 'label' => 'Title', 'sortable' => true],
            ['key' => 'slug', 'label' => 'Slug', 'sortable' => true],
            ['key' => 'status', 'label' => 'Status', 'sortable' => true],
            ['key' => 'author_name', 'label' => 'Author', 'sortable' => true],
            ['key' => 'updated_at', 'label' => 'Updated', 'sortable' => true],
        ],
        'posts' => [
            ['key' => 'title', 'label' => 'Title', 'sortable' => true],
            ['key' => 'slug', 'label' => 'Slug', 'sortable' => true],
            ['key' => 'category_name', 'label' => 'Category', 'sortable' => true],
            ['key' => 'status', 'label' => 'Status', 'sortable' => true],
            ['key' => 'published_at', 'label' => 'Published', 'sortable' => true],
            ['key' => 'author_name', 'label' => 'Author', 'sortable' => true],
        ],
        'categories' => [
            ['key' => 'name', 'label' => 'Name', 'sortable' => true],
            ['key' => 'slug', 'label' => 'Slug', 'sortable' => true],
            ['key' => 'description', 'label' => 'Description', 'sortable' => true],
        ],
        'users' => [
            ['key' => 'username', 'label' => 'Username', 'sortable' => true],
            ['key' => 'display_name', 'label' => 'Display Name', 'sortable' => true],
            ['key' => 'email', 'label' => 'Email', 'sortable' => true],
            ['key' => 'role_name', 'label' => 'Role', 'sortable' => true],
        ],
        'roles' => [
            ['key' => 'name', 'label' => 'Role Name', 'sortable' => true],
            ['key' => 'capabilities', 'label' => 'Capabilities', 'sortable' => false],
        ],
    ];

    public static function getColumns(string $module): array
    {
        return self::$configs[$module] ?? [];
    }

    public static function getExportColumns(string $module): array
    {
        return self::getColumns($module);
    }

    public static function getColumnByKey(string $module, string $key): ?array
    {
        foreach (self::getColumns($module) as $col) {
            if ($col['key'] === $key) {
                return $col;
            }
        }
        return null;
    }

    public static function resolveFromRequest(string $module): array
    {
        $all = self::getColumns($module);
        $defaultKeys = array_column($all, 'key');
        $requested = $_GET['columns'] ?? '';
        if (is_string($requested) && $requested !== '') {
            $keys = array_filter(array_map('trim', explode(',', $requested)));
            $valid = [];
            foreach ($keys as $k) {
                if (self::getColumnByKey($module, $k)) {
                    $valid[] = $k;
                }
            }
            if ($valid !== []) {
                return $valid;
            }
        }
        if (!empty($_SESSION['list_columns'][$module]) && is_array($_SESSION['list_columns'][$module])) {
            return $_SESSION['list_columns'][$module];
        }
        return $defaultKeys;
    }

    public static function hasCustomColumns(string $module): bool
    {
        $all = array_column(self::getColumns($module), 'key');
        $current = self::resolveFromRequest($module);
        return $current !== $all;
    }
}
