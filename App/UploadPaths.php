<?php
namespace App;

/**
 * Filesystem paths for user uploads under public/uploads (or uploads_path in config/app.php).
 */
class UploadPaths
{
    public static function root(): string
    {
        return defined('UPLOADS_ROOT') ? UPLOADS_ROOT : ROOT . '/public/uploads';
    }

    public static function structure(): string
    {
        return self::root() . '/structure';
    }

    public static function profile(): string
    {
        return self::root() . '/profile';
    }

    public static function profileAttachments(): string
    {
        return self::root() . '/profile/attachments';
    }

    public static function grievanceStatus(): string
    {
        return self::root() . '/grievance/status';
    }

    public static function grievanceAttachments(): string
    {
        return self::root() . '/grievance/attachments';
    }

    public static function socioEconomic(): string
    {
        return self::root() . '/socio-economic';
    }
}
