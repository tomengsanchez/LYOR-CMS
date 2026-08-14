<?php
namespace App;

use App\Models\Category;
use App\Models\Page;
use App\Models\Post;

class Permalink
{
    /** Reserved first path segments — never resolve as plain pages/posts. */
    private const RESERVED = [
        'admin', 'api', 'serve', 'share', 'blog', 'p', 'index.json', 'site.json', 'blog.json',
        'sitemap.xml', 'robots.txt', 'llms.txt', 'llms-full.txt', 'feed', 'feed.xml', 'login', 'help', 'pages', 'posts', 'categories',
        'media', 'settings', 'users', 'account', 'notifications', 'admin-guide', 'system',
    ];

    public static function urlForPage(object $page): string
    {
        $slug = rawurlencode((string) ($page->slug ?? ''));
        $structure = PermalinkSettings::get()->page_structure;
        if ($structure === PermalinkSettings::PAGE_PLAIN) {
            return '/' . $slug;
        }
        return '/p/' . $slug;
    }

    public static function urlForPost(object $post): string
    {
        $slug = rawurlencode((string) ($post->slug ?? ''));
        $structure = PermalinkSettings::get()->post_structure;
        if ($structure === PermalinkSettings::POST_PLAIN) {
            return '/' . $slug;
        }
        if ($structure === PermalinkSettings::POST_YEAR_MONTH) {
            $date = (string) ($post->published_at ?? $post->created_at ?? date('Y-m-d'));
            $year = substr($date, 0, 4);
            $month = substr($date, 5, 2);
            return '/' . $year . '/' . $month . '/' . $slug;
        }
        if ($structure === PermalinkSettings::POST_CATEGORY) {
            $catSlug = 'uncategorized';
            if (!empty($post->category_slug)) {
                $catSlug = rawurlencode((string) $post->category_slug);
            } elseif (!empty($post->category_id)) {
                $cat = Category::find((int) $post->category_id);
                if ($cat) {
                    $catSlug = rawurlencode((string) $cat->slug);
                }
            }
            return '/' . $catSlug . '/' . $slug;
        }
        return '/blog/' . $slug;
    }

    /**
     * Resolve a public path to content. Returns null if no match.
     *
     * @return array{type: string, slug: string}|null
     */
    public static function resolvePath(string $path): ?array
    {
        $path = '/' . trim($path, '/');
        if ($path === '/') {
            return null;
        }

        $segments = array_values(array_filter(explode('/', trim($path, '/'))));
        if ($segments === []) {
            return null;
        }

        if (in_array(strtolower($segments[0]), self::RESERVED, true)) {
            return null;
        }

        $settings = PermalinkSettings::get();
        $count = count($segments);

        // Page plain: /about
        if ($settings->page_structure === PermalinkSettings::PAGE_PLAIN && $count === 1) {
            $page = Page::findBySlug($segments[0], true);
            if ($page) {
                return ['type' => 'page', 'slug' => $page->slug];
            }
        }

        // Post plain: /my-post (only if not a page)
        if ($settings->post_structure === PermalinkSettings::POST_PLAIN && $count === 1) {
            $post = Post::findBySlug($segments[0], true);
            if ($post) {
                return ['type' => 'post', 'slug' => $post->slug];
            }
        }

        // Post year/month: /2026/08/slug
        if ($settings->post_structure === PermalinkSettings::POST_YEAR_MONTH && $count === 3) {
            if (preg_match('/^\d{4}$/', $segments[0]) && preg_match('/^\d{2}$/', $segments[1])) {
                $post = Post::findBySlug($segments[2], true);
                if ($post) {
                    return ['type' => 'post', 'slug' => $post->slug];
                }
            }
        }

        // Post category: /category-slug/post-slug
        if ($settings->post_structure === PermalinkSettings::POST_CATEGORY && $count === 2) {
            $cat = Category::findBySlug($segments[0]);
            $post = Post::findBySlug($segments[1], true);
            if ($cat && $post && (int) ($post->category_id ?? 0) === (int) $cat->id) {
                return ['type' => 'post', 'slug' => $post->slug];
            }
        }

        return null;
    }
}
