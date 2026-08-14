<?php
namespace App;

use App\Models\AppSettings;
use App\Models\Category;
use App\Models\Media;
use App\Models\Post;

/**
 * SEO, social sharing, and LLM-friendly metadata for public CMS pages.
 */
class PublicSeo
{
    /** Site-wide defaults when no CMS page exists (e.g. empty homepage). */
    public static function siteContext(?object $branding = null): array
    {
        $branding = $branding ?? AppSettings::getBrandingConfig();
        $seo = AppSettings::getSiteSeoConfig();
        $share = SocialShare::forSite($branding);
        $baseUrl = SocialShare::baseUrl();

        return [
            'share' => $share,
            'json_ld' => self::siteJsonLd($branding, $share),
            'json_url' => $seo->enable_json_export && $baseUrl !== '' ? $baseUrl . '/site.json' : '',
            'robots_noindex' => false,
            'llm_summary' => trim((string) ($seo->llm_site_summary ?? '')),
            'llms_url' => LlmsTxt::isEnabled() && $baseUrl !== '' ? $baseUrl . '/llms.txt' : '',
        ];
    }

    /** @return array<string, mixed> */
    public static function forSite(?object $branding = null): array
    {
        return SocialShare::forSite($branding);
    }

    /** @return array<string, mixed> Site JSON for LLM crawlers. */
    public static function siteJsonDocument(?object $branding = null): array
    {
        $branding = $branding ?? AppSettings::getBrandingConfig();
        $seo = AppSettings::getSiteSeoConfig();
        $share = SocialShare::forSite($branding);
        $baseUrl = SocialShare::baseUrl();

        return [
            'type' => 'site',
            'title' => (string) ($share['title'] ?? $branding->app_name ?? ''),
            'url' => $baseUrl . '/',
            'language' => self::localeLanguage($seo->locale ?? 'en_US'),
            'seo' => [
                'title' => (string) ($share['title'] ?? ''),
                'description' => (string) ($share['description'] ?? ''),
                'keywords' => trim((string) ($seo->site_keywords ?? '')),
                'robots' => 'index, follow',
            ],
            'summary' => trim((string) ($seo->llm_site_summary ?? '')) ?: (string) ($share['description'] ?? ''),
            'site_name' => trim((string) ($branding->app_name ?? 'Simple CMS')),
            'organization' => trim((string) ($branding->company_name ?? '')),
            'endpoints' => [
                'sitemap' => !empty($seo->enable_sitemap) ? $baseUrl . '/sitemap.xml' : null,
                'robots' => $baseUrl . '/robots.txt',
                'llms' => LlmsTxt::isEnabled() ? $baseUrl . '/llms.txt' : null,
                'llms_full' => LlmsTxt::isEnabled() ? $baseUrl . '/llms-full.txt' : null,
                'blog' => $baseUrl . '/blog',
                'blog_json' => !empty($seo->enable_json_export) ? $baseUrl . '/blog.json' : null,
                'feed' => $seo->enable_rss_feed ? $baseUrl . '/feed.xml' : null,
            ],
        ];
    }

    /** @return array<string, mixed> Context for public layout (share meta, JSON-LD, robots). */
    public static function pageContext(object $page, ?object $branding = null, bool $isHomepage = false, array $breadcrumbs = []): array
    {
        $branding = $branding ?? AppSettings::getBrandingConfig();
        $seo = AppSettings::getSiteSeoConfig();
        $share = self::shareMeta($page, $branding, $isHomepage);
        $baseUrl = SocialShare::baseUrl();
        $jsonPath = $isHomepage ? '/index.json' : Permalink::urlForPage($page) . '.json';
        $pageLlm = trim((string) ($page->llm_summary ?? ''));
        $siteLlm = trim((string) ($seo->llm_site_summary ?? ''));

        return [
            'share' => $share,
            'json_ld' => self::jsonLd($page, $branding, $share, $isHomepage, $breadcrumbs),
            'json_url' => $seo->enable_json_export && $baseUrl !== '' ? $baseUrl . $jsonPath : '',
            'robots_noindex' => !empty($page->robots_noindex),
            'llm_summary' => $pageLlm !== '' ? $pageLlm : ($isHomepage ? $siteLlm : ''),
        ];
    }

