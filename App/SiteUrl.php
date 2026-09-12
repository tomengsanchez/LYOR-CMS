<?php
namespace App;

/**
 * Prefix a site path with config/app.php `base_url` (BASE_URL).
 *
 * Empty base → `/blog/slug` (host-portable).
 * Path prefix `/paper` → `/paper/blog/slug`.
 * Absolute origin `http://cms.local` → `http://cms.local/blog/slug`.
 */
final class SiteUrl
{
    public static function base(?string $override = null): string
    {
        if ($override !== null) {
            return rtrim($override, '/');
        }

        return defined('BASE_URL') ? rtrim((string) BASE_URL, '/') : '';
    }

    /** Path prefix only (`/paper` or empty). Absolute origins contribute their URL path. */
    public static function pathPrefix(?string $override = null): string
    {
        $base = self::base($override);
        if ($base === '') {
            return '';
        }
        if (str_starts_with($base, '/')) {
            return $base;
        }
        if (preg_match('#^https?://[^/]+(/.*)?$#i', $base, $m)) {
            return rtrim((string) ($m[1] ?? ''), '/');
        }

        return '';
    }

    public static function href(string $path, ?string $base = null): string
    {
        $path = '/' . ltrim($path, '/');
        $root = self::base($base);
        if ($root === '') {
            return $path;
        }
        if (str_starts_with($root, '/')) {
            return $root . $path;
        }

        return $root . $path;
    }
}
