<?php
namespace App;

use App\Models\AppSettings;
use App\Models\Category;
use App\Models\Page;
use App\Models\Post;
use App\Models\Tag;

/**
 * llmstxt.org documents and AI-crawler robots rules.
 */
class LlmsTxt
{
    /** Common generative-AI / training crawlers. */
    public const AI_CRAWLERS = [
        'GPTBot',
        'ChatGPT-User',
        'OAI-SearchBot',
        'Google-Extended',
        'GoogleOther',
        'ClaudeBot',
        'Claude-Web',
        'Anthropic-AI',
        'PerplexityBot',
        'Applebot-Extended',
        'Bytespider',
        'CCBot',
        'Amazonbot',
        'meta-externalagent',
        'FacebookBot',
        'cohere-ai',
        'YouBot',
    ];

    public static function isEnabled(): bool
    {
        return !empty(AppSettings::getSiteSeoConfig()->enable_llms_txt);
    }

    public static function allowAiCrawlers(): bool
    {
        return !empty(AppSettings::getSiteSeoConfig()->allow_ai_crawlers);
    }

    /** Compact index (llms.txt). */
    public static function indexDocument(?object $branding = null): string
    {
        return self::build(false, $branding);
    }

    /** Longer document with summaries (llms-full.txt). */
    public static function fullDocument(?object $branding = null): string
    {
        return self::build(true, $branding);
    }

    public static function robotsTxt(): string
    {
        $base = SocialShare::baseUrl();
        $seo = AppSettings::getSiteSeoConfig();
        $lines = [
            '# Simple CMS robots',
            'User-agent: *',
            'Allow: /',
            'Disallow: /admin',
            'Disallow: /api',
            '',
        ];

        $allow = self::allowAiCrawlers();
        $lines[] = $allow
            ? '# AI / LLM crawlers — allowed (General → SEO)'
            : '# AI / LLM crawlers — blocked (General → SEO)';
        foreach (self::AI_CRAWLERS as $bot) {
            $lines[] = 'User-agent: ' . $bot;
            $lines[] = $allow ? 'Allow: /' : 'Disallow: /';
        }
        $lines[] = '';

        if ($base !== '') {
            if (!empty($seo->enable_sitemap)) {
                $lines[] = 'Sitemap: ' . $base . '/sitemap.xml';
            }
            if (self::isEnabled()) {
                $lines[] = '# LLM discovery: ' . $base . '/llms.txt';
                $lines[] = '# LLM full: ' . $base . '/llms-full.txt';
            }
            if (!empty($seo->enable_json_export)) {
                $lines[] = '# Machine-readable: ' . $base . '/site.json';
            }
            if (!empty($seo->enable_rss_feed)) {
                $lines[] = '# RSS: ' . $base . '/feed.xml';
            }
        }

        return implode("\n", $lines) . "\n";
    }

