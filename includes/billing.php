<?php
require_once __DIR__ . '/../config/db.php';


// The account holder's personal Taskvel Pro plan ('free' | 'pro'). Dormant
// column from migration_02 — this is its first real consumer.
function user_plan(int $userId): string
{
    try {
        $stmt = db()->prepare('SELECT plan FROM users WHERE id = ?');
        $stmt->execute([$userId]);
        return $stmt->fetchColumn() ?: 'free';
    } catch (Throwable $e) {
        error_log('[Taskvel] user_plan fallback (is migration_02 applied?): ' . $e->getMessage());
        return 'free';
    }
}

// A team's effective plan: an explicit team-level upgrade (teams.plan,
// e.g. a business sponsoring one specific team) takes precedence; otherwise
// the team inherits its owner's personal plan, so a Pro user's teams are
// unlimited by default without needing a separate per-team subscription.
function team_plan(int $teamId): string
{
    try {
        $stmt = db()->prepare(
            "SELECT t.plan, tm.user_id AS owner_id
             FROM teams t
             JOIN team_members tm ON tm.team_id = t.id AND tm.role = 'owner'
             WHERE t.id = ?"
        );
        $stmt->execute([$teamId]);
        $row = $stmt->fetch();
        if (!$row) return 'free';
        if (!empty($row['plan']) && $row['plan'] !== 'free') return $row['plan'];
        return user_plan((int)$row['owner_id']);
    } catch (Throwable $e) {
        // teams.plan column not migrated yet (migration_08_billing.sql) —
        // treat every team as 'free' instead of throwing.
        error_log('[Taskvel] team_plan fallback (is migration_08 applied?): ' . $e->getMessage());
        return 'free';
    }
}

function plan_limits(string $plan): array
{
    $defaults = ['max_teams' => 1, 'max_members' => 3, 'max_projects' => 1, 'max_attachment_mb' => 10, 'max_ai_daily' => 3];
    try {
        $stmt = db()->prepare('SELECT * FROM plan_limits WHERE plan = ?');
        $stmt->execute([$plan]);
        return ($stmt->fetch() ?: $defaults) + $defaults; // union keeps old rows (pre migration_10) from missing max_teams
    } catch (Throwable $e) {
        // plan_limits table not migrated yet — fall back to free-plan defaults.
        error_log('[Taskvel] plan_limits fallback (is migration_08 applied?): ' . $e->getMessage());
        return $defaults;
    }
}

// Called before creating a team — blocks with a clear upgrade message.
// Limit is keyed off the *creating user's* personal plan (there's no team
// yet at this point to key it off of).
function require_team_creation_allowed(int $userId): void
{
    $limits = plan_limits(user_plan($userId));
    $stmt = db()->prepare(
        "SELECT COUNT(*) FROM teams t
         JOIN team_members tm ON tm.team_id = t.id AND tm.user_id = ? AND tm.role = 'owner'"
    );
    $stmt->execute([$userId]);
    if ((int)$stmt->fetchColumn() >= $limits['max_teams']) {
        json_response(['error' => "Free plan allows {$limits['max_teams']} team(s). Upgrade to Taskvel Pro to create more.", 'upgrade_required' => true], 402);
    }
}

// Called before inviting/adding a member — blocks with a clear upgrade message.
function require_seats_available(int $teamId): void
{
    $limits = plan_limits(team_plan($teamId));
    $stmt = db()->prepare('SELECT COUNT(*) FROM team_members WHERE team_id = ?');
    $stmt->execute([$teamId]);
    if ((int)$stmt->fetchColumn() >= $limits['max_members']) {
        json_response(['error' => "This team is on the free plan (max {$limits['max_members']} members). Upgrade to add more people.", 'upgrade_required' => true], 402);
    }
}

