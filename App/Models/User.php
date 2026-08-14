<?php
namespace App\Models;

class User
{
    /** True when the account may sign in (web or API). */
    public static function canLogin(?object $user): bool
    {
        if ($user === null) {
            return false;
        }

        return trim((string) ($user->password_hash ?? '')) !== '';
    }

    public static function loginAccessLabel(?object $user): string
    {
        return self::canLogin($user) ? 'Can sign in' : 'Reference only';
    }
}
