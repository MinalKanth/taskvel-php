<?php
/**
 * Feature 4, Part A — trial reminders + expiry.
 *
 * Run this once a day (e.g. via cron at 8am):
 *   0 8 * * *  php /path/to/taskvel-php/cron/send_trial_reminders.php
 *
 * Sends an in-app notification + email at 7/3/1 days before a trial ends,
 * and again on the day it expires — then flips plan_source='trial' users
 * whose trial has actually passed back to 'free' via recompute_user_plan(),
 * which never touches a real Stripe subscriber or an org-seat holder.
 * trial_reminder_log makes every send idempotent, so re-running this script
 * (or running it late) never double-emails anyone.
 */
require_once __DIR__ . '/../includes/licensing.php';
require_once __DIR__ . '/../includes/notifications.php';
require_once __DIR__ . '/../includes/mailer.php';

$pdo = db();
$sent = 0;

$milestones = [
    '7d' => 7,
    '3d' => 3,
    '1d' => 1,
];

foreach ($milestones as $milestone => $daysBefore) {
    $stmt = $pdo->prepare(
        "SELECT id, name, email FROM users
         WHERE plan_source = 'trial' AND plan = 'pro'
           AND trial_ends_at IS NOT NULL
           AND DATE(trial_ends_at) = DATE_ADD(CURDATE(), INTERVAL ? DAY)
           AND id NOT IN (SELECT user_id FROM trial_reminder_log WHERE milestone = ?)"
    );
    $stmt->execute([$daysBefore, $milestone]);

    foreach ($stmt->fetchAll() as $u) {
        $link = './billing.php';
        try {
            create_notification($pdo, (int)$u['id'], 'trial_reminder', "Your Pro trial ends in $daysBefore day" . ($daysBefore > 1 ? 's' : ''), null, $link);
            send_trial_reminder_email($u['email'], $u['name'], $milestone, $link);
            $pdo->prepare('INSERT INTO trial_reminder_log (user_id, milestone) VALUES (?, ?)')->execute([$u['id'], $milestone]);
            $sent++;
        } catch (Throwable $e) {
            fwrite(STDERR, "Failed $milestone reminder for user {$u['id']}: {$e->getMessage()}\n");
        }
    }
}

// Trials that expired today (or earlier, in case the cron missed a day) —
// notify once, then downgrade.
$stmt = $pdo->prepare(
    "SELECT id, name, email FROM users
     WHERE plan_source = 'trial' AND plan = 'pro'
       AND trial_ends_at IS NOT NULL AND trial_ends_at <= NOW()
       AND id NOT IN (SELECT user_id FROM trial_reminder_log WHERE milestone = 'expired')"
);
$stmt->execute();
foreach ($stmt->fetchAll() as $u) {
    $link = './billing.php';
    try {
        create_notification($pdo, (int)$u['id'], 'trial_expired', 'Your Pro trial has ended', 'Upgrade to keep unlimited teams, seats, and projects.', $link);
        send_trial_reminder_email($u['email'], $u['name'], 'expired', $link);
        $pdo->prepare('INSERT INTO trial_reminder_log (user_id, milestone) VALUES (?, \'expired\')')->execute([$u['id']]);
        $sent++;
    } catch (Throwable $e) {
        fwrite(STDERR, "Failed expiry notice for user {$u['id']}: {$e->getMessage()}\n");
    }
    recompute_user_plan((int)$u['id']); // downgrades to free unless something else (shouldn't, given the WHERE above) grants pro
}

echo "Sent $sent trial notice(s)\n";