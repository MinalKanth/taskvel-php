<?php
require_once __DIR__ . '/security.php';

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 24 * 60 * 60, // 24 hours — without this the cookie itself is a "until browser closes" session cookie, regardless of the server-side timeouts below
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
const SESSION_IDLE_TIMEOUT = 24 * 60 * 60;      // 24 hours of inactivity
const SESSION_ABSOLUTE_LIFETIME = 24 * 60 * 60; // 24 hours max, even if active
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

// Moved here from admin.php — login.php and forgot-password.php need this
// for their post-login redirect, but they only require_once auth.php,
// not admin.php, so it has to live here rather than there.
function current_user_role(): string
{
    $uid = current_user_id();
    if (!$uid) return 'guest';
    static $role = null;
    if ($role === null) {
        $stmt = db()->prepare('SELECT role FROM users WHERE id = ?');
        $stmt->execute([$uid]);
        $role = $stmt->fetchColumn() ?: 'user';
    }
    return $role;
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
    $verifyToken = bin2hex(random_bytes(32));

    // email_verified_at stays NULL until the link is clicked — attempt_login()
    // blocks unverified accounts, so a typo'd address like a@gmail.com can
    // register a row but can never actually be used to log in.
    $stmt = db()->prepare(
        'INSERT INTO users (name, email, password_hash, plan, plan_source, trial_ends_at,
                             email_verification_token, email_verification_sent_at)
         VALUES (?, ?, ?, \'pro\', \'trial\', DATE_ADD(NOW(), INTERVAL 30 DAY), ?, NOW())'
    );
    $stmt->execute([$name, $email, $hash, $verifyToken]);
    $userId = (int)db()->lastInsertId();

    db()->prepare('INSERT INTO streaks (user_id) VALUES (?)')->execute([$userId]);
    rate_limit_hit("register:$ip", 3600);
    audit_log($userId, 'register_success', ['email' => $email]);

    require_once __DIR__ . '/mailer.php';
    try {
        send_verification_email($email, $name, $verifyToken);
    } catch (Throwable $e) {
        error_log('Verification email failed for ' . $email . ': ' . $e->getMessage());
    }

    // needs_verification tells the caller not to log the user in yet.
    return ['ok' => true, 'user_id' => $userId, 'needs_verification' => true, 'email' => $email];
}

function attempt_login(string $email, string $password): array
{
    $email = clean_email($email);
    $ip = client_ip();

    $limitKey = "login:$email:$ip";
    if (!rate_limit_check($limitKey, 5, 900)) {
        audit_log(null, 'login_rate_limited', ['email' => $email]);
        return ['ok' => false, 'error' => 'Too many failed attempts. Please wait 15 minutes and try again.'];
    }

    $stmt = db()->prepare('SELECT id, password_hash, is_active, email_verified_at FROM users WHERE email = ?');
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

    if (!$user['email_verified_at']) {
        audit_log((int)$user['id'], 'login_unverified_email', ['email' => $email]);
        // 'unverified' => true is what the login page uses to show the resend option.
        return ['ok' => false, 'error' => 'Please verify your email before logging in.', 'unverified' => true, 'email' => $email];
    }

    rate_limit_reset($limitKey);
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int)$user['id'];
    $_SESSION['session_started_at'] = time();
    $_SESSION['last_activity'] = time();
    $_SESSION['last_rotated_at'] = time();
    unset($_SESSION['csrf_token']);
    audit_log((int)$user['id'], 'login_success', []);

    // Re-evaluate plan/trial/org status right now, on every login — this is
    // what actually enforces trial expiry, grace-period lockouts, and
    // admin-granted extensions ending, instead of relying solely on the
    // daily cron (cron/send_trial_reminders.php) having been scheduled.
    // A stale trial_ends_at from months ago should never grant access
    // just because nothing happened to touch that row since signup.
    require_once __DIR__ . '/licensing.php';
    recompute_user_plan((int)$user['id']);

    return ['ok' => true, 'user_id' => (int)$user['id']];
}