    /** @return array<string, mixed> Open Graph / Twitter payload. */
    public static function shareMeta(object $page, ?object $branding = null, bool $isHomepage = false): array
    {
        $branding = $branding ?? AppSettings::getBrandingConfig();
        $baseUrl = SocialShare::baseUrl();
        $slug = (string) ($page->slug ?? '');
        $url = $isHomepage ? $baseUrl . '/' : $baseUrl . Permalink::urlForPage($page);
        $title = trim((string) ($page->meta_title ?? ''));
        if ($title === '') {
            $title = trim((string) ($page->title ?? ''));
        }
        $title = AppSettings::formatSeoTitle($title);
        $description = trim((string) ($page->meta_description ?? ''));
        if ($description === '') {
            $description = SocialShare::descriptionFromText(
                trim((string) ($page->llm_summary ?? '')),
                ContentBlocks::plainTextFromEntity($page)
            );
        }
        if ($description === '') {
            $description = SocialShare::siteDescription($branding);
        }
        $image = self::imageFromPage($page, $branding);

        return [
            'title' => $title,
            'description' => $description,
            'url' => $url,
            'type' => $isHomepage ? 'website' : 'article',
            'site_name' => trim((string) ($branding->app_name ?? 'Simple CMS')),
            'image' => $image['url'],
            'image_width' => $image['width'],
            'image_height' => $image['height'],
            'image_alt' => $image['alt'],
            'image_type' => $image['type'],
            'published_time' => !empty($page->updated_at) ? (string) $page->updated_at : null,
            'modified_time' => !empty($page->updated_at) ? (string) $page->updated_at : null,
        ];
    }

    /** @return array<string, mixed> Schema.org WebPage (JSON-LD). */
    public static function jsonLd(object $page, ?object $branding, array $share, bool $isHomepage = false, array $breadcrumbs = []): array
    {
        $branding = $branding ?? AppSettings::getBrandingConfig();
        $seo = AppSettings::getSiteSeoConfig();
        $summary = trim((string) ($page->llm_summary ?? ''));
        if ($summary === '' && $isHomepage) {
            $summary = trim((string) ($seo->llm_site_summary ?? ''));
        }
        $description = trim((string) ($share['description'] ?? ''));
        $data = [
            '@type' => $isHomepage ? 'WebSite' : 'WebPage',
            'name' => (string) ($share['title'] ?? $page->title ?? ''),
            'description' => $summary !== '' ? $summary : $description,
            'url' => (string) ($share['url'] ?? ''),
            'inLanguage' => self::localeLanguage($seo->locale ?? 'en_US'),
            'isPartOf' => self::websiteNode($branding, $seo),
            'publisher' => self::organizationNode($branding),
        ];
        if (!empty($page->updated_at)) {
            $data['dateModified'] = (string) $page->updated_at;
        }
        if (!empty($share['image'])) {
            $data['primaryImageOfPage'] = [
                '@type' => 'ImageObject',
                'url' => (string) $share['image'],
            ];
        }
        if ($isHomepage) {
            $data['potentialAction'] = self::searchAction();
        }

        $crumb = self::breadcrumbList($breadcrumbs, $page, $share);
        if ($crumb === null) {
            $data['@context'] = 'https://schema.org';
            return $data;
        }
        return [
            '@context' => 'https://schema.org',
            '@graph' => [$data, $crumb],
        ];
    }

    /** @return array<string, mixed> */
    public static function siteJsonLd(?object $branding, array $share): array
    {
        $branding = $branding ?? AppSettings::getBrandingConfig();
        $seo = AppSettings::getSiteSeoConfig();
        $summary = trim((string) ($seo->llm_site_summary ?? ''));

        $node = [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => trim((string) ($branding->app_name ?? 'Simple CMS')),
            'description' => $summary !== '' ? $summary : (string) ($share['description'] ?? ''),
            'url' => SocialShare::baseUrl() . '/',
            'inLanguage' => self::localeLanguage($seo->locale ?? 'en_US'),
            'publisher' => self::organizationNode($branding),
            'potentialAction' => self::searchAction(),
        ];
        $sameAs = self::sameAsLinks($seo);
        if ($sameAs !== []) {
            $node['sameAs'] = $sameAs;
        }
        return $node;
    }

    /** @return array<string, mixed> */
    private static function websiteNode(?object $branding, object $seo): array
    {
        $branding = $branding ?? AppSettings::getBrandingConfig();
        $node = [
            '@type' => 'WebSite',
            'name' => trim((string) ($branding->app_name ?? 'Simple CMS')),
            'url' => SocialShare::baseUrl() . '/',
            'description' => trim((string) ($seo->llm_site_summary ?? '')) ?: null,
            'potentialAction' => self::searchAction(),
        ];
        return $node;
    }

