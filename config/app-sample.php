<?php

$app = [
    'base_url' => '',  // e.g. '/paper' if app is in subfolder
    // Optional absolute path to the uploads directory (the folder that contains structure/, profile/, etc.).
    // Use when the web app ROOT differs from where files are stored, e.g. multiple public_* deploy trees on one host:
    // 'uploads_path' => '/home/user/ecosysinternals/public-dev2/public/uploads',

    // Only these REMOTE_ADDR values may supply X-Forwarded-For / Client-IP for login throttling.
    // Leave empty unless PAPeR sits behind a known reverse proxy (nginx, Cloudflare, etc.).
    'trusted_proxies' => [
        // '127.0.0.1',
        // '::1',
    ],

    // Browser security headers (Core\SecurityHeaders). Set enabled => false to disable.
    'security_headers' => [
        'enabled' => true,
        'hsts' => true,
        'hsts_max_age' => 31536000,
        // Optional custom CSP string; omit to use the built-in default (self + jsDelivr/jQuery CDN + Maps + Video embeds
        // + Google Analytics / Ads / AdSense / Programmable Search hosts used by System → General).
        // Default img-src includes https: so Media “Register URL” / layout hotlinks work. If you override csp, keep that
        // (or list allowed image hosts), media-src for HTTPS video files, and frame-src for maps.google.com,
        // www.google.com, Google Ads/CSE frames, www.youtube-nocookie.com, and player.vimeo.com.
        // 'csp' => "...",
    ],

    'cors' => [
        'enabled' => true,
        'allow_all_origins' => false,  // true = any Origin (local dev only)
        'allowed_origins' => [
            'http://localhost:4200',
            'http://cms.local',
            'http://eco.local',
            'http://eco.local:4200',
        ],
        'allow_methods' => 'POST, GET, OPTIONS',
        'allow_headers' => 'Content-Type, Authorization, X-App-Id, X-App-Secret, Idempotency-Key, X-Device-Model, X-Device-OS, X-OS-Version',
        'max_age' => 86400,
    ],
];

if (PHP_SAPI !== 'cli') {
    $cors = $app['cors'] ?? [];
    if (!empty($cors['enabled'])) {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        $allowAll = !empty($cors['allow_all_origins']);
        $allowed = $cors['allowed_origins'] ?? [];
        $mayAllowOrigin = $origin !== '' && ($allowAll || in_array($origin, $allowed, true));

        if ($mayAllowOrigin) {
            header('Access-Control-Allow-Origin: ' . $origin);
            header('Vary: Origin');
        }

        header('Access-Control-Allow-Methods: ' . ($cors['allow_methods'] ?? 'POST, GET, OPTIONS'));
        header('Access-Control-Allow-Headers: ' . ($cors['allow_headers'] ?? 'Content-Type, Authorization'));
        $maxAge = (int) ($cors['max_age'] ?? 86400);
        if ($maxAge > 0) {
            header('Access-Control-Max-Age: ' . $maxAge);
        }

        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
            http_response_code($mayAllowOrigin ? 204 : 403);
            exit;
        }
    }
}

return $app;
