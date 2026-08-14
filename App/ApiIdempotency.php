<?php
namespace App;

/** No-op stub — idempotency removed in Simple CMS. */
class ApiIdempotency
{
    public static function readKeyFromRequest(): ?string
    {
        return null;
    }

    public static function replay(int $userId, string $scope, string $key): ?array
    {
        return null;
    }

    /** @param array<string, mixed> $envelope */
    public static function store(int $userId, string $scope, string $key, int $httpStatus, array $envelope): void
    {
    }
}
