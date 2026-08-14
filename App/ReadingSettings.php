<?php
namespace App;

use App\Models\AppSettings;
use App\Models\Page;

/**
 * WordPress-style Reading settings (Settings → Reading).
 */
class ReadingSettings
{
    public const FRONT_PAGE = 'page';
    public const FRONT_POSTS = 'posts';

    public static function get(): object
    {
        $show = strtolower(trim(AppSettings::get('reading_show_on_front', self::FRONT_PAGE)));
        if (!in_array($show, [self::FRONT_PAGE, self::FRONT_POSTS], true)) {
            $show = self::FRONT_PAGE;
        }
        $pageId = (int) AppSettings::get('reading_page_on_front', '0');
        $perPage = (int) AppSettings::get('reading_posts_per_page', '10');
        $perPage = max(1, min(50, $perPage));

        return (object) [
            'show_on_front' => $show,
            'page_on_front' => $pageId > 0 ? $pageId : 0,
            'posts_per_page' => $perPage,
        ];
    }

    /** @param array<string, mixed> $data */
    public static function save(array $data): void
    {
        $show = strtolower(trim((string) ($data['reading_show_on_front'] ?? self::FRONT_PAGE)));
        if (!in_array($show, [self::FRONT_PAGE, self::FRONT_POSTS], true)) {
            $show = self::FRONT_PAGE;
        }
        AppSettings::set('reading_show_on_front', $show);
        AppSettings::set('reading_page_on_front', (string) max(0, (int) ($data['reading_page_on_front'] ?? 0)));
        $perPage = max(1, min(50, (int) ($data['reading_posts_per_page'] ?? 10)));
        AppSettings::set('reading_posts_per_page', (string) $perPage);
    }

    public static function frontPageOptions(): array
    {
        return Page::publishedOptions();
    }

    public static function resolveFrontPage(): ?object
    {
        $settings = self::get();
        if ($settings->show_on_front === self::FRONT_POSTS) {
            return null;
        }
        if ($settings->page_on_front > 0) {
            $page = Page::findPublished($settings->page_on_front);
            if ($page) {
                return $page;
            }
        }
        $page = Page::findBySlug('welcome', true);
        if (!$page) {
            $page = Page::findBySlug('home', true);
        }
        return $page;
    }
}
