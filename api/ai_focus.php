<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/ai.php';
require_login();

$uid    = current_user_id();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$in     = body();

switch ("$method:$action") {

    // ---------------- FOCUS BRIEFING ----------------
    case 'POST:focus':
        $quota = ai_consume_daily_quota($uid);
        if (!$quota['ok']) {
            json_response(['error' => $quota['error'], 'upgrade_required' => true], 402);
        }

        // Normally called at most once/day, but allow a little headroom for
        // manual re-requests ("give me another tip") without opening the
        // door to abuse.
        $rl = enforce_rate_limit_soft("ai_focus:$uid", 10, 3600);
        if (!$rl['ok']) {
            json_response(['error' => $rl['error'] ?? 'Too many AI requests, please slow down.'], 429);
        }
        rate_limit_hit("ai_focus:$uid", 3600);

        $tasks = is_array($in['tasks'] ?? null) ? $in['tasks'] : [];
        $result = ai_daily_focus($tasks);
        if (!$result['ok']) {
            json_response(['error' => $result['error']], 502);
        }

        json_response(['focus' => $result['message'], 'ai_quota_remaining' => $quota['remaining']]);
        break;

    default:
        json_response(['error' => 'Unknown action'], 404);
}
