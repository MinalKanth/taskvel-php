<?php
// Run once daily, e.g. crontab: 0 6 * * * php /path/to/cron/org_grace_check.php
require_once __DIR__ . '/../includes/licensing.php';
require_once __DIR__ . '/../includes/mailer.php';

$pdo = db();

$stmt = $pdo->query("SELECT * FROM organizations WHERE plan_status = 'active' AND renewal_date < CURDATE()");
foreach ($stmt->fetchAll() as $org) {
    recompute_org_grace_status($org);
}

$stmt = $pdo->query("SELECT * FROM organizations WHERE plan_status = 'past_due'");
foreach ($stmt->fetchAll() as $org) {
    $daysLeft = (int)floor((strtotime($org['grace_ends_at']) - strtotime(date('Y-m-d'))) / 86400);

    if ($daysLeft === 3 || $daysLeft === 1) {
        $milestone = $daysLeft . 'd';
        $already = $pdo->prepare('SELECT 1 FROM org_billing_reminder_log WHERE organization_id = ? AND milestone = ?');
        $already->execute([$org['id'], $milestone]);
        if (!$already->fetchColumn()) {
            $ownerStmt = $pdo->prepare("SELECT u.email, u.name FROM organization_members om JOIN users u ON u.id = om.user_id WHERE om.organization_id = ? AND om.role = 'owner' LIMIT 1");
            $ownerStmt->execute([$org['id']]);
            if ($owner = $ownerStmt->fetch()) {
                send_org_grace_reminder_email($owner['email'], $owner['name'], $org['name'], $daysLeft);
            }
            $pdo->prepare('INSERT INTO org_billing_reminder_log (organization_id, milestone) VALUES (?, ?)')->execute([$org['id'], $milestone]);
        }
    }

    if ($org['grace_ends_at'] < date('Y-m-d')) {
        recompute_org_grace_status($org);
        $already = $pdo->prepare("SELECT 1 FROM org_billing_reminder_log WHERE organization_id = ? AND milestone = 'locked'");
        $already->execute([$org['id']]);
        if (!$already->fetchColumn()) {
            $ownerStmt = $pdo->prepare("SELECT u.email, u.name FROM organization_members om JOIN users u ON u.id = om.user_id WHERE om.organization_id = ? AND om.role = 'owner' LIMIT 1");
            $ownerStmt->execute([$org['id']]);
            if ($owner = $ownerStmt->fetch()) {
                send_org_locked_email($owner['email'], $owner['name'], $org['name']);
            }
            $pdo->prepare("INSERT INTO org_billing_reminder_log (organization_id, milestone) VALUES (?, 'locked')")->execute([$org['id']]);
        }
    }
}
echo "Org grace check complete.\n";