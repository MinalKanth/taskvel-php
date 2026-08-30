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