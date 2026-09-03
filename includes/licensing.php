<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/stripe.php';
require_once __DIR__ . '/auth.php';

// ────────────────────────────────────────────────────────────
// PLAN RECOMPUTATION — the single place that decides whether a user
// is currently 'pro', and why. Called after anything that could change
// the answer: seat assignment/suspension/removal, trial expiry, a Stripe
// webhook event. Keeping this in one function (rather than letting each
// caller set users.plan directly) is what makes it safe to have three
// independent sources of Pro access (trial, Stripe, org seat) without
// them stepping on each other — e.g. suspending an org seat never
// accidentally cancels a real Stripe subscription's Pro status.
// ────────────────────────────────────────────────────────────
function recompute_user_plan(int $userId): void
{
    $pdo = db();

    // An active (non-suspended) seat in a *paid* organization always wins —
    // an org is paying for this account, so it takes precedence over an
    // expiring trial. Must join organizations and check plan_status here —
    // an org created but never checked out through Stripe has plan_status
    // 'pending' and must NOT grant Pro to its members.
    // 'past_due' = the organization's 7-day grace period — members keep
    // access. Only 'locked' actually cuts them off.
    $stmt = $pdo->prepare(
        "SELECT 1 FROM organization_members om
         JOIN organizations o ON o.id = om.organization_id
         WHERE om.user_id = ? AND om.status = 'active' AND o.plan_status IN ('active','past_due') LIMIT 1"
    );
    $stmt->execute([$userId]);
    if ($stmt->fetchColumn()) {
        $pdo->prepare("UPDATE users SET plan = 'pro', plan_source = 'org_seat' WHERE id = ?")->execute([$userId]);
        return;
    }

    $stmt = $pdo->prepare('SELECT plan, plan_source, trial_ends_at FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    if (!$user) return;

    // A real paid subscription (set by the Stripe webhook) is untouched here.
    if ($user['plan_source'] === 'stripe') return;

    // Still within the trial window.
    if ($user['plan_source'] === 'trial' && $user['trial_ends_at'] && $user['trial_ends_at'] > date('Y-m-d H:i:s')) {
        return; // already 'pro'/'trial' — nothing to change
    }

    // Trial expired (or was never on one, or their org seat was just taken away): free.
    $pdo->prepare("UPDATE users SET plan = 'free', plan_source = 'none' WHERE id = ?")->execute([$userId]);
}

function generate_temp_password(): string
{
    // 12 chars from an unambiguous alphabet (no 0/O/1/l/I) so it's easy to
    // read aloud or retype from an email, while staying high-entropy.
    $alphabet = 'ABCDEFGHJKMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789';
    $pw = '';
    for ($i = 0; $i < 12; $i++) $pw .= $alphabet[random_int(0, strlen($alphabet) - 1)];
    return $pw;
}

// ────────────────────────────────────────────────────────────
// ORGANIZATION MEMBERSHIP / PERMISSIONS
// A user belongs to at most one organization today (deliberate scope
// limit — see migration_13's header comment; "multiple organizations
// per user" is listed as a future enhancement).
// ────────────────────────────────────────────────────────────
function user_organization_membership(int $userId): ?array
{
    $stmt = db()->prepare(
        "SELECT om.*, o.name AS org_name, o.logo_url AS org_logo_url, o.brand_color AS org_brand_color
         FROM organization_members om
         JOIN organizations o ON o.id = om.organization_id
         WHERE om.user_id = ? LIMIT 1"
    );
    $stmt->execute([$userId]);
    return $stmt->fetch() ?: null;
}

function org_role(int $orgId, int $userId): ?string
{
    $stmt = db()->prepare('SELECT role FROM organization_members WHERE organization_id = ? AND user_id = ?');
    $stmt->execute([$orgId, $userId]);
    return $stmt->fetchColumn() ?: null;
}

function can_manage_org(int $orgId, int $userId): bool
{
    return in_array(org_role($orgId, $userId), ['owner', 'admin'], true);
}

// Blocks with 403 unless the current user is an owner/admin of this org —
// "Only Organization Owners and authorized HR/Admin users can purchase,
// assign, revoke, or manage seats."
function require_org_admin(int $orgId): void
{
    if (!can_manage_org($orgId, current_user_id())) {
        json_response(['error' => 'Only the organization owner or an admin can do this'], 403);
    }
}

function require_org_member(int $orgId): void
{
    if (!org_role($orgId, current_user_id())) {
        json_response(['error' => 'You are not a member of this organization'], 403);
    }
}

function org_seat_counts(int $orgId): array
{
    $stmt = db()->prepare('SELECT seats_purchased FROM organizations WHERE id = ?');
    $stmt->execute([$orgId]);
    $purchased = (int)$stmt->fetchColumn();

    $stmt = db()->prepare('SELECT COUNT(*) FROM organization_members WHERE organization_id = ?');
    $stmt->execute([$orgId]);
    $assigned = (int)$stmt->fetchColumn();

    return ['purchased' => $purchased, 'assigned' => $assigned, 'available' => max(0, $purchased - $assigned)];
}

/**
 * Assigns one seat to $email — the core of "Employee Provisioning".
 * Handles both existing Taskvel users and brand-new accounts, inside a
 * single transaction with a row lock on the organization so concurrent
 * invites can never oversell seats ("ensure all seat operations are
 * transactional to avoid inconsistencies", "prevent assigning more users
 * than the number of purchased seats").
 *
 * @return array{ok:bool, error?:string, user_id?:int, is_new_user?:bool, temp_password?:string}
 */
function assign_org_seat(int $orgId, string $email, string $role, int $actorId): array
{
    $pdo = db();
    $email = clean_email($email);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) return ['ok' => false, 'error' => 'Invalid email address'];
    if (!in_array($role, ['admin', 'employee'], true)) $role = 'employee';

    $pdo->beginTransaction();
    try {
        // Row-lock the organization for the duration of this transaction —
        // this is what makes concurrent invites safe against overselling.
        $stmt = $pdo->prepare('SELECT seats_purchased FROM organizations WHERE id = ? FOR UPDATE');
        $stmt->execute([$orgId]);
        $purchased = $stmt->fetchColumn();
        if ($purchased === false) { $pdo->rollBack(); return ['ok' => false, 'error' => 'Organization not found']; }

        $stmt = $pdo->prepare('SELECT COUNT(*) FROM organization_members WHERE organization_id = ?');
        $stmt->execute([$orgId]);
        $assigned = (int)$stmt->fetchColumn();
        if ($assigned >= ORG_MAX_SEATS) {
            $pdo->rollBack();
            return ['ok' => false, 'error' => 'This organization has reached the maximum of ' . ORG_MAX_SEATS . ' seats.'];
        }
        if ($assigned >= min((int)$purchased, ORG_MAX_SEATS)) {
            $pdo->rollBack();
            return ['ok' => false, 'error' => 'No seats available — purchase more seats or free one up first'];
        }

        $stmt = $pdo->prepare('SELECT id, name FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $existing = $stmt->fetch();
        $isNewUser = !$existing;
        $tempPassword = null;

        if ($existing) {
            $userId = (int)$existing['id'];
            $already = $pdo->prepare('SELECT 1 FROM organization_members WHERE organization_id = ? AND user_id = ?');
            $already->execute([$orgId, $userId]);
            if ($already->fetchColumn()) { $pdo->rollBack(); return ['ok' => false, 'error' => 'That person is already a member of this organization']; }

            $existingOrg = $pdo->prepare('SELECT 1 FROM organization_members WHERE user_id = ?');
            $existingOrg->execute([$userId]);
            if ($existingOrg->fetchColumn()) { $pdo->rollBack(); return ['ok' => false, 'error' => 'That user already belongs to a different organization']; }

            $joinedAt = 'NOW()'; // they already have an account — the seat is live immediately
        } else {
            $tempPassword = generate_temp_password();
            $hash = password_hash($tempPassword, PASSWORD_BCRYPT);
            $name = clean_str(explode('@', $email)[0], 120);
            $stmt = $pdo->prepare('INSERT INTO users (name, email, password_hash, plan, plan_source, must_change_password) VALUES (?, ?, ?, \'free\', \'none\', 1)');
            $stmt->execute([$name, $email, $hash]);
            $userId = (int)$pdo->lastInsertId();
            $pdo->prepare('INSERT INTO streaks (user_id) VALUES (?)')->execute([$userId]);
            $joinedAt = 'NULL'; // not yet logged in — set on their first login instead
        }

        $pdo->prepare("INSERT INTO organization_members (organization_id, user_id, role, invited_by, joined_at) VALUES (?, ?, ?, ?, $joinedAt)")
            ->execute([$orgId, $userId, $role, $actorId]);

        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        error_log('[Taskvel] assign_org_seat failed: ' . $e->getMessage());
        return ['ok' => false, 'error' => 'Could not assign seat — please try again'];
    }

    recompute_user_plan($userId); // grants Pro access now that they hold a seat

    audit_log($actorId, 'org_seat_assigned', ['organization_id' => $orgId, 'target_user_id' => $userId, 'email' => $email, 'is_new_user' => $isNewUser]);

    return ['ok' => true, 'user_id' => $userId, 'is_new_user' => $isNewUser, 'temp_password' => $tempPassword, 'name' => $existing ? $existing['name'] : explode('@', $email)[0]];
}

function release_org_seat(int $orgId, int $userId, int $actorId, string $reason = 'removed'): void
{
    $pdo = db();
    $pdo->prepare('DELETE FROM organization_members WHERE organization_id = ? AND user_id = ?')->execute([$orgId, $userId]);
    recompute_user_plan($userId);
    audit_log($actorId, 'org_seat_released', ['organization_id' => $orgId, 'target_user_id' => $userId, 'reason' => $reason]);
}

// ────────────────────────────────────────────────────────────
// ENTERPRISE GRACE PERIOD — calendar-month billing.
// ────────────────────────────────────────────────────────────

function end_of_month(?string $date = null): string
{
    return date('Y-m-t', $date ? strtotime($date) : time());
}

// Called whenever a payment for this org succeeds — realigns billing back
// to run through the end of the calendar month the payment landed in.
function reactivate_org_billing(int $orgId): void
{
    $pdo = db();
    $pdo->prepare("UPDATE organizations SET plan_status = 'active', grace_ends_at = NULL, renewal_date = ? WHERE id = ?")
        ->execute([end_of_month(), $orgId]);

    $stmt = $pdo->prepare('SELECT user_id FROM organization_members WHERE organization_id = ?');
    $stmt->execute([$orgId]);
    foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $memberId) {
        recompute_user_plan((int)$memberId);
    }
}

// Run daily by cron/org_grace_check.php.
function recompute_org_grace_status(array $org): void
{
    $pdo = db();
    $today = date('Y-m-d');

    if ($org['plan_status'] === 'active' && $org['renewal_date'] && $org['renewal_date'] < $today) {
        $graceEnds = date('Y-m-d', strtotime($org['renewal_date'] . ' +7 days'));
        $pdo->prepare("UPDATE organizations SET plan_status = 'past_due', grace_ends_at = ? WHERE id = ?")
            ->execute([$graceEnds, $org['id']]);
        return;
    }

    if ($org['plan_status'] === 'past_due' && $org['grace_ends_at'] && $org['grace_ends_at'] < $today) {
        $pdo->prepare("UPDATE organizations SET plan_status = 'locked' WHERE id = ?")->execute([$org['id']]);
        $stmt = $pdo->prepare('SELECT user_id FROM organization_members WHERE organization_id = ?');
        $stmt->execute([$org['id']]);
        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $memberId) {
            recompute_user_plan((int)$memberId);
        }
    }
}