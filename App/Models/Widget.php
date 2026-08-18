<?php
namespace App\Models;

use App\AuditLog;
use App\Permalink;
use Core\Database;

class Widget
{
    public const AREA_HEADER = 'header';
    public const AREA_AFTER_HEADER = 'after_header';
    public const AREA_HOME = 'home';
    public const AREA_SIDEBAR = 'sidebar';
    public const AREA_AFTER_CONTENT = 'after_content';
    public const AREA_FOOTER = 'footer';

    /** @return array<string, string> */
    public static function areas(): array
    {
        return [
            self::AREA_HEADER => 'Header',
            self::AREA_AFTER_HEADER => 'After header',
            self::AREA_HOME => 'Homepage',
            self::AREA_SIDEBAR => 'Sidebar',
            self::AREA_AFTER_CONTENT => 'After content',
            self::AREA_FOOTER => 'Footer',
        ];
    }

    public static function areaHelp(string $area): string
    {
        return match ($area) {
            self::AREA_HEADER => 'Slim bar above the site header. Search, social links, or a short call-to-action. Always visible (no Discussion toggle).',
            self::AREA_AFTER_HEADER => 'Full-width band under the navigation. Good for an announcement or call-to-action.',
            self::AREA_HOME => 'Homepage only, below the page or latest-posts feed. Featured stories work well here.',
            self::AREA_SIDEBAR => 'Shown beside content when Discussion → Show sidebar is on and this area has widgets.',
            self::AREA_AFTER_CONTENT => 'Below the page or post on public pages. CTA, pages list, or more posts.',
            self::AREA_FOOTER => 'Columns above the copyright line.',
            default => '',
        };
    }

