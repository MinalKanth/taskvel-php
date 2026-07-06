<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();

$uid = current_user_id();
$pdo = db();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$in = body();

switch ("$method:$action") {

    case 'GET:pull':
        $stmt = $pdo->prepare('SELECT state_json, updated_at FROM user_state WHERE user_id = ?');
        $stmt->execute([$uid]);
        $row = $stmt->fetch();
        if (!$row) { json_response(['state' => null]); break; }
        json_response(['state' => json_decode($row['state_json'], true), 'updated_at' => $row['updated_at']]);
        break;

    case 'POST:push':
        enforce_rate_limit("statepush:$uid", 120, 60); // 120 pushes/min/user — generous for the 600ms client debounce, but bounds abuse
        $state = $in['state'] ?? null;
        if (!is_array($state)) json_response(['error' => 'Missing or invalid state'], 422);
        $json = json_encode($state);
        if ($json === false) json_response(['error' => 'State could not be encoded'], 422);
        if (strlen($json) > 12 * 1024 * 1024) json_response(['error' => 'State payload too large'], 413);
        $stmt = $pdo->prepare('INSERT INTO user_state (user_id, state_json) VALUES (?, ?)
                                ON DUPLICATE KEY UPDATE state_json = VALUES(state_json)');
        $stmt->execute([$uid, $json]);
        json_response(['ok' => true]);
        break;

    default:
        json_response(['error' => 'Unknown route'], 404);
}
