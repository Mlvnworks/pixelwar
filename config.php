<?php
require_once __DIR__ . '/classes/env.php';
require_once __DIR__ . '/classes/database-initializer.php';
require_once __DIR__ . '/classes/supabase-storage.php';

Env::load(__DIR__ . '/.env');

if (!defined('APP_ENV')) {
    define('APP_ENV', Env::get('APP_ENV', 'local'));
}

if (!defined('APP_DEBUG')) {
    define('APP_DEBUG', Env::getBool('APP_DEBUG', false));
}

if (!defined('APP_NAME')) {
    define('APP_NAME', Env::get('APP_NAME', 'Pixelwar'));
}

if (!defined('APP_URL')) {
    define('APP_URL', Env::get('APP_URL', 'http://localhost'));
}

if (!defined('APP_TIMEZONE')) {
    define('APP_TIMEZONE', Env::get('APP_TIMEZONE', 'Asia/Manila'));
}

if (!defined('DB_HOST')) {
    define('DB_HOST', Env::get('DB_HOST'));
}

if (!defined('DB_USER')) {
    define('DB_USER', Env::get('DB_USER'));
}

if (!defined('DB_PASS')) {
    define('DB_PASS', Env::get('DB_PASS', ''));
}

if (!defined('DB_NAME')) {
    define('DB_NAME', Env::get('DB_NAME'));
}

if (!defined('DB_PORT')) {
    define('DB_PORT', Env::getInt('DB_PORT', 3306) ?? 3306);
}

if (!defined('MAIL_HOST')) {
    define('MAIL_HOST', Env::get('MAIL_HOST'));
}

if (!defined('MAIL_PORT')) {
    define('MAIL_PORT', Env::getInt('MAIL_PORT', 587) ?? 587);
}

if (!defined('MAIL_ENCRYPTION')) {
    define('MAIL_ENCRYPTION', Env::get('MAIL_ENCRYPTION', 'tls'));
}

if (!defined('MAIL_USERNAME')) {
    define('MAIL_USERNAME', Env::get('MAIL_USERNAME'));
}

if (!defined('MAIL_PASSWORD')) {
    define('MAIL_PASSWORD', Env::get('MAIL_PASSWORD'));
}

if (!defined('MAIL_FROM_ADDRESS')) {
    define('MAIL_FROM_ADDRESS', Env::get('MAIL_FROM_ADDRESS', MAIL_USERNAME));
}

if (!defined('MAIL_FROM_NAME')) {
    define('MAIL_FROM_NAME', Env::get('MAIL_FROM_NAME', APP_NAME));
}

if (!defined('SUPABASE_URL')) {
    define('SUPABASE_URL', Env::get('SUPABASE_URL'));
}

if (!defined('SUPABASE_SERVICE_ROLE_KEY')) {
    define('SUPABASE_SERVICE_ROLE_KEY', Env::get('SUPABASE_SERVICE_ROLE_KEY'));
}

if (!defined('SUPABASE_STORAGE_BUCKET')) {
    define('SUPABASE_STORAGE_BUCKET', Env::get('SUPABASE_STORAGE_BUCKET', 'pixelwar'));
}

if (!defined('SUPABASE_STORAGE_AVATAR_FOLDER')) {
    define('SUPABASE_STORAGE_AVATAR_FOLDER', Env::get('SUPABASE_STORAGE_AVATAR_FOLDER', 'avatars'));
}

if (!defined('SUPABASE_STORAGE_CHALLENGE_HTML_FOLDER')) {
    define('SUPABASE_STORAGE_CHALLENGE_HTML_FOLDER', Env::get('SUPABASE_STORAGE_CHALLENGE_HTML_FOLDER', 'challenge-sources/html'));
}

if (!defined('SUPABASE_STORAGE_CHALLENGE_CSS_FOLDER')) {
    define('SUPABASE_STORAGE_CHALLENGE_CSS_FOLDER', Env::get('SUPABASE_STORAGE_CHALLENGE_CSS_FOLDER', 'challenge-sources/css'));
}

ini_set('display_errors', APP_DEBUG ? '1' : '0');
error_reporting(APP_DEBUG ? E_ALL : 0);

$connection = null;
$con = null;

$hasAnyDatabaseConfig = DB_HOST !== null || DB_USER !== null || DB_NAME !== null;
$hasFullDatabaseConfig = DB_HOST !== null && DB_USER !== null && DB_NAME !== null;

if ($hasAnyDatabaseConfig && !$hasFullDatabaseConfig) {
    error_log('Pixelwar database configuration is incomplete. Set DB_HOST, DB_USER, and DB_NAME.');
    http_response_code(500);
    require __DIR__ . '/components/500.php';
    exit;
}

if (!$hasFullDatabaseConfig) {
    return;
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $databaseInitializer = new DatabaseInitializer(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
    $connection = $databaseInitializer->initialize();
    $con = $connection;
} catch (Throwable $err) {
    error_log('Pixelwar database error: ' . $err->getMessage());
    http_response_code(500);
    require __DIR__ . '/components/500.php';
    exit;
}
