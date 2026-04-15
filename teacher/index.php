<?php
require dirname(__DIR__) . '/config.php';

date_default_timezone_set(APP_TIMEZONE);

$isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443);

session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => $isSecure,
    'httponly' => true,
    'samesite' => 'Lax',
]);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

ob_start();

try {
    $content = isset($_GET['c']) ? trim((string) $_GET['c']) : 'dashboard';

    require __DIR__ . '/submission.php';
    require __DIR__ . '/navigator/navigator.php';
} catch (Throwable $err) {
    error_log('Pixelwar Teacher Error: ' . $err->getMessage());
    http_response_code(500);

    if (ob_get_level() > 0) {
        ob_clean();
    }

    require dirname(__DIR__) . '/components/500.php';
    exit;
}