    /** @return array<string, mixed> */
    private static function searchAction(): array
    {
        $base = SocialShare::baseUrl();
        return [
            '@type' => 'SearchAction',
            'target' => [
                '@type' => 'EntryPoint',
                'urlTemplate' => $base . '/blog?q={search_term_string}',
            ],
            'query-input' => 'required name=search_term_string',
        ];
    }

    /**
     * @param array<int, object> $ancestors
     * @return array<string, mixed>|null
     */
    private static function breadcrumbList(array $ancestors, object $page, array $share): ?array
    {
        if ($ancestors === []) {
            return null;
        }
        $base = SocialShare::baseUrl();
        $items = [
            [
                '@type' => 'ListItem',
                'position' => 1,
                'name' => 'Home',
                'item' => $base . '/',
            ],
        ];
        $pos = 2;
        foreach ($ancestors as $crumb) {
            $items[] = [
                '@type' => 'ListItem',
                'position' => $pos,
                'name' => (string) ($crumb->title ?? ''),
                'item' => $base . Permalink::urlForPage($crumb),
            ];
            $pos++;
        }
        $items[] = [
            '@type' => 'ListItem',
            'position' => $pos,
            'name' => (string) ($page->title ?? $share['title'] ?? ''),
            'item' => (string) ($share['url'] ?? ''),
        ];
        return [
            '@type' => 'BreadcrumbList',
            'itemListElement' => $items,
        ];
    }

    /** @return array<int, string> */
    public static function sameAsLinks(?object $seo = null): array
    {
        $seo = $seo ?? AppSettings::getSiteSeoConfig();
        $links = [];
        $twitter = trim((string) ($seo->twitter_handle ?? ''));
        if ($twitter !== '') {
            $links[] = 'https://x.com/' . $twitter;
        }
        foreach (['facebook_url', 'linkedin_url'] as $key) {
            $url = trim((string) ($seo->$key ?? ''));
            if ($url !== '') {
                $links[] = $url;
            }
        }
        return array_values(array_unique($links));
    }

    /** @return array<string, mixed> */
    private static function organizationNode(?object $branding): array
    {
        $branding = $branding ?? AppSettings::getBrandingConfig();
        $org = [
            '@type' => 'Organization',
            'name' => trim((string) ($branding->company_name ?? '')) ?: trim((string) ($branding->app_name ?? 'Simple CMS')),
            'url' => SocialShare::baseUrl() . '/',
        ];
        $logo = SocialShare::fallbackImage($branding);
        if (!empty($logo['url'])) {
            $org['logo'] = $logo['url'];
        }
        $sameAs = self::sameAsLinks();
        if ($sameAs !== []) {
            $org['sameAs'] = $sameAs;
        }
        return $org;
    }

    public static function localeLanguage(string $locale): string
    {
        return str_replace('_', '-', AppSettings::normalizeLocale($locale));
    }

    /** Machine-readable page document for LLM crawlers and tools. */
    public static function jsonDocument(object $page, ?object $branding = null, bool $isHomepage = false): array
    {
        $branding = $branding ?? AppSettings::getBrandingConfig();
        $seo = AppSettings::getSiteSeoConfig();
        $share = self::shareMeta($page, $branding, $isHomepage);
        $summary = trim((string) ($page->llm_summary ?? ''));
        if ($summary === '' && $isHomepage) {
            $summary = trim((string) ($seo->llm_site_summary ?? ''));
        }
        $bodyText = ContentBlocks::plainTextFromEntity($page);
        $blocks = ContentBlocks::parse($page->blocks_json ?? null);
        $layout = \App\LayoutBuilder::hasLayout($page)
            ? \App\LayoutBuilder::parse($page->layout_json ?? null)
            : null;

        return [
            'type' => 'page',
            'title' => (string) ($page->title ?? ''),
            'slug' => (string) ($page->slug ?? ''),
            'url' => (string) ($share['url'] ?? ''),
            'language' => self::localeLanguage($seo->locale ?? 'en_US'),
            'seo' => [
                'title' => (string) ($share['title'] ?? ''),
                'description' => (string) ($share['description'] ?? ''),
                'robots' => !empty($page->robots_noindex) ? 'noindex, nofollow' : 'index, follow',
            ],
            'summary' => $summary !== '' ? $summary : (string) ($share['description'] ?? ''),
            'content_text' => $bodyText,
            'layout' => $layout,
            'blocks' => $blocks !== [] ? $blocks : null,
            'updated_at' => !empty($page->updated_at) ? (string) $page->updated_at : null,
            'site_name' => trim((string) ($branding->app_name ?? 'Simple CMS')),
        ];
    }

