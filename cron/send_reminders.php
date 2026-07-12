<?php
/**
 * Daily deadline reminder digest for the personal Taskvel app.
 *
 * Run this once a day (e.g. via a cron job at 8am):
 *   0 8 * * *  php /path/to/taskvel-php/cron/send_reminders.php
 *
 * Personal tasks live in the `user_state` JSON blob (see api/state.php),
 * not a relational table, so this scans each user's saved state for
 * overdue / due-today tasks and sends ONE push digest per user per day
 * (tracked in push_digest_log so re-running the cron never double-sends).
 *
 * Team/project task push notifications (assignment, completion) are sent
 * immediately and separately from api/project_tasks.php — this script is
 * only for the personal app's own deadlines.
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/webpush.php';

$pdo = db();
$today = date('Y-m-d');

$stmt = $pdo->query('SELECT us.user_id, us.state_json, u.name
                      FROM user_state us
                      JOIN users u ON u.id = us.user_id
                      WHERE us.user_id NOT IN (SELECT user_id FROM push_digest_log WHERE sent_date = CURDATE())');

$sent = 0;
foreach ($stmt->fetchAll() as $row) {
    $state = json_decode($row['state_json'], true);
    if (!$state || empty($state['tasks'])) continue;

    $overdue = 0; $dueToday = 0; $firstName = null;
    foreach ($state['tasks'] as $t) {
        if (!empty($t['done']) || empty($t['deadline'])) continue;
        if ($t['deadline'] < $today) { $overdue++; $firstName = $firstName ?? $t['name']; }
        elseif ($t['deadline'] === $today) { $dueToday++; $firstName = $firstName ?? $t['name']; }
    }
    if (!$overdue && !$dueToday) continue;

    $bits = [];
    if ($overdue) $bits[] = "$overdue overdue";
    if ($dueToday) $bits[] = "$dueToday due today";
    $body = implode(', ', $bits) . ($firstName ? " — including \"$firstName\"" : '');

    try {
        send_web_push_to_user($pdo, (int)$row['user_id'], [
            'title' => 'Taskvel — you have tasks that need attention',
            'body'  => $body,
            'url'   => './taskvel-pro.php',
            'tag'   => 'taskvel-daily-digest',
        ]);
        $pdo->prepare('INSERT INTO push_digest_log (user_id, sent_date) VALUES (?, CURDATE())
                       ON DUPLICATE KEY UPDATE sent_date = sent_date')->execute([$row['user_id']]);
        $sent++;
    } catch (Throwable $e) {
        fwrite(STDERR, "Failed to push to user {$row['user_id']}: {$e->getMessage()}\n");
    }
}

echo "Sent $sent reminder digest(s)\n";