    /** @return array<string, string> */
    public static function types(): array
    {
        return [
            'recent_posts' => 'Recent posts',
            'featured_posts' => 'Featured posts',
            'cta' => 'Call to action',
            'pages' => 'Pages',
            'social' => 'Social links',
            'categories' => 'Categories',
            'tags' => 'Tag cloud',
            'archives' => 'Monthly archives',
            'custom_html' => 'Custom HTML',
            'search' => 'Search box',
            'newsletter' => 'Newsletter signup',
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
        $config = self::encodeConfig(self::sanitizeConfig($type, is_array($data['config'] ?? null) ? $data['config'] : []));
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
        $type = (string) ($existing->widget_type ?? 'custom_html');
        $config = array_key_exists('config', $data)
            ? self::encodeConfig(self::sanitizeConfig($type, is_array($data['config']) ? $data['config'] : []))
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

    /**
     * Fill empty widget areas from a style-pack starter map. Occupied areas are left alone.
     *
     * @param array<string, mixed> $byArea
     */
    public static function installStarterIfEmpty(array $byArea): int
    {
        $map = self::sanitizeStarterMap($byArea);
        $filled = 0;
        foreach ($map as $area => $rows) {
            if (self::forArea($area, false) !== []) {
                continue;
            }
            self::saveAreaWidgets($area, $rows);
            $filled++;
        }
        return $filled;
    }

    /**
     * @param array<string, mixed> $byArea
     * @return array<string, list<array<string, mixed>>>
     */
    public static function sanitizeStarterMap(array $byArea): array
    {
        $out = [];
        foreach ($byArea as $area => $rows) {
            $area = (string) $area;
            if (!array_key_exists($area, self::areas()) || !is_array($rows)) {
                continue;
            }
            $clean = [];
            foreach ($rows as $row) {
                if (!is_array($row)) {
                    continue;
                }
                $type = (string) ($row['widget_type'] ?? '');
                if (!array_key_exists($type, self::types())) {
                    continue;
                }
                $clean[] = [
                    'widget_type' => $type,
                    'title' => mb_substr(trim((string) ($row['title'] ?? '')), 0, 150),
                    'config' => self::sanitizeConfig($type, is_array($row['config'] ?? null) ? $row['config'] : []),
                    'is_enabled' => !array_key_exists('is_enabled', $row) || !empty($row['is_enabled']),
                ];
            }
            if ($clean !== []) {
                $out[$area] = $clean;
            }
        }
        return $out;
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

    /**
     * @param array<string, mixed> $config
     * @return array<string, mixed>
     */
    public static function sanitizeConfig(string $type, array $config): array
    {
        switch ($type) {
            case 'recent_posts':
            case 'featured_posts':
                $max = $type === 'featured_posts' ? 6 : 10;
                $count = (int) ($config['count'] ?? 5);
                return ['count' => max(1, min($max, $count))];
            case 'archives':
                $count = (int) ($config['count'] ?? 12);
                return ['count' => max(1, min(24, $count))];
            case 'cta':
                return [
                    'headline' => mb_substr(trim((string) ($config['headline'] ?? '')), 0, 160),
                    'text' => mb_substr(trim((string) ($config['text'] ?? '')), 0, 500),
                    'label' => mb_substr(trim((string) ($config['label'] ?? '')), 0, 80),
                    'url' => self::safeWidgetUrl((string) ($config['url'] ?? '')),
                ];
            case 'social':
                $links = [];
                $raw = $config['links'] ?? [];
                if (is_string($raw)) {
                    $raw = self::parseSocialLines($raw);
                }
                if (!is_array($raw)) {
                    $raw = [];
                }
                foreach ($raw as $item) {
                    if (!is_array($item)) {
                        continue;
                    }
                    $url = self::safeWidgetUrl((string) ($item['url'] ?? ''));
                    if ($url === '') {
                        continue;
                    }
                    $label = mb_substr(trim((string) ($item['label'] ?? '')), 0, 40);
                    $links[] = [
                        'label' => $label !== '' ? $label : self::labelFromUrl($url),
                        'url' => $url,
                    ];
                    if (count($links) >= 8) {
                        break;
                    }
                }
                return ['links' => $links];
            case 'custom_html':
                $html = (string) ($config['html'] ?? '');
                return ['html' => mb_substr($html, 0, 20000)];
            case 'newsletter':
                return [
                    'intro' => mb_substr(trim((string) ($config['intro'] ?? '')), 0, 200),
                    'placeholder' => mb_substr(trim((string) ($config['placeholder'] ?? '')), 0, 80),
                    'button' => mb_substr(trim((string) ($config['button'] ?? '')), 0, 40),
                    'consent_label' => mb_substr(trim((string) ($config['consent_label'] ?? '')), 0, 160),
                ];
            default:
                return [];
        }
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
        $config = self::sanitizeConfig($type, self::decodeConfig($widget->config_json ?? null));
        $title = trim((string) ($widget->title ?? ''));
        $inner = '';

        switch ($type) {
            case 'recent_posts':
                $limit = (int) ($config['count'] ?? 5);
                $posts = Post::publishedList($limit, 0);
                $inner = '<ul class="widget-list">';
                foreach ($posts as $p) {
                    $inner .= '<li><a href="' . htmlspecialchars(Permalink::urlForPost($p)) . '">'
                        . htmlspecialchars($p->title) . '</a></li>';
                }
                $inner .= '</ul>';
                break;
            case 'featured_posts':
                $limit = (int) ($config['count'] ?? 3);
                $posts = Post::publishedList($limit, 0);
                foreach ($posts as $p) {
                    $inner .= self::renderFeaturedCard($p);
                }
                if ($inner !== '') {
                    $inner = '<div class="widget-featured">' . $inner . '</div>';
                }
                break;
            case 'cta':
                $headline = (string) ($config['headline'] ?? '');
                $text = (string) ($config['text'] ?? '');
                $label = (string) ($config['label'] ?? '');
                $url = (string) ($config['url'] ?? '');
                if ($headline !== '') {
                    $inner .= '<p class="widget-cta-headline">' . htmlspecialchars($headline) . '</p>';
                }
                if ($text !== '') {
                    $inner .= '<p class="widget-cta-text">' . nl2br(htmlspecialchars($text), false) . '</p>';
                }
                if ($label !== '' && $url !== '') {
                    $inner .= '<p class="widget-cta-actions"><a class="btn btn-primary btn-sm" href="'
                        . htmlspecialchars($url) . '">' . htmlspecialchars($label) . '</a></p>';
                }
                break;
            case 'pages':
                $pages = Page::publishedOptions();
                $inner = '<ul class="widget-list">';
                foreach ($pages as $pg) {
                    $inner .= '<li><a href="' . htmlspecialchars(Permalink::urlForPage($pg)) . '">'
                        . htmlspecialchars((string) $pg->title) . '</a></li>';
                }
                $inner .= '</ul>';
                break;
            case 'social':
                $links = is_array($config['links'] ?? null) ? $config['links'] : [];
                $inner = '<ul class="widget-social">';
                foreach ($links as $link) {
                    if (!is_array($link)) {
                        continue;
                    }
                    $url = (string) ($link['url'] ?? '');
                    $label = (string) ($link['label'] ?? '');
                    if ($url === '' || $label === '') {
                        continue;
                    }
                    $inner .= '<li><a href="' . htmlspecialchars($url) . '" rel="noopener">'
                        . htmlspecialchars($label) . '</a></li>';
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
            case 'archives':
                $limit = (int) ($config['count'] ?? 12);
                $inner = '<ul class="widget-list">';
                foreach (Post::archiveMonths($limit) as $row) {
                    $inner .= '<li><a href="' . htmlspecialchars($row->url) . '">'
                        . htmlspecialchars($row->label) . '</a> <span class="text-muted">(' . (int) $row->count . ')</span></li>';
                }
                $inner .= '</ul>';
                break;
            case 'search':
                $inner = '<form action="/search" method="get" class="widget-search"><input type="search" name="q" class="form-control form-control-sm" placeholder="Search…" aria-label="Search"></form>';
                break;
            case 'newsletter':
                $inner = NewsletterSubscriber::renderForm($config, isset($widget->id) ? (int) $widget->id : null);
                break;
            case 'custom_html':
            default:
                $inner = (string) ($config['html'] ?? '');
                break;
        }

        if (trim(strip_tags($inner)) === '' && !str_contains($inner, '<img') && !str_contains($inner, '<form')) {
            return '';
        }
        $out = '<div class="public-widget public-widget--' . htmlspecialchars($type) . '">';
        if ($title !== '') {
            $out .= '<h3 class="public-widget-title">' . htmlspecialchars($title) . '</h3>';
        }
        $out .= '<div class="public-widget-body">' . $inner . '</div></div>';
        return $out;
    }

    private static function renderFeaturedCard(object $post): string
    {
        $url = htmlspecialchars(Permalink::urlForPost($post));
        $title = htmlspecialchars((string) ($post->title ?? ''));
        $excerpt = '';
        if (!\App\ContentPassword::isLocked('post', $post)) {
            $excerpt = trim(strip_tags((string) ($post->excerpt ?? '')));
            if ($excerpt === '') {
                $excerpt = trim(strip_tags((string) ($post->body ?? '')));
            }
            if (mb_strlen($excerpt) > 160) {
                $excerpt = mb_substr($excerpt, 0, 157) . '…';
            }
        }
        $img = '';
        $mediaId = (int) ($post->featured_image_id ?? 0);
        if ($mediaId > 0) {
            $img = Media::responsiveImg($mediaId, [
                'alt' => (string) ($post->title ?? ''),
                'class' => 'widget-featured-img img-fluid',
                'preferred_width' => 480,
            ]);
        }
        $html = '<article class="widget-featured-card">';
        if ($img !== '') {
            $html .= '<a href="' . $url . '" class="widget-featured-media">' . $img . '</a>';
        }
        $html .= '<h4 class="widget-featured-title"><a href="' . $url . '">' . $title . '</a></h4>';
        if ($excerpt !== '') {
            $html .= '<p class="widget-featured-excerpt">' . htmlspecialchars($excerpt) . '</p>';
        }
        $html .= '</article>';
        return $html;
    }

    private static function safeWidgetUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }
        if (str_starts_with($url, '/') && !str_starts_with($url, '//')) {
            return mb_substr($url, 0, 500);
        }
        if (preg_match('#^https?://#i', $url) && filter_var($url, FILTER_VALIDATE_URL)) {
            return mb_substr($url, 0, 500);
        }
        return '';
    }

    /** @return list<array{label: string, url: string}> */
    public static function parseSocialLines(string $raw): array
    {
        $out = [];
        foreach (preg_split('/\R/', $raw) ?: [] as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            $label = '';
            $url = $line;
            if (str_contains($line, '|')) {
                [$label, $url] = array_map('trim', explode('|', $line, 2));
            }
            $out[] = ['label' => $label, 'url' => $url];
        }
        return $out;
    }

    public static function socialLinesFromConfig(array $config): string
    {
        $links = is_array($config['links'] ?? null) ? $config['links'] : [];
        $lines = [];
        foreach ($links as $item) {
            if (!is_array($item)) {
                continue;
            }
            $label = trim((string) ($item['label'] ?? ''));
            $url = trim((string) ($item['url'] ?? ''));
            if ($url === '') {
                continue;
            }
            $lines[] = $label !== '' ? ($label . '|' . $url) : $url;
        }
        return implode("\n", $lines);
    }

    private static function labelFromUrl(string $url): string
    {
        $host = parse_url($url, PHP_URL_HOST);
        if (!is_string($host) || $host === '') {
            return 'Link';
        }
        return preg_replace('/^www\./i', '', $host) ?: 'Link';
    }
}
