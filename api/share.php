<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/mailer.php';
require_login();

$uid    = current_user_id();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$in     = body();
$pdo    = db();

switch ("$method:$action") {

    // Invite a collaborator by email to a task you own
    case 'POST:invite':
        $taskId = (int)($in['task_id'] ?? 0);
        $email  = strtolower(trim($in['email'] ?? ''));
        $perm   = in_array($in['permission'] ?? 'edit', ['view', 'edit']) ? $in['permission'] : 'edit';

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) json_response(['error' => 'Invalid email'], 422);

        $stmt = $pdo->prepare('SELECT owner_id, title FROM tasks WHERE id = ?');
        $stmt->execute([$taskId]);
        $task = $stmt->fetch();
        if (!$task || (int)$task['owner_id'] !== $uid) json_response(['error' => 'Forbidden'], 403);

        // is this email already a registered user?
        $userStmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $userStmt->execute([$email]);
        $existingUser = $userStmt->fetch();

        $token = bin2hex(random_bytes(24));

        $stmt = $pdo->prepare('INSERT INTO task_shares (task_id, owner_id, shared_with_user_id, invite_email, permission, status, invite_token)
                                VALUES (?, ?, ?, ?, ?, "pending", ?)
                                ON DUPLICATE KEY UPDATE permission = VALUES(permission), status = "pending", invite_token = VALUES(invite_token)');
        $stmt->execute([$taskId, $uid, $existingUser['id'] ?? null, $email, $perm, $token]);

        $inviter = current_user();
        send_invite_email($email, $inviter['name'], $task['title'], $token);

        if ($existingUser) {
            $pdo->prepare('INSERT INTO notifications (user_id, type, title, body, task_id) VALUES (?, "shared", ?, ?, ?)')
                ->execute([$existingUser['id'], "{$inviter['name']} shared a task with you", "\"{$task['title']}\" — $perm access", $taskId]);
        }

        json_response(['ok' => true, 'status' => 'pending']);
        break;

    // List all shares for a task you own
    case 'GET:list':
        $taskId = (int)($_GET['task_id'] ?? 0);
        $stmt = $pdo->prepare('SELECT id, invite_email, permission, status, invited_at, responded_at FROM task_shares WHERE task_id = ? AND owner_id = ?');
        $stmt->execute([$taskId, $uid]);
        json_response(['shares' => $stmt->fetchAll()]);
        break;

    // Tasks shared *with* the current user
    case 'GET:shared-with-me':
        $stmt = $pdo->prepare("SELECT t.*, u.name AS owner_name, ts.permission
                                FROM task_shares ts
                                JOIN tasks t ON t.id = ts.task_id
                                JOIN users u ON u.id = t.owner_id
                                WHERE ts.shared_with_user_id = ? AND ts.status = 'accepted'
                                ORDER BY t.due_date IS NULL, t.due_date ASC");
        $stmt->execute([$uid]);
        json_response(['tasks' => $stmt->fetchAll()]);
        break;

    // Revoke access
    case 'POST:revoke':
        $shareId = (int)($in['share_id'] ?? 0);
        $stmt = $pdo->prepare('UPDATE task_shares SET status = "revoked" WHERE id = ? AND owner_id = ?');
        $stmt->execute([$shareId, $uid]);
        json_response(['ok' => true]);
        break;

    default:
        json_response(['error' => 'Unknown route'], 404);
}
