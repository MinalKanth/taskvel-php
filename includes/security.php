<?php
/**
 * Central security bootstrap — required by includes/auth.php, so every page
 * and API endpoint in the app gets this automatically. Covers:
 *   - Security response headers (clickjacking, MIME-sniffing, CSP, etc.)
 *   - CSRF token issue/verify
 *   - Sliding-window rate limiting (brute-force protection)
 *   - A safe global error/exception handler (no leaked stack traces)
 *   - Small input-sanitization helpers used across API endpoints
 */

require_once __DIR__ . '/../config/db.php';

// ────────────────────────────────────────────────────────────
// SECURITY HEADERS — sent on every single request, API or page.
// ────────────────────────────────────────────────────────────
function send_security_headers(): void
{
    if (headers_sent()) return;

    header('X-Frame-Options: DENY');                              // clickjacking
    header('X-Content-Type-Options: nosniff');                    // MIME-sniffing
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: geolocation=(), camera=(), microphone=(), payment=()');
    header('Cross-Origin-Opener-Policy: same-origin');
    header('Cross-Origin-Resource-Policy: same-origin');

    // Content-Security-Policy: this app is a single-origin, no-framework
    // vanilla JS/CSS app with no third-party scripts, so a tight policy is
    // realistic. Google Fonts stylesheet is the one external origin used.
    $csp = "default-src 'self'; "
         . "script-src 'self' 'unsafe-inline'; "   // inline <script> blocks are used throughout by design
         . "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; "
         . "font-src 'self' https://fonts.gstatic.com data:; "
         . "img-src 'self' data:; "
         . "connect-src 'self'; "
         . "frame-ancestors 'none'; "
         . "base-uri 'self'; "
         . "form-action 'self'";
    header("Content-Security-Policy: $csp");

    // HSTS only makes sense once you're actually on HTTPS — sending it over
    // plain HTTP would be a lie the browser can't act on usefully.
    if (is_https()) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}

function is_https(): bool
{
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
        || (($_SERVER['SERVER_PORT'] ?? '') == 443);
}

function client_ip(): string
{
    // Trust X-Forwarded-For only insofar as most hosts put the real client
    // IP first; if you're behind a specific known proxy, tighten this.
    $fwd = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '';
    if ($fwd) return trim(explode(',', $fwd)[0]);
    return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
}

send_security_headers();

// ────────────────────────────────────────────────────────────
// SAFE ERROR HANDLING — never leak stack traces, queries, or file paths
// to the client. Everything gets logged server-side instead.
// ────────────────────────────────────────────────────────────
ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

set_exception_handler(function (Throwable $e): void {
    error_log('[Taskvel] Uncaught ' . get_class($e) . ': ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: application/json');
    }
    echo json_encode(['error' => 'Something went wrong. Please try again.']);
    exit;
});

set_error_handler(function (int $severity, string $message, string $file, int $line): bool {
    if (!(error_reporting() & $severity)) return false;
    error_log("[Taskvel] PHP error ($severity): $message in $file:$line");
    return true; // don't let PHP's default handler print it
});

// ────────────────────────────────────────────────────────────
// CSRF PROTECTION
// A per-session token is embedded in every page (<meta name="csrf-token">)
// and sent back as an X-CSRF-Token header by js/api-client.js on every
// state-changing request. require_csrf() is called automatically by
// require_login() for POST/PUT/DELETE — nothing else to wire up per endpoint.
// ────────────────────────────────────────────────────────────
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function require_csrf(): void
{
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    if (!in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) return;

    $sent = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    $expected = $_SESSION['csrf_token'] ?? '';
    if ($sent === '' || $expected === '' || !hash_equals($expected, $sent)) {
        audit_log(null, 'csrf_rejected', ['path' => $_SERVER['REQUEST_URI'] ?? '']);
        json_response(['error' => 'Invalid or missing CSRF token. Please refresh the page and try again.'], 403);
    }
}

// ────────────────────────────────────────────────────────────
// RATE LIMITING — sliding window backed by the rate_limits table.
// Returns true if the action is allowed, false if the caller should be
// blocked. Always call rate_limit_hit() only on the attempts you actually
// want to count (e.g. failed logins, not every page view).
// ────────────────────────────────────────────────────────────
function rate_limit_check(string $key, int $maxAttempts, int $windowSeconds): bool
{
    try {
        $pdo = db();
        $stmt = $pdo->prepare('SELECT attempts, window_start FROM rate_limits WHERE rl_key = ?');
        $stmt->execute([$key]);
        $row = $stmt->fetch();
        if (!$row) return true;

        $elapsed = time() - strtotime($row['window_start']);
        if ($elapsed > $windowSeconds) return true; // window has expired, effectively reset
        return (int)$row['attempts'] < $maxAttempts;
    } catch (Throwable $e) {
        // Fail OPEN, not closed: a missing/un-migrated rate_limits table
        // should never turn into a full login/registration outage. Losing
        // brute-force throttling temporarily is the safer trade-off vs.
        // locking every user out because of an infra/migration gap.
        error_log('[Taskvel] rate_limit_check failed (is migration_07 applied?): ' . $e->getMessage());
        return true;
    }
}

