<?php
define('ROOT', __DIR__);

require_once ROOT . '/Core/SystemDebug.php';
\Core\SystemDebug::startRequest();
spl_autoload_register(function ($class) {
    \Core\SystemDebug::logClass($class);
    return false;
}, true, true);

spl_autoload_register(function ($class) {
    $file = ROOT . '/' . str_replace('\\', '/', $class) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

$vendorAutoload = ROOT . '/vendor/autoload.php';
if (is_file($vendorAutoload)) {
    require_once $vendorAutoload;
}

$logDir = ROOT . '/logs';
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}
ini_set('log_errors', 1);
ini_set('error_log', $logDir . '/php_error.log');

\Core\Logger::init();
\Core\Database::init(require ROOT . '/config/database.php');

$appConfig = file_exists(ROOT . '/config/app.php') ? require ROOT . '/config/app.php' : [];
define('BASE_URL', rtrim($appConfig['base_url'] ?? '', '/'));
$uploadsPath = trim((string) ($appConfig['uploads_path'] ?? ''));
define('UPLOADS_ROOT', $uploadsPath !== '' ? rtrim($uploadsPath, '/\\') : ROOT . '/public/uploads');
$trustedProxies = [];
if (is_array($appConfig['trusted_proxies'] ?? null)) {
    foreach ($appConfig['trusted_proxies'] as $ip) {
        $ip = trim((string) $ip);
        if ($ip !== '' && filter_var($ip, FILTER_VALIDATE_IP)) {
            $trustedProxies[] = $ip;
        }
    }
}
define('TRUSTED_PROXIES', $trustedProxies);

\Core\Auth::init();
\App\UserTime::apply();
\Core\SecurityHeaders::apply(is_array($appConfig) ? $appConfig : []);

if (!function_exists('admin_url')) {
    function admin_url(string $path = ''): string
    {
        return \App\AdminPath::url($path);
    }
}
