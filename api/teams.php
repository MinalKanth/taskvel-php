<?php
require_once __DIR__ . '/../includes/teams.php';
require_once __DIR__ . '/../includes/billing.php';
require_login();

// ────────────────────────────────────────────────────────────
// BILLING FALLBACKS — used only if includes/billing.php doesn't
// already define these. Adjust FREE limits as needed.
// ────────────────────────────────────────────────────────────
if (!function_exists('require_team_creation_allowed')) {
    function require_team_creation_allowed(int $uid): void
    {
        $freeTeamLimit = 1; // free plan: max teams a user can own
        try {
            $stmt = db()->prepare(
                "SELECT COUNT(*) FROM teams t
                 JOIN team_members tm ON tm.team_id = t.id AND tm.user_id = ? AND tm.role = 'owner'
                 WHERE COALESCE(t.plan, 'free') = 'free'"
            );
            $stmt->execute([$uid]);
        } catch (Throwable $e) {
            // Billing migration (teams.plan) not applied yet — count all owned teams instead
            // so team creation never 500s because of a missing optional column.
            $stmt = db()->prepare(
                "SELECT COUNT(*) FROM teams t
                 JOIN team_members tm ON tm.team_id = t.id AND tm.user_id = ? AND tm.role = 'owner'"
            );
            $stmt->execute([$uid]);
        }
        if ((int)$stmt->fetchColumn() >= $freeTeamLimit) {
            json_response(['error' => "Free plan allows $freeTeamLimit team. Upgrade to create more teams."], 402);
        }
    }
}

if (!function_exists('require_seats_available')) {
    function require_seats_available(int $teamId): void
    {
        $freeSeatLimit = 3; // free plan: max members per team
        $pdo = db();
        $stmt = $pdo->prepare('SELECT COALESCE(plan, "free") AS plan FROM teams WHERE id = ?');
        $stmt->execute([$teamId]);
        $plan = $stmt->fetchColumn();
        if ($plan !== 'free') return; // paid plans: unlimited seats

        $stmt = $pdo->prepare('SELECT COUNT(*) FROM team_members WHERE team_id = ?');
        $stmt->execute([$teamId]);
        if ((int)$stmt->fetchColumn() >= $freeSeatLimit) {
            json_response(['error' => "Free plan allows $freeSeatLimit members per team. Upgrade to add more seats."], 402);
        }
    }
}

$uid = current_user_id();
$pdo = db();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$in = body();

