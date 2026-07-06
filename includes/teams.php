<?php
require_once __DIR__ . '/auth.php';

// Returns 'owner' | 'manager' | 'member' | null (not a member of this team)
function team_role(int $teamId, int $userId): ?string
{
    $stmt = db()->prepare('SELECT role FROM team_members WHERE team_id = ? AND user_id = ?');
    $stmt->execute([$teamId, $userId]);
    $row = $stmt->fetch();
    return $row ? $row['role'] : null;
}

function project_team_id(int $projectId): ?int
{
    $stmt = db()->prepare('SELECT team_id FROM projects WHERE id = ?');
    $stmt->execute([$projectId]);
    $row = $stmt->fetch();
    return $row ? (int)$row['team_id'] : null;
}

function task_project_id(int $taskId): ?int
{
    $stmt = db()->prepare('SELECT project_id FROM project_tasks WHERE id = ?');
    $stmt->execute([$taskId]);
    $row = $stmt->fetch();
    return $row ? (int)$row['project_id'] : null;
}

// Managers and owners can add/remove/edit/assign/delete any task in the team.
function can_manage_team(int $teamId, int $userId): bool
{
    $role = team_role($teamId, $userId);
    return $role === 'owner' || $role === 'manager';
}

function require_team_member(int $teamId): string
{
    $role = team_role($teamId, current_user_id());
    if (!$role) json_response(['error' => 'Not a member of this team'], 403);
    return $role;
}

function require_team_manager(int $teamId): void
{
    if (!can_manage_team($teamId, current_user_id())) {
        json_response(['error' => 'Only team owners/managers can do this'], 403);
    }
}

function log_project_activity(int $projectId, int $userId, string $message): void
{
    $stmt = db()->prepare('INSERT INTO project_activity_log (project_id, user_id, message) VALUES (?, ?, ?)');
    $stmt->execute([$projectId, $userId, $message]);
}
