<?php
// ------------------------------------------------------------
// Database connection (PDO, MySQL)
// Update these 4 values for your hosting environment.
// ------------------------------------------------------------
define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_NAME', getenv('DB_NAME') ?: 'taskvel_php');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');

// Base URL used in invite/reset-password emails (no trailing slash).
// Prefers the APP_URL environment variable if it's set (recommended for
// CLI/cron contexts like cron/send_reminders.php, where there's no HTTP
// request to detect a host from). Otherwise auto-detects the scheme + host
// from the current request, so links are correct on production even if
// APP_URL was never configured on the server.
function detect_app_url(): string
{
    $envUrl = getenv('APP_URL');
    if ($envUrl) return rtrim($envUrl, '/');

    if (!empty($_SERVER['HTTP_HOST'])) {
        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
            || (($_SERVER['SERVER_PORT'] ?? '') == 443);
        $scheme = $isHttps ? 'https' : 'http';
        return $scheme . '://' . $_SERVER['HTTP_HOST'];
    }

    // Last-resort fallback for CLI contexts (e.g. cron) with no APP_URL set.
    // Set the APP_URL environment variable on your server/cron job to avoid this.
    return 'http://localhost/taskvel-php';
}
define('APP_URL', detect_app_url());

// SMTP settings for invite emails (leave SMTP_HOST empty to fall back to PHP mail())
define('SMTP_HOST', getenv('SMTP_HOST') ?: 'smtp.gmail.com');
define('SMTP_PORT', getenv('SMTP_PORT') ?: 587);
define('SMTP_USER', getenv('SMTP_USER') ?: 'minal.viprak@gmail.com');
define('SMTP_PASS', getenv('SMTP_PASS') ?: 'fabfkythogdyzvus');
// define('SMTP_FROM', getenv('SMTP_FROM') ?: 'noreply@taskvel.app');
// define('SMTP_FROM_NAME', 'Taskvel');
// define('SMTP_HOST', getenv('SMTP_HOST') ?: 'smtp.gmail.com');
// define('SMTP_PORT', getenv('SMTP_PORT') ?: 587);

// define('SMTP_HOST', getenv('SMTP_HOST') ?: 'smtp.hostinger.com');
// define('SMTP_PORT', getenv('SMTP_PORT') ?: 465);
// define('SMTP_USER', getenv('SMTP_USER') ?: 'info@samalconsultancy.com');
// define('SMTP_PASS', getenv('SMTP_PASS') ?: 'SAmal@2026in');
define('SMTP_FROM', getenv('SMTP_FROM') ?: 'noreply@samalconsultancy.com');
define('SMTP_FROM_NAME', 'Samal Consultancy');

function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }
    return $pdo;
}
// // ------------------------------------------------------------
// // Database connection (PDO, MySQL)
// // Update these 4 values for your hosting environment.
// // ------------------------------------------------------------
// define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
// define('DB_NAME', getenv('DB_NAME') ?: 'taskvel_php');
// define('DB_USER', getenv('DB_USER') ?: 'root');
// define('DB_PASS', getenv('DB_PASS') ?: '');

// // Base URL used in invite emails (no trailing slash)
// define('APP_URL', getenv('APP_URL') ?: 'http://localhost/taskvel-php');

// // SMTP settings for invite emails (leave SMTP_HOST empty to fall back to PHP mail())
// define('SMTP_HOST', getenv('SMTP_HOST') ?: 'smtp.gmail.com');
// define('SMTP_PORT', getenv('SMTP_PORT') ?: 587);
// define('SMTP_USER', getenv('SMTP_USER') ?: 'minal.viprak@gmail.com');
// define('SMTP_PASS', getenv('SMTP_PASS') ?: 'fabfkythogdyzvus');
// define('SMTP_FROM', getenv('SMTP_FROM') ?: 'noreply@taskvel.app');
// define('SMTP_FROM_NAME', 'Taskvel');

// function db(): PDO
// {
//     static $pdo = null;
//     if ($pdo === null) {
//         $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
//         $pdo = new PDO($dsn, DB_USER, DB_PASS, [
//             PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
//             PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
//             PDO::ATTR_EMULATE_PREPARES   => false,
//         ]);
//     }
//     return $pdo;
// }
