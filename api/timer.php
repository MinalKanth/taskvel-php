<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();

$uid = current_user_id();
$pdo = db();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$in = body();

// A task_id may be supplied to attach a time log / focus session to a
// specific task — verify the caller can actually see that task before
// letting them log time against it (previously unchecked, allowing writes
// against arbitrary other users' tasks).
function timer_task_ok(PDO $pdo, ?int $taskId, int $uid): bool
{
    if (!$taskId) return true; // untagged time logs are fine
    $stmt = $pdo->prepare("SELECT 1 FROM tasks WHERE id = ? AND owner_id = ?
                            UNION
                            SELECT 1 FROM task_shares WHERE task_id = ? AND shared_with_user_id = ? AND status = 'accepted'");
    $stmt->execute([$taskId, $uid, $taskId, $uid]);
    return (bool)$stmt->fetch();
}

switch ("$method:$action") {

    // ---- generic time tracking (start/stop) ----
    case 'POST:start':
        $taskId = isset($in['task_id']) ? (int)$in['task_id'] : null;
        if (!timer_task_ok($pdo, $taskId, $uid)) json_response(['error' => 'Forbidden'], 403);
        $pdo->prepare('INSERT INTO time_logs (task_id, user_id, tag, started_at) VALUES (?, ?, ?, NOW())')
            ->execute([$taskId, $uid, clean_str($in['tag'] ?? '', 60) ?: null]);
        json_response(['id' => (int)$pdo->lastInsertId()]);
        break;

    case 'POST:stop':
        $id = (int)($in['id'] ?? 0);
        $pdo->prepare('UPDATE time_logs SET ended_at = NOW(), duration_seconds = TIMESTAMPDIFF(SECOND, started_at, NOW()) WHERE id = ? AND user_id = ?')
            ->execute([$id, $uid]);
        json_response(['ok' => true]);
        break;

    case 'GET:report':
        // per-tag totals for last N days
        $days = (int)($_GET['days'] ?? 7);
        $stmt = $pdo->prepare("SELECT COALESCE(tag,'Untagged') AS tag, SUM(duration_seconds) AS seconds
                                FROM time_logs WHERE user_id = ? AND started_at >= (NOW() - INTERVAL ? DAY)
                                GROUP BY tag ORDER BY seconds DESC");
        $stmt->execute([$uid, $days]);
        json_response(['report' => $stmt->fetchAll()]);
        break;

    // ---- pomodoro / focus sessions ----
    case 'POST:focus-log':
        $taskId = isset($in['task_id']) ? (int)$in['task_id'] : null;
        if (!timer_task_ok($pdo, $taskId, $uid)) json_response(['error' => 'Forbidden'], 403);
        $pdo->prepare('INSERT INTO focus_sessions (user_id, task_id, mode, ambient_sound, duration_seconds, started_at, ended_at, completed)
                        VALUES (?, ?, ?, ?, ?, ?, NOW(), 1)')
            ->execute([
                $uid,
                $taskId,
                one_of($in['mode'] ?? 'focus', ['focus', 'short_break', 'long_break'], 'focus'),
                clean_str($in['ambient_sound'] ?? '', 60) ?: null,
                max(0, (int)($in['duration_seconds'] ?? 0)),
                clean_str($in['started_at'] ?? date('Y-m-d H:i:s'), 19),
            ]);

        update_streak($pdo, $uid);
        json_response(['ok' => true]);
        break;

    case 'GET:focus-history':
        $stmt = $pdo->prepare('SELECT * FROM focus_sessions WHERE user_id = ? ORDER BY started_at DESC LIMIT 100');
        $stmt->execute([$uid]);
        json_response(['sessions' => $stmt->fetchAll()]);
        break;

    case 'GET:streak':
        $stmt = $pdo->prepare('SELECT * FROM streaks WHERE user_id = ?');
        $stmt->execute([$uid]);
        json_response(['streak' => $stmt->fetch() ?: ['current_streak' => 0, 'longest_streak' => 0]]);
        break;

    default:
        json_response(['error' => 'Unknown route'], 404);
}

function update_streak(PDO $pdo, int $uid): void
{
    $stmt = $pdo->prepare('SELECT * FROM streaks WHERE user_id = ?');
    $stmt->execute([$uid]);
    $s = $stmt->fetch();
    $today = date('Y-m-d');

    if (!$s) {
        $pdo->prepare('INSERT INTO streaks (user_id, current_streak, longest_streak, last_active_date) VALUES (?, 1, 1, ?)')->execute([$uid, $today]);
        return;
    }
    if ($s['last_active_date'] === $today) return; // already counted today

    $yesterday = date('Y-m-d', strtotime('-1 day'));
    $current = ($s['last_active_date'] === $yesterday) ? $s['current_streak'] + 1 : 1;
    $longest = max($current, $s['longest_streak']);

    $pdo->prepare('UPDATE streaks SET current_streak = ?, longest_streak = ?, last_active_date = ? WHERE user_id = ?')
        ->execute([$current, $longest, $today, $uid]);
}
