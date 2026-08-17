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
        // Card-level counts (subtasks, attachments, blockers) are rolled up
        // here via correlated subqueries so the board can render rich cards
        // (checklist progress, paperclip count, blocked badge) in one call
        // instead of N+1 requests per task.
        $stmt = $pdo->prepare('SELECT pt.*, u.name AS assignee_name, u.email AS assignee_email,
                                      c.name AS creator_name,
                                      (SELECT COUNT(*) FROM project_task_subtasks s WHERE s.task_id = pt.id) AS subtask_total,
                                      (SELECT COUNT(*) FROM project_task_subtasks s WHERE s.task_id = pt.id AND s.done = 1) AS subtask_done,
                                      (SELECT COUNT(*) FROM attachments a WHERE a.project_task_id = pt.id) AS attachment_count,
                                      (SELECT COUNT(*) FROM project_task_dependencies d
                                        JOIN project_tasks blk ON blk.id = d.depends_on_id
                                        WHERE d.task_id = pt.id AND blk.status <> \'done\') AS blocked_by_open_count
                               FROM project_tasks pt
                               LEFT JOIN users u ON u.id = pt.assignee_id
                               LEFT JOIN users c ON c.id = pt.created_by
                               WHERE pt.project_id = ?
                               ORDER BY FIELD(pt.status,\'todo\',\'in_progress\',\'done\'),
                                        FIELD(pt.priority,\'critical\',\'high\',\'medium\',\'low\'),
                                        pt.due_date IS NULL, pt.due_date ASC, pt.created_at DESC');
        $stmt->execute([$projectId]);
        $tasks = $stmt->fetchAll();

        // Attach labels to each task in one extra query rather than one per task.
        if ($tasks) {
            $ids = array_column($tasks, 'id');
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $lstmt = $pdo->prepare("SELECT tl.task_id, l.id, l.name, l.color FROM project_task_labels tl
                                     JOIN project_labels l ON l.id = tl.label_id WHERE tl.task_id IN ($placeholders)");
            $lstmt->execute($ids);
            $byTask = [];
            foreach ($lstmt->fetchAll() as $row) {
                $byTask[$row['task_id']][] = ['id' => (int)$row['id'], 'name' => $row['name'], 'color' => $row['color']];
            }
            foreach ($tasks as &$t) { $t['labels'] = $byTask[$t['id']] ?? []; }
            unset($t);
        }
        json_response(['tasks' => $tasks]);
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

        // Guard: don't let a task be marked done while it's still blocked by
        // an open dependency, unless the caller explicitly overrides it.
        if (($in['status'] ?? $task['status']) === 'done' && $task['status'] !== 'done' && empty($in['force'])) {
            $bstmt = $pdo->prepare('SELECT t.title FROM project_task_dependencies d JOIN project_tasks t ON t.id = d.depends_on_id
                                     WHERE d.task_id = ? AND t.status <> \'done\'');
            $bstmt->execute([$id]);
            $openBlockers = $bstmt->fetchAll();
            if ($openBlockers) {
                json_response(['error' => 'This task is still blocked by: ' . implode(', ', array_column($openBlockers, 'title')),
                               'blocked' => true], 409);
            }
        }

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

    // ============================================================
    // SUBTASKS — checklist inside a board task.
    // ============================================================
    case 'GET:subtasks':
        $taskId = (int)($_GET['task_id'] ?? 0);
        $projectId = task_project_id($taskId);
        if (!$projectId) json_response(['error' => 'Not found'], 404);
        require_team_member(project_team_id($projectId));
        $stmt = $pdo->prepare('SELECT * FROM project_task_subtasks WHERE task_id = ? ORDER BY position ASC, id ASC');
        $stmt->execute([$taskId]);
        json_response(['subtasks' => $stmt->fetchAll()]);
        break;

    case 'POST:subtask-add':
        $taskId = (int)($in['task_id'] ?? 0);
        $ctx = project_task_edit_context($taskId, $uid);
        if (!$ctx) json_response(['error' => 'Not found'], 404);
        [$projectId, , , $canEdit] = $ctx;
        if (!$canEdit) json_response(['error' => 'You cannot edit this task'], 403);
        $title = clean_str($in['title'] ?? '', 255);
        if ($title === '') json_response(['error' => 'Subtask title is required'], 422);
        $pos = (int)$pdo->query("SELECT COALESCE(MAX(position),-1)+1 FROM project_task_subtasks WHERE task_id = $taskId")->fetchColumn();
        $stmt = $pdo->prepare('INSERT INTO project_task_subtasks (task_id, title, position, created_by) VALUES (?, ?, ?, ?)');
        $stmt->execute([$taskId, $title, $pos, $uid]);
        json_response(['ok' => true, 'id' => (int)$pdo->lastInsertId()], 201);
        break;

    case 'POST:subtask-toggle':
        $id = (int)($in['id'] ?? 0);
        $stmt = $pdo->prepare('SELECT task_id, done FROM project_task_subtasks WHERE id = ?');
        $stmt->execute([$id]);
        $sub = $stmt->fetch();
        if (!$sub) json_response(['error' => 'Not found'], 404);
        $ctx = project_task_edit_context((int)$sub['task_id'], $uid);
        if (!$ctx || !$ctx[3]) json_response(['error' => 'You cannot edit this task'], 403);
        $pdo->prepare('UPDATE project_task_subtasks SET done = ? WHERE id = ?')->execute([$sub['done'] ? 0 : 1, $id]);
        json_response(['ok' => true]);
        break;

    case 'POST:subtask-reorder':
        $taskId = (int)($in['task_id'] ?? 0);
        $ctx = project_task_edit_context($taskId, $uid);
        if (!$ctx || !$ctx[3]) json_response(['error' => 'You cannot edit this task'], 403);
        $order = is_array($in['order'] ?? null) ? $in['order'] : [];
        $stmt = $pdo->prepare('UPDATE project_task_subtasks SET position = ? WHERE id = ? AND task_id = ?');
        foreach (array_values($order) as $i => $subId) { $stmt->execute([$i, (int)$subId, $taskId]); }
        json_response(['ok' => true]);
        break;

    case 'DELETE:subtask-delete':
        $id = (int)($_GET['id'] ?? 0);
        $stmt = $pdo->prepare('SELECT task_id FROM project_task_subtasks WHERE id = ?');
        $stmt->execute([$id]);
        $taskId = $stmt->fetchColumn();
        if (!$taskId) json_response(['error' => 'Not found'], 404);
        $ctx = project_task_edit_context((int)$taskId, $uid);
        if (!$ctx || !$ctx[3]) json_response(['error' => 'You cannot edit this task'], 403);
        $pdo->prepare('DELETE FROM project_task_subtasks WHERE id = ?')->execute([$id]);
        json_response(['ok' => true]);
        break;

    // ============================================================
    // LABELS — project-scoped, coloured tags.
    // ============================================================
    case 'GET:labels':
        $projectId = (int)($_GET['project_id'] ?? 0);
        $teamId = project_team_id($projectId);
        if (!$teamId) json_response(['error' => 'Not found'], 404);
        require_team_member($teamId);
        $stmt = $pdo->prepare('SELECT * FROM project_labels WHERE project_id = ? ORDER BY name ASC');
        $stmt->execute([$projectId]);
        json_response(['labels' => $stmt->fetchAll()]);
        break;

    case 'POST:label-create':
        $projectId = (int)($in['project_id'] ?? 0);
        $teamId = project_team_id($projectId);
        if (!$teamId) json_response(['error' => 'Not found'], 404);
        require_team_member($teamId); // any member can spin up a new label, same as Asana/Jira tag creation
        $name = clean_str($in['name'] ?? '', 40);
        if ($name === '') json_response(['error' => 'Label name is required'], 422);
        $color = preg_match('/^#[0-9a-fA-F]{6}$/', (string)($in['color'] ?? '')) ? $in['color'] : '#4f46e5';
        try {
            $stmt = $pdo->prepare('INSERT INTO project_labels (project_id, name, color) VALUES (?, ?, ?)');
            $stmt->execute([$projectId, $name, $color]);
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) json_response(['error' => 'A label with that name already exists'], 422);
            throw $e;
        }
        json_response(['ok' => true, 'id' => (int)$pdo->lastInsertId(), 'name' => $name, 'color' => $color], 201);
        break;

    case 'DELETE:label-delete':
        $id = (int)($_GET['id'] ?? 0);
        $stmt = $pdo->prepare('SELECT project_id FROM project_labels WHERE id = ?');
        $stmt->execute([$id]);
        $projectId = $stmt->fetchColumn();
        if (!$projectId) json_response(['error' => 'Not found'], 404);
        require_team_manager(project_team_id((int)$projectId)); // deleting affects everyone's board — managers only
        $pdo->prepare('DELETE FROM project_labels WHERE id = ?')->execute([$id]);
        json_response(['ok' => true]);
        break;

    case 'POST:task-label-toggle':
        $taskId = (int)($in['task_id'] ?? 0);
        $labelId = (int)($in['label_id'] ?? 0);
        $ctx = project_task_edit_context($taskId, $uid);
        if (!$ctx || !$ctx[3]) json_response(['error' => 'You cannot edit this task'], 403);
        [$projectId] = $ctx;
        $stmt = $pdo->prepare('SELECT 1 FROM project_labels WHERE id = ? AND project_id = ?');
        $stmt->execute([$labelId, $projectId]);
        if (!$stmt->fetch()) json_response(['error' => 'Label not found in this project'], 404);
        if (!empty($in['on'])) {
            $pdo->prepare('INSERT IGNORE INTO project_task_labels (task_id, label_id) VALUES (?, ?)')->execute([$taskId, $labelId]);
        } else {
            $pdo->prepare('DELETE FROM project_task_labels WHERE task_id = ? AND label_id = ?')->execute([$taskId, $labelId]);
        }
        json_response(['ok' => true]);
        break;

    // ============================================================
    // DEPENDENCIES — "task is blocked by depends_on".
    // ============================================================
    case 'GET:dependencies':
        $taskId = (int)($_GET['task_id'] ?? 0);
        $projectId = task_project_id($taskId);
        if (!$projectId) json_response(['error' => 'Not found'], 404);
        require_team_member(project_team_id($projectId));
        $stmt = $pdo->prepare('SELECT d.id, d.depends_on_id, t.title, t.status FROM project_task_dependencies d
                               JOIN project_tasks t ON t.id = d.depends_on_id WHERE d.task_id = ? ORDER BY d.id ASC');
        $stmt->execute([$taskId]);
        $blockedBy = $stmt->fetchAll();
        $stmt2 = $pdo->prepare('SELECT d.id, d.task_id, t.title, t.status FROM project_task_dependencies d
                                JOIN project_tasks t ON t.id = d.task_id WHERE d.depends_on_id = ? ORDER BY d.id ASC');
        $stmt2->execute([$taskId]);
        $blocks = $stmt2->fetchAll();
        json_response(['blocked_by' => $blockedBy, 'blocks' => $blocks]);
        break;

    case 'POST:dependency-add':
        $taskId = (int)($in['task_id'] ?? 0);
        $dependsOnId = (int)($in['depends_on_id'] ?? 0);
        if ($taskId === $dependsOnId || !$dependsOnId) json_response(['error' => 'Pick a different task to depend on'], 422);
        $ctx = project_task_edit_context($taskId, $uid);
        if (!$ctx || !$ctx[3]) json_response(['error' => 'You cannot edit this task'], 403);
        [$projectId] = $ctx;
        // The task depended on must live in the same project.
        $stmt = $pdo->prepare('SELECT title FROM project_tasks WHERE id = ? AND project_id = ?');
        $stmt->execute([$dependsOnId, $projectId]);
        $target = $stmt->fetch();
        if (!$target) json_response(['error' => 'That task is not on this board'], 422);
        // Cheap cycle guard: reject if the target already (directly or
        // transitively, up to 10 hops) depends on this task.
        $seen = [$taskId]; $frontier = [$dependsOnId];
        for ($hop = 0; $hop < 10 && $frontier; $hop++) {
            $ph = implode(',', array_fill(0, count($frontier), '?'));
            $q = $pdo->prepare("SELECT depends_on_id FROM project_task_dependencies WHERE task_id IN ($ph)");
            $q->execute($frontier);
            $next = array_column($q->fetchAll(), 'depends_on_id');
            foreach ($next as $n) { if (in_array($n, $seen)) json_response(['error' => 'That would create a circular dependency'], 422); }
            $seen = array_merge($seen, $next);
            $frontier = $next;
        }
        try {
            $stmt = $pdo->prepare('INSERT INTO project_task_dependencies (task_id, depends_on_id, created_by) VALUES (?, ?, ?)');
            $stmt->execute([$taskId, $dependsOnId, $uid]);
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) json_response(['error' => 'That dependency already exists'], 422);
            throw $e;
        }
        log_project_activity($projectId, $uid, "linked \"{$target['title']}\" as a blocker");
        json_response(['ok' => true, 'id' => (int)$pdo->lastInsertId()], 201);
        break;

    case 'DELETE:dependency-delete':
        $id = (int)($_GET['id'] ?? 0);
        $stmt = $pdo->prepare('SELECT task_id FROM project_task_dependencies WHERE id = ?');
        $stmt->execute([$id]);
        $taskId = $stmt->fetchColumn();
        if (!$taskId) json_response(['error' => 'Not found'], 404);
        $ctx = project_task_edit_context((int)$taskId, $uid);
        if (!$ctx || !$ctx[3]) json_response(['error' => 'You cannot edit this task'], 403);
        $pdo->prepare('DELETE FROM project_task_dependencies WHERE id = ?')->execute([$id]);
        json_response(['ok' => true]);
        break;

    default:
        json_response(['error' => 'Unknown route'], 404);
}
