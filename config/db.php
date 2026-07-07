<?php
// ------------------------------------------------------------
// Database connection (PDO, MySQL)
// Update these 4 values for your hosting environment.
// ------------------------------------------------------------
define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_NAME', getenv('DB_NAME') ?: 'taskvel_php');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');

// Base URL used in invite emails (no trailing slash)
define('APP_URL', getenv('APP_URL') ?: 'http://localhost/taskvel-php');

// SMTP settings for invite emails (leave SMTP_HOST empty to fall back to PHP mail())
define('SMTP_HOST', getenv('SMTP_HOST') ?: 'smtp.gmail.com');
define('SMTP_PORT', getenv('SMTP_PORT') ?: 587);
define('SMTP_USER', getenv('SMTP_USER') ?: 'minal.viprak@gmail.com');
define('SMTP_PASS', getenv('SMTP_PASS') ?: 'fabfkythogdyzvus');
define('SMTP_FROM', getenv('SMTP_FROM') ?: 'noreply@taskvel.app');
define('SMTP_FROM_NAME', 'Taskvel');

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