    /** @param array<int, object> $tags */
    public static function postContext(object $post, ?object $branding = null, array $tags = []): array
    {
        $branding = $branding ?? AppSettings::getBrandingConfig();
        $seo = AppSettings::getSiteSeoConfig();
        $share = SocialShare::forPost($post, $branding);
        $baseUrl = SocialShare::baseUrl();
        $tagNames = [];
        foreach ($tags as $tag) {
            $name = trim((string) ($tag->name ?? ''));
            if ($name !== '') {
                $tagNames[] = $name;
            }
        }
        $share['tags'] = $tagNames;
        $jsonUrl = '';
        if ($seo->enable_json_export && $baseUrl !== '' && !empty($post->slug)) {
            $jsonUrl = $baseUrl . '/blog/' . rawurlencode((string) $post->slug) . '.json';
        }
        $llm = trim((string) ($post->llm_summary ?? ''));

        return [
            'share' => $share,
            'json_ld' => self::postJsonLd($post, $branding, $share, $tagNames),
            'json_url' => $jsonUrl,
            'robots_noindex' => !empty($post->robots_noindex),
            'llm_summary' => $llm,
        ];
    }

    /** @param array<int, string> $tagNames */
    public static function postJsonLd(object $post, ?object $branding, array $share, array $tagNames = []): array
    {
        $branding = $branding ?? AppSettings::getBrandingConfig();
        $seo = AppSettings::getSiteSeoConfig();
        $summary = trim((string) ($post->llm_summary ?? ''));
        $description = $summary !== '' ? $summary : (string) ($share['description'] ?? '');
        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'BlogPosting',
            'headline' => (string) ($post->title ?? $share['title'] ?? ''),
            'description' => $description,
            'url' => (string) ($share['url'] ?? ''),
            'mainEntityOfPage' => (string) ($share['url'] ?? ''),
            'inLanguage' => self::localeLanguage($seo->locale ?? 'en_US'),
            'isPartOf' => self::websiteNode($branding, $seo),
            'publisher' => self::organizationNode($branding),
        ];
        if (!empty($post->published_at)) {
            $data['datePublished'] = (string) $post->published_at;
        }
        if (!empty($post->updated_at)) {
            $data['dateModified'] = (string) $post->updated_at;
        }
        if (!empty($post->author_name)) {
            $data['author'] = [
                '@type' => 'Person',
                'name' => (string) $post->author_name,
            ];
        }
        if (!empty($share['image'])) {
            $data['image'] = (string) $share['image'];
        }
        if (!empty($post->category_name)) {
            $data['articleSection'] = (string) $post->category_name;
        }
        if ($tagNames !== []) {
            $data['keywords'] = implode(', ', $tagNames);
        }
        return $data;
    }

    public static function archiveContext(string $title, string $description, string $path, string $schemaType = 'CollectionPage', ?object $branding = null): array
    {
        $branding = $branding ?? AppSettings::getBrandingConfig();
        $seo = AppSettings::getSiteSeoConfig();
        $share = SocialShare::forArchive($title, $description, $path, $branding);
        $jsonLd = [
            '@context' => 'https://schema.org',
            '@type' => $schemaType,
            'name' => (string) ($share['title'] ?? $title),
            'description' => (string) ($share['description'] ?? ''),
            'url' => (string) ($share['url'] ?? ''),
            'inLanguage' => self::localeLanguage($seo->locale ?? 'en_US'),
            'isPartOf' => self::websiteNode($branding, $seo),
        ];
        $jsonUrl = '';
        $base = SocialShare::baseUrl();
        if ($path === '/blog' && $seo->enable_json_export && $base !== '') {
            $jsonUrl = $base . '/blog.json';
        }

        return [
            'share' => $share,
            'json_ld' => $jsonLd,
            'json_url' => $jsonUrl,
            'robots_noindex' => false,
            'llm_summary' => trim((string) ($seo->llm_site_summary ?? '')),
        ];
    }

    /** @return array<string, mixed> */
    public static function blogJsonDocument(?object $branding = null): array
    {
        $branding = $branding ?? AppSettings::getBrandingConfig();
        $seo = AppSettings::getSiteSeoConfig();
        $base = SocialShare::baseUrl();
        $posts = [];
        foreach (Post::publishedForLlms(50) as $post) {
            $share = SocialShare::forPost($post, $branding);
            $summary = trim((string) ($post->llm_summary ?? ''));
            if ($summary === '') {
                $summary = (string) ($share['description'] ?? '');
            }
            $posts[] = [
                'title' => (string) ($post->title ?? ''),
                'slug' => (string) ($post->slug ?? ''),
                'url' => (string) ($share['url'] ?? ''),
                'json_url' => $base !== '' && !empty($post->slug) ? $base . '/blog/' . rawurlencode((string) $post->slug) . '.json' : null,
                'summary' => $summary,
                'category' => !empty($post->category_name) ? (string) $post->category_name : null,
                'published_at' => !empty($post->published_at) ? (string) $post->published_at : null,
            ];
        }
        $categories = [];
        foreach (Category::publishedForSitemap() as $cat) {
            $categories[] = [
                'name' => (string) $cat->name,
                'url' => $base . '/blog/category/' . rawurlencode((string) $cat->slug),
            ];
        }

        return [
            'type' => 'blog',
            'title' => trim((string) ($branding->app_name ?? 'Simple CMS')) . ' blog',
            'url' => $base . '/blog',
            'language' => self::localeLanguage($seo->locale ?? 'en_US'),
            'summary' => trim((string) ($seo->llm_site_summary ?? '')) ?: SocialShare::siteDescription($branding),
            'posts' => $posts,
            'categories' => $categories,
        ];
    }

    /** Machine-readable post document for LLM crawlers and tools. */
    public static function postJsonDocument(object $post, ?object $branding = null): array
    {
        $branding = $branding ?? AppSettings::getBrandingConfig();
        $seo = AppSettings::getSiteSeoConfig();
        $share = SocialShare::forPost($post, $branding);
        $bodyText = ContentBlocks::plainTextFromEntity($post);
        $blocks = ContentBlocks::parse($post->blocks_json ?? null);
        $layout = \App\LayoutBuilder::hasLayout($post)
            ? \App\LayoutBuilder::parse($post->layout_json ?? null)
            : null;

        return [
            'type' => 'post',
            'title' => (string) ($post->title ?? ''),
            'slug' => (string) ($post->slug ?? ''),
            'url' => (string) ($share['url'] ?? ''),
            'language' => self::localeLanguage($seo->locale ?? 'en_US'),
            'seo' => [
                'title' => (string) ($share['title'] ?? ''),
                'description' => (string) ($share['description'] ?? ''),
                'robots' => !empty($post->robots_noindex) ? 'noindex, nofollow' : 'index, follow',
            ],
            'summary' => trim((string) ($post->llm_summary ?? '')) ?: (string) ($share['description'] ?? ''),
            'content_text' => $bodyText,
            'layout' => $layout,
            'blocks' => $blocks !== [] ? $blocks : null,
            'category' => !empty($post->category_name) ? (string) $post->category_name : null,
            'published_at' => !empty($post->published_at) ? (string) $post->published_at : null,
            'updated_at' => !empty($post->updated_at) ? (string) $post->updated_at : null,
            'site_name' => trim((string) ($branding->app_name ?? 'Simple CMS')),
        ];
    }

    /** @return array{url: string, width: ?int, height: ?int, alt: string, type: string} */
    public static function imageFromPage(object $page, ?object $branding = null): array
    {
        $mediaId = (int) ($page->featured_image_id ?? 0);
        if ($mediaId > 0 && Media::isImageMime((string) ($page->featured_mime_type ?? ''))) {
            return [
                'url' => Media::publicShareUrl($mediaId),
                'width' => !empty($page->featured_width) ? (int) $page->featured_width : null,
                'height' => !empty($page->featured_height) ? (int) $page->featured_height : null,
                'alt' => trim((string) ($page->featured_alt_text ?? $page->title ?? '')),
                'type' => (string) ($page->featured_mime_type ?? 'image/jpeg'),
            ];
        }
        return SocialShare::fallbackImage($branding);
    }

    public static function plainText(string $html): string
    {
        $text = trim(strip_tags($html));
        return preg_replace('/\s+/u', ' ', $text) ?? '';
    }

    public static function normalizeLlmSummary(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }
        if (mb_strlen($value) > 2000) {
            $value = mb_substr($value, 0, 2000);
        }
        return $value;
    }

    public static function normalizeMetaDescription(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }
        if (mb_strlen($value) > 500) {
            $value = mb_substr($value, 0, 500);
        }
        return $value;
    }

    public static function normalizeMetaTitle(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }
        if (mb_strlen($value) > 255) {
            $value = mb_substr($value, 0, 255);
        }
        return $value;
    }
}
