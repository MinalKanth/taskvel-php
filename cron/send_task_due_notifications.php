<?php
// Run every 5 minutes via cron. Sends a push when a task is ~1 hour from
// its due time ("due soon"), and once more the moment it passes due
// ("overdue") — each stage fires exactly once per task.

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/webpush.php';

$pdo = db();

// ────────────────────────────────────────────────────────────
// 1. PERSONAL TASKS (tasks table) — has both due_date and due_time
// ────────────────────────────────────────────────────────────

// Due soon: due datetime falls within the next hour, not yet warned, not done.
$stmt = $pdo->query(
    "SELECT id, owner_id, title,
            CONCAT(due_date, ' ', COALESCE(due_time, '23:59:59')) AS due_at
     FROM tasks
     WHERE due_date IS NOT NULL
       AND status != 'done'
       AND due_soon_notified_at IS NULL
       AND CONCAT(due_date, ' ', COALESCE(due_time, '23:59:59'))
           BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 1 HOUR)"
);
foreach ($stmt->fetchAll() as $t) {
    try {
        send_web_push_to_user($pdo, (int)$t['owner_id'], [
            'title' => 'Task due soon',
            'body'  => "\"{$t['title']}\" is due within the hour.",
            'url'   => './taskvel-pro.php',
            'tag'   => 'taskvel-task-due-soon-' . $t['id'],
        ]);
    } catch (Throwable $e) { error_log('due-soon push failed for task ' . $t['id'] . ': ' . $e->getMessage()); }

    $pdo->prepare('UPDATE tasks SET due_soon_notified_at = NOW() WHERE id = ?')->execute([$t['id']]);
}

// Overdue: due datetime has passed, not yet warned, not done. Fires once only.
$stmt = $pdo->query(
    "SELECT id, owner_id, title,
            CONCAT(due_date, ' ', COALESCE(due_time, '23:59:59')) AS due_at
     FROM tasks
     WHERE due_date IS NOT NULL
       AND status != 'done'
       AND overdue_notified_at IS NULL
       AND CONCAT(due_date, ' ', COALESCE(due_time, '23:59:59')) < NOW()"
);
foreach ($stmt->fetchAll() as $t) {
    try {
        send_web_push_to_user($pdo, (int)$t['owner_id'], [
            'title' => 'Task overdue',
            'body'  => "\"{$t['title']}\" was due and is still pending.",
            'url'   => './taskvel-pro.php',
            'tag'   => 'taskvel-task-overdue-' . $t['id'],
        ]);
    } catch (Throwable $e) { error_log('overdue push failed for task ' . $t['id'] . ': ' . $e->getMessage()); }

    $pdo->prepare('UPDATE tasks SET overdue_notified_at = NOW() WHERE id = ?')->execute([$t['id']]);
}

// ────────────────────────────────────────────────────────────
// 2. TEAM TASKS (team_tasks table) — only has due_date, no due_time,
//    so "due" is treated as end of that calendar day (23:59:59).
//    Only notifies the assignee; unassigned tasks are skipped.
// ────────────────────────────────────────────────────────────

$stmt = $pdo->query(
    "SELECT id, assignee_id, title, team_id
     FROM team_tasks
     WHERE due_date IS NOT NULL
       AND assignee_id IS NOT NULL
       AND status != 'done'
       AND due_soon_notified_at IS NULL
       AND CONCAT(due_date, ' 23:59:59')
           BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 1 HOUR)"
);
foreach ($stmt->fetchAll() as $t) {
    try {
        send_web_push_to_user($pdo, (int)$t['assignee_id'], [
            'title' => 'Task due soon',
            'body'  => "\"{$t['title']}\" is due within the hour.",
            'url'   => './team.php?id=' . $t['team_id'] . '#team-tasks',
            'tag'   => 'taskvel-team-task-due-soon-' . $t['id'],
        ]);
    } catch (Throwable $e) { error_log('team due-soon push failed for task ' . $t['id'] . ': ' . $e->getMessage()); }

    $pdo->prepare('UPDATE team_tasks SET due_soon_notified_at = NOW() WHERE id = ?')->execute([$t['id']]);
}

$stmt = $pdo->query(
    "SELECT id, assignee_id, title, team_id
     FROM team_tasks
     WHERE due_date IS NOT NULL
       AND assignee_id IS NOT NULL
       AND status != 'done'
       AND overdue_notified_at IS NULL
       AND CONCAT(due_date, ' 23:59:59') < NOW()"
);
foreach ($stmt->fetchAll() as $t) {
    try {
        send_web_push_to_user($pdo, (int)$t['assignee_id'], [
            'title' => 'Task overdue',
            'body'  => "\"{$t['title']}\" was due and is still pending.",
            'url'   => './team.php?id=' . $t['team_id'] . '#team-tasks',
            'tag'   => 'taskvel-team-task-overdue-' . $t['id'],
        ]);
    } catch (Throwable $e) { error_log('team overdue push failed for task ' . $t['id'] . ': ' . $e->getMessage()); }

    $pdo->prepare('UPDATE team_tasks SET overdue_notified_at = NOW() WHERE id = ?')->execute([$t['id']]);
}

echo "Task due-date notification sweep completed at " . date('Y-m-d H:i:s') . "\n";