<?php
namespace App;

use Core\Auth;
use Core\Database;
use App\Models\AppSettings;

/**
 * Organization general preferences (region, timezone) for System → General.
 * Stored in app_settings (org-wide). Legacy per-user rows in user_dashboard_config
 * are read only as a fallback when org keys are unset.
 */
class GeneralSettings
{
    public const MODULE = 'general';

    public const DEFAULT_REGION = '';
    public const DEFAULT_TIMEZONE = 'UTC';

    /** @return array{region: string, timezone: string} */
    public static function get(): array
    {
        $out = ['region' => self::DEFAULT_REGION, 'timezone' => self::DEFAULT_TIMEZONE];

        try {
            $orgTz = AppSettings::get('timezone', null);
            $orgRegion = AppSettings::get('region', null);
            $legacy = null;
            $needLegacy = !(is_string($orgTz) && $orgTz !== '' && in_array($orgTz, timezone_identifiers_list(), true))
                || $orgRegion === null;
            if ($needLegacy) {
                $legacy = self::legacyUserConfig();
            }
            if (is_string($orgTz) && $orgTz !== '' && in_array($orgTz, timezone_identifiers_list(), true)) {
                $out['timezone'] = $orgTz;
            } elseif ($legacy !== null && isset($legacy['timezone']) && (string) $legacy['timezone'] !== '') {
                $candidate = (string) $legacy['timezone'];
                if (in_array($candidate, timezone_identifiers_list(), true)) {
                    $out['timezone'] = $candidate;
                }
            }
            if (is_string($orgRegion)) {
                $out['region'] = $orgRegion;
            } elseif ($legacy !== null && isset($legacy['region'])) {
                $out['region'] = (string) $legacy['region'];
            }
        } catch (\Throwable $e) {
            // Fresh install / DB unavailable — keep defaults
        }

        return $out;
    }

    /** Resolved IANA timezone for PHP + MySQL session. */
    public static function timezoneId(): string
    {
        $tz = self::get()['timezone'] ?? self::DEFAULT_TIMEZONE;
        if ($tz === '' || !in_array($tz, timezone_identifiers_list(), true)) {
            return self::DEFAULT_TIMEZONE;
        }

        return $tz;
    }

    public static function save(array $config): void
    {
        $region = trim((string) ($config['region'] ?? ''));
        $timezone = trim((string) ($config['timezone'] ?? self::DEFAULT_TIMEZONE));
        if ($timezone === '' || !in_array($timezone, timezone_identifiers_list(), true)) {
            $timezone = self::DEFAULT_TIMEZONE;
        }
        AppSettings::set('region', $region);
        AppSettings::set('timezone', $timezone);
    }

    /** @return array{region?: string, timezone?: string}|null */
    private static function legacyUserConfig(): ?array
    {
        $userId = Auth::id();
        if (!$userId) {
            return null;
        }
        $db = Database::getInstance();
        $stmt = $db->prepare('SELECT config FROM user_dashboard_config WHERE user_id = ? AND module = ?');
        $stmt->execute([$userId, self::MODULE]);
        $row = $stmt->fetch(\PDO::FETCH_OBJ);
        if (!$row || $row->config === null || $row->config === '') {
            return null;
        }
        $decoded = json_decode($row->config, true);

        return is_array($decoded) ? $decoded : null;
    }

    /** Regions for dropdown (value => label). */
    public static function regions(): array
    {
        return [
            '' => '— Select region —',
            'asia_pacific' => 'Asia Pacific',
            'south_asia'   => 'South Asia',
            'east_asia'    => 'East Asia',
            'europe'       => 'Europe',
            'americas'     => 'Americas',
            'africa'       => 'Africa',
            'middle_east'  => 'Middle East',
        ];
    }

    /** All valid PHP timezones for dropdown. */
    public static function timezones(): array
    {
        return timezone_identifiers_list();
    }
}
