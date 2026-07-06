<?php
require_once __DIR__ . '/../includes/auth.php';

$action = $_GET['action'] ?? '';
$in = body();

switch ($action) {
    case 'register':
        $res = register_user($in['name'] ?? '', $in['email'] ?? '', $in['password'] ?? '');
        if (!$res['ok']) json_response(['error' => $res['error']], 422);
        session_regenerate_id(true);
        $_SESSION['user_id'] = $res['user_id'];
        $_SESSION['session_started_at'] = time();
        $_SESSION['last_activity'] = time();
        $_SESSION['last_rotated_at'] = time();
        unset($_SESSION['csrf_token']);
        json_response(['ok' => true, 'user' => current_user()]);
        break;

    case 'login':
        $res = attempt_login($in['email'] ?? '', $in['password'] ?? '');
        if (!$res['ok']) json_response(['error' => $res['error']], 401);
        json_response(['ok' => true, 'user' => current_user()]);
        break;

    case 'logout':
        require_csrf(); // state-changing — require the token even for logout
        audit_log(current_user_id(), 'logout', []);
        $_SESSION = [];
        session_destroy();
        json_response(['ok' => true]);
        break;

    case 'me':
        $u = current_user();
        if (!$u) json_response(['error' => 'Unauthenticated'], 401);
        json_response(['user' => $u]);
        break;

    default:
        json_response(['error' => 'Unknown action'], 404);
}
