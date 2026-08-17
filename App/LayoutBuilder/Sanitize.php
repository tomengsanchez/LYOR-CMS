<?php
namespace App\LayoutBuilder;

/**
 * LayoutBuilder implementation: Sanitize.
 */
trait Sanitize
{
    private static function id(mixed $existing): string
    {
        $existing = is_string($existing) ? trim($existing) : '';
        if ($existing !== '' && preg_match('/^[a-zA-Z0-9_\-]{4,40}$/', $existing)) {
            return $existing;
        }
        try {
            return 'el_' . bin2hex(random_bytes(6));
        } catch (\Throwable $e) {
            return 'el_' . uniqid();
        }
    }

    private static function safeUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '' || $url === '#') {
            return $url === '#' ? '#' : '';
        }
        if (str_starts_with($url, '/') || str_starts_with($url, '#')) {
            return mb_substr($url, 0, 500);
        }
        if (preg_match('#^https?://#i', $url) && filter_var($url, FILTER_VALIDATE_URL)) {
            return mb_substr($url, 0, 500);
        }
        return '';
    }

    /** Background-image URLs: no quotes/parentheses that could break out of url("…"). */
    private static function safeCssUrl(string $url): string
    {
        $url = self::safeUrl($url);
        if ($url === '' || $url === '#') {
            return '';
        }
        if (str_contains($url, '..') || preg_match('/[\\\\\'"()<>\\s]/', $url)) {
            return '';
        }
        return $url;
    }

    private static function safeOpacity(mixed $value): string
    {
        if ($value === '' || $value === null) {
            return '';
        }
        if (!is_numeric($value)) {
            return '';
        }
        $n = (int) $value;
        if ($n < 0) {
            $n = 0;
        }
        if ($n > 80) {
            $n = 80;
        }
        return (string) $n;
    }

    private static function safeColor(string $color): string
    {
        $color = trim($color);
        if ($color === '') {
            return '';
        }
        $token = strtolower($color);
        if (isset(self::COLOR_TOKENS[$token])) {
            return $token;
        }
        if (preg_match('/^var\(\s*(--pub-[a-z-]+)\s*\)$/i', $color, $m)) {
            $var = strtolower($m[1]);
            foreach (self::COLOR_TOKENS as $key => $cssVar) {
                if ($cssVar === $var) {
                    return $key;
                }
            }
            return '';
        }
        if (preg_match('/^#[0-9a-fA-F]{3,8}$/', $color)) {
            return $color;
        }
        return '';
    }

    /** Stored token or hex → CSS color for the stylesheet. */
    private static function colorCssValue(string $stored): string
    {
        $stored = trim($stored);
        if ($stored === '') {
            return '';
        }
        if (isset(self::COLOR_TOKENS[$stored])) {
            return 'var(' . self::COLOR_TOKENS[$stored] . ')';
        }
        if (preg_match('/^#[0-9a-fA-F]{3,8}$/', $stored)) {
            return $stored;
        }
        return '';
    }

    private static function safeSpacing(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }
        // e.g. 1rem, 16px, 1.5rem 2rem
        if (preg_match('/^(\d+(\.\d+)?(px|rem|em|%)\s*){1,4}$/', $value)) {
            return mb_substr($value, 0, 40);
        }
        return '';
    }

    private static function safeFontSize(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }
        if (preg_match('/^\d+(\.\d+)?(px|rem|em)$/', $value)) {
            return $value;
        }
        return '';
    }

    private static function safeHeight(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }
        if (preg_match('/^\d+(\.\d+)?(px|rem|em|%|vh|vw)$/', $value)) {
            return mb_substr($value, 0, 20);
        }
        return '';
    }

    private static function safeRadius(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }
        if (preg_match('/^(\d+(\.\d+)?(px|rem|em|%)\s*){1,4}$/', $value)) {
            return mb_substr($value, 0, 40);
        }
        return '';
    }

    private static function safeBorderStyle(string $value): string
    {
        $value = strtolower(trim($value));
        return in_array($value, ['solid', 'dashed', 'dotted', 'double', 'none'], true) ? $value : '';
    }

    private static function safeShadowKey(string $value): string
    {
        $value = strtolower(trim($value));
        return isset(self::BOX_SHADOWS[$value]) ? $value : '';
    }

    private static function safeFontWeight(string $value): string
    {
        $value = trim($value);
        return in_array($value, ['400', '500', '600', '700'], true) ? $value : '';
    }

    private static function safeLineHeight(string $value): string
    {
        $value = trim($value);
        if (!preg_match('/^\d(\.\d{1,2})?$/', $value)) {
            return '';
        }
        $n = (float) $value;
        if ($n < 1 || $n > 2.5) {
            return '';
        }
        return $value;
    }

    /** Allow safe subset of HTML for custom HTML modules. */
    public static function sanitizeHtml(string $html): string
    {
        $html = preg_replace('#<(script|iframe|object|embed|form|input|button)[^>]*>.*?</\1>#is', '', $html) ?? '';
        $html = preg_replace('#<(script|iframe|object|embed|form|input|button)[^>]*/?>#is', '', $html) ?? '';
        $html = preg_replace('/\son\w+\s*=\s*("|\').*?\1/i', '', $html) ?? '';
        $html = preg_replace('/\s(href|src)\s*=\s*("|\')\s*javascript:[^"\']*\2/i', '', $html) ?? '';
        return $html;
    }
}
