<?php
// Public endpoint — taskvel-free.php has no login/session, so this is
// rate-limited per IP instead of per user, same pattern as
// api/ai_suggest_public.php.
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/ai.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$in     = body();

switch ("$method:$action") {

    case 'POST:focus':
        $ip = client_ip();
        $rl = enforce_rate_limit_soft("ai_focus_public:$ip", 6, 3600);
        if (!$rl['ok']) {
            json_response(['error' => $rl['error'] ?? 'Too many AI requests from this network, please try again later.'], 429);
        }
        rate_limit_hit("ai_focus_public:$ip", 3600);

        $tasks = is_array($in['tasks'] ?? null) ? $in['tasks'] : [];
        $result = ai_daily_focus($tasks);
        if (!$result['ok']) {
            json_response(['error' => $result['error']], 502);
        }

        json_response(['focus' => $result['message']]);
        break;

    default:
        json_response(['error' => 'Unknown action'], 404);
}
