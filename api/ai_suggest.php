<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/ai.php';
require_login();

$uid    = current_user_id();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$in     = body();

switch ("$method:$action") {

    // ---------------- SUGGEST ----------------
    case 'POST:suggest':
        // A soft per-user rate limit — AI calls are the most expensive thing
        // this app does per-request, so this keeps one user (or a bug in the
        // frontend retry logic) from burning the whole free quota.
        $rl = enforce_rate_limit_soft("ai_suggest:$uid", 20, 3600);
        if (!$rl['ok']) {
            json_response(['error' => $rl['error'] ?? 'Too many AI requests, please slow down.'], 429);
        }
        rate_limit_hit("ai_suggest:$uid", 3600);

        $name = clean_str($in['name'] ?? '', 255);
        $note = clean_str($in['note'] ?? '', 1000);
        if ($name === '') {
            json_response(['error' => 'Task name is required.'], 400);
        }

        $result = ai_suggest_task($name, $note);
        if (!$result['ok']) {
            json_response(['error' => $result['error']], 502);
        }

        json_response(['suggestion' => $result]);
        break;

    default:
        json_response(['error' => 'Unknown action'], 404);
}
