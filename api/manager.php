<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/mailer.php';
require_once __DIR__ . '/../config/workhours.php';
require_login();

$me = current_user();
$myEmail = $me['email'];
$pdo = db();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$in = body();

function date_range_bounds(string $range, ?string $from, ?string $to): array
{
    $today = date('Y-m-d');
    if ($range === 'week') return [date('Y-m-d', strtotime('monday this week')), $today];
    if ($range === 'month') return [date('Y-m-01'), $today];
    if ($range === 'custom' && $from && $to) return [$from, $to];
    return [$today, $today]; // 'today' default
}

switch ("$method:$action") {

    // Everything the manager dashboard needs: every task reported to me
    // (via a task-level or day-level report-to email match), grouped by
    // employee, within a date range — "one by one or all at once".
    case 'GET:overview':
        [$from, $to] = date_range_bounds($_GET['range'] ?? 'today', $_GET['from'] ?? null, $_GET['to'] ?? null);

        $stmt = $pdo->prepare('SELECT wt.*, w.work_date, w.user_id, w.checkin_at, w.checkout_at, u.name AS employee_name, u.email AS employee_email
                               FROM workday_tasks wt
                               JOIN workdays w ON w.id = wt.workday_id
                               JOIN users u ON u.id = w.user_id
                               WHERE w.work_date BETWEEN ? AND ?
                                 AND (wt.report_to_email = ? OR w.report_to_email = ?)
                               ORDER BY w.work_date DESC, wt.created_at ASC');
        $stmt->execute([$from, $to, $myEmail, $myEmail]);
        $tasks = $stmt->fetchAll();

        // Per-employee rollup
        $byEmployee = [];
        foreach ($tasks as $t) {
            $key = $t['user_id'];
            if (!isset($byEmployee[$key])) {
                $byEmployee[$key] = [
                    'user_id' => (int)$t['user_id'], 'name' => $t['employee_name'], 'email' => $t['employee_email'],
                    'done' => 0, 'pending_approval' => 0, 'in_progress' => 0, 'pending' => 0,
                    'total_seconds' => 0, 'completed_durations' => [],
                ];
            }
            $byEmployee[$key][$t['status']]++;
            if ($t['duration_seconds']) {
                $byEmployee[$key]['total_seconds'] += (int)$t['duration_seconds'];
                $byEmployee[$key]['completed_durations'][] = (int)$t['duration_seconds'];
            }
        }
        foreach ($byEmployee as &$e) {
            $durs = $e['completed_durations'];
            $e['avg_seconds'] = $durs ? (int)round(array_sum($durs) / count($durs)) : 0;
            unset($e['completed_durations']);
        }
        unset($e);

        // Late check-in / early checkout / overtime flags (distinct workdays in range)
        $stmt = $pdo->prepare('SELECT DISTINCT w.id, w.user_id, w.work_date, w.checkin_at, w.checkout_at, u.name AS employee_name
                               FROM workdays w JOIN users u ON u.id = w.user_id
                               WHERE w.work_date BETWEEN ? AND ?
                                 AND (w.report_to_email = ? OR EXISTS (
                                     SELECT 1 FROM workday_tasks wt2 WHERE wt2.workday_id = w.id AND wt2.report_to_email = ?
                                 ))');
        $stmt->execute([$from, $to, $myEmail, $myEmail]);
        $workdays = $stmt->fetchAll();
        $flags = ['late_checkin' => [], 'early_checkout' => [], 'overtime' => []];
        foreach ($workdays as $w) {
            $dayStr = $w['work_date'];
            if (strtotime($w['checkin_at']) > strtotime("$dayStr " . EXPECTED_CHECKIN_TIME) + 300) {
                $flags['late_checkin'][] = ['name' => $w['employee_name'], 'date' => $dayStr, 'time' => date('H:i', strtotime($w['checkin_at']))];
            }
            if ($w['checkout_at'] && strtotime($w['checkout_at']) < strtotime("$dayStr " . EXPECTED_CHECKOUT_TIME) - 300) {
                $flags['early_checkout'][] = ['name' => $w['employee_name'], 'date' => $dayStr, 'time' => date('H:i', strtotime($w['checkout_at']))];
            }
            if ($w['checkout_at']) {
                $worked = strtotime($w['checkout_at']) - strtotime($w['checkin_at']);
                if ($worked > STANDARD_WORK_MINUTES * 60) {
                    $flags['overtime'][] = ['name' => $w['employee_name'], 'date' => $dayStr, 'minutes' => (int)round(($worked - STANDARD_WORK_MINUTES * 60) / 60)];
                }
            }
        }

        // Overdue: in-progress tasks that have blown past their expected_minutes soft SLA
        $overdue = array_values(array_filter($tasks, function ($t) {
            if ($t['status'] !== 'in_progress' || !$t['expected_minutes'] || !$t['started_at']) return false;
            return (time() - strtotime($t['started_at'])) > $t['expected_minutes'] * 60;
        }));

        // 7-day trend (tasks completed per day, across everyone reporting to me)
        $stmt = $pdo->prepare('SELECT w.work_date, COUNT(*) AS done_count
                               FROM workday_tasks wt JOIN workdays w ON w.id = wt.workday_id
                               WHERE wt.status = "done" AND w.work_date >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
                                 AND (wt.report_to_email = ? OR w.report_to_email = ?)
                               GROUP BY w.work_date ORDER BY w.work_date ASC');
        $stmt->execute([$myEmail, $myEmail]);
        $trendRaw = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        $trend = [];
        for ($i = 6; $i >= 0; $i--) {
            $d = date('Y-m-d', strtotime("-$i days"));
            $trend[] = ['date' => $d, 'count' => (int)($trendRaw[$d] ?? 0)];
        }

        json_response([
            'tasks' => $tasks,
            'employees' => array_values($byEmployee),
            'flags' => $flags,
            'overdue' => $overdue,
            'trend' => $trend,
            'range' => ['from' => $from, 'to' => $to],
        ]);
        break;

    // Manager approves/rejects a task directly from the dashboard (same
    // effect as the emailed link, but doesn't need a click-through).
    case 'POST:approve':
    case 'POST:reject':
        $taskId = (int)($in['task_id'] ?? 0);
        $stmt = $pdo->prepare('SELECT wt.*, w.user_id, u.email AS employee_email FROM workday_tasks wt
                               JOIN workdays w ON w.id = wt.workday_id JOIN users u ON u.id = w.user_id WHERE wt.id = ?');
        $stmt->execute([$taskId]);
        $task = $stmt->fetch();
        if (!$task || $task['report_to_email'] !== $myEmail) json_response(['error' => 'Not found or not yours to approve'], 404);
        if ($task['approval_status'] !== 'pending') json_response(['error' => 'Already decided'], 409);

        if ($action === 'approve') {
            $pdo->prepare("UPDATE workday_tasks SET status = 'done', approval_status = 'approved' WHERE id = ?")->execute([$taskId]);
        } else {
            $pdo->prepare("UPDATE workday_tasks SET status = 'in_progress', approval_status = 'rejected', completed_at = NULL WHERE id = ?")->execute([$taskId]);
        }
        try {
            $verb = $action === 'approve' ? 'approved ✓' : 'sent back for more work';
            send_mail($task['employee_email'], "Your task was $verb — \"{$task['title']}\"",
                "<p>\"{$task['title']}\" was just <strong>$verb</strong> by {$me['name']}.</p>");
        } catch (Throwable $e) {}
        json_response(['ok' => true]);
        break;

    // CSV export of the currently filtered task list.
    case 'GET:export-csv':
        [$from, $to] = date_range_bounds($_GET['range'] ?? 'today', $_GET['from'] ?? null, $_GET['to'] ?? null);
        $stmt = $pdo->prepare('SELECT w.work_date, u.name AS employee, wt.title, wt.status, wt.duration_seconds, wt.started_at, wt.completed_at
                               FROM workday_tasks wt JOIN workdays w ON w.id = wt.workday_id JOIN users u ON u.id = w.user_id
                               WHERE w.work_date BETWEEN ? AND ? AND (wt.report_to_email = ? OR w.report_to_email = ?)
                               ORDER BY w.work_date DESC');
        $stmt->execute([$from, $to, $myEmail, $myEmail]);
        $rows = $stmt->fetchAll();
        header('Content-Type: text/csv');
        header('X-Content-Type-Options: nosniff');
        header('Content-Disposition: attachment; filename="taskvel-team-report.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['Date', 'Employee', 'Task', 'Status', 'Duration (min)', 'Started', 'Completed']);
        foreach ($rows as $r) {
            fputcsv($out, array_map('csv_safe', [$r['work_date'], $r['employee'], $r['title'], $r['status'],
                $r['duration_seconds'] ? round($r['duration_seconds'] / 60) : '', $r['started_at'], $r['completed_at']]));
        }
        fclose($out);
        exit;

    default:
        json_response(['error' => 'Unknown route'], 404);
}
