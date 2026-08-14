<?php
namespace Core;

class Helpers
{
    /** Escape for HTML context (prevents XSS). */
    public static function e(?string $s): string
    {
        return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
    }
}
