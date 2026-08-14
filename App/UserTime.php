<?php
namespace App;

use DateTimeImmutable;
use DateTimeZone;

/**
 * App timezone for PHP date functions and shared datetime formatting.
 *
 * System timestamps (audit_log.created_at, status log created_at, notifications):
 *   format with kind "system" after MySQL session time_zone is aligned.
 * Business datetimes (effective_at, date_recorded):
 *   format with kind "business" — wall-clock pass-through, no UTC conversion.
 */
class UserTime
{
    public const KIND_SYSTEM = 'system';
    public const KIND_BUSINESS = 'business';

    private static bool $applied = false;

    /** Apply org timezone to PHP date_* (works for web and CLI; no Auth required). */
    public static function applyForCurrentUser(): void
    {
        self::apply();
    }

    /**
     * @param bool $force Re-apply after System → General timezone change in the same request
     */
    public static function apply(bool $force = false): void
    {
        if (self::$applied && !$force) {
            return;
        }
        $tz = self::timezoneId();
        if ($tz !== '' && in_array($tz, timezone_identifiers_list(), true)) {
            date_default_timezone_set($tz);
        }
        self::$applied = true;
    }

    /** IANA timezone id from System → General (org), else DEFAULT. */
    public static function timezoneId(): string
    {
        return GeneralSettings::timezoneId();
    }

    /**
     * MySQL/MariaDB session offset for SET time_zone (portable; no zone tables required).
     * Example: +08:00
     */
    public static function mysqlOffset(): string
    {
        try {
            $tz = new DateTimeZone(self::timezoneId());
            $dt = new DateTimeImmutable('now', $tz);

            return $dt->format('P');
        } catch (\Exception $e) {
            return '+00:00';
        }
    }

    /**
     * Format a datetime string for display.
     *
     * @param string $kind self::KIND_SYSTEM|self::KIND_BUSINESS
     *   Both kinds format as naive wall-clock in the app timezone (no offset math).
     *   The flag exists so call sites stay column-aware and never UTC-convert business fields.
     */
    public static function format(?string $value, string $kind = self::KIND_SYSTEM, string $pattern = 'Y-m-d H:i:s'): string
    {
        if ($value === null) {
            return '';
        }
        $trimmed = trim($value);
        if ($trimmed === '') {
            return '';
        }

        // Explicit unused kind check keeps API honest for callers / static analysis.
        if ($kind !== self::KIND_SYSTEM && $kind !== self::KIND_BUSINESS) {
            $kind = self::KIND_SYSTEM;
        }

        self::apply();

        try {
            $tz = new DateTimeZone(self::timezoneId());
            // Treat stored value as wall-clock in app TZ — do not assume UTC.
            $dt = new DateTimeImmutable($trimmed, $tz);

            return $dt->format($pattern);
        } catch (\Exception $e) {
            $t = strtotime($trimmed);

            return $t ? date($pattern, $t) : $trimmed;
        }
    }

    public static function formatSystem(?string $value, string $pattern = 'Y-m-d H:i:s'): string
    {
        return self::format($value, self::KIND_SYSTEM, $pattern);
    }

    public static function formatBusiness(?string $value, string $pattern = 'Y-m-d H:i:s'): string
    {
        return self::format($value, self::KIND_BUSINESS, $pattern);
    }

    /** Current wall-clock in app timezone (for explicit audit writes). */
    public static function nowSql(): string
    {
        self::apply();

        return date('Y-m-d H:i:s');
    }
}
