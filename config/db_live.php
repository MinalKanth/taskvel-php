<?php




 
// ------------------------------------------------------------
// Load .env into getenv()/$_ENV before any config constant reads it.
// Every config/*.php file in this app calls getenv(), so this must run
// first — db.php is required earliest by nearly every entry point,
// which is why the loader lives here rather than in each file.
// ------------------------------------------------------------
(function () {
    static $loaded = false;
    if ($loaded) return;
    $loaded = true;
    $envFile = __DIR__ . '/../.env';
    if (!is_file($envFile)) return;
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) continue;
        if (!str_contains($line, '=')) continue;
        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        // Strip matching surrounding quotes, e.g. KEY="value with spaces"
        if (strlen($value) >= 2 && (($value[0] === '"' && str_ends_with($value, '"')) || ($value[0] === "'" && str_ends_with($value, "'")))) {
            $value = substr($value, 1, -1);
        }
        if ($key === '') continue;
        putenv("$key=$value");
        $_ENV[$key] = $value;
    }
})();


// ------------------------------------------------------------
// Database connection (PDO, MySQL)
// Update these 4 values for your hosting environment.
// ------------------------------------------------------------
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'insoftsolutions_samal');
define('DB_USER', getenv('DB_USER') ?: 'insoftsolutions_samal');
define('DB_PASS', getenv('DB_PASS') ?: 'SAmal@2026in');

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


// ==========================================================
// SMTP CONFIGURATION — Namecheap cPanel / PHPMailer
// ==========================================================

define('SMTP_HOST', getenv('SMTP_HOST') ?: 'premium901.web-hosting.com');
define('SMTP_PORT', (int)(getenv('SMTP_PORT') ?: 465));

define('SMTP_USER', getenv('SMTP_USER') ?: 'info@samalconsultancy.com');
define('SMTP_PASS', getenv('SMTP_PASS') ?: 'SAmal@2026in');

define('SMTP_FROM', getenv('SMTP_FROM') ?: 'info@samalconsultancy.com');
define('SMTP_FROM_NAME', getenv('SMTP_FROM_NAME') ?: 'Samal Consultancy');

// 465 = SSL / SMTPS
// 587 = TLS / STARTTLS
define('SMTP_SECURE', strtolower(getenv('SMTP_SECURE') ?: 'ssl'));


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