<?php
require_once __DIR__ . '/../includes/teams.php';
require_once __DIR__ . '/../includes/notifications.php';
require_once __DIR__ . '/../includes/team_task_updates.php';
require_once __DIR__ . '/../includes/webpush.php';
require_login();

$uid = current_user_id();
$pdo = db();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$in = body();

switch ("$method:$action") {

    // Cross-team "assigned to me" feed for team tasks (the lighter-weight
    // task type, outside a Project) — mirrors project_tasks.php's
    // GET:my-tasks so the My Work view can merge both sources.
    case 'GET:my-tasks':
        $stmt = $pdo->prepare('SELECT tt.*, t.name AS team_name
                               FROM team_tasks tt
                               JOIN teams t ON t.id = tt.team_id
                               JOIN team_members tm ON tm.team_id = t.id AND tm.user_id = ?
                               WHERE tt.assignee_id = ?
                               ORDER BY tt.due_date IS NULL, tt.due_date ASC, FIELD(tt.priority,\'urgent\',\'high\',\'medium\',\'low\')');
        $stmt->execute([$uid, $uid]);
        json_response(['tasks' => $stmt->fetchAll()]);
        break;

    // All tasks for a team (any member can view).
    case 'GET:list':
        $teamId = (int)($_GET['team_id'] ?? 0);
        require_team_member($teamId);
        $stmt = $pdo->prepare('SELECT tt.*, u.name AS assignee_name, u.email AS assignee_email,
                                      c.name AS creator_name
                               FROM team_tasks tt
                               LEFT JOIN users u ON u.id = tt.assignee_id
                               LEFT JOIN users c ON c.id = tt.created_by
                               WHERE tt.team_id = ?
                               ORDER BY FIELD(tt.status,\'todo\',\'in_progress\',\'done\'),
                                        FIELD(tt.priority,\'urgent\',\'high\',\'medium\',\'low\'),
                                        tt.due_date IS NULL, tt.due_date ASC, tt.created_at DESC');
        $stmt->execute([$teamId]);
        json_response(['tasks' => $stmt->fetchAll()]);
        break;

    // Per-member progress rollup — lets owners/managers "monitor task progress" at a glance.
    case 'GET:summary':
        $teamId = (int)($_GET['team_id'] ?? 0);
        require_team_member($teamId);
        $stmt = $pdo->prepare('SELECT u.id, u.name,
                                      COUNT(tt.id) AS total,
                                      SUM(tt.status = \'done\') AS done,
                                      SUM(tt.status = \'in_progress\') AS in_progress,
                                      SUM(tt.status = \'todo\') AS todo,
                                      COALESCE(AVG(tt.progress), 0) AS avg_progress
                               FROM team_members tm
                               JOIN users u ON u.id = tm.user_id
                               LEFT JOIN team_tasks tt ON tt.assignee_id = u.id AND tt.team_id = ?
                               WHERE tm.team_id = ?
                               GROUP BY u.id, u.name
                               ORDER BY u.name');
        $stmt->execute([$teamId, $teamId]);
        json_response(['summary' => $stmt->fetchAll()]);
        break;

    // Assign a new task. Owners/managers can assign to anyone on the team;
    // regular members can only create a task assigned to themself.
    case 'POST:create':
        $teamId = (int)($in['team_id'] ?? 0);
        require_team_member($teamId);
        $title = clean_str($in['title'] ?? '', 255);
        if ($title === '') json_response(['error' => 'Task title is required'], 422);

        $assigneeId = !empty($in['assignee_id']) ? (int)$in['assignee_id'] : null;
        if ($assigneeId && $assigneeId !== $uid && !can_manage_team($teamId, $uid)) {
            json_response(['error' => 'Only team owners/managers can assign tasks to other people'], 403);
        }
        if ($assigneeId && !team_role($teamId, $assigneeId)) {
            json_response(['error' => 'Assignee is not on this team'], 422);
        }

        $dueDate = clean_str($in['due_date'] ?? '', 10) ?: null;
        if ($dueDate !== null && !DateTime::createFromFormat('Y-m-d', $dueDate)) {
            json_response(['error' => 'Invalid due date'], 422);
        }

        $stmt = $pdo->prepare('INSERT INTO team_tasks (team_id, title, description, priority, assignee_id, created_by, due_date)
                               VALUES (?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([
            $teamId, $title, clean_str($in['description'] ?? '', 3000),
            one_of($in['priority'] ?? 'medium', ['low', 'medium', 'high', 'urgent'], 'medium'),
            $assigneeId, $uid, $dueDate,
        ]);
        $taskId = (int)$pdo->lastInsertId();

        if ($assigneeId && $assigneeId !== $uid) {
            $stmt2 = $pdo->prepare('SELECT name FROM users WHERE id = ?');
            $stmt2->execute([$uid]);
            $byName = $stmt2->fetchColumn();

            try {
                create_notification(
                    $pdo, $assigneeId, 'team_task_assigned',
                    'New task assigned to you',
                    "$byName assigned you: \"$title\"",
                    './team.php?id=' . $teamId . '#team-tasks',
                    $taskId
                );
            } catch (Throwable $e) { /* in-app notification is best-effort — never block task creation */ }

            try {
                send_web_push_to_user($pdo, $assigneeId, [
                    'title' => 'New task assigned to you',
                    'body'  => "$byName assigned you: \"$title\"",
                    'url'   => './team.php?id=' . $teamId . '#team-tasks',
                    'tag'   => 'taskvel-team-task-assignment',
                ]);
            } catch (Throwable $e) { /* push is best-effort */ }
        }

        json_response(['ok' => true, 'task_id' => $taskId]);
        break;

    case 'POST:update':
        $id = (int)($in['id'] ?? 0);
        $stmt = $pdo->prepare('SELECT * FROM team_tasks WHERE id = ?');
        $stmt->execute([$id]);
        $task = $stmt->fetch();
        if (!$task) json_response(['error' => 'Not found'], 404);

        $teamId = (int)$task['team_id'];
        require_team_member($teamId);
        $isManager = can_manage_team($teamId, $uid);
        $isAssignee = $task['assignee_id'] == $uid;

        if ($isManager) {
            // Full edit rights: title, description, priority, due date, assignee, status, progress.
            $assigneeId = array_key_exists('assignee_id', $in) ? ($in['assignee_id'] ?: null) : $task['assignee_id'];
            if ($assigneeId && !team_role($teamId, $assigneeId)) json_response(['error' => 'Assignee is not on this team'], 422);

            $progress = isset($in['progress']) ? max(0, min(100, (int)$in['progress'])) : (int)$task['progress'];
            $status = one_of($in['status'] ?? $task['status'], ['todo', 'in_progress', 'done'], $task['status']);
            if ($status === 'done') $progress = 100;

            $stmt = $pdo->prepare('UPDATE team_tasks SET title = ?, description = ?, priority = ?, status = ?, progress = ?, assignee_id = ?, due_date = ? WHERE id = ?');
            $stmt->execute([
                isset($in['title']) ? clean_str($in['title'], 255) : $task['title'],
                isset($in['description']) ? clean_str($in['description'], 3000) : $task['description'],
                one_of($in['priority'] ?? $task['priority'], ['low', 'medium', 'high', 'urgent'], $task['priority']),
                $status, $progress,
                $assigneeId, isset($in['due_date']) ? (clean_str($in['due_date'], 10) ?: null) : $task['due_date'], $id,
            ]);
            if (isset($in['due_date']) && $in['due_date'] != $task['due_date']) {
                $pdo->prepare('UPDATE team_tasks SET due_soon_notified_at = NULL, overdue_notified_at = NULL WHERE id = ?')->execute([$id]);
            }

            $newAssignee = $in['assignee_id'] ?? $task['assignee_id'];
            if ($newAssignee != $task['assignee_id'] && $assigneeId && $assigneeId != $uid) {
                $stmt2 = $pdo->prepare('SELECT name FROM users WHERE id = ?'); $stmt2->execute([$uid]); $byName = $stmt2->fetchColumn();
                try {
                    create_notification($pdo, $assigneeId, 'team_task_assigned', 'Task assigned to you',
                        "\"{$task['title']}\" was assigned to you by $byName",
                        './team.php?id=' . $teamId . '#team-tasks', $id);
                } catch (Throwable $e) {}
                try {
                    send_web_push_to_user($pdo, $assigneeId, [
                        'title' => 'Task assigned to you',
                        'body'  => "\"{$task['title']}\" was assigned to you",
                        'url'   => './team.php?id=' . $teamId . '#team-tasks',
                        'tag'   => 'taskvel-team-task-assignment',
                    ]);
                } catch (Throwable $e) {}
            }
        } elseif ($isAssignee) {
            // Regular members (assignee) can update status and progress on their own task, nothing else.
            $progress = isset($in['progress']) ? max(0, min(100, (int)$in['progress'])) : (int)$task['progress'];
            $status = isset($in['status']) ? one_of($in['status'], ['todo', 'in_progress', 'done'], $task['status']) : $task['status'];
            if ($status === 'done') $progress = 100;
            if (!isset($in['progress']) && !isset($in['status'])) {
                json_response(['error' => 'Nothing to update'], 422);
            }
            $pdo->prepare('UPDATE team_tasks SET status = ?, progress = ? WHERE id = ?')->execute([$status, $progress, $id]);
        } else {
            json_response(['error' => 'You do not have permission to edit this task'], 403);
        }

        // Let the creator know when their task gets completed by someone else.
        $newStatus = $in['status'] ?? $task['status'];
        if ($newStatus === 'done' && $task['status'] !== 'done' && $task['created_by'] != $uid) {
            $stmt2 = $pdo->prepare('SELECT name FROM users WHERE id = ?'); $stmt2->execute([$uid]); $byName = $stmt2->fetchColumn();
            try {
                create_notification($pdo, (int)$task['created_by'], 'team_task_completed', 'Task completed ✓',
                    "$byName completed \"{$task['title']}\"", './team.php?id=' . $teamId . '#team-tasks', $id);
            } catch (Throwable $e) {}
            try {
                send_web_push_to_user($pdo, (int)$task['created_by'], [
                    'title' => 'Task completed ✓',
                    'body'  => "$byName completed \"{$task['title']}\"",
                    'url'   => './team.php?id=' . $teamId . '#team-tasks',
                    'tag'   => 'taskvel-team-task-completion',
                ]);
            } catch (Throwable $e) {}
        }

        json_response(['ok' => true]);
        break;

    // Feature 3: "Update Progress" — the assignee (or a manager) submits a
    // status/percentage update with optional notes and attachments. Unlike
    // POST:update (a full edit), this always writes a team_task_updates row
    // and notifies the task's "senior" (creator / team owner).
    case 'POST:progress-update':
        $id = (int)($in['id'] ?? 0);
        $stmt = $pdo->prepare('SELECT * FROM team_tasks WHERE id = ?');
        $stmt->execute([$id]);
        $task = $stmt->fetch();
        if (!$task) json_response(['error' => 'Not found'], 404);
        $teamId = (int)$task['team_id'];
        require_team_member($teamId);
        if ($task['assignee_id'] != $uid && !can_manage_team($teamId, $uid)) {
            json_response(['error' => 'Only the assignee or a team manager can post a progress update'], 403);
        }

        $statusTo = one_of($in['status'] ?? $task['status'], ['todo', 'in_progress', 'done'], $task['status']);
        $progressTo = isset($in['progress']) ? max(0, min(100, (int)$in['progress'])) : (int)$task['progress'];
        if ($statusTo === 'done') $progressTo = 100;
        $notes = clean_str($in['notes'] ?? '', 2000) ?: null;
        $attachmentIds = array_filter(array_map('intval', $in['attachment_ids'] ?? []));

        $pdo->beginTransaction();
        $pdo->prepare('UPDATE team_tasks SET status = ?, progress = ? WHERE id = ?')
            ->execute([$statusTo, $progressTo, $id]);
        $pdo->prepare('INSERT INTO team_task_updates (team_task_id, user_id, status_from, status_to, progress_from, progress_to, notes)
                       VALUES (?, ?, ?, ?, ?, ?, ?)')
            ->execute([$id, $uid, $task['status'], $statusTo, $task['progress'], $progressTo, $notes]);
        $updateId = (int)$pdo->lastInsertId();
        if ($attachmentIds) {
            // Only link files the current user staged against this exact task, never open to linking arbitrary attachment IDs.
            $placeholders = implode(',', array_fill(0, count($attachmentIds), '?'));
            $pdo->prepare("UPDATE attachments SET team_task_update_id = ?, team_task_id = NULL
                           WHERE id IN ($placeholders) AND uploaded_by = ? AND team_task_id = ? AND team_task_update_id IS NULL")
                ->execute(array_merge([$updateId], $attachmentIds, [$uid, $id]));
        }
        $pdo->commit();

        try { notify_team_task_update($pdo, $task, $uid, $statusTo, $progressTo, $notes); }
        catch (Throwable $e) { /* notifications are best-effort — the update itself already succeeded */ }

        json_response(['ok' => true, 'update_id' => $updateId]);
        break;

    // Full update history/timeline for one task — "keep a history of all updates".
    case 'GET:updates':
        $id = (int)($_GET['task_id'] ?? 0);
        $stmt = $pdo->prepare('SELECT team_id FROM team_tasks WHERE id = ?');
        $stmt->execute([$id]);
        $teamId = $stmt->fetchColumn();
        if (!$teamId) json_response(['error' => 'Not found'], 404);
        require_team_member((int)$teamId);
        $stmt = $pdo->prepare('SELECT ttu.*, u.name AS user_name
                               FROM team_task_updates ttu JOIN users u ON u.id = ttu.user_id
                               WHERE ttu.team_task_id = ? ORDER BY ttu.created_at DESC');
        $stmt->execute([$id]);
        json_response(['updates' => $stmt->fetchAll()]);
        break;

    case 'DELETE:delete':
        $id = (int)($_GET['id'] ?? 0);
        $stmt = $pdo->prepare('SELECT team_id, created_by, title FROM team_tasks WHERE id = ?');
        $stmt->execute([$id]);
        $task = $stmt->fetch();
        if (!$task) json_response(['error' => 'Not found'], 404);
        $teamId = (int)$task['team_id'];
        // Managers/owners can delete anything; regular members can delete only tasks they created themself.
        if (!can_manage_team($teamId, $uid) && $task['created_by'] != $uid) {
            json_response(['error' => 'Only managers (or the task creator) can delete this'], 403);
        }
        $pdo->prepare('DELETE FROM team_tasks WHERE id = ?')->execute([$id]);
        json_response(['ok' => true]);
        break;

    default:
        json_response(['error' => 'Unknown route'], 404);
}