<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();

$uid = current_user_id();
$pdo = db();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$in = body();

function can_access_task(PDO $pdo, int $taskId, int $uid): bool
{
    $stmt = $pdo->prepare("SELECT 1 FROM tasks WHERE id = ? AND owner_id = ?
                            UNION
                            SELECT 1 FROM task_shares WHERE task_id = ? AND shared_with_user_id = ? AND status='accepted'");
    $stmt->execute([$taskId, $uid, $taskId, $uid]);
    return (bool)$stmt->fetch();
}

switch ("$method:$action") {
    case 'GET:list':
        $taskId = (int)($_GET['task_id'] ?? 0);
        if (!can_access_task($pdo, $taskId, $uid)) json_response(['error' => 'Forbidden'], 403);
        $stmt = $pdo->prepare("SELECT r.*, u.name AS user_name FROM remarks r JOIN users u ON u.id = r.user_id
                                WHERE r.task_id = ? AND r.is_draft = 0 ORDER BY r.created_at ASC");
        $stmt->execute([$taskId]);
        json_response(['remarks' => $stmt->fetchAll()]);
        break;

    case 'POST:add':
        $taskId = (int)($in['task_id'] ?? 0);
        if (!can_access_task($pdo, $taskId, $uid)) json_response(['error' => 'Forbidden'], 403);
        $remarkBody = clean_str($in['body'] ?? '', 5000);
        if ($remarkBody === '') json_response(['error' => 'Remark cannot be empty'], 422);
        $pdo->prepare('INSERT INTO remarks (task_id, user_id, body, is_draft) VALUES (?, ?, ?, 0)')
            ->execute([$taskId, $uid, $remarkBody]);

        // notify the task owner if someone else commented
        $stmt = $pdo->prepare('SELECT owner_id, title FROM tasks WHERE id = ?');
        $stmt->execute([$taskId]);
        $task = $stmt->fetch();
        if ($task && (int)$task['owner_id'] !== $uid) {
            $me = current_user();
            $pdo->prepare('INSERT INTO notifications (user_id, type, title, body, task_id) VALUES (?, "comment", ?, ?, ?)')
                ->execute([$task['owner_id'], "{$me['name']} commented on \"{$task['title']}\"", mb_substr($remarkBody, 0, 120), $taskId]);
        }
        json_response(['ok' => true], 201);
        break;

    case 'POST:draft':
        $taskId = (int)($in['task_id'] ?? 0);
        if (!can_access_task($pdo, $taskId, $uid)) json_response(['error' => 'Forbidden'], 403);
        $pdo->prepare('DELETE FROM remarks WHERE task_id = ? AND user_id = ? AND is_draft = 1')->execute([$taskId, $uid]);
        $draftBody = clean_str($in['body'] ?? '', 5000);
        if ($draftBody !== '') {
            $pdo->prepare('INSERT INTO remarks (task_id, user_id, body, is_draft) VALUES (?, ?, ?, 1)')
                ->execute([$taskId, $uid, $draftBody]);
        }
        json_response(['ok' => true]);
        break;

    case 'DELETE:delete':
        $id = (int)($_GET['id'] ?? 0);
        $pdo->prepare('DELETE FROM remarks WHERE id = ? AND user_id = ?')->execute([$id, $uid]);
        json_response(['ok' => true]);
        break;

    default:
        json_response(['error' => 'Unknown route'], 404);
}
