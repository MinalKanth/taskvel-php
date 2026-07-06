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
        $stmt = $pdo->prepare('SELECT * FROM templates WHERE user_id = ? ORDER BY created_at DESC');
        $stmt->execute([$uid]);
        $rows = $stmt->fetchAll();
        foreach ($rows as &$r) $r['payload'] = json_decode($r['payload'], true);
        json_response(['templates' => $rows]);
        break;

    case 'POST:create':
        $pdo->prepare('INSERT INTO templates (user_id, name, payload) VALUES (?, ?, ?)')
            ->execute([$uid, $in['name'] ?? 'Untitled template', json_encode($in['payload'] ?? [])]);
        json_response(['ok' => true, 'id' => (int)$pdo->lastInsertId()], 201);
        break;

    case 'DELETE:delete':
        $id = (int)($_GET['id'] ?? 0);
        $pdo->prepare('DELETE FROM templates WHERE id = ? AND user_id = ?')->execute([$id, $uid]);
        json_response(['ok' => true]);
        break;

    default:
        json_response(['error' => 'Unknown route'], 404);
}
