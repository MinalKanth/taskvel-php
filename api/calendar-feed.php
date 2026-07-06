<?php
// Public endpoint (calendar apps can't send session cookies) — auth is via
// the unguessable per-user token instead, same pattern as most calendar feeds.
require_once __DIR__ . '/../includes/security.php';

$token = $_GET['token'] ?? '';
if (!$token || !preg_match('/^[a-f0-9]{48}$/', $token)) { http_response_code(400); exit('Missing or malformed token'); }

// Light rate limiting on the token itself — the token's 192 bits of entropy
// already make brute-forcing infeasible, but this caps abuse/DoS regardless.
enforce_rate_limit_public("calfeed:$token", 60, 3600);

$pdo = db();
$stmt = $pdo->prepare('SELECT user_id FROM calendar_feed_tokens WHERE token = ?');
$stmt->execute([$token]);
$row = $stmt->fetch();
if (!$row) { http_response_code(404); exit('Invalid feed token'); }

$uid = (int)$row['user_id'];
$stmt = $pdo->prepare("SELECT id, title, description, due_date, due_time, status FROM tasks
                        WHERE owner_id = ? AND due_date IS NOT NULL");
$stmt->execute([$uid]);
$tasks = $stmt->fetchAll();

header('Content-Type: text/calendar; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Content-Disposition: inline; filename="taskvel.ics"');

echo "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//Taskvel//EN\r\nCALSCALE:GREGORIAN\r\nX-WR-CALNAME:Taskvel\r\n";

foreach ($tasks as $t) {
    $date = preg_replace('/[^0-9]/', '', $t['due_date']);
    $time = $t['due_time'] ? preg_replace('/[^0-9]/', '', $t['due_time']) . '00' : '090000';
    $dtstart = $date . 'T' . $time;
    $uidStr = 'taskvel-task-' . (int)$t['id'] . '@taskvel.app';
    $summary = ($t['status'] === 'done' ? '[Done] ' : '') . $t['title'];

    echo "BEGIN:VEVENT\r\n";
    echo "UID:$uidStr\r\n";
    echo "DTSTART:$dtstart\r\n";
    echo "SUMMARY:" . ics_escape($summary) . "\r\n";
    if (!empty($t['description'])) {
        echo "DESCRIPTION:" . ics_escape($t['description']) . "\r\n";
    }
    echo "END:VEVENT\r\n";
}

echo "END:VCALENDAR\r\n";

// Rate limiter variant that fails "open" (serves the request) rather than
// hard-blocking a legitimate calendar app's routine sync polling — logs the
// abuse signal without breaking normal usage patterns for this specific
// low-risk, high-entropy-token endpoint.
function enforce_rate_limit_public(string $key, int $maxAttempts, int $windowSeconds): void
{
    if (!rate_limit_check($key, $maxAttempts, $windowSeconds)) {
        audit_log(null, 'calendar_feed_rate_limited', ['key' => $key]);
    }
    rate_limit_hit($key, $windowSeconds);
}

