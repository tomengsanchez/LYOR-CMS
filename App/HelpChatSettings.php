<?php
namespace App;

/** Help chat removed in Simple CMS. */
class HelpChatSettings
{
    public static function isEnabled(): bool
    {
        return false;
    }

    /** @return array<string, mixed> */
    public static function get(): array
    {
        return ['enabled' => false];
    }

    /** @param array<string, mixed> $data */
    public static function save(array $data): void
    {
    }
}
