<?php
// Public endpoint — taskvel-free.php has no login/session at all, so this
// cannot require_login() like api/ai_suggest.php does. Instead it's kept
// safe with a strict per-IP rate limit, since anyone on the internet can
// hit this and it costs a real (free-tier) API call each time.
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/ai.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$in     = body();

switch ("$method:$action") {

    case 'POST:suggest':
        $ip = client_ip();
        // Tighter than the logged-in endpoint (8/hour vs 20/hour) since
        // there's no per-user identity to key off — just an IP.
        $rl = enforce_rate_limit_soft("ai_suggest_public:$ip", 8, 3600);
        if (!$rl['ok']) {
            json_response(['error' => $rl['error'] ?? 'Too many AI requests from this network, please try again later.'], 429);
        }
        rate_limit_hit("ai_suggest_public:$ip", 3600);

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
