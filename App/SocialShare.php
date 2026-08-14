<?php
namespace App;

use App\Models\AppSettings;
use App\Models\Media;

/**
 * Open Graph / Twitter Card meta for public pages (Facebook, LinkedIn, X, etc.).
 */
class SocialShare
{
    public const RECOMMENDED_WIDTH = 1200;
    public const RECOMMENDED_HEIGHT = 630;

    /** @return array<string, mixed> */
    public static function forPost(object $post, ?object $branding = null): array
    {
        $branding = $branding ?? AppSettings::getBrandingConfig();
        $baseUrl = self::baseUrl();
        $url = $baseUrl . Permalink::urlForPost($post);
        $title = trim((string) ($post->meta_title ?? ''));
        if ($title === '') {
            $title = trim((string) ($post->title ?? ''));
        }
        $title = AppSettings::formatSeoTitle($title);
        $description = trim((string) ($post->meta_description ?? ''));
        if ($description === '') {
            $description = self::descriptionFromText(
                trim((string) ($post->llm_summary ?? '')),
                trim((string) ($post->excerpt ?? '')) ?: ContentBlocks::plainTextFromEntity($post)
            );
        }
        if ($description === '') {
            $description = self::siteDescription($branding);
        }
        $image = self::imageFromPost($post, $branding);

        return [
            'title' => $title,
            'description' => $description,
            'url' => $url,
            'type' => 'article',
            'site_name' => trim((string) ($branding->app_name ?? 'Simple CMS')),
            'image' => $image['url'],
            'image_width' => $image['width'],
            'image_height' => $image['height'],
            'image_alt' => $image['alt'],
            'image_type' => $image['type'],
            'published_time' => !empty($post->published_at) ? (string) $post->published_at : null,
            'modified_time' => !empty($post->updated_at) ? (string) $post->updated_at : null,
            'section' => !empty($post->category_name) ? (string) $post->category_name : null,
        ];
    }

    /** @return array<string, mixed> */
    public static function forBlog(?object $branding = null): array
    {
        $branding = $branding ?? AppSettings::getBrandingConfig();
        $baseUrl = self::baseUrl();
        $fallback = self::fallbackImage($branding);
        $appName = trim((string) ($branding->app_name ?? 'Simple CMS'));

        return [
            'title' => AppSettings::formatSeoTitle('Blog — ' . $appName),
            'description' => self::siteDescription($branding),
            'url' => $baseUrl . '/blog',
            'type' => 'website',
            'site_name' => trim((string) ($branding->app_name ?? 'Simple CMS')),
            'image' => $fallback['url'],
            'image_width' => $fallback['width'],
            'image_height' => $fallback['height'],
            'image_alt' => $fallback['alt'],
            'image_type' => $fallback['type'],
            'published_time' => null,
        ];
    }

    /** Category or tag archive share meta. */
    public static function forArchive(string $title, string $description, string $path, ?object $branding = null): array
    {
        $branding = $branding ?? AppSettings::getBrandingConfig();
        $baseUrl = self::baseUrl();
        $fallback = self::fallbackImage($branding);
        $desc = trim($description);
        if ($desc === '') {
            $desc = self::siteDescription($branding);
        }

        return [
            'title' => AppSettings::formatSeoTitle($title),
            'description' => $desc,
            'url' => $baseUrl . $path,
            'type' => 'website',
            'site_name' => trim((string) ($branding->app_name ?? 'Simple CMS')),
            'image' => $fallback['url'],
            'image_width' => $fallback['width'],
            'image_height' => $fallback['height'],
            'image_alt' => $fallback['alt'],
            'image_type' => $fallback['type'],
            'published_time' => null,
        ];
    }

