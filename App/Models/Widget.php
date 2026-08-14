<?php
namespace App\Models;

use App\AuditLog;
use Core\Database;

class Widget
{
    public const AREA_SIDEBAR = 'sidebar';
    public const AREA_FOOTER = 'footer';

    /** @return array<string, string> */
    public static function areas(): array
    {
        return [
            self::AREA_SIDEBAR => 'Sidebar',
            self::AREA_FOOTER  => 'Footer',
        ];
    }

    /** @return array<string, string> */
    public static function types(): array
    {
        return [
            'recent_posts' => 'Recent posts',
            'categories'   => 'Categories',
            'tags'         => 'Tag cloud',
            'custom_html'  => 'Custom HTML',
            'search'       => 'Search box',
        ];
    }

    /** @return array<int, object> */
    public static function forArea(string $area, bool $enabledOnly = true): array
    {
        if (!array_key_exists($area, self::areas())) {
            return [];
        }
        $sql = 'SELECT * FROM cms_widgets WHERE area = ?';
        if ($enabledOnly) {
            $sql .= ' AND is_enabled = 1';
        }
        $sql .= ' ORDER BY sort_order ASC, id ASC';
        $stmt = Database::getInstance()->prepare($sql);
        $stmt->execute([$area]);
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }

    public static function areaHasWidgets(string $area): bool
    {
        return self::forArea($area) !== [];
    }

    public static function find(int $id): ?object
    {
        $stmt = Database::getInstance()->prepare('SELECT * FROM cms_widgets WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch(\PDO::FETCH_OBJ);
        return $row ?: null;
    }

    /** @param array<string, mixed> $data */
    public static function create(array $data): int
    {
        $area = (string) ($data['area'] ?? self::AREA_SIDEBAR);
        if (!array_key_exists($area, self::areas())) {
            $area = self::AREA_SIDEBAR;
        }
        $type = (string) ($data['widget_type'] ?? 'custom_html');
        if (!array_key_exists($type, self::types())) {
            $type = 'custom_html';
        }
        $config = self::encodeConfig($data['config'] ?? []);
        $stmt = Database::getInstance()->prepare('
            INSERT INTO cms_widgets (area, widget_type, title, config_json, sort_order, is_enabled)
            VALUES (?, ?, ?, ?, ?, ?)
        ');
        $stmt->execute([
            $area,
            $type,
            trim((string) ($data['title'] ?? '')) ?: null,
            $config,
            (int) ($data['sort_order'] ?? 0),
            !empty($data['is_enabled']) ? 1 : 0,
        ]);
        $id = (int) Database::getInstance()->lastInsertId();
        AuditLog::record('widget', $id, 'created');
        return $id;
    }

    /** @param array<string, mixed> $data */
    public static function update(int $id, array $data): bool
    {
        $existing = self::find($id);
        if (!$existing) {
            return false;
        }
        $config = array_key_exists('config', $data)
            ? self::encodeConfig($data['config'])
            : ($existing->config_json ?? null);
        $stmt = Database::getInstance()->prepare('
            UPDATE cms_widgets SET title = ?, config_json = ?, sort_order = ?, is_enabled = ?
            WHERE id = ?
        ');
        $stmt->execute([
            trim((string) ($data['title'] ?? $existing->title ?? '')) ?: null,
            $config,
            (int) ($data['sort_order'] ?? $existing->sort_order ?? 0),
            !empty($data['is_enabled']) ? 1 : 0,
            $id,
        ]);
        AuditLog::record('widget', $id, 'updated');
        return true;
    }

    public static function delete(int $id): bool
    {
        $stmt = Database::getInstance()->prepare('DELETE FROM cms_widgets WHERE id = ?');
        $stmt->execute([$id]);
        if ($stmt->rowCount() > 0) {
            AuditLog::record('widget', $id, 'deleted');
            return true;
        }
        return false;
    }

    /** @param array<int, array<string, mixed>> $rows */
    public static function saveAreaWidgets(string $area, array $rows): void
    {
        if (!array_key_exists($area, self::areas())) {
            return;
        }
        $db = Database::getInstance();
        $db->prepare('DELETE FROM cms_widgets WHERE area = ?')->execute([$area]);
        $sort = 0;
        foreach ($rows as $row) {
            $type = (string) ($row['widget_type'] ?? '');
            if (!array_key_exists($type, self::types())) {
                continue;
            }
            $sort += 10;
            self::create([
                'area' => $area,
                'widget_type' => $type,
                'title' => $row['title'] ?? '',
                'config' => $row['config'] ?? [],
                'sort_order' => $sort,
                'is_enabled' => !empty($row['is_enabled']),
            ]);
        }
    }

    /** @return array<string, mixed> */
    public static function decodeConfig(?string $json): array
    {
        if ($json === null || trim($json) === '') {
            return [];
        }
        $data = json_decode($json, true);
        return is_array($data) ? $data : [];
    }

    /** @param array<string, mixed>|mixed $config */
    private static function encodeConfig($config): ?string
    {
        if (!is_array($config) || $config === []) {
            return null;
        }
        return json_encode($config, JSON_UNESCAPED_UNICODE);
    }

    public static function renderArea(string $area): string
    {
        $html = '';
        foreach (self::forArea($area) as $widget) {
            $html .= self::renderWidget($widget);
        }
        return $html;
    }

    public static function renderWidget(object $widget): string
    {
        $type = (string) ($widget->widget_type ?? '');
        $config = self::decodeConfig($widget->config_json ?? null);
        $title = trim((string) ($widget->title ?? ''));
        $inner = '';

        switch ($type) {
            case 'recent_posts':
                $limit = max(1, min(10, (int) ($config['count'] ?? 5)));
                $posts = Post::publishedList($limit, 0);
                $inner = '<ul class="widget-list">';
                foreach ($posts as $p) {
                    $inner .= '<li><a href="' . htmlspecialchars(\App\Permalink::urlForPost($p)) . '">'
                        . htmlspecialchars($p->title) . '</a></li>';
                }
                $inner .= '</ul>';
                break;
            case 'categories':
                $cats = Category::all();
                $inner = '<ul class="widget-list">';
                foreach ($cats as $c) {
                    $inner .= '<li><a href="/blog/category/' . htmlspecialchars($c->slug) . '">'
                        . htmlspecialchars($c->name) . '</a></li>';
                }
                $inner .= '</ul>';
                break;
            case 'tags':
                $tags = Tag::all();
                $inner = '<div class="widget-tag-cloud">';
                foreach ($tags as $t) {
                    $inner .= '<a href="/blog/tag/' . htmlspecialchars($t->slug) . '" class="public-tag">'
                        . htmlspecialchars($t->name) . '</a> ';
                }
                $inner .= '</div>';
                break;
            case 'search':
                $inner = '<form action="/blog" method="get" class="widget-search"><input type="search" name="q" class="form-control form-control-sm" placeholder="Search blog…" aria-label="Search"></form>';
                break;
            case 'custom_html':
            default:
                $inner = (string) ($config['html'] ?? '');
                break;
        }

        if ($inner === '') {
            return '';
        }
        $out = '<div class="public-widget public-widget--' . htmlspecialchars($type) . '">';
        if ($title !== '') {
            $out .= '<h3 class="public-widget-title">' . htmlspecialchars($title) . '</h3>';
        }
        $out .= '<div class="public-widget-body">' . $inner . '</div></div>';
        return $out;
    }
}
