<?php
namespace App;

/** Development tools removed in Simple CMS. */
class DevelopmentSettings
{
    public static function isStatusCheckEnabled(): bool
    {
        return false;
    }

    /** @return array<string, mixed> */
    public static function get(): array
    {
        return [];
    }
}
