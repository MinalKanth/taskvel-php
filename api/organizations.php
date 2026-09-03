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
        // Always the end of the current calendar month/year — not "1
        // month from signup day" — so every future grace cycle lands on
        // a real calendar boundary.
        $renewalDate = $billingCycle === 'yearly' ? date('Y-12-31') : date('Y-m-t');
        $pdo->prepare('INSERT INTO organizations (name, owner_user_id, billing_cycle, seats_purchased, plan_status, renewal_date)
                       VALUES (?, ?, ?, 0, \'pending\', ?)')
            ->execute([$name, $uid, $billingCycle, $renewalDate]);
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

            // Per-employee task progress across the whole organization — powers
    // the "Team dashboard" the owner/admin sees, showing everyone's
    // personal task completion at a glance.
    case 'GET:progress':
        $orgId = (int)($_GET['org_id'] ?? 0);
        require_org_admin($orgId);

        $stmt = $pdo->prepare(
            "SELECT om.user_id, om.role, u.name, u.email,
                    COUNT(pt.id) AS total_tasks,
                    SUM(pt.done = 1) AS done_tasks,
                    SUM(pt.done = 0) AS open_tasks,
                    SUM(pt.done = 0 AND pt.deadline IS NOT NULL AND pt.deadline < CURDATE()) AS overdue_tasks,
                    MAX(pt.done_at) AS last_activity
             FROM organization_members om
             JOIN users u ON u.id = om.user_id
             LEFT JOIN personal_tasks pt ON pt.user_id = om.user_id
             WHERE om.organization_id = ?
             GROUP BY om.user_id, om.role, u.name, u.email
             ORDER BY FIELD(om.role,'owner','admin','employee'), u.name"
        );
        $stmt->execute([$orgId]);
        json_response(['progress' => $stmt->fetchAll()]);
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

    // Full-org report — one row per employee with their task counts, so an
    // owner/admin can download the whole team's standing in one file
    // instead of screenshotting the dashboard. Same fputcsv pattern as
    // the personal export in api/export.php.
    case 'GET:export-report':
        $orgId = (int)($_GET['org_id'] ?? 0);
        require_org_admin($orgId);

        $stmt = $pdo->prepare(
            "SELECT u.name, u.email, om.role,
                    COUNT(pt.id) AS total_tasks,
                    SUM(pt.done = 1) AS done_tasks,
                    SUM(pt.done = 0) AS open_tasks,
                    SUM(pt.done = 0 AND pt.deadline IS NOT NULL AND pt.deadline < CURDATE()) AS overdue_tasks,
                    MAX(pt.done_at) AS last_activity
             FROM organization_members om
             JOIN users u ON u.id = om.user_id
             LEFT JOIN personal_tasks pt ON pt.user_id = om.user_id
             WHERE om.organization_id = ?
             GROUP BY u.id, u.name, u.email, om.role
             ORDER BY FIELD(om.role,'owner','admin','employee'), u.name"
        );
        $stmt->execute([$orgId]);
        $rows = $stmt->fetchAll();

        $orgNameStmt = $pdo->prepare('SELECT name FROM organizations WHERE id = ?');
        $orgNameStmt->execute([$orgId]);
        $orgName = $orgNameStmt->fetchColumn() ?: 'organization';

        audit_log($uid, 'org_report_exported', ['organization_id' => $orgId, 'rows' => count($rows)]);

        header('Content-Type: text/csv');
        header('X-Content-Type-Options: nosniff');
        header('Content-Disposition: attachment; filename="' . preg_replace('/[^a-z0-9\-]+/i', '-', $orgName) . '-report-' . date('Y-m-d') . '.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['Name', 'Email', 'Role', 'Total Tasks', 'Done', 'Open', 'Overdue', 'Last Active']);
        foreach ($rows as $r) {
            fputcsv($out, [
                $r['name'], $r['email'], $r['role'], $r['total_tasks'],
                $r['done_tasks'] ?? 0, $r['open_tasks'] ?? 0, $r['overdue_tasks'] ?? 0,
                $r['last_activity'] ?? '—',
            ]);
        }
        fclose($out);
        exit;

    // Activity log — every seat/invite/billing event already recorded via
    // audit_log() elsewhere in this file, includes/licensing.php, and
    // api/stripe-webhook.php. This just surfaces it, filtered to this org
    // (JSON_EXTRACT on meta.organization_id) and joined to the actor's name.
    case 'GET:activity':
        $orgId = (int)($_GET['org_id'] ?? 0);
        require_org_admin($orgId);
        $stmt = $pdo->prepare(
            "SELECT sal.id, sal.event, sal.meta, sal.created_at, u.name AS actor_name, u.email AS actor_email
             FROM security_audit_log sal
             LEFT JOIN users u ON u.id = sal.user_id
             WHERE JSON_EXTRACT(sal.meta, '$.organization_id') = ?
             ORDER BY sal.created_at DESC LIMIT 200"
        );
        $stmt->execute([$orgId]);
        json_response(['activity' => $stmt->fetchAll()]);
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

    // Bulk import — same path as a single invite (assign_org_seat, then
    // the onboarding/added email), just looped, so seat-cap and
    // duplicate-membership checks stay in exactly one place. Never fails
    // the whole batch for one bad row — returns a per-row result instead
    // so the admin can see exactly what happened to each email.
    case 'POST:bulk-invite':
        $orgId = (int)($in['org_id'] ?? 0);
        require_org_admin($orgId);
        $rows = is_array($in['rows'] ?? null) ? $in['rows'] : [];
        if (!$rows) json_response(['error' => 'No rows to import'], 422);
        if (count($rows) > 50) json_response(['error' => 'Import at most 50 rows at a time'], 422);

        $stmt = $pdo->prepare('SELECT name FROM organizations WHERE id = ?');
        $stmt->execute([$orgId]);
        $orgName = $stmt->fetchColumn();
        $loginUrl = (!empty($_SERVER['HTTPS']) ? 'https://' : 'http://') . ($_SERVER['HTTP_HOST'] ?? '') . '/login.php';

        $results = [];
        foreach ($rows as $row) {
            $email = clean_email($row['email'] ?? '');
            $role = in_array($row['role'] ?? '', ['admin', 'employee'], true) ? $row['role'] : 'employee';
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $results[] = ['email' => $row['email'] ?? '', 'ok' => false, 'error' => 'Invalid email address'];
                continue;
            }

            $result = assign_org_seat($orgId, $email, $role, $uid);
            if (!$result['ok']) {
                $results[] = ['email' => $email, 'ok' => false, 'error' => $result['error']];
                continue;
            }

            $emailSent = false;
            try {
                $emailSent = $result['is_new_user']
                    ? send_org_onboarding_email($email, $result['name'], $orgName, $loginUrl, $result['temp_password'])
                    : send_org_added_email($email, $result['name'], $orgName, $loginUrl);
            } catch (Throwable $e) {
                error_log('[org-bulk-invite] Email send threw for ' . $email . ': ' . $e->getMessage());
            }
            $results[] = ['email' => $email, 'ok' => true, 'is_new_user' => $result['is_new_user'], 'email_sent' => $emailSent];
        }

        audit_log($uid, 'org_bulk_invite', ['organization_id' => $orgId, 'total' => count($rows), 'succeeded' => count(array_filter($results, fn($r) => $r['ok']))]);
        json_response(['results' => $results]);
        break;

    // "Add additional seats at any time."
    case 'POST:add-seats':
        $orgId = (int)($in['org_id'] ?? 0);
        require_org_admin($orgId);
        $additional = max(1, (int)($in['seats'] ?? 0));

        $stmt = $pdo->prepare('SELECT seats_purchased FROM organizations WHERE id = ?');
        $stmt->execute([$orgId]);
        $current = (int)$stmt->fetchColumn();
        if ($current + $additional > ORG_MAX_SEATS) {
            json_response(['error' => 'Taskvel Enterprise allows a maximum of ' . ORG_MAX_SEATS . ' seats (currently ' . $current . ').'], 422);
        }

        $pdo->prepare('UPDATE organizations SET seats_purchased = seats_purchased + ? WHERE id = ?')->execute([$additional, $orgId]);
        $stmt = $pdo->prepare('SELECT billing_cycle FROM organizations WHERE id = ?');
        $stmt->execute([$orgId]);
        $cycle = $stmt->fetchColumn();
        $pdo->prepare('INSERT INTO organization_billing_history (organization_id, description, seats, billing_cycle) VALUES (?, ?, ?, ?)')
            ->execute([$orgId, "Added $additional seat(s)", $additional, $cycle]);

        audit_log($uid, 'org_seats_added', ['organization_id' => $orgId, 'seats_added' => $additional]);
        json_response(['ok' => true, 'seats' => org_seat_counts($orgId)]);
        break;

    // White-label branding — logo + accent color that replaces Taskvel's
    // default theme for every member of this org, app-wide.
    case 'POST:update-branding':
        $orgId = (int)($in['org_id'] ?? 0);
        require_org_admin($orgId);

        $logoUrl = trim((string)($in['logo_url'] ?? ''));
        if ($logoUrl !== '' && !filter_var($logoUrl, FILTER_VALIDATE_URL)) {
            json_response(['error' => 'Logo must be a valid image URL'], 422);
        }
        $brandColor = trim((string)($in['brand_color'] ?? ''));
        if ($brandColor !== '' && !preg_match('/^#[0-9a-fA-F]{6}$/', $brandColor)) {
            json_response(['error' => 'Brand color must be a hex code like #4f46e5'], 422);
        }

        $pdo->prepare('UPDATE organizations SET logo_url = ?, brand_color = ? WHERE id = ?')
            ->execute([$logoUrl ?: null, $brandColor ?: null, $orgId]);

        audit_log($uid, 'org_branding_updated', ['organization_id' => $orgId, 'has_logo' => $logoUrl !== '', 'brand_color' => $brandColor ?: null]);
        json_response(['ok' => true]);
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