function rate_limit_hit(string $key, int $windowSeconds): void
{
    try {
        $pdo = db();
        $stmt = $pdo->prepare('SELECT attempts, window_start FROM rate_limits WHERE rl_key = ?');
        $stmt->execute([$key]);
        $row = $stmt->fetch();

        if (!$row || (time() - strtotime($row['window_start'])) > $windowSeconds) {
            $pdo->prepare('INSERT INTO rate_limits (rl_key, attempts, window_start) VALUES (?, 1, NOW())
                            ON DUPLICATE KEY UPDATE attempts = 1, window_start = NOW()')->execute([$key]);
        } else {
            $pdo->prepare('UPDATE rate_limits SET attempts = attempts + 1 WHERE rl_key = ?')->execute([$key]);
        }
    } catch (Throwable $e) {
        error_log('[Taskvel] rate_limit_hit failed (is migration_07 applied?): ' . $e->getMessage());
    }
}

function rate_limit_reset(string $key): void
{
    try {
        db()->prepare('DELETE FROM rate_limits WHERE rl_key = ?')->execute([$key]);
    } catch (Throwable $e) {
        error_log('[Taskvel] rate_limit_reset failed: ' . $e->getMessage());
    }
}

// Convenience wrapper for the common "block and respond" case.
function enforce_rate_limit(string $key, int $maxAttempts, int $windowSeconds, string $message = 'Too many attempts. Please wait and try again.'): void
{
    if (!rate_limit_check($key, $maxAttempts, $windowSeconds)) {
        json_response(['error' => $message], 429);
    }
}

// ────────────────────────────────────────────────────────────
// AUDIT LOG — auth events, permission denials, and anything else worth
// having a paper trail for during an incident.
// ────────────────────────────────────────────────────────────
function audit_log(?int $userId, string $event, array $meta = []): void
{
    try {
        db()->prepare('INSERT INTO security_audit_log (user_id, event, ip_address, user_agent, meta) VALUES (?, ?, ?, ?, ?)')
            ->execute([$userId, $event, client_ip(), substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255), json_encode($meta)]);
    } catch (Throwable $e) {
        error_log('[Taskvel] audit_log failed: ' . $e->getMessage());
    }
}

// ────────────────────────────────────────────────────────────
// INPUT HELPERS — small, focused sanitizers used across endpoints.
// These complement (never replace) parameterized queries and output
// escaping — defense in depth, not a substitute for either.
// ────────────────────────────────────────────────────────────

// Strips null bytes and control characters (except tab/newline), trims,
// and hard-caps length. Use on every free-text field before it touches
// the database.
function clean_str(?string $s, int $maxLen = 1000): string
{
    $s = (string)$s;
    $s = str_replace("\0", '', $s);
    $s = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $s); // control chars, keep \t \n
    $s = trim($s);
    if (function_exists('mb_substr')) $s = mb_substr($s, 0, $maxLen);
    else $s = substr($s, 0, $maxLen);
    return $s;
}

function clean_email(?string $s): string
{
    $s = strtolower(trim((string)$s));
    return mb_substr($s, 0, 190);
}

// Escapes a CSV cell against "formula injection" (CWE-1236) — Excel/Sheets
// will execute a cell starting with = + - @ as a formula when opened.
function csv_safe($value): string
{
    $value = (string)$value;
    if ($value !== '' && in_array($value[0], ['=', '+', '-', '@'], true)) {
        return "'" . $value;
    }
    return $value;
}

// Escapes the handful of characters RFC 5545 (iCalendar) treats specially,
// so a task title containing a comma/semicolon/backslash can't corrupt the
// generated .ics structure.
function ics_escape(string $s): string
{
    $s = str_replace(['\\', ',', ';'], ['\\\\', '\\,', '\\;'], $s);
    return str_replace(["\r\n", "\n", "\r"], '\\n', $s);
}

// Whitelists a value against a fixed set of allowed options — the standard
// defense for anything that ends up in SQL as an identifier/enum rather
// than a bound parameter (ORDER BY direction, sort column, etc.).
function one_of(string $value, array $allowed, string $default): string
{
    return in_array($value, $allowed, true) ? $value : $default;
}
