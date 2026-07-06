<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();

$uid = current_user_id();
$pdo = db();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$in = body();

switch ("$method:$action") {
    case 'GET:list':
        $stmt = $pdo->prepare('SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 50');
        $stmt->execute([$uid]);
        json_response(['notifications' => $stmt->fetchAll()]);
        break;

    case 'POST:mark-seen':
        $pdo->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = ?')->execute([$uid]);
        json_response(['ok' => true]);
        break;

    case 'GET:unread-count':
        $stmt = $pdo->prepare('SELECT COUNT(*) c FROM notifications WHERE user_id = ? AND is_read = 0');
        $stmt->execute([$uid]);
        json_response(['count' => (int)$stmt->fetch()['c']]);
        break;

    default:
        json_response(['error' => 'Unknown route'], 404);
}