switch ("$method:$action") {

    // List every team the current user belongs to, with their role in each.
    case 'GET:list':
        $stmt = $pdo->prepare('SELECT t.id, t.name, t.created_by, tm.role,
                                      (SELECT COUNT(*) FROM team_members WHERE team_id = t.id) AS member_count,
                                      (SELECT COUNT(*) FROM projects WHERE team_id = t.id AND archived = 0) AS project_count
                               FROM teams t
                               JOIN team_members tm ON tm.team_id = t.id
                               WHERE tm.user_id = ?
                               ORDER BY t.created_at DESC');
        $stmt->execute([$uid]);
        json_response(['teams' => $stmt->fetchAll()]);
        break;

    case 'POST:create':
        require_team_creation_allowed($uid);
        $name = clean_str($in['name'] ?? '', 120);
        if ($name === '') json_response(['error' => 'Team name is required'], 422);
        $pdo->beginTransaction();
        $stmt = $pdo->prepare('INSERT INTO teams (name, created_by) VALUES (?, ?)');
        $stmt->execute([$name, $uid]);
        $teamId = (int)$pdo->lastInsertId();
        $pdo->prepare('INSERT INTO team_members (team_id, user_id, role) VALUES (?, ?, ?)')
            ->execute([$teamId, $uid, 'owner']);
        $pdo->commit();
        json_response(['ok' => true, 'team_id' => $teamId]);
        break;

    // One team's details (any member can view) — used by team.php to show
    // the real team name instead of a generic "Team" heading.
    case 'GET:get':
        $teamId = (int)($_GET['team_id'] ?? 0);
        $myRole = require_team_member($teamId);
        $stmt = $pdo->prepare('SELECT t.id, t.name, t.created_by, t.created_at,
                                      (SELECT COUNT(*) FROM team_members WHERE team_id = t.id) AS member_count,
                                      (SELECT COUNT(*) FROM projects WHERE team_id = t.id AND archived = 0) AS project_count,
                                      (SELECT COUNT(*) FROM team_events WHERE team_id = t.id AND event_date >= CURDATE()) AS upcoming_event_count
                               FROM teams t WHERE t.id = ?');
        try {
            $stmt->execute([$teamId]);
            $team = $stmt->fetch();
        } catch (Throwable $e) {
            // team_events migration not applied yet — degrade gracefully.
            $stmt = $pdo->prepare('SELECT t.id, t.name, t.created_by, t.created_at,
                                          (SELECT COUNT(*) FROM team_members WHERE team_id = t.id) AS member_count,
                                          (SELECT COUNT(*) FROM projects WHERE team_id = t.id AND archived = 0) AS project_count,
                                          0 AS upcoming_event_count
                                   FROM teams t WHERE t.id = ?');
            $stmt->execute([$teamId]);
            $team = $stmt->fetch();
        }
        if (!$team) json_response(['error' => 'Not found'], 404);
        $team['my_role'] = $myRole;
        json_response(['team' => $team]);
        break;

    // Members + their role for one team (any member can view).
    case 'GET:members':
        $teamId = (int)($_GET['team_id'] ?? 0);
        require_team_member($teamId);
        $stmt = $pdo->prepare('SELECT u.id, u.name, u.email, tm.role, tm.joined_at
                               FROM team_members tm JOIN users u ON u.id = tm.user_id
                               WHERE tm.team_id = ? ORDER BY FIELD(tm.role,\'owner\',\'manager\',\'member\'), u.name');
        $stmt->execute([$teamId]);
        json_response(['members' => $stmt->fetchAll()]);
        break;

    // Invite an already-registered user by email. Owners/managers only.
    case 'POST:invite':
        $teamId = (int)($in['team_id'] ?? 0);
        require_team_manager($teamId);
        require_seats_available($teamId);
        enforce_rate_limit("invite:$teamId:" . current_user_id(), 20, 3600, 'Too many invites sent. Please wait before inviting more people.');
        $email = clean_email($in['email'] ?? '');
        $role = in_array($in['role'] ?? 'member', ['manager', 'member']) ? $in['role'] : 'member';
        if ($email === '') json_response(['error' => 'Email is required'], 422);
        $stmt = $pdo->prepare('SELECT id, name FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if (!$user) json_response(['error' => 'No Taskvel account found for that email. They need to sign up first.'], 404);
        if (team_role($teamId, $user['id'])) json_response(['error' => 'That person is already on the team'], 409);
        $pdo->prepare('INSERT INTO team_members (team_id, user_id, role) VALUES (?, ?, ?)')
            ->execute([$teamId, $user['id'], $role]);
        json_response(['ok' => true, 'name' => $user['name']]);
        break;

    case 'POST:update-role':
        $teamId = (int)($in['team_id'] ?? 0);
        $targetUserId = (int)($in['user_id'] ?? 0);
        $newRole = $in['role'] ?? '';
        if (!in_array($newRole, ['manager', 'member'])) json_response(['error' => 'Invalid role'], 422);
        // Only the owner may change roles (prevents managers promoting themselves to owner-equivalent power struggles).
        if (team_role($teamId, $uid) !== 'owner') json_response(['error' => 'Only the team owner can change roles'], 403);
        if (team_role($teamId, $targetUserId) === 'owner') json_response(['error' => 'Cannot change the owner\'s role'], 403);
        $pdo->prepare('UPDATE team_members SET role = ? WHERE team_id = ? AND user_id = ?')
            ->execute([$newRole, $teamId, $targetUserId]);
        json_response(['ok' => true]);
        break;

    case 'POST:remove-member':
        $teamId = (int)($in['team_id'] ?? 0);
        $targetUserId = (int)($in['user_id'] ?? 0);
        require_team_manager($teamId);
        if (team_role($teamId, $targetUserId) === 'owner') json_response(['error' => 'Cannot remove the team owner'], 403);
        // Unassign their tasks in this team's projects rather than leaving dangling references.
        $pdo->prepare('UPDATE project_tasks pt JOIN projects p ON p.id = pt.project_id
                       SET pt.assignee_id = NULL WHERE p.team_id = ? AND pt.assignee_id = ?')
            ->execute([$teamId, $targetUserId]);
        $pdo->prepare('DELETE FROM team_members WHERE team_id = ? AND user_id = ?')
            ->execute([$teamId, $targetUserId]);
        json_response(['ok' => true]);
        break;

    case 'POST:leave':
        $teamId = (int)($in['team_id'] ?? 0);
        if (team_role($teamId, $uid) === 'owner') json_response(['error' => 'Transfer ownership before leaving, or delete the team instead'], 403);
        $pdo->prepare('UPDATE project_tasks pt JOIN projects p ON p.id = pt.project_id
                       SET pt.assignee_id = NULL WHERE p.team_id = ? AND pt.assignee_id = ?')
            ->execute([$teamId, $uid]);
        $pdo->prepare('DELETE FROM team_members WHERE team_id = ? AND user_id = ?')->execute([$teamId, $uid]);
        json_response(['ok' => true]);
        break;

    case 'DELETE:delete':
        $teamId = (int)($_GET['team_id'] ?? 0);
        if (team_role($teamId, $uid) !== 'owner') json_response(['error' => 'Only the owner can delete the team'], 403);
        $pdo->prepare('DELETE FROM teams WHERE id = ?')->execute([$teamId]);
        json_response(['ok' => true]);
        break;

    default:
        json_response(['error' => 'Unknown route'], 404);
}