<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/teams.php';
require_login();

$uid    = current_user_id();
$pdo    = db();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$in     = body();

// Messages older than this are purged every time anyone sends or lists —
// no cron job required. This keeps the table small automatically: once a
// message crosses 7 days old, the very next chat activity on that team
// deletes it for good.
const CHAT_RETENTION_DAYS = 7;

function chat_purge_old(PDO $pdo, int $teamId): void
{
    $pdo->prepare('DELETE FROM team_chat_messages WHERE team_id = ? AND created_at < (NOW() - INTERVAL ' . CHAT_RETENTION_DAYS . ' DAY)')
        ->execute([$teamId]);
}

function chat_require_member(int $teamId, int $uid): void
{
    if (!team_role($teamId, $uid)) {
        json_response(['error' => 'Not a member of this team'], 403);
    }
}

switch ("$method:$action") {

    // ---------------- LIST (also used for polling) ----------------
    // ?team_id=1&since_id=42 — returns messages with id > since_id, so the
    // frontend can poll every few seconds and only append what's new.
    case 'GET:list':
        $teamId = (int)($_GET['team_id'] ?? 0);
        chat_require_member($teamId, $uid);
        chat_purge_old($pdo, $teamId);

        $sinceId = (int)($_GET['since_id'] ?? 0);
        if ($sinceId > 0) {
            $stmt = $pdo->prepare(
                'SELECT m.id, m.user_id, u.name, m.message, m.created_at
                 FROM team_chat_messages m JOIN users u ON u.id = m.user_id
                 WHERE m.team_id = ? AND m.id > ?
                 ORDER BY m.id ASC LIMIT 200'
            );
            $stmt->execute([$teamId, $sinceId]);
        } else {
            // Initial load: most recent 50, oldest-first for display.
            $stmt = $pdo->prepare(
                'SELECT m.id, m.user_id, u.name, m.message, m.created_at
                 FROM team_chat_messages m JOIN users u ON u.id = m.user_id
                 WHERE m.team_id = ?
                 ORDER BY m.id DESC LIMIT 50'
            );
            $stmt->execute([$teamId]);
        }
        $rows = $stmt->fetchAll();
        if ($sinceId === 0) $rows = array_reverse($rows);

        json_response([
            'messages' => $rows,
            'retention_days' => CHAT_RETENTION_DAYS,
        ]);
        break;

    // ---------------- SEND ----------------
    case 'POST:send':
        $teamId = (int)($in['team_id'] ?? 0);
        chat_require_member($teamId, $uid);

        // Light per-user rate limit — a live chat box is the easiest
        // feature in this app to accidentally spam (stuck key, double
        // click, etc.), so this is just a safety net, not a real limit
        // on normal conversation.
        $rl = enforce_rate_limit_soft("team_chat_send:$uid", 30, 60);
        if (!$rl['ok']) {
            json_response(['error' => $rl['error'] ?? 'Sending too fast, please slow down.'], 429);
        }
        rate_limit_hit("team_chat_send:$uid", 60);

        $message = clean_str($in['message'] ?? '', 2000);
        if ($message === '') {
            json_response(['error' => 'Message cannot be empty'], 400);
        }

        chat_purge_old($pdo, $teamId);

        $stmt = $pdo->prepare('INSERT INTO team_chat_messages (team_id, user_id, message) VALUES (?, ?, ?)');
        $stmt->execute([$teamId, $uid, $message]);
        $id = (int)$pdo->lastInsertId();

        $stmt = $pdo->prepare(
            'SELECT m.id, m.user_id, u.name, m.message, m.created_at
             FROM team_chat_messages m JOIN users u ON u.id = m.user_id WHERE m.id = ?'
        );
        $stmt->execute([$id]);

        json_response(['message' => $stmt->fetch()], 201);
        break;

    default:
        json_response(['error' => 'Unknown action'], 404);
}
