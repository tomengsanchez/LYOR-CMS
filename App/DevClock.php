<?php
namespace App;

/** Development clock removed in Simple CMS. */
class DevClock
{
    public static function isOverridden(): bool
    {
        return false;
    }

    public static function getOverride(): ?string
    {
        return null;
    }
}
