<?php
require_once __DIR__ . '/security.php';

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => is_https(),
        'path'     => '/',
    ]);
    session_start();
}

// ────────────────────────────────────────────────────────────
// SESSION HARDENING — idle timeout + absolute lifetime, and periodic ID
// rotation so a long-lived session doesn't keep the same cookie value
// forever (limits the window a stolen session ID is useful in).
// ────────────────────────────────────────────────────────────
const SESSION_IDLE_TIMEOUT = 2 * 60 * 60;      // 2 hours of inactivity
const SESSION_ABSOLUTE_LIFETIME = 12 * 60 * 60; // 12 hours max, even if active
const SESSION_ROTATE_INTERVAL = 15 * 60;        // rotate session ID every 15 min

if (!empty($_SESSION['user_id'])) {
    $now = time();
    $idleFor = $now - ($_SESSION['last_activity'] ?? $now);
    $aliveFor = $now - ($_SESSION['session_started_at'] ?? $now);

    if ($idleFor > SESSION_IDLE_TIMEOUT || $aliveFor > SESSION_ABSOLUTE_LIFETIME) {
        audit_log($_SESSION['user_id'] ?? null, 'session_expired', ['idle_for' => $idleFor, 'alive_for' => $aliveFor]);
        $_SESSION = [];
        session_destroy();
    } else {
        if (empty($_SESSION['session_started_at'])) $_SESSION['session_started_at'] = $now;
        if (($now - ($_SESSION['last_rotated_at'] ?? 0)) > SESSION_ROTATE_INTERVAL) {
            session_regenerate_id(true);
            $_SESSION['last_rotated_at'] = $now;
        }
        $_SESSION['last_activity'] = $now;
    }
}

function current_user_id(): ?int
{
    return $_SESSION['user_id'] ?? null;
}

function current_user(): ?array
{
    $id = current_user_id();
    if (!$id) return null;
    static $cache = null;
    if ($cache === null) {
        $stmt = db()->prepare('SELECT id, name, email, avatar_url, accent_color, theme, timezone FROM users WHERE id = ?');
        $stmt->execute([$id]);
        $cache = $stmt->fetch() ?: null;
    }
    return $cache;
}

// Called by every API endpoint. Enforces both authentication AND CSRF (for
// state-changing methods) in one place, so no individual endpoint can
// forget either check.
function require_login(): void
{
    if (!current_user_id()) {
        json_response(['error' => 'Unauthenticated'], 401);
    }
    require_csrf();
}

function json_response($data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json');
    header('X-Content-Type-Options: nosniff');
    echo json_encode($data);
    exit;
}

// Reads and decodes the JSON request body. Caps size to guard against
// memory-exhaustion from an oversized payload, and never lets malformed
// JSON silently become an empty array without the caller knowing —
// callers that need strictness can check json_last_error() themselves.
function body(): array
{
    $raw = file_get_contents('php://input', false, null, 0, 15 * 1024 * 1024); // 15MB cap — the state-sync endpoint is the only caller that needs anywhere near this; everything else is naturally tiny
    if ($raw === false || $raw === '') return [];
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function register_user(string $name, string $email, string $password): array
{
    $ip = client_ip();
    $rl = enforce_rate_limit_soft("register:$ip", 8, 3600);
    if (!$rl['ok']) return $rl;

    $name = clean_str($name, 190);
    $email = clean_email($email);

    if ($name === '' || $email === '' || strlen($password) < 8) {
        return ['ok' => false, 'error' => 'Name, valid email, and password (8+ chars) are required.'];
    }
    if (strlen($password) > 200) {
        return ['ok' => false, 'error' => 'Password is too long.'];
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['ok' => false, 'error' => 'Invalid email address.'];
    }
    if (is_common_password($password)) {
        return ['ok' => false, 'error' => 'That password is too common. Please choose something less guessable.'];
    }

    $stmt = db()->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        rate_limit_hit("register:$ip", 3600);
        audit_log(null, 'register_duplicate_email', ['email' => $email]);
        return ['ok' => false, 'error' => 'An account with that email already exists.'];
    }

    $hash = password_hash($password, PASSWORD_BCRYPT);
    $stmt = db()->prepare('INSERT INTO users (name, email, password_hash) VALUES (?, ?, ?)');
    $stmt->execute([$name, $email, $hash]);
    $userId = (int)db()->lastInsertId();

    db()->prepare('INSERT INTO streaks (user_id) VALUES (?)')->execute([$userId]);
    rate_limit_hit("register:$ip", 3600);
    audit_log($userId, 'register_success', ['email' => $email]);

    return ['ok' => true, 'user_id' => $userId];
}

function attempt_login(string $email, string $password): array
{
    $email = clean_email($email);
    $ip = client_ip();

    // Rate limit on the (email, IP) pair — slows credential stuffing without
    // letting one malicious IP lock out an unrelated legitimate user who
    // happens to share that IP (e.g. NAT'd office network) from every account.
    $limitKey = "login:$email:$ip";
    if (!rate_limit_check($limitKey, 5, 900)) { // 5 attempts / 15 min
        audit_log(null, 'login_rate_limited', ['email' => $email]);
        return ['ok' => false, 'error' => 'Too many failed attempts. Please wait 15 minutes and try again.'];
    }

    $stmt = db()->prepare('SELECT id, password_hash, is_active FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        rate_limit_hit($limitKey, 900);
        audit_log($user['id'] ?? null, 'login_failed', ['email' => $email]);
        return ['ok' => false, 'error' => 'Invalid email or password.'];
    }
    if (!$user['is_active']) {
        audit_log((int)$user['id'], 'login_disabled_account', ['email' => $email]);
        return ['ok' => false, 'error' => 'This account has been disabled.'];
    }

    rate_limit_reset($limitKey);
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int)$user['id'];
    $_SESSION['session_started_at'] = time();
    $_SESSION['last_activity'] = time();
    $_SESSION['last_rotated_at'] = time();
    unset($_SESSION['csrf_token']); // fresh CSRF token per session, issued lazily on next csrf_token() call
    audit_log((int)$user['id'], 'login_success', []);

    return ['ok' => true, 'user_id' => (int)$user['id']];
}

// A short, illustrative blocklist — swap for a proper list (e.g. the top
// 10k from Have I Been Pwned's Pwned Passwords) in production.
function is_common_password(string $password): bool
{
    static $blocked = [
        'password', 'password1', '12345678', '123456789', 'qwerty123',
        'letmein', 'welcome1', 'admin123', 'iloveyou', 'password123',
    ];
    return in_array(strtolower($password), $blocked, true);
}

// Same shape as enforce_rate_limit() but returns quietly instead of calling
// json_response()/exit — used inside register_user() which needs to return
// a normal ['ok' => false, ...] array rather than short-circuit the whole
// request (register_user() is also called from invite-accept.php's HTML flow).
function enforce_rate_limit_soft(string $key, int $maxAttempts, int $windowSeconds): array
{
    if (!rate_limit_check($key, $maxAttempts, $windowSeconds)) {
        return ['ok' => false, 'error' => 'Too many signups from this network. Please wait a while and try again.'];
    }
    return ['ok' => true];
}