function require_project_slot_available(int $teamId): void
{
    $limits = plan_limits(team_plan($teamId));
    $stmt = db()->prepare('SELECT COUNT(*) FROM projects WHERE team_id = ? AND archived = 0');
    $stmt->execute([$teamId]);
    if ((int)$stmt->fetchColumn() >= $limits['max_projects']) {
        json_response(['error' => "This team is on the free plan (max {$limits['max_projects']} project). Upgrade to add more.", 'upgrade_required' => true], 402);
    }
}



// ─────────────────────────────────────────────────────────────
// TRADING JOURNAL — paid feature (₹49 plan / Pro / Enterprise all
// set users.plan='pro', so this reuses that single flag) with a
// 10-day trial that starts on the user's FIRST trading entry, not
// on signup. Viewing existing data is NEVER blocked — only writes.
// ─────────────────────────────────────────────────────────────

const TRADING_JOURNAL_TRIAL_DAYS = 10;

function trading_journal_access(int $userId): array
{
    // IMPORTANT: don't use user_plan()==='pro' alone — every brand-new
    // signup automatically gets a 30-day account-wide Pro trial
    // (plan_source='trial'), which is unrelated to this feature's own
    // 10-day, first-entry-triggered trial. Only a REAL paid plan (Stripe
    // subscription, an org seat, or an admin grant) counts as "paid" here.
    $stmt = db()->prepare('SELECT plan, plan_source FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    $u = $stmt->fetch();
    $isPaid = $u && $u['plan'] === 'pro' && in_array($u['plan_source'], ['stripe', 'org_seat', 'admin'], true);

    $stmt = db()->prepare('SELECT trading_journal_trial_started_at FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    $startedAt = $stmt->fetchColumn();

    $trialEndsAt = null;
    $trialActive = false;
    $daysLeft = null;

    if ($startedAt) {
        $trialEndsAt = date('Y-m-d H:i:s', strtotime($startedAt) + TRADING_JOURNAL_TRIAL_DAYS * 86400);
        $trialActive = $trialEndsAt > date('Y-m-d H:i:s');
        if ($trialActive) {
            $daysLeft = max(0, (int)ceil((strtotime($trialEndsAt) - time()) / 86400));
        }
    }

    // Writable if: paid, OR trial hasn't started yet (their next write
    // starts it), OR trial started and hasn't expired.
    $canWrite = $isPaid || !$startedAt || $trialActive;

    return [
        'is_paid'          => $isPaid,
        'trial_started'    => (bool)$startedAt,
        'trial_started_at' => $startedAt,
        'trial_ends_at'    => $trialEndsAt,
        'trial_active'     => $trialActive,
        'days_left'        => $daysLeft,
        'can_write'        => $canWrite,
    ];
}

// Call before any Trading Journal write. Ends the request with 402 +
// upgrade_required if the trial has expired and there's no paid plan.
function require_trading_journal_write(int $userId): void
{
    $access = trading_journal_access($userId);
    if (!$access['can_write']) {
        json_response([
            'error' => 'Your 10-day Trading Journal trial has ended. Subscribe to the ₹49 plan, Pro, or Enterprise to keep adding entries — your existing data is safe and still visible.',
            'upgrade_required' => true,
            'trading_journal_trial_expired' => true,
        ], 402);
    }
}

// Call ONLY when creating a brand-new trading entry (not edits, not
// goals, not journal). Starts the clock the first time, and only the
// first time, a free-plan user logs a trade.
function maybe_start_trading_journal_trial(int $userId): void
{
    $stmt = db()->prepare('SELECT plan, plan_source FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    $u = $stmt->fetch();
    if ($u && $u['plan'] === 'pro' && in_array($u['plan_source'], ['stripe', 'org_seat', 'admin'], true)) {
        return; // real paid users never need the trial clock
    }
    $stmt = db()->prepare('UPDATE users SET trading_journal_trial_started_at = NOW() WHERE id = ? AND trading_journal_trial_started_at IS NULL');
    $stmt->execute([$userId]);
}