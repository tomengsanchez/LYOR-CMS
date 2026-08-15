<?php
namespace Core;

/**
 * Sends baseline HTTP security headers for browser responses.
 * CSP allows first-party assets plus known CDNs used by the layout (jsDelivr, jQuery CDN).
 * frame-src also allows Google Maps embeds used by the Structure GPS dialog.
 * img-src includes https: so Media “Register URL” / layout hotlinks (Unsplash, etc.) can render.
 */
class SecurityHeaders
{
    public static function apply(array $appConfig = []): void
    {
        if (PHP_SAPI === 'cli' || headers_sent()) {
            return;
        }

        $cfg = is_array($appConfig['security_headers'] ?? null) ? $appConfig['security_headers'] : [];
        if (array_key_exists('enabled', $cfg) && empty($cfg['enabled'])) {
            return;
        }

        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: geolocation=(), microphone=(), camera=()');

        $csp = trim((string) ($cfg['csp'] ?? ''));
        if ($csp === '') {
            $csp = "default-src 'self'; "
                . "base-uri 'self'; "
                . "form-action 'self'; "
                . "frame-ancestors 'self'; "
                . "frame-src 'self' https://maps.google.com https://www.google.com; "
                . "img-src 'self' data: blob: https:; "
                . "font-src 'self' data: https://cdn.jsdelivr.net; "
                . "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; "
                . "script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://code.jquery.com; "
                . "connect-src 'self'";
        }
        header('Content-Security-Policy: ' . $csp);

        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || ((int) ($_SERVER['SERVER_PORT'] ?? 0) === 443);
        if ($https && !empty($cfg['hsts'] ?? true)) {
            $maxAge = max(0, (int) ($cfg['hsts_max_age'] ?? 31536000));
            header('Strict-Transport-Security: max-age=' . $maxAge . '; includeSubDomains');
        }
    }
}
