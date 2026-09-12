<?php
namespace App;

use App\Models\AppSettings;

/**
 * Google Analytics, Ads/AdSense, Search Console, and Programmable Search (System → General).
 * Stores IDs only — never raw script snippets.
 */
class GoogleSettings
{
    public static function get(): object
    {
        $seo = AppSettings::getSiteSeoConfig();

        return (object) [
            'analytics_id' => self::normalizeAnalyticsId(AppSettings::get('google_analytics_id', '')),
            'ads_id' => self::normalizeAdsId(AppSettings::get('google_ads_id', '')),
            'adsense_client' => self::normalizeAdsenseClient(AppSettings::get('google_adsense_client', '')),
            'cse_cx' => self::normalizeCseCx(AppSettings::get('google_cse_cx', '')),
            'site_verification' => trim((string) ($seo->google_site_verification ?? '')),
        ];
    }

    /** @param array<string, mixed> $data */
    public static function save(array $data): void
    {
        AppSettings::set('google_analytics_id', self::normalizeAnalyticsId((string) ($data['google_analytics_id'] ?? '')));
        AppSettings::set('google_ads_id', self::normalizeAdsId((string) ($data['google_ads_id'] ?? '')));
        AppSettings::set('google_adsense_client', self::normalizeAdsenseClient((string) ($data['google_adsense_client'] ?? '')));
        AppSettings::set('google_cse_cx', self::normalizeCseCx((string) ($data['google_cse_cx'] ?? '')));
    }

    public static function normalizeAnalyticsId(string $raw): string
    {
        $raw = strtoupper(preg_replace('/\s+/', '', trim($raw)) ?? '');
        if (preg_match('/^G-[A-Z0-9]{4,20}$/', $raw)) {
            return $raw;
        }
        if (preg_match('/^UA-\d{4,10}-\d{1,4}$/', $raw)) {
            return $raw;
        }

        return '';
    }

    public static function normalizeAdsId(string $raw): string
    {
        $raw = strtoupper(preg_replace('/\s+/', '', trim($raw)) ?? '');
        if (preg_match('/^AW-\d{5,20}$/', $raw)) {
            return $raw;
        }

        return '';
    }

    public static function normalizeAdsenseClient(string $raw): string
    {
        $raw = strtolower(preg_replace('/\s+/', '', trim($raw)) ?? '');
        if (preg_match('/^ca-pub-\d{10,20}$/', $raw)) {
            return $raw;
        }

        return '';
    }

    public static function normalizeCseCx(string $raw): string
    {
        $raw = trim($raw);
        if (preg_match('/^[a-zA-Z0-9_.:-]{8,80}$/', $raw)) {
            return $raw;
        }

        return '';
    }

    public static function hasPublicTags(?object $cfg = null): bool
    {
        $cfg = $cfg ?? self::get();

        return ($cfg->analytics_id ?? '') !== ''
            || ($cfg->ads_id ?? '') !== ''
            || ($cfg->adsense_client ?? '') !== '';
    }

    public static function shouldInjectOnPublic(): bool
    {
        if (PublicTheme::isPreviewActive() || PublicTheme::isCustomizerFrame()) {
            return false;
        }

        return self::hasPublicTags();
    }

    public static function gtagLoaderId(?object $cfg = null): string
    {
        $cfg = $cfg ?? self::get();
        if (($cfg->analytics_id ?? '') !== '') {
            return (string) $cfg->analytics_id;
        }

        return (string) ($cfg->ads_id ?? '');
    }
}