    private static function build(bool $full, ?object $branding): string
    {
        $branding = $branding ?? AppSettings::getBrandingConfig();
        $seo = AppSettings::getSiteSeoConfig();
        $base = SocialShare::baseUrl();
        $name = trim((string) ($branding->app_name ?? 'Simple CMS'));
        $summary = trim((string) ($seo->llm_site_summary ?? ''));
        if ($summary === '') {
            $summary = SocialShare::siteDescription($branding);
        }

        $out = [];
        $out[] = '# ' . $name;
        $out[] = '';
        if ($summary !== '') {
            $out[] = '> ' . self::oneLine($summary);
            $out[] = '';
        }
        $org = trim((string) ($branding->company_name ?? ''));
        if ($org !== '') {
            $out[] = 'Published by ' . $org . '.';
            $out[] = '';
        }
        $out[] = 'This file helps language models understand the site. Prefer these URLs over scraping HTML.';
        $out[] = '';

        $out[] = '## Optional';
        if ($base !== '') {
            $out[] = '- [Home](' . $base . '/): Site homepage';
            if (!empty($seo->enable_sitemap)) {
                $out[] = '- [Sitemap](' . $base . '/sitemap.xml): All public URLs';
            }
            if (!empty($seo->enable_json_export)) {
                $out[] = '- [Site JSON](' . $base . '/site.json): Machine-readable site document';
                $out[] = '- [Blog JSON](' . $base . '/blog.json): Published posts catalog';
            }
            if (!empty($seo->enable_rss_feed)) {
                $out[] = '- [RSS feed](' . $base . '/feed.xml): Latest posts';
            }
            $out[] = '- [Blog](' . $base . '/blog): Article listing';
            if ($full) {
                $out[] = '- [llms.txt](' . $base . '/llms.txt): Compact index';
            } else {
                $out[] = '- [llms-full.txt](' . $base . '/llms-full.txt): Longer summaries';
            }
        }
        $out[] = '';

        $pages = Page::publishedForLlms();
        if ($pages !== []) {
            $out[] = '## Pages';
            foreach ($pages as $page) {
                $url = $base . Permalink::urlForPage($page);
                $label = trim((string) ($page->title ?? 'Page'));
                $hint = self::pageHint($page, $full);
                $out[] = '- [' . self::oneLine($label) . '](' . $url . ')' . ($hint !== '' ? ': ' . $hint : '');
                if ($full && !empty($seo->enable_json_export)) {
                    $jsonPath = Page::isHomepageSlug((string) $page->slug) ? '/index.json' : Permalink::urlForPage($page) . '.json';
                    $out[] = '  JSON: ' . $base . $jsonPath;
                }
            }
            $out[] = '';
        }

        $posts = Post::publishedForLlms($full ? 50 : 30);
        if ($posts !== []) {
            $out[] = '## Posts';
            foreach ($posts as $post) {
                $url = $base . Permalink::urlForPost($post);
                $label = trim((string) ($post->title ?? 'Post'));
                $hint = self::postHint($post, $full);
                $out[] = '- [' . self::oneLine($label) . '](' . $url . ')' . ($hint !== '' ? ': ' . $hint : '');
                if ($full && !empty($seo->enable_json_export) && !empty($post->slug)) {
                    $out[] = '  JSON: ' . $base . '/blog/' . rawurlencode((string) $post->slug) . '.json';
                }
            }
            $out[] = '';
        }

        $categories = Category::publishedForSitemap();
        if ($categories !== []) {
            $out[] = '## Categories';
            foreach ($categories as $cat) {
                $url = $base . '/blog/category/' . rawurlencode((string) $cat->slug);
                $hint = trim((string) ($cat->description ?? ''));
                $out[] = '- [' . self::oneLine((string) $cat->name) . '](' . $url . ')' . ($hint !== '' ? ': ' . self::oneLine($hint, 160) : '');
            }
            $out[] = '';
        }

        $tags = Tag::publishedForSitemap();
        if ($tags !== []) {
            $out[] = '## Tags';
            foreach ($tags as $tag) {
                $url = $base . '/blog/tag/' . rawurlencode((string) $tag->slug);
                $out[] = '- [' . self::oneLine((string) $tag->name) . '](' . $url . ')';
            }
            $out[] = '';
        }

        return implode("\n", $out);
    }

    private static function pageHint(object $page, bool $full): string
    {
        $summary = trim((string) ($page->llm_summary ?? ''));
        if ($summary === '') {
            $summary = trim((string) ($page->meta_description ?? ''));
        }
        if ($summary === '' && $full) {
            $summary = ContentBlocks::plainTextFromEntity($page);
        }
        return self::oneLine($summary, $full ? 400 : 180);
    }

    private static function postHint(object $post, bool $full): string
    {
        $summary = trim((string) ($post->llm_summary ?? ''));
        if ($summary === '') {
            $summary = trim((string) ($post->meta_description ?? ''));
        }
        if ($summary === '') {
            $summary = trim((string) ($post->excerpt ?? ''));
        }
        if ($summary === '' && $full) {
            $summary = ContentBlocks::plainTextFromEntity($post);
        }
        return self::oneLine($summary, $full ? 400 : 180);
    }

    private static function oneLine(string $text, int $max = 280): string
    {
        $text = trim(preg_replace('/\s+/u', ' ', $text) ?? '');
        if ($text === '') {
            return '';
        }
        if (mb_strlen($text) > $max) {
            return mb_substr($text, 0, $max - 1) . '…';
        }
        return $text;
    }
}
