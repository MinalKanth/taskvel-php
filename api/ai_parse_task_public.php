<?php
// Public endpoint — taskvel-free.php has no login/session, so this is
// rate-limited per IP instead of per user, same pattern as the other
// _public.php AI endpoints.
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/ai.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$in     = body();

switch ("$method:$action") {

    case 'POST:parse':
        $ip = client_ip();
        $rl = enforce_rate_limit_soft("ai_parse_public:$ip", 8, 3600);
        if (!$rl['ok']) {
            json_response(['error' => $rl['error'] ?? 'Too many AI requests from this network, please try again later.'], 429);
        }
        rate_limit_hit("ai_parse_public:$ip", 3600);

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
