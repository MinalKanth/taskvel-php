<?php
require_once __DIR__ . '/../includes/teams.php';
require_login();

$uid = current_user_id();
$pdo = db();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$in = body();

switch ("$method:$action") {

    case 'GET:list':
        $teamId = (int)($_GET['team_id'] ?? 0);
        require_team_member($teamId);
        $stmt = $pdo->prepare('SELECT p.*,
                                (SELECT COUNT(*) FROM project_tasks WHERE project_id = p.id) AS task_count,
                                (SELECT COUNT(*) FROM project_tasks WHERE project_id = p.id AND status = \'done\') AS done_count
                               FROM projects p WHERE p.team_id = ? AND p.archived = 0
                               ORDER BY p.created_at DESC');
        $stmt->execute([$teamId]);
        json_response(['projects' => $stmt->fetchAll()]);
        break;

    case 'GET:get':
        $id = (int)($_GET['id'] ?? 0);
        $stmt = $pdo->prepare('SELECT * FROM projects WHERE id = ?');
        $stmt->execute([$id]);
        $project = $stmt->fetch();
        if (!$project) json_response(['error' => 'Not found'], 404);
        require_team_member((int)$project['team_id']);
        json_response(['project' => $project]);
        break;

    case 'POST:create':
        $teamId = (int)($in['team_id'] ?? 0);
        require_team_member($teamId); // any member can spin up a project
        $name = clean_str($in['name'] ?? '', 150);
        if ($name === '') json_response(['error' => 'Project name is required'], 422);
        $color = preg_match('/^#[0-9a-fA-F]{6}$/', $in['color'] ?? '') ? $in['color'] : '#4f46e5';
        $stmt = $pdo->prepare('INSERT INTO projects (team_id, name, description, color, created_by) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$teamId, $name, clean_str($in['description'] ?? '', 2000), $color, $uid]);
        $projectId = (int)$pdo->lastInsertId();
        log_project_activity($projectId, $uid, 'created this project');
        json_response(['ok' => true, 'project_id' => $projectId]);
        break;

    case 'POST:update':
        $id = (int)($in['id'] ?? 0);
        $teamId = project_team_id($id);
        if (!$teamId) json_response(['error' => 'Not found'], 404);
        require_team_manager($teamId);
        $color = preg_match('/^#[0-9a-fA-F]{6}$/', $in['color'] ?? '') ? $in['color'] : '#4f46e5';
        $stmt = $pdo->prepare('UPDATE projects SET name = ?, description = ?, color = ? WHERE id = ?');
        $stmt->execute([clean_str($in['name'] ?? '', 150), clean_str($in['description'] ?? '', 2000), $color, $id]);
        json_response(['ok' => true]);
        break;

    case 'POST:archive':
        $id = (int)($in['id'] ?? 0);
        $teamId = project_team_id($id);
        if (!$teamId) json_response(['error' => 'Not found'], 404);
        require_team_manager($teamId);
        $pdo->prepare('UPDATE projects SET archived = 1 WHERE id = ?')->execute([$id]);
        json_response(['ok' => true]);
        break;

    case 'DELETE:delete':
        $id = (int)($_GET['id'] ?? 0);
        $teamId = project_team_id($id);
        if (!$teamId) json_response(['error' => 'Not found'], 404);
        require_team_manager($teamId);
        $pdo->prepare('DELETE FROM projects WHERE id = ?')->execute([$id]);
        json_response(['ok' => true]);
        break;

    default:
        json_response(['error' => 'Unknown route'], 404);
}
