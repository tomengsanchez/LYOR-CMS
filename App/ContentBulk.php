<?php
namespace App;

/**
 * Shared sanitizers for admin list bulk actions (pages / posts).
 */
class ContentBulk
{
    public const MAX_IDS = 100;

    /**
     * @return list<int>
     */
    public static function idsFromRequest(): array
    {
        $raw = $_POST['ids'] ?? [];
        if (!is_array($raw)) {
            return [];
        }
        $out = [];
        foreach ($raw as $id) {
            $n = (int) $id;
            if ($n <= 0) {
                continue;
            }
            $out[$n] = $n;
            if (count($out) >= self::MAX_IDS) {
                break;
            }
        }
        return array_values($out);
    }

    /** Front page (configured or welcome/home fallback) — never draft/delete in bulk. */
    public static function protectedPageIds(): array
    {
        $ids = [];
        $settings = ReadingSettings::get();
        if ((int) $settings->page_on_front > 0) {
            $ids[] = (int) $settings->page_on_front;
        }
        $front = ReadingSettings::resolveFrontPage();
        if ($front) {
            $ids[] = (int) $front->id;
        }
        foreach (['welcome', 'home'] as $slug) {
            $page = \App\Models\Page::findBySlug($slug, false);
            if ($page) {
                $ids[] = (int) $page->id;
            }
        }
        return array_values(array_unique($ids));
    }

    /**
     * @param list<int> $ids
     * @param list<int> $skip
     * @return list<int>
     */
    public static function withoutIds(array $ids, array $skip): array
    {
        if ($skip === []) {
            return $ids;
        }
        $map = [];
        foreach ($skip as $id) {
            $map[(int) $id] = true;
        }
        $out = [];
        foreach ($ids as $id) {
            $id = (int) $id;
            if ($id <= 0 || isset($map[$id])) {
                continue;
            }
            $out[] = $id;
        }
        return $out;
    }
}
