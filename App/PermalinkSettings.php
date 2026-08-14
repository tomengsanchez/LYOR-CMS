<?php
namespace App;

use App\Models\AppSettings;

class PermalinkSettings
{
    public const PAGE_DEFAULT = 'p/%pagename%';
    public const PAGE_PLAIN = '%pagename%';
    public const POST_DEFAULT = 'blog/%postname%';
    public const POST_PLAIN = '%postname%';
    public const POST_YEAR_MONTH = '%year%/%monthnum%/%postname%';
    public const POST_CATEGORY = '%category%/%postname%';

    /** @return array<string, string> */
    public static function pageStructures(): array
    {
        return [
            self::PAGE_DEFAULT => 'Default (/p/sample-page)',
            self::PAGE_PLAIN   => 'Plain (/sample-page)',
        ];
    }

    /** @return array<string, string> */
    public static function postStructures(): array
    {
        return [
            self::POST_DEFAULT     => 'Default (/blog/post-name)',
            self::POST_PLAIN       => 'Plain (/post-name)',
            self::POST_YEAR_MONTH  => 'Year/Month (/2026/08/post-name)',
            self::POST_CATEGORY    => 'Category (/category/post-name)',
        ];
    }

    public static function get(): object
    {
        $page = trim(AppSettings::get('permalink_page_structure', self::PAGE_DEFAULT));
        $post = trim(AppSettings::get('permalink_post_structure', self::POST_DEFAULT));
        if (!array_key_exists($page, self::pageStructures())) {
            $page = self::PAGE_DEFAULT;
        }
        if (!array_key_exists($post, self::postStructures())) {
            $post = self::POST_DEFAULT;
        }
        return (object) [
            'page_structure' => $page,
            'post_structure' => $post,
        ];
    }

    /** @param array<string, mixed> $data */
    public static function save(array $data): void
    {
        $page = (string) ($data['permalink_page_structure'] ?? self::PAGE_DEFAULT);
        $post = (string) ($data['permalink_post_structure'] ?? self::POST_DEFAULT);
        if (!array_key_exists($page, self::pageStructures())) {
            $page = self::PAGE_DEFAULT;
        }
        if (!array_key_exists($post, self::postStructures())) {
            $post = self::POST_DEFAULT;
        }
        AppSettings::set('permalink_page_structure', $page);
        AppSettings::set('permalink_post_structure', $post);
    }

    public static function usesDefaultPageRoute(): bool
    {
        return self::get()->page_structure === self::PAGE_DEFAULT;
    }

    public static function usesDefaultPostRoute(): bool
    {
        return self::get()->post_structure === self::POST_DEFAULT;
    }
}