function verify_email_token(string $token): array
{
    if ($token === '' || !preg_match('/^[a-f0-9]{64}$/', $token)) {
        return ['ok' => false, 'error' => 'This verification link is invalid.'];
    }
    $stmt = db()->prepare('SELECT id, name, email_verified_at FROM users WHERE email_verification_token = ?');
    $stmt->execute([$token]);
    $user = $stmt->fetch();
    if (!$user) {
        return ['ok' => false, 'error' => 'This verification link is invalid or has already been used.'];
    }
    if ($user['email_verified_at']) {
        return ['ok' => true, 'already_verified' => true];
    }
    db()->prepare('UPDATE users SET email_verified_at = NOW(), email_verification_token = NULL WHERE id = ?')
        ->execute([$user['id']]);
    audit_log((int)$user['id'], 'email_verified', []);
    return ['ok' => true];
}

function resend_verification_email(string $email): array
{
    $ip = client_ip();
    $rl = enforce_rate_limit_soft("resend-verify:$ip", 5, 3600);
    if (!$rl['ok']) return $rl;
    rate_limit_hit("resend-verify:$ip", 3600);

    $email = clean_email($email);
    $stmt = db()->prepare('SELECT id, name, email_verified_at, email_verification_sent_at FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    // Same response whether or not the account exists, so this can't be
    // used to enumerate registered emails.
    if (!$user || $user['email_verified_at']) {
        return ['ok' => true];
    }

    // Throttle resends per-account too, separate from the per-IP limit above.
    if ($user['email_verification_sent_at'] && strtotime($user['email_verification_sent_at']) > time() - 60) {
        return ['ok' => false, 'error' => 'Please wait a minute before requesting another verification email.'];
    }

    $token = bin2hex(random_bytes(32));
    db()->prepare('UPDATE users SET email_verification_token = ?, email_verification_sent_at = NOW() WHERE id = ?')
        ->execute([$token, $user['id']]);

    require_once __DIR__ . '/mailer.php';
    try {
        send_verification_email($email, $user['name'], $token);
    } catch (Throwable $e) {
        error_log('Resend verification email failed for ' . $email . ': ' . $e->getMessage());
        return ['ok' => false, 'error' => 'Could not send the email right now. Please try again shortly.'];
    }

    return ['ok' => true];
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

function request_password_reset(string $email): array
{
    $ip = client_ip();
    $rl = enforce_rate_limit_soft("pwreset:$ip", 5, 3600);
    if (!$rl['ok']) return $rl;

    $email = clean_email($email);
    rate_limit_hit("pwreset:$ip", 3600);

    $stmt = db()->prepare('SELECT id, name FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    // Always behave the same whether or not the account exists, so this
    // can't be used to check which emails are registered.
    if ($user) {
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', time() + 3600); // 1 hour

        db()->prepare('DELETE FROM password_resets WHERE email = ?')->execute([$email]);
        db()->prepare('INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)')
            ->execute([$email, $token, $expires]);

        require_once __DIR__ . '/mailer.php';
        send_password_reset_email($email, $user['name'], $token);
        audit_log((int)$user['id'], 'password_reset_requested', []);
    }

    return ['ok' => true];
}

function validate_reset_token(string $token): ?array
{
    if ($token === '') return null;
    $stmt = db()->prepare('SELECT * FROM password_resets WHERE token = ?');
    $stmt->execute([$token]);
    $row = $stmt->fetch();
    if (!$row) return null;
    if (strtotime($row['expires_at']) < time()) return null;
    return $row;
}

function reset_password(string $token, string $password): array
{
    $reset = validate_reset_token($token);
    if (!$reset) {
        return ['ok' => false, 'error' => 'This reset link is invalid or has expired. Please request a new one.'];
    }
    if (strlen($password) < 8) {
        return ['ok' => false, 'error' => 'Password must be at least 8 characters.'];
    }
    if (strlen($password) > 200) {
        return ['ok' => false, 'error' => 'Password is too long.'];
    }
    if (is_common_password($password)) {
        return ['ok' => false, 'error' => 'That password is too common. Please choose something less guessable.'];
    }

    $stmt = db()->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->execute([$reset['email']]);
    $user = $stmt->fetch();
    if (!$user) {
        return ['ok' => false, 'error' => 'This reset link is invalid or has expired. Please request a new one.'];
    }

    $hash = password_hash($password, PASSWORD_BCRYPT);
    db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([$hash, $user['id']]);
    db()->prepare('DELETE FROM password_resets WHERE email = ?')->execute([$reset['email']]);

    audit_log((int)$user['id'], 'password_reset_success', []);
    return ['ok' => true];
}