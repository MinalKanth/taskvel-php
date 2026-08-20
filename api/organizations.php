<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../includes/licensing.php';
require_once __DIR__ . '/../includes/notifications.php';
require_once __DIR__ . '/../includes/mailer.php';
require_once __DIR__ . '/../includes/security.php';
require_login();

$uid = current_user_id();
$pdo = db();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$in = body();

switch ("$method:$action") {

    // The org the current user belongs to (if any) — used by billing.php
    // to decide what to show, and by anyone (not just admins) to see their
    // own membership/status.
    case 'GET:mine':
        $membership = user_organization_membership($uid);
        json_response(['membership' => $membership]);
        break;

    case 'POST:create':
        $existing = user_organization_membership($uid);
        if ($existing) json_response(['error' => 'You already belong to an organization. Leave it first to create a new one.'], 422);

        $name = clean_str($in['name'] ?? '', 150);
        if ($name === '') json_response(['error' => 'Organization name is required'], 422);
        $billingCycle = one_of($in['billing_cycle'] ?? 'monthly', ['monthly', 'yearly'], 'monthly');
        $seats = max(1, (int)($in['seats'] ?? 5));

        $pdo->beginTransaction();
        // seats_purchased starts at 0 — the stripe webhook's org:<id>:seats:<n>
        // handler adds the paid seat count once checkout.session.completed
        // fires (same additive path used for "add more seats" later, so we
        // never double-count by also setting seats here).
        $pdo->prepare('INSERT INTO organizations (name, owner_user_id, billing_cycle, seats_purchased, plan_status, renewal_date)
                       VALUES (?, ?, ?, 0, \'pending\', DATE_ADD(CURDATE(), INTERVAL 1 ' . ($billingCycle === 'yearly' ? 'YEAR' : 'MONTH') . '))')
            ->execute([$name, $uid, $billingCycle]);
        $orgId = (int)$pdo->lastInsertId();
        $pdo->prepare('INSERT INTO organization_members (organization_id, user_id, role, joined_at) VALUES (?, ?, \'owner\', NOW())')
            ->execute([$orgId, $uid]);
        // Real payment happens through Stripe Checkout (see api/billing.php)
        // and this row is what api/stripe-webhook.php reconciles against
        // once payment is confirmed — recorded here as pending so the
        // dashboard has an entry immediately.
        $pdo->prepare('INSERT INTO organization_billing_history (organization_id, description, seats, billing_cycle) VALUES (?, ?, ?, ?)')
            ->execute([$orgId, "Initial plan — $seats seats", $seats, $billingCycle]);
        $pdo->commit();

        recompute_user_plan($uid);
        audit_log($uid, 'org_created', ['organization_id' => $orgId, 'seats' => $seats]);

        json_response(['ok' => true, 'organization_id' => $orgId, 'seats' => $seats]);
        break;

    // Dashboard: seats purchased/assigned/available, plan, renewal, status,
    // recent invites.
    case 'GET:dashboard':
        $orgId = (int)($_GET['org_id'] ?? 0);
        require_org_member($orgId);

        $stmt = $pdo->prepare('SELECT * FROM organizations WHERE id = ?');
        $stmt->execute([$orgId]);
        $org = $stmt->fetch();
        if (!$org) json_response(['error' => 'Not found'], 404);

        $stmt = $pdo->prepare(
            "SELECT om.*, u.name, u.email FROM organization_members om
             JOIN users u ON u.id = om.user_id
             WHERE om.organization_id = ? ORDER BY om.invited_at DESC LIMIT 10"
        );
        $stmt->execute([$orgId]);
        $recent = $stmt->fetchAll();

        json_response([
            'organization' => $org,
            'seats' => org_seat_counts($orgId),
            'my_role' => org_role($orgId, $uid),
            'recently_invited' => $recent,
        ]);
        break;

    case 'GET:members':
        $orgId = (int)($_GET['org_id'] ?? 0);
        require_org_member($orgId);
        $stmt = $pdo->prepare(
            "SELECT om.id, om.role, om.status, om.invited_at, om.joined_at, u.id AS user_id, u.name, u.email
             FROM organization_members om JOIN users u ON u.id = om.user_id
             WHERE om.organization_id = ? ORDER BY FIELD(om.role,'owner','admin','employee'), u.name"
        );
        $stmt->execute([$orgId]);
        json_response(['members' => $stmt->fetchAll()]);
        break;

    case 'GET:billing-history':
        $orgId = (int)($_GET['org_id'] ?? 0);
        require_org_admin($orgId);
        $stmt = $pdo->prepare('SELECT * FROM organization_billing_history WHERE organization_id = ? ORDER BY created_at DESC');
        $stmt->execute([$orgId]);
        json_response(['history' => $stmt->fetchAll()]);
        break;

    // "Add employees using their email addresses ... existing or new users."
    case 'POST:invite':
        $orgId = (int)($in['org_id'] ?? 0);
        require_org_admin($orgId);
        $email = clean_email($in['email'] ?? '');
        $role = $in['role'] ?? 'employee';

        $stmt = $pdo->prepare('SELECT name FROM organizations WHERE id = ?');
        $stmt->execute([$orgId]);
        $orgName = $stmt->fetchColumn();

        $result = assign_org_seat($orgId, $email, $role, $uid);
        if (!$result['ok']) json_response(['error' => $result['error']], 422);

        $loginUrl = (!empty($_SERVER['HTTPS']) ? 'https://' : 'http://') . ($_SERVER['HTTP_HOST'] ?? '') . '/login.php';
        $emailSent = false;
        try {
            if ($result['is_new_user']) {
                $emailSent = send_org_onboarding_email($email, $result['name'], $orgName, $loginUrl, $result['temp_password']);
            } else {
                $emailSent = send_org_added_email($email, $result['name'], $orgName, $loginUrl);
            }
            if (!$emailSent) error_log("[org-invite] send_mail() returned false for $email — check SMTP_HOST/SMTP_PORT/SMTP_USER/SMTP_PASS in config/db.php and your PHP error log for the specific SMTP step that failed.");
        } catch (Throwable $e) {
            error_log('[org-invite] Email send threw for ' . $email . ': ' . $e->getMessage());
        }

        json_response(['ok' => true, 'user_id' => $result['user_id'], 'is_new_user' => $result['is_new_user'], 'email_sent' => $emailSent]);
        break;

    // "Add additional seats at any time."
    case 'POST:add-seats':
        $orgId = (int)($in['org_id'] ?? 0);
        require_org_admin($orgId);
        $additional = max(1, (int)($in['seats'] ?? 0));

        $pdo->prepare('UPDATE organizations SET seats_purchased = seats_purchased + ? WHERE id = ?')->execute([$additional, $orgId]);
        $stmt = $pdo->prepare('SELECT billing_cycle FROM organizations WHERE id = ?');
        $stmt->execute([$orgId]);
        $cycle = $stmt->fetchColumn();
        $pdo->prepare('INSERT INTO organization_billing_history (organization_id, description, seats, billing_cycle) VALUES (?, ?, ?, ?)')
            ->execute([$orgId, "Added $additional seat(s)", $additional, $cycle]);

        audit_log($uid, 'org_seats_added', ['organization_id' => $orgId, 'seats_added' => $additional]);
        json_response(['ok' => true, 'seats' => org_seat_counts($orgId)]);
        break;

    // "Remove users from seats" — releases the seat entirely.
    case 'POST:remove-member':
        $orgId = (int)($in['org_id'] ?? 0);
        $targetUserId = (int)($in['user_id'] ?? 0);
        require_org_admin($orgId);
        if (org_role($orgId, $targetUserId) === 'owner') json_response(['error' => 'Cannot remove the organization owner'], 403);
        release_org_seat($orgId, $targetUserId, $uid, 'removed');
        json_response(['ok' => true]);
        break;

    // "Suspend users without deleting their data" — keeps the seat reserved,
    // revokes Pro access until reactivated.
    case 'POST:suspend-member':
        $orgId = (int)($in['org_id'] ?? 0);
        $targetUserId = (int)($in['user_id'] ?? 0);
        require_org_admin($orgId);
        if (org_role($orgId, $targetUserId) === 'owner') json_response(['error' => 'Cannot suspend the organization owner'], 403);
        $pdo->prepare("UPDATE organization_members SET status = 'suspended' WHERE organization_id = ? AND user_id = ?")->execute([$orgId, $targetUserId]);
        recompute_user_plan($targetUserId);
        audit_log($uid, 'org_member_suspended', ['organization_id' => $orgId, 'target_user_id' => $targetUserId]);
        json_response(['ok' => true]);
        break;

    case 'POST:reactivate-member':
        $orgId = (int)($in['org_id'] ?? 0);
        $targetUserId = (int)($in['user_id'] ?? 0);
        require_org_admin($orgId);
        $pdo->prepare("UPDATE organization_members SET status = 'active' WHERE organization_id = ? AND user_id = ?")->execute([$orgId, $targetUserId]);
        recompute_user_plan($targetUserId);
        audit_log($uid, 'org_member_reactivated', ['organization_id' => $orgId, 'target_user_id' => $targetUserId]);
        json_response(['ok' => true]);
        break;

    // "Transfer seats between employees" — atomically frees one seat and
    // assigns it to someone else, so the net seat count never changes and
    // no window exists where the seat looks free to a concurrent invite.
    case 'POST:transfer-seat':
        $orgId = (int)($in['org_id'] ?? 0);
        $fromUserId = (int)($in['from_user_id'] ?? 0);
        $toEmail = clean_email($in['to_email'] ?? '');
        require_org_admin($orgId);
        if (org_role($orgId, $fromUserId) === 'owner') json_response(['error' => 'Cannot transfer the organization owner\'s seat'], 403);

        $pdo->beginTransaction();
        try {
            $pdo->prepare('DELETE FROM organization_members WHERE organization_id = ? AND user_id = ?')->execute([$orgId, $fromUserId]);
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            json_response(['error' => 'Could not release the source seat'], 500);
        }
        recompute_user_plan($fromUserId);

        $stmt = $pdo->prepare('SELECT name FROM organizations WHERE id = ?');
        $stmt->execute([$orgId]);
        $orgName = $stmt->fetchColumn();

        $result = assign_org_seat($orgId, $toEmail, 'employee', $uid);
        if (!$result['ok']) json_response(['error' => 'Seat released from the old member, but assigning it failed: ' . $result['error']], 422);

        $loginUrl = (!empty($_SERVER['HTTPS']) ? 'https://' : 'http://') . ($_SERVER['HTTP_HOST'] ?? '') . '/login.php';
        try {
            if ($result['is_new_user']) send_org_onboarding_email($toEmail, $result['name'], $orgName, $loginUrl, $result['temp_password']);
            else send_org_added_email($toEmail, $result['name'], $orgName, $loginUrl);
        } catch (Throwable $e) {}

        audit_log($uid, 'org_seat_transferred', ['organization_id' => $orgId, 'from_user_id' => $fromUserId, 'to_email' => $toEmail]);
        json_response(['ok' => true]);
        break;

    default:
        json_response(['error' => 'Unknown route'], 404);
}