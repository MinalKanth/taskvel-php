<?php
// ONE-TIME SCRIPT — run this once from the command line (or once via
// browser if you must), then you can delete it.
//
//   php cron/backfill_recompute_all_plans.php
//
// Forces every existing user's plan to the correct current state right
// now, using the exact same precedence logic as everywhere else in the
// app (org seat > Stripe > admin-granted > trial > free) — instead of
// waiting for each person's next login to trigger it. This is what drops
// any legacy/unrestricted account straight to Free immediately.
require_once __DIR__ . '/../includes/licensing.php';

$pdo = db();
$ids = $pdo->query('SELECT id FROM users')->fetchAll(PDO::FETCH_COLUMN);

$changed = 0;
foreach ($ids as $id) {
    $before = $pdo->prepare('SELECT plan, plan_source FROM users WHERE id = ?');
    $before->execute([$id]);
    $b = $before->fetch();

    recompute_user_plan((int)$id);

    $after = $pdo->prepare('SELECT plan, plan_source FROM users WHERE id = ?');
    $after->execute([$id]);
    $a = $after->fetch();

    if ($b['plan'] !== $a['plan'] || $b['plan_source'] !== $a['plan_source']) {
        $changed++;
        echo "User #$id: {$b['plan']}/{$b['plan_source']} -> {$a['plan']}/{$a['plan_source']}\n";
    }
}

echo "\nDone. Checked " . count($ids) . " users, changed $changed.\n";