    /** @return array<string, mixed> Site homepage / fallback share meta. */
    public static function forSite(?object $branding = null): array
    {
        $branding = $branding ?? AppSettings::getBrandingConfig();
        $baseUrl = self::baseUrl();
        $fallback = self::fallbackImage($branding);
        $appName = trim((string) ($branding->app_name ?? 'Simple CMS'));

        return [
            'title' => AppSettings::formatSeoTitle($appName),
            'description' => self::siteDescription($branding),
            'url' => $baseUrl . '/',
            'type' => 'website',
            'site_name' => $appName,
            'image' => $fallback['url'],
            'image_width' => $fallback['width'],
            'image_height' => $fallback['height'],
            'image_alt' => $fallback['alt'],
            'image_type' => $fallback['type'],
            'published_time' => null,
        ];
    }

    public static function baseUrl(): string
    {
        $base = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
        if ($base !== '') {
            return $base;
        }
        if (PHP_SAPI !== 'cli' && !empty($_SERVER['HTTP_HOST'])) {
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            return $scheme . '://' . $_SERVER['HTTP_HOST'];
        }
        return '';
    }

    /** @return array{url: string, width: ?int, height: ?int, alt: string, type: string} */
    public static function imageFromPost(object $post, ?object $branding = null): array
    {
        $mediaId = (int) ($post->featured_image_id ?? 0);
        if ($mediaId > 0 && Media::isImageMime((string) ($post->featured_mime_type ?? ''))) {
            return [
                'url' => Media::publicShareUrl($mediaId),
                'width' => !empty($post->featured_width) ? (int) $post->featured_width : null,
                'height' => !empty($post->featured_height) ? (int) $post->featured_height : null,
                'alt' => trim((string) ($post->featured_alt_text ?? $post->title ?? '')),
                'type' => (string) ($post->featured_mime_type ?? 'image/jpeg'),
            ];
        }
        return self::fallbackImage($branding);
    }

    /** @return array{url: string, width: ?int, height: ?int, alt: string, type: string} */
    public static function fallbackImage(?object $branding = null): array
    {
        $branding = $branding ?? AppSettings::getBrandingConfig();
        $seo = AppSettings::getSiteSeoConfig();
        $mediaId = (int) ($seo->default_image_id ?? 0);
        if ($mediaId > 0) {
            $media = Media::find($mediaId);
            if ($media && Media::isImageMime((string) ($media->mime_type ?? ''))) {
                return [
                    'url' => Media::publicShareUrl($mediaId),
                    'width' => !empty($media->width) ? (int) $media->width : null,
                    'height' => !empty($media->height) ? (int) $media->height : null,
                    'alt' => trim((string) ($media->alt_text ?? $branding->app_name ?? 'Simple CMS')),
                    'type' => (string) ($media->mime_type ?? 'image/jpeg'),
                ];
            }
        }
        $baseUrl = self::baseUrl();
        $logoPath = $branding->logo_path ?? '';
        if ($logoPath !== '' && $baseUrl !== '') {
            return [
                'url' => $baseUrl . '/serve/app-logo',
                'width' => null,
                'height' => null,
                'alt' => trim((string) ($branding->app_name ?? 'Simple CMS')),
                'type' => 'image/png',
            ];
        }
        return [
            'url' => '',
            'width' => null,
            'height' => null,
            'alt' => '',
            'type' => '',
        ];
    }

    public static function siteDescription(?object $branding = null): string
    {
        $desc = AppSettings::defaultSeoDescription();
        if ($desc !== '') {
            return $desc;
        }
        $seo = AppSettings::getSiteSeoConfig();
        $llm = trim((string) ($seo->llm_site_summary ?? ''));
        if ($llm !== '') {
            return mb_strlen($llm) > 300 ? mb_substr($llm, 0, 297) . '…' : $llm;
        }
        $branding = $branding ?? AppSettings::getBrandingConfig();
        return trim((string) ($branding->app_name ?? 'Simple CMS'));
    }

    public static function descriptionFromText(string $excerpt, string $body): string
    {
        $text = trim($excerpt);
        if ($text === '') {
            $text = trim(strip_tags($body));
        }
        $text = preg_replace('/\s+/u', ' ', $text) ?? '';
        if (mb_strlen($text) > 300) {
            $text = mb_substr($text, 0, 297) . '…';
        }
        return $text;
    }
}
