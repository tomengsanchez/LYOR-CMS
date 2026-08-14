<?php
namespace App;

/** URL prefix for the admin panel. */
class AdminPath
{
    public const PREFIX = '/admin';

    public static function url(string $path = ''): string
    {
        $path = ltrim($path, '/');
        return $path === '' ? self::PREFIX : self::PREFIX . '/' . $path;
    }
}
