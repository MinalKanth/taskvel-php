<?php
require_once __DIR__ . '/../includes/teams.php';
require_once __DIR__ . '/../includes/webpush.php';
require_login();

$uid = current_user_id();
$pdo = db();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$in = body();

switch ("$method:$action") {

    case 'GET:list':
        $projectId = (int)($_GET['project_id'] ?? 0);
        $teamId = project_team_id($projectId);
        if (!$teamId) json_response(['error' => 'Not found'], 404);
        require_team_member($teamId);
        $stmt = $pdo->prepare('SELECT pt.*, u.name AS assignee_name, u.email AS assignee_email,
                                      c.name AS creator_name
                               FROM project_tasks pt
                               LEFT JOIN users u ON u.id = pt.assignee_id
                               LEFT JOIN users c ON c.id = pt.created_by
                               WHERE pt.project_id = ?
                               ORDER BY FIELD(pt.status,\'todo\',\'in_progress\',\'done\'),
                                        FIELD(pt.priority,\'critical\',\'high\',\'medium\',\'low\'),
                                        pt.due_date IS NULL, pt.due_date ASC, pt.created_at DESC');
        $stmt->execute([$projectId]);
        json_response(['tasks' => $stmt->fetchAll()]);
        break;

    // Per-assignee progress — the "keep track of it one by one or all at once" view.
    case 'GET:summary':
        $projectId = (int)($_GET['project_id'] ?? 0);
        $teamId = project_team_id($projectId);
        if (!$teamId) json_response(['error' => 'Not found'], 404);
        require_team_member($teamId);
        $stmt = $pdo->prepare('SELECT u.id, u.name,
                                      COUNT(pt.id) AS total,
                                      SUM(pt.status = \'done\') AS done,
                                      SUM(pt.status = \'in_progress\') AS in_progress,
                                      SUM(pt.status = \'todo\') AS todo
                               FROM team_members tm
                               JOIN users u ON u.id = tm.user_id
                               LEFT JOIN project_tasks pt ON pt.assignee_id = u.id AND pt.project_id = ?
                               WHERE tm.team_id = ?
                               GROUP BY u.id, u.name
                               ORDER BY u.name');
        $stmt->execute([$projectId, $teamId]);
        json_response(['summary' => $stmt->fetchAll()]);
        break;

    case 'POST:create':
        $projectId = (int)($in['project_id'] ?? 0);
        $teamId = project_team_id($projectId);
        if (!$teamId) json_response(['error' => 'Not found'], 404);
        require_team_member($teamId);
        $title = clean_str($in['title'] ?? '', 255);
        if ($title === '') json_response(['error' => 'Task title is required'], 422);
        $assigneeId = !empty($in['assignee_id']) ? (int)$in['assignee_id'] : null;
        // Members may only assign new tasks to themself; managers/owners can assign to anyone on the team.
        if ($assigneeId && $assigneeId !== $uid && !can_manage_team($teamId, $uid)) {
            json_response(['error' => 'Only managers can assign tasks to other people'], 403);
        }
        if ($assigneeId && !team_role($teamId, $assigneeId)) json_response(['error' => 'Assignee is not on this team'], 422);
        $stmt = $pdo->prepare('INSERT INTO project_tasks (project_id, title, description, priority, assignee_id, created_by, due_date)
                               VALUES (?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([
            $projectId, $title, clean_str($in['description'] ?? '', 3000),
            one_of($in['priority'] ?? 'medium', ['low', 'medium', 'high', 'critical'], 'medium'),
            $assigneeId, $uid, clean_str($in['due_date'] ?? '', 10) ?: null,
        ]);
        $taskId = (int)$pdo->lastInsertId();
        log_project_activity($projectId, $uid, "added task \"$title\"");
        if ($assigneeId && $assigneeId != $uid) {
            $stmt2 = $pdo->prepare('SELECT name FROM users WHERE id = ?'); $stmt2->execute([$uid]); $byName = $stmt2->fetchColumn();
            try { send_web_push_to_user($pdo, $assigneeId, [
                'title' => 'New task assigned to you',
                'body'  => "$byName assigned you: \"$title\"",
                'url'   => './project.php?id=' . $projectId,
                'tag'   => 'taskvel-assignment',
            ]); } catch (Throwable $e) { /* push is best-effort — never block the actual task creation */ }
        }
        json_response(['ok' => true, 'task_id' => $taskId]);
        break;

    case 'POST:update':
        $id = (int)($in['id'] ?? 0);
        $projectId = task_project_id($id);
        if (!$projectId) json_response(['error' => 'Not found'], 404);
        $teamId = project_team_id($projectId);
        $role = require_team_member($teamId);
        $stmt = $pdo->prepare('SELECT * FROM project_tasks WHERE id = ?');
        $stmt->execute([$id]);
        $task = $stmt->fetch();
        $isManager = can_manage_team($teamId, $uid);
        $isAssignee = $task['assignee_id'] == $uid;

        if ($isManager) {
            // Full edit rights: title, description, priority, due date, assignee, status.
            $assigneeId = array_key_exists('assignee_id', $in) ? ($in['assignee_id'] ?: null) : $task['assignee_id'];
            if ($assigneeId && !team_role($teamId, $assigneeId)) json_response(['error' => 'Assignee is not on this team'], 422);
            $stmt = $pdo->prepare('UPDATE project_tasks SET title = ?, description = ?, priority = ?, status = ?, assignee_id = ?, due_date = ? WHERE id = ?');
            $stmt->execute([
                isset($in['title']) ? clean_str($in['title'], 255) : $task['title'],
                isset($in['description']) ? clean_str($in['description'], 3000) : $task['description'],
                one_of($in['priority'] ?? $task['priority'], ['low', 'medium', 'high', 'critical'], $task['priority']),
                one_of($in['status'] ?? $task['status'], ['todo', 'in_progress', 'done'], $task['status']),
                $assigneeId, isset($in['due_date']) ? (clean_str($in['due_date'], 10) ?: null) : $task['due_date'], $id,
            ]);
            if (($in['assignee_id'] ?? $task['assignee_id']) != $task['assignee_id']) {
                log_project_activity($projectId, $uid, "reassigned \"{$task['title']}\"");
                if ($assigneeId && $assigneeId != $uid) {
                    try { send_web_push_to_user($pdo, $assigneeId, [
                        'title' => 'Task assigned to you',
                        'body'  => "\"{$task['title']}\" was assigned to you",
                        'url'   => './project.php?id=' . $projectId,
                        'tag'   => 'taskvel-assignment',
                    ]); } catch (Throwable $e) {}
                }
            }
        } elseif ($isAssignee) {
            // Regular members can only move the status of their own assigned task — not rewrite it.
            if (!isset($in['status']) || !in_array($in['status'], ['todo', 'in_progress', 'done'])) {
                json_response(['error' => 'You can only update the status of tasks assigned to you'], 403);
            }
            $pdo->prepare('UPDATE project_tasks SET status = ? WHERE id = ?')->execute([$in['status'], $id]);
        } else {
            json_response(['error' => 'You do not have permission to edit this task'], 403);
        }
        if (isset($in['status']) && $in['status'] === 'done' && $task['status'] !== 'done') {
            log_project_activity($projectId, $uid, "completed \"{$task['title']}\"");
            // Let the task's creator know it's done (if someone else completed it).
            if ($task['created_by'] != $uid) {
                $stmt2 = $pdo->prepare('SELECT name FROM users WHERE id = ?'); $stmt2->execute([$uid]); $byName = $stmt2->fetchColumn();
                try { send_web_push_to_user($pdo, (int)$task['created_by'], [
                    'title' => 'Task completed ✓',
                    'body'  => "$byName completed \"{$task['title']}\"",
                    'url'   => './project.php?id=' . $projectId,
                    'tag'   => 'taskvel-completion',
                ]); } catch (Throwable $e) {}
            }
        }
        json_response(['ok' => true]);
        break;

    case 'DELETE:delete':
        $id = (int)($_GET['id'] ?? 0);
        $projectId = task_project_id($id);
        if (!$projectId) json_response(['error' => 'Not found'], 404);
        $teamId = project_team_id($projectId);
        $stmt = $pdo->prepare('SELECT created_by, title FROM project_tasks WHERE id = ?');
        $stmt->execute([$id]);
        $task = $stmt->fetch();
        // Managers/owners can delete anything; regular members can delete only tasks they created themself.
        if (!can_manage_team($teamId, $uid) && $task['created_by'] != $uid) {
            json_response(['error' => 'Only managers (or the task creator) can delete this'], 403);
        }
        $pdo->prepare('DELETE FROM project_tasks WHERE id = ?')->execute([$id]);
        log_project_activity($projectId, $uid, "deleted \"{$task['title']}\"");
        json_response(['ok' => true]);
        break;

    case 'GET:comments':
        $taskId = (int)($_GET['task_id'] ?? 0);
        $projectId = task_project_id($taskId);
        if (!$projectId) json_response(['error' => 'Not found'], 404);
        require_team_member(project_team_id($projectId));
        $stmt = $pdo->prepare('SELECT c.*, u.name AS user_name FROM project_task_comments c
                               JOIN users u ON u.id = c.user_id WHERE c.task_id = ? ORDER BY c.created_at ASC');
        $stmt->execute([$taskId]);
        json_response(['comments' => $stmt->fetchAll()]);
        break;

    case 'POST:comment':
        $taskId = (int)($in['task_id'] ?? 0);
        $projectId = task_project_id($taskId);
        if (!$projectId) json_response(['error' => 'Not found'], 404);
        require_team_member(project_team_id($projectId));
        $bodyText = clean_str($in['body'] ?? '', 2000);
        if ($bodyText === '') json_response(['error' => 'Comment cannot be empty'], 422);
        $pdo->prepare('INSERT INTO project_task_comments (task_id, user_id, body) VALUES (?, ?, ?)')
            ->execute([$taskId, $uid, $bodyText]);
        json_response(['ok' => true]);
        break;

    case 'GET:activity':
        $projectId = (int)($_GET['project_id'] ?? 0);
        $teamId = project_team_id($projectId);
        if (!$teamId) json_response(['error' => 'Not found'], 404);
        require_team_member($teamId);
        $stmt = $pdo->prepare('SELECT a.*, u.name AS user_name FROM project_activity_log a
                               JOIN users u ON u.id = a.user_id WHERE a.project_id = ?
                               ORDER BY a.created_at DESC LIMIT 30');
        $stmt->execute([$projectId]);
        json_response(['activity' => $stmt->fetchAll()]);
        break;

    default:
        json_response(['error' => 'Unknown route'], 404);
}
