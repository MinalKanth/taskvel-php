<?php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '0'); // keep off — PHP errors should never leak into the JSON response

header('Content-Type: application/json');

// This file lives in /api, so config/ and includes/ are one level up.
require_once __DIR__ . '/../config/db.php';       // provides db(): PDO
require_once __DIR__ . '/../includes/mailer.php'; // send_mail(), email_shell(), send_contact_notification_email()

$pdo = db();

session_start();

// ---- Config ----
const CONTACT_NOTIFY_EMAIL = 'info@samalconsultancy.com'; // <-- change if you want a different inbox
const CONTACT_MIN_INTERVAL = 30; // seconds between submissions per session (basic spam throttle)

function json_out(bool $ok, string $message, int $code = 200): void
{
    http_response_code($code);
    echo json_encode(['success' => $ok, 'message' => $message]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_out(false, 'Invalid request method.', 405);
}

// Accept JSON (fetch) with a form-encoded fallback
$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data)) {
    $data = $_POST;
}

// Honeypot — bots fill hidden fields, real visitors never see it.
// Respond with success so bots don't learn the field is being checked.
if (!empty($data['website'])) {
    json_out(true, "Thank you for contacting us! Your message has been sent successfully. We'll get back to you as soon as possible.");
}

// Basic per-session rate limit
$now = time();
if (!empty($_SESSION['last_contact_submit']) && ($now - $_SESSION['last_contact_submit']) < CONTACT_MIN_INTERVAL) {
    json_out(false, 'Please wait a moment before sending another message.', 429);
}

$name    = trim((string)($data['name'] ?? ''));
$phone   = trim((string)($data['phone'] ?? ''));
$email   = trim((string)($data['email'] ?? ''));
$message = trim((string)($data['message'] ?? ''));

if ($name === '' || $phone === '' || $email === '' || $message === '') {
    json_out(false, 'Please fill in all fields before sending.', 422);
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_out(false, 'Please enter a valid email address.', 422);
}
if (mb_strlen($name) > 190 || mb_strlen($phone) > 30 || mb_strlen($email) > 190) {
    json_out(false, 'One of the fields is too long.', 422);
}
if (mb_strlen($message) > 5000) {
    json_out(false, 'Message is too long — please keep it under 5000 characters.', 422);
}

$ip = $_SERVER['REMOTE_ADDR'] ?? null;

try {
    $stmt = $pdo->prepare(
        'INSERT INTO contact_messages (name, phone, email, message, ip_address, created_at)
         VALUES (:name, :phone, :email, :message, :ip, NOW())'
    );
    $stmt->execute([
        ':name'    => $name,
        ':phone'   => $phone,
        ':email'   => $email,
        ':message' => $message,
        ':ip'      => $ip,
    ]);
} catch (\Throwable $e) {
    error_log('Contact form DB insert failed: ' . $e->getMessage());
    // TEMP DEBUG: shows the real DB error in the response so you can see it
    // in the Network tab. Remove the getMessage() part before going live.
    json_out(false, "DB insert failed: " . $e->getMessage(), 500);
}

// Email is best-effort: a failed notification should never block the
// success response, since the message is already safely saved in the DB.
try {
    send_contact_notification_email(CONTACT_NOTIFY_EMAIL, $name, $phone, $email, $message);
} catch (\Throwable $e) {
    error_log('Contact notification email failed: ' . $e->getMessage());
}

$_SESSION['last_contact_submit'] = $now;

json_out(true, "Thank you for contacting us! Your message has been sent successfully. We'll get back to you as soon as possible.");