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
        $stmt = $pdo->prepare('SELECT state_json, version, updated_at FROM user_state WHERE user_id = ?');
        $stmt->execute([$uid]);
        $row = $stmt->fetch();
        if (!$row) { json_response(['state' => null, 'version' => 0]); break; }
        json_response([
            'state'      => json_decode($row['state_json'], true),
            'version'    => (int)$row['version'],
            'updated_at' => $row['updated_at'],
        ]);
        break;

    case 'POST:push':
        enforce_rate_limit("statepush:$uid", 120, 60);
        rate_limit_hit("statepush:$uid", 60);   // actually count each push
        $state = $in['state'] ?? null;
        $base  = (int)($in['base_version'] ?? -1);          // NEW: client's known version
        if (!is_array($state)) json_response(['error' => 'Missing or invalid state'], 422);
        $json = json_encode($state);
        if ($json === false) json_response(['error' => 'State could not be encoded'], 422);
        if (strlen($json) > 12 * 1024 * 1024) json_response(['error' => 'State payload too large'], 413);

        // Optimistic concurrency: the UPDATE only lands if nobody else pushed
        // since this client last pulled. 0 affected rows ⇒ conflict ⇒ 409.
        $stmt = $pdo->prepare('UPDATE user_state
                                SET state_json = ?, version = version + 1
                                WHERE user_id = ? AND version = ?');
        $stmt->execute([$json, $uid, $base]);

        if ($stmt->rowCount() === 0) {
            // Either the row doesn't exist yet (first push) or version mismatch.
            $ins = $pdo->prepare('INSERT IGNORE INTO user_state (user_id, state_json, version) VALUES (?, ?, 1)');
            $ins->execute([$uid, $json]);
            if ($ins->rowCount() === 0) {
                $cur = $pdo->prepare('SELECT version FROM user_state WHERE user_id = ?');
                $cur->execute([$uid]);
                json_response(['error' => 'conflict', 'server_version' => (int)$cur->fetchColumn()], 409);
            }
            json_response(['ok' => true, 'version' => 1]);
        }
        json_response(['ok' => true, 'version' => $base + 1]);
        break;

    default:
        json_response(['error' => 'Unknown route'], 404);
}
