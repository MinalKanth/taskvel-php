<?php
require_once __DIR__ . '/../includes/teams.php';
require_login();

$uid = current_user_id();
$pdo = db();
$q = trim((string)($_GET['q'] ?? ''));

if (mb_strlen($q) < 2) {
    json_response(['personal_tasks' => [], 'project_tasks' => [], 'team_tasks' => [], 'projects' => [], 'teams' => []]);
}

$needle = '%' . $q . '%';
$PER_GROUP = 6;

// ── Personal tasks (owned or shared-with-accepted) — same visibility rule as api/tasks.php ──
$stmt = $pdo->prepare("SELECT id, title, status, priority, due_date FROM tasks
                        WHERE (owner_id = ? OR id IN (
                            SELECT task_id FROM task_shares WHERE shared_with_user_id = ? AND status = 'accepted'
                        )) AND title LIKE ?
                        ORDER BY updated_at DESC LIMIT $PER_GROUP");
$stmt->execute([$uid, $uid, $needle]);
$personalTasks = $stmt->fetchAll();

// ── Project (board) tasks — across every team the user belongs to ──
$stmt = $pdo->prepare("SELECT pt.id, pt.title, pt.status, pt.priority, pt.project_id, p.name AS project_name
                        FROM project_tasks pt
                        JOIN projects p ON p.id = pt.project_id
                        JOIN team_members tm ON tm.team_id = p.team_id AND tm.user_id = ?
                        WHERE pt.title LIKE ?
                        ORDER BY pt.updated_at DESC LIMIT $PER_GROUP");
$stmt->execute([$uid, $needle]);
$projectTasks = $stmt->fetchAll();

// ── Team tasks — across every team the user belongs to ──
$stmt = $pdo->prepare("SELECT tt.id, tt.title, tt.status, tt.priority, tt.team_id, t.name AS team_name
                        FROM team_tasks tt
                        JOIN team_members tm ON tm.team_id = tt.team_id AND tm.user_id = ?
                        JOIN teams t ON t.id = tt.team_id
                        WHERE tt.title LIKE ?
                        ORDER BY tt.updated_at DESC LIMIT $PER_GROUP");
$stmt->execute([$uid, $needle]);
$teamTasks = $stmt->fetchAll();

// ── Projects by name ──
$stmt = $pdo->prepare("SELECT p.id, p.name, p.color, t.name AS team_name
                        FROM projects p
                        JOIN team_members tm ON tm.team_id = p.team_id AND tm.user_id = ?
                        JOIN teams t ON t.id = p.team_id
                        WHERE p.name LIKE ? AND p.archived = 0
                        ORDER BY p.name ASC LIMIT $PER_GROUP");
$stmt->execute([$uid, $needle]);
$projects = $stmt->fetchAll();

// ── Teams by name ──
$stmt = $pdo->prepare("SELECT t.id, t.name, tm.role
                        FROM teams t
                        JOIN team_members tm ON tm.team_id = t.id AND tm.user_id = ?
                        WHERE t.name LIKE ?
                        ORDER BY t.name ASC LIMIT $PER_GROUP");
$stmt->execute([$uid, $needle]);
$teams = $stmt->fetchAll();

json_response([
    'personal_tasks' => $personalTasks,
    'project_tasks'  => $projectTasks,
    'team_tasks'     => $teamTasks,
    'projects'       => $projects,
    'teams'          => $teams,
]);