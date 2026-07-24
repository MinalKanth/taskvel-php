<?php
require_once __DIR__ . '/../config/db.php';

function team_plan(int $teamId): string
{
    try {
        $stmt = db()->prepare('SELECT plan FROM teams WHERE id = ?');
        $stmt->execute([$teamId]);
        return $stmt->fetchColumn() ?: 'free';
    } catch (Throwable $e) {
        // teams.plan column not migrated yet (migration_08_billing.sql) —
        // treat every team as 'free' instead of throwing.
        error_log('[Taskvel] team_plan fallback (is migration_08 applied?): ' . $e->getMessage());
        return 'free';
    }
}

function plan_limits(string $plan): array
{
    $defaults = ['max_members' => 3, 'max_projects' => 1, 'max_attachment_mb' => 10];
    try {
        $stmt = db()->prepare('SELECT * FROM plan_limits WHERE plan = ?');
        $stmt->execute([$plan]);
        return $stmt->fetch() ?: $defaults;
    } catch (Throwable $e) {
        // plan_limits table not migrated yet — fall back to free-plan defaults.
        error_log('[Taskvel] plan_limits fallback (is migration_08 applied?): ' . $e->getMessage());
        return $defaults;
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