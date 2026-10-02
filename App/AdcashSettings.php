<?php
namespace App;

use App\Models\AppSettings;

/**
 * AdCash ad zones (System → General).
 * Stores zone IDs only — never raw script snippets.
 * The public site loads AdCash's official aclib.js once and runs each configured zone.
 */
class AdcashSettings
{
    /** @var array<string, string> property => aclib runner method */
    private const ZONE_METHODS = [
        'autotag'       => 'runAutoTag',
        'pop'           => 'runPop',
        'interstitial'  => 'runInterstitial',
        'inpage_push'   => 'runInPagePush',
        'banner'        => 'runBanner',
        'video_slider'  => 'runVideoSlider',
    ];

    public static function get(): object
    {
        $cfg = [];
        foreach (self::ZONE_METHODS as $prop => $method) {
            $cfg[$prop] = self::normalizeZoneId(AppSettings::get('adcash_zone_' . $prop, ''));
        }

        return (object) $cfg;
    }

    /** @param array<string, mixed> $data */
    public static function save(array $data): void
    {
        foreach (self::ZONE_METHODS as $prop => $method) {
            $key = 'adcash_zone_' . $prop;
            AppSettings::set($key, self::normalizeZoneId((string) ($data[$key] ?? '')));
        }
    }

    public static function normalizeZoneId(string $raw): string
    {
        $raw = preg_replace('/\s+/', '', trim($raw)) ?? '';
        if (preg_match('/^[a-zA-Z0-9]{4,40}$/', $raw)) {
            return $raw;
        }

        return '';
    }

    public static function hasPublicTags(?object $cfg = null): bool
    {
        $cfg = $cfg ?? self::get();
        foreach (self::ZONE_METHODS as $prop => $method) {
            if ((string) ($cfg->{$prop} ?? '') !== '') {
                return true;
            }
        }

        return false;
    }

    public static function shouldInjectOnPublic(): bool
    {
        if (PublicTheme::isPreviewActive() || PublicTheme::isCustomizerFrame()) {
            return false;
        }

        return self::hasPublicTags();
    }

    /**
     * Configured zones only.
     *
     * @return array<string, string> property => zone ID
     */
    public static function activeZones(?object $cfg = null): array
    {
        $cfg = $cfg ?? self::get();
        $zones = [];
        foreach (self::ZONE_METHODS as $prop => $method) {
            $zoneId = (string) ($cfg->{$prop} ?? '');
            if ($zoneId !== '') {
                $zones[$prop] = $zoneId;
            }
        }

        return $zones;
    }
}
