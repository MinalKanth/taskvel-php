<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/ai.php';
require_login();

$uid    = current_user_id();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$in     = body();

switch ("$method:$action") {

    // ---------------- PARSE FREE-TEXT INTO A TASK ----------------
    case 'POST:parse':
        $rl = enforce_rate_limit_soft("ai_parse:$uid", 20, 3600);
        if (!$rl['ok']) {
            json_response(['error' => $rl['error'] ?? 'Too many AI requests, please slow down.'], 429);
        }
        rate_limit_hit("ai_parse:$uid", 3600);

        $text = clean_str($in['text'] ?? '', 500);
        if ($text === '') {
            json_response(['error' => 'Text is required.'], 400);
        }

        $result = ai_parse_task_from_text($text);
        if (!$result['ok']) {
            json_response(['error' => $result['error']], 502);
        }

        json_response(['task' => $result]);
        break;

    default:
        json_response(['error' => 'Unknown action'], 404);
}
