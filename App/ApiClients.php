<?php
namespace App;

/** API client gate removed in Simple CMS. */
class ApiClients
{
    /** @return array{enabled: bool} */
    public static function getConfig(): array
    {
        return ['enabled' => false];
    }

    /** @return list<array<string, mixed>> */
    public static function listForUi(): array
    {
        return [];
    }
}
