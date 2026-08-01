<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/mailer.php';
require_once __DIR__ . '/../includes/webhooks.php';
require_once __DIR__ . '/../config/workhours.php';
require_login();

$uid = current_user_id();
$pdo = db();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$in = body();
$today = date('Y-m-d');

function get_todays_workday(PDO $pdo, int $uid, string $today): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM workdays WHERE user_id = ? AND work_date = ?');
    $stmt->execute([$uid, $today]);
    return $stmt->fetch() ?: null;
}

function fmt_duration(int $seconds): string
{
    $h = intdiv($seconds, 3600);
    $m = intdiv($seconds % 3600, 60);
    if ($h > 0) return "{$h}h {$m}m";
    return "{$m}m";
}

function user_name(PDO $pdo, int $uid): string
{
    $stmt = $pdo->prepare('SELECT name FROM users WHERE id = ?'); $stmt->execute([$uid]);
    return (string)$stmt->fetchColumn();
}

switch ("$method:$action") {

    case 'GET:today':
        $workday = get_todays_workday($pdo, $uid, $today);
        $tasks = []; $breaks = [];
        if ($workday) {
            $stmt = $pdo->prepare('SELECT * FROM workday_tasks WHERE workday_id = ? ORDER BY created_at ASC');
            $stmt->execute([$workday['id']]);
            $tasks = $stmt->fetchAll();
            $stmt = $pdo->prepare('SELECT * FROM workday_breaks WHERE workday_id = ? ORDER BY started_at ASC');
            $stmt->execute([$workday['id']]);
            $breaks = $stmt->fetchAll();
        }
        json_response(['workday' => $workday, 'tasks' => $tasks, 'breaks' => $breaks]);
        break;

    // Who this user could send their end-of-day report to: the
    // owner/manager(s) of every team they belong to, plus every teammate
    // on those teams — used to populate the "Send report to" picker at
    // checkout. Gracefully returns empty lists if the Teams module's
    // tables aren't installed, since Daily Check-in works standalone.
    case 'GET:report-contacts':
        $managers = [];
        $teamMembers = [];
        try {
            $stmt = $pdo->prepare(
                "SELECT DISTINCT u.id, u.name, u.email, t.name AS team_name
                 FROM team_members me
                 JOIN team_members tm ON tm.team_id = me.team_id AND tm.role IN ('owner','manager') AND tm.user_id != ?
                 JOIN users u ON u.id = tm.user_id
                 JOIN teams t ON t.id = me.team_id
                 WHERE me.user_id = ?
                 ORDER BY t.name, u.name"
            );
            $stmt->execute([$uid, $uid]);
            $managers = $stmt->fetchAll();

            $stmt = $pdo->prepare(
                "SELECT DISTINCT u.id, u.name, u.email, t.name AS team_name
                 FROM team_members me
                 JOIN team_members tm ON tm.team_id = me.team_id AND tm.user_id != ?
                 JOIN users u ON u.id = tm.user_id
                 JOIN teams t ON t.id = me.team_id
                 WHERE me.user_id = ?
                 ORDER BY t.name, u.name"
            );
            $stmt->execute([$uid, $uid]);
            $teamMembers = $stmt->fetchAll();
        } catch (Throwable $e) {
            // Teams tables not installed — Daily Check-in still works with custom emails only.
        }
        json_response(['managers' => $managers, 'team_members' => $teamMembers]);
        break;

    case 'POST:checkin':
        $existing = get_todays_workday($pdo, $uid, $today);
        if ($existing) json_response(['workday' => $existing, 'already' => true]);
        $reportTo = clean_email($in['report_to_email'] ?? '');
        if ($reportTo !== '' && !filter_var($reportTo, FILTER_VALIDATE_EMAIL)) json_response(['error' => 'Invalid report-to email'], 422);
        $stmt = $pdo->prepare('INSERT INTO workdays (user_id, work_date, checkin_at, report_to_email) VALUES (?, ?, NOW(), ?)');
        $stmt->execute([$uid, $today, $reportTo ?: null]);
        $workday = get_todays_workday($pdo, $uid, $today);
        $lateBy = strtotime($workday['checkin_at']) - strtotime($today . ' ' . EXPECTED_CHECKIN_TIME);
        if ($lateBy > 300) { // more than 5 min late
            notify_chat('Late check-in', user_name($pdo, $uid) . ' checked in ' . fmt_duration($lateBy) . ' late (' . date('H:i', strtotime($workday['checkin_at'])) . ').');
        }
        json_response(['workday' => $workday]);
        break;

    // Lets someone who already checked out resume the same workday — e.g.
    // they stepped out, came back, and want to log more tasks or extend
    // their day. Keeps the original checkin_at and all existing tasks;
    // just clears checkout_at so the UI treats the day as open again.
    case 'POST:reopen':
        $workday = get_todays_workday($pdo, $uid, $today);
        if (!$workday) json_response(['error' => 'You have not checked in today'], 422);
        if (!$workday['checkout_at']) json_response(['ok' => true, 'workday' => $workday]); // already open, idempotent
        $pdo->prepare('UPDATE workdays SET checkout_at = NULL WHERE id = ?')->execute([$workday['id']]);
        audit_log($uid, 'workday_reopened', ['workday_id' => $workday['id']]);
        $workday = get_todays_workday($pdo, $uid, $today);
        json_response(['ok' => true, 'workday' => $workday]);
        break;

    case 'POST:add-task':
        enforce_rate_limit("workday-add:$uid", 100, 3600);
        $workday = get_todays_workday($pdo, $uid, $today);
        if (!$workday) json_response(['error' => 'Check in first'], 422);
        if ($workday['checkout_at']) json_response(['error' => 'You already checked out for today'], 422);
        $title = clean_str($in['title'] ?? '', 255);
        if ($title === '') json_response(['error' => 'Task title is required'], 422);
        $reportTo = clean_email($in['report_to_email'] ?? '');
        if ($reportTo !== '' && !filter_var($reportTo, FILTER_VALIDATE_EMAIL)) json_response(['error' => 'Invalid report-to email'], 422);
        $expected = !empty($in['expected_minutes']) ? min(1440, max(1, (int)$in['expected_minutes'])) : null;
        $stmt = $pdo->prepare('INSERT INTO workday_tasks (workday_id, title, report_to_email, expected_minutes) VALUES (?, ?, ?, ?)');
        $stmt->execute([$workday['id'], $title, $reportTo ?: null, $expected]);
        json_response(['ok' => true, 'task_id' => (int)$pdo->lastInsertId()]);
        break;

    case 'POST:start-task':
        $id = (int)($in['id'] ?? 0);
        $stmt = $pdo->prepare('SELECT wt.*, w.user_id FROM workday_tasks wt JOIN workdays w ON w.id = wt.workday_id WHERE wt.id = ?');
        $stmt->execute([$id]);
        $task = $stmt->fetch();
        if (!$task || $task['user_id'] != $uid) json_response(['error' => 'Not found'], 404);
        if ($task['status'] !== 'pending') json_response(['ok' => true]); // idempotent
        $pdo->prepare('UPDATE workday_tasks SET status = \'in_progress\', started_at = NOW() WHERE id = ?')->execute([$id]);

        if (!empty($task['report_to_email'])) {
            $name = user_name($pdo, $uid);
            $subject = "$name started a task: \"{$task['title']}\" — Taskvel";
            $html = "<div style=\"font-family:Arial,sans-serif;max-width:480px;margin:auto;padding:24px;border:1px solid #eee;border-radius:12px\">"
                  . "<h2 style=\"margin:0 0 12px\">▶ Task started</h2>"
                  . "<p><strong>$name</strong> just started:</p>"
                  . "<p style=\"font-size:16px;font-weight:600;margin:8px 0\">\"{$task['title']}\"</p>"
                  . "<p style=\"color:#666\">Started at " . date('g:i A') . "</p></div>";
            try { send_mail($task['report_to_email'], $subject, $html); } catch (Throwable $e) {}
            notify_chat('Task started', "$name started \"{$task['title']}\"");
        }
        json_response(['ok' => true]);
        break;

    // Marking a task done either finishes it immediately (no report-to
    // person to check with) or moves it to pending-approval and emails a
    // one-click approve/reject link — the classic "approval before closing
    // a task" workflow managers ask for.
    case 'POST:complete-task':
        $id = (int)($in['id'] ?? 0);
        $stmt = $pdo->prepare('SELECT wt.*, w.user_id, w.checkin_at, u.name, u.email as user_email FROM workday_tasks wt
                               JOIN workdays w ON w.id = wt.workday_id JOIN users u ON u.id = w.user_id WHERE wt.id = ?');
        $stmt->execute([$id]);
        $task = $stmt->fetch();
        if (!$task || $task['user_id'] != $uid) json_response(['error' => 'Not found'], 404);
        if (in_array($task['status'], ['done', 'pending_approval'])) json_response(['ok' => true]); // idempotent

        $startRef = $task['started_at'] ?: $task['checkin_at'];
        $duration = max(0, strtotime('now') - strtotime($startRef));

        if (!empty($task['report_to_email'])) {
            $token = bin2hex(random_bytes(24));
            $pdo->prepare('UPDATE workday_tasks SET status = \'pending_approval\', completed_at = NOW(), duration_seconds = ?,
                           started_at = COALESCE(started_at, ?), approval_status = \'pending\', approval_token = ? WHERE id = ?')
                ->execute([$duration, $startRef, $token, $id]);

            $durationText = fmt_duration($duration);
            $startedText = date('g:i A', strtotime($startRef));
            $completedText = date('g:i A');
            $approveUrl = APP_URL . '/api/approve-task.php?token=' . $token . '&decision=approve';
            $rejectUrl = APP_URL . '/api/approve-task.php?token=' . $token . '&decision=reject';
            $subject = "Approval needed: \"{$task['title']}\" — Taskvel";
            $html = "<div style=\"font-family:Arial,sans-serif;max-width:520px;margin:auto;padding:24px;border:1px solid #eee;border-radius:12px\">"
                  . "<h2 style=\"margin:0 0 12px\">Task awaiting your approval</h2>"
                  . "<p><strong>{$task['name']}</strong> marked this task complete:</p>"
                  . "<p style=\"font-size:16px;font-weight:600;margin:8px 0\">\"{$task['title']}\"</p>"
                  . "<table style=\"font-size:13px;color:#555;margin:12px 0\">"
                  . "<tr><td style=\"padding:2px 10px 2px 0\">Started</td><td><strong>$startedText</strong></td></tr>"
                  . "<tr><td style=\"padding:2px 10px 2px 0\">Completed</td><td><strong>$completedText</strong></td></tr>"
                  . "<tr><td style=\"padding:2px 10px 2px 0\">Duration</td><td><strong>$durationText</strong></td></tr>"
                  . "</table>"
                  . "<div style=\"margin:20px 0\">"
                  . "<a href=\"$approveUrl\" style=\"background:#059669;color:#fff;padding:12px 20px;border-radius:8px;text-decoration:none;display:inline-block;margin-right:10px\">✓ Approve</a>"
                  . "<a href=\"$rejectUrl\" style=\"background:#dc2626;color:#fff;padding:12px 20px;border-radius:8px;text-decoration:none;display:inline-block\">✕ Send back</a>"
                  . "</div></div>";
            try { send_mail($task['report_to_email'], $subject, $html); } catch (Throwable $e) {}
            notify_chat('Approval needed', "{$task['name']} completed \"{$task['title']}\" ($durationText) — awaiting approval");
            json_response(['ok' => true, 'status' => 'pending_approval', 'duration_seconds' => $duration]);
        } else {
            // No one to check with — mark done immediately, same as before.
            $pdo->prepare('UPDATE workday_tasks SET status = \'done\', completed_at = NOW(), duration_seconds = ?,
                           started_at = COALESCE(started_at, ?), approval_status = \'auto\' WHERE id = ?')
                ->execute([$duration, $startRef, $id]);
            json_response(['ok' => true, 'status' => 'done', 'duration_seconds' => $duration]);
        }
        break;

    // Lets an employee resume a task their manager sent back (rejected).
    case 'POST:reopen-task':
        $id = (int)($in['id'] ?? 0);
        $stmt = $pdo->prepare('SELECT wt.*, w.user_id FROM workday_tasks wt JOIN workdays w ON w.id = wt.workday_id WHERE wt.id = ?');
        $stmt->execute([$id]);
        $task = $stmt->fetch();
        if (!$task || $task['user_id'] != $uid) json_response(['error' => 'Not found'], 404);
        $pdo->prepare('UPDATE workday_tasks SET status = \'in_progress\', approval_status = \'auto\', completed_at = NULL WHERE id = ?')->execute([$id]);
        json_response(['ok' => true]);
        break;

    case 'DELETE:delete-task':
        $id = (int)($_GET['id'] ?? 0);
        $stmt = $pdo->prepare('SELECT wt.id, w.user_id FROM workday_tasks wt JOIN workdays w ON w.id = wt.workday_id WHERE wt.id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if (!$row || $row['user_id'] != $uid) json_response(['error' => 'Not found'], 404);
        $pdo->prepare('DELETE FROM workday_tasks WHERE id = ?')->execute([$id]);
        json_response(['ok' => true]);
        break;

    // ── Break tracking (Lunch / Tea / Personal / Other) ──
    case 'POST:break-start':
        $workday = get_todays_workday($pdo, $uid, $today);
        if (!$workday || $workday['checkout_at']) json_response(['error' => 'Not checked in'], 422);
        $stmt = $pdo->prepare('SELECT id FROM workday_breaks WHERE workday_id = ? AND ended_at IS NULL');
        $stmt->execute([$workday['id']]);
        if ($stmt->fetch()) json_response(['error' => 'A break is already in progress'], 422);
        $type = in_array($in['break_type'] ?? 'other', ['lunch', 'tea', 'personal', 'other']) ? $in['break_type'] : 'other';
        $pdo->prepare('INSERT INTO workday_breaks (workday_id, break_type, started_at) VALUES (?, ?, NOW())')->execute([$workday['id'], $type]);
        json_response(['ok' => true]);
        break;

    case 'POST:break-end':
        $workday = get_todays_workday($pdo, $uid, $today);
        if (!$workday) json_response(['error' => 'Not checked in'], 422);
        $pdo->prepare('UPDATE workday_breaks SET ended_at = NOW() WHERE workday_id = ? AND ended_at IS NULL')->execute([$workday['id']]);
        json_response(['ok' => true]);
        break;

    // ── Idle detection — tab-level only (mouse/keyboard inactivity while
    // Taskvel is the active tab). Client detects the gap and reports it
    // after the fact; this is NOT screen or activity capture. ──
    case 'POST:log-idle':
        $workday = get_todays_workday($pdo, $uid, $today);
        if (!$workday) json_response(['ok' => true]); // silently ignore if not checked in
        $seconds = max(0, (int)($in['idle_seconds'] ?? 0));
        if ($seconds < 60) json_response(['ok' => true]); // ignore noise under a minute
        $pdo->prepare('INSERT INTO workday_idle_log (workday_id, idle_started_at, idle_ended_at, idle_seconds) VALUES (?, ?, NOW(), ?)')
            ->execute([$workday['id'], date('Y-m-d H:i:s', time() - $seconds), $seconds]);
        json_response(['ok' => true]);
        break;

    case 'POST:checkout':
        $workday = get_todays_workday($pdo, $uid, $today);
        if (!$workday) json_response(['error' => 'You have not checked in today'], 422);
        if ($workday['checkout_at']) json_response(['error' => 'Already checked out'], 422);

        $pdo->prepare('UPDATE workday_breaks SET ended_at = NOW() WHERE workday_id = ? AND ended_at IS NULL')->execute([$workday['id']]);

        $stmt = $pdo->prepare('SELECT * FROM workday_tasks WHERE workday_id = ? ORDER BY created_at ASC');
        $stmt->execute([$workday['id']]);
        $tasks = $stmt->fetchAll();

        $notes = clean_str($in['notes'] ?? '', 3000);
        $pdo->prepare('UPDATE workdays SET checkout_at = NOW(), notes = ? WHERE id = ?')->execute([$notes ?: null, $workday['id']]);

        $checkoutAt = date('Y-m-d H:i:s');
        $earlyBy = strtotime($today . ' ' . EXPECTED_CHECKOUT_TIME) - strtotime($checkoutAt);
        if ($earlyBy > 300) notify_chat('Early checkout', user_name($pdo, $uid) . ' checked out ' . fmt_duration($earlyBy) . ' early.');

        $stmt = $pdo->prepare('SELECT COALESCE(SUM(TIMESTAMPDIFF(SECOND, started_at, COALESCE(ended_at, NOW()))), 0) FROM workday_breaks WHERE workday_id = ?');
        $stmt->execute([$workday['id']]);
        $breakSeconds = (int)$stmt->fetchColumn();
        $workedSeconds = max(0, strtotime($checkoutAt) - strtotime($workday['checkin_at']) - $breakSeconds);
        $overtimeSeconds = max(0, $workedSeconds - STANDARD_WORK_MINUTES * 60);

        $done = array_filter($tasks, fn($t) => in_array($t['status'], ['done']));
        $pendingApproval = array_filter($tasks, fn($t) => $t['status'] === 'pending_approval');
        $pending = array_filter($tasks, fn($t) => $t['status'] === 'pending');
        $inProgress = array_filter($tasks, fn($t) => $t['status'] === 'in_progress');
        $totalSeconds = array_sum(array_map(fn($t) => (int)($t['duration_seconds'] ?? 0), $tasks));

        $summary = [
            'total' => count($tasks), 'done' => count($done), 'pending_approval' => count($pendingApproval),
            'pending' => count($pending), 'in_progress' => count($inProgress),
            'total_duration_seconds' => $totalSeconds, 'total_duration_text' => fmt_duration($totalSeconds),
            'worked_seconds' => $workedSeconds, 'worked_text' => fmt_duration($workedSeconds),
            'break_seconds' => $breakSeconds, 'break_text' => fmt_duration($breakSeconds),
            'overtime_seconds' => $overtimeSeconds, 'overtime_text' => $overtimeSeconds > 0 ? fmt_duration($overtimeSeconds) : null,
        ];

        // ── Build the recipient list ─────────────────────────────────
        // 1) Legacy per-day/per-task report-to emails (unchanged, kept
        //    for backward compatibility with existing check-ins).
        $legacyEmails = array_filter(array_merge([$workday['report_to_email']], array_map(fn($t) => $t['report_to_email'], $tasks)));

        // 2) A manager picked from the checkout screen. Must actually be an
        //    owner/manager on a team this user belongs to — never trust an
        //    arbitrary user_id from the client.
        $managerEmail = null;
        $managerUserId = !empty($in['manager_user_id']) ? (int)$in['manager_user_id'] : null;
        if ($managerUserId) {
            $stmt = $pdo->prepare(
                "SELECT u.email FROM team_members me
                 JOIN team_members tm ON tm.team_id = me.team_id AND tm.user_id = ? AND tm.role IN ('owner','manager')
                 JOIN users u ON u.id = tm.user_id
                 WHERE me.user_id = ? LIMIT 1"
            );
            $stmt->execute([$managerUserId, $uid]);
            $managerEmail = $stmt->fetchColumn() ?: null;
        }

        // 3) Any number of teammates picked from the checkout screen. Same
        //    must-share-a-team-with-me rule applies to every id.
        $teamMemberEmails = [];
        $teamMemberIds = array_values(array_unique(array_filter(array_map('intval', $in['team_member_user_ids'] ?? []))));
        if ($teamMemberIds) {
            $placeholders = implode(',', array_fill(0, count($teamMemberIds), '?'));
            $stmt = $pdo->prepare(
                "SELECT DISTINCT u.email FROM team_members me
                 JOIN team_members tm ON tm.team_id = me.team_id AND tm.user_id IN ($placeholders)
                 JOIN users u ON u.id = tm.user_id
                 WHERE me.user_id = ?"
            );
            $stmt->execute(array_merge($teamMemberIds, [$uid]));
            $teamMemberEmails = array_column($stmt->fetchAll(), 'email');
        }

        // 4) Any custom, non-Taskvel email addresses typed in directly.
        $customEmails = [];
        foreach (($in['custom_emails'] ?? []) as $raw) {
            $email = clean_email($raw);
            if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) $customEmails[] = $email;
        }

        $reportEmails = array_values(array_unique(array_merge($legacyEmails, array_filter([$managerEmail]), $teamMemberEmails, $customEmails)));

        if ($reportEmails) {
            $userName = user_name($pdo, $uid);
            $link = (!empty($_SERVER['HTTPS']) ? 'https://' : 'http://') . ($_SERVER['HTTP_HOST'] ?? '') . '/manager.php';
            foreach ($reportEmails as $email) {
                try { send_daily_report_email($email, $userName, $today, $summary, $tasks, $notes, $link); } catch (Throwable $e) {}
            }
            notify_chat('Day checked out', "$userName checked out — {$summary['done']} done, {$summary['worked_text']} worked");
            $pdo->prepare('UPDATE workdays SET summary_sent = 1 WHERE id = ?')->execute([$workday['id']]);
            audit_log($uid, 'daily_report_sent', [
                'workday_id' => $workday['id'], 'recipient_count' => count($reportEmails),
                'manager_included' => (bool)$managerEmail, 'team_members_included' => count($teamMemberEmails), 'custom_emails_included' => count($customEmails),
            ]);
        }

        json_response(['ok' => true, 'summary' => $summary, 'notified' => $reportEmails]);
        break;

    default:
        json_response(['error' => 'Unknown route'], 404);
}