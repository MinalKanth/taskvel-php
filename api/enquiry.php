<?php
/**
 * PUBLIC endpoint — receives contact/event enquiry form submissions from
 * events.php / event.php / the Samal site and feeds the admin Enquiries inbox.
 *
 * No login required (it's a public form), so it defends itself instead:
 *   - per-IP rate limit (5 submissions / hour)
 *   - honeypot field ("website") that bots fill and humans never see
 *   - strict validation + length caps on every field
 *
 * Frontend usage (plain fetch, no CSRF needed for anonymous visitors):
 *
 *   await fetch('api/enquiry.php', {
 *     method: 'POST',
 *     headers: { 'Content-Type': 'application/json' },
 *     body: JSON.stringify({ name, email, phone, message, event_id, website: '' })
 *   });
 */
require_once __DIR__ . '/../includes/security.php';

if (session_status() === PHP_SESSION_NONE) session_start();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    json_response(['error' => 'Method not allowed'], 405);
}

$ip = client_ip();
enforce_rate_limit("enquiry:$ip", 5, 3600, 'Too many submissions. Please try again later.');

$raw = file_get_contents('php://input', false, null, 0, 64 * 1024);
$in  = json_decode((string)$raw, true);
if (!is_array($in)) $in = $_POST; // also accept classic form posts

// Honeypot: real users never see this field; bots auto-fill it.
if (!empty($in['website'])) {
    json_response(['ok' => true]); // pretend success, store nothing
}

$name    = clean_str($in['name'] ?? '', 120);
$email   = clean_email($in['email'] ?? '');
$phone   = clean_str($in['phone'] ?? '', 30);
$message = clean_str($in['message'] ?? '', 3000);
$eventId = (int)($in['event_id'] ?? 0);

if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_response(['error' => 'Please provide your name and a valid email.'], 422);
}
if ($phone !== '' && !preg_match('/^[0-9+\-\s()]{6,30}$/', $phone)) {
    json_response(['error' => 'Please provide a valid phone number.'], 422);
}

// Only link to an event that actually exists — otherwise store as general.
if ($eventId > 0) {
    $stmt = db()->prepare('SELECT id FROM events WHERE id = ?');
    $stmt->execute([$eventId]);
    if (!$stmt->fetch()) $eventId = 0;
}

db()->prepare('INSERT INTO enquiries (event_id, name, email, phone, message, ip_address)
               VALUES (?, ?, ?, ?, ?, ?)')
    ->execute([$eventId ?: null, $name, $email, $phone ?: null, $message ?: null, $ip]);

rate_limit_hit("enquiry:$ip", 3600);
audit_log(null, 'enquiry_received', ['event_id' => $eventId ?: null, 'email' => $email]);

json_response(['ok' => true, 'message' => 'Thank you — we\'ll get back to you soon.'], 201);
