<?php
namespace App;

/** Live traffic removed in Simple CMS — no-op stub for auth logging hooks. */
class TrafficLog
{
    public static function startRequest(): void
    {
    }

    public static function recordAuthEvent(string $type, string $message, ?int $userId = null, ?string $ip = null): void
    {
    }

    public static function recordBlockedHit(string $ip, string $path, ?string $userAgent = null): void
    {
    }

    public static function retentionDays(): int
    {
        return 30;
    }

    public static function pruneOlderThan(int $days): int
    {
        return 0;
    }
}
