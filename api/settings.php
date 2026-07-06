<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/vapid.php';
require_login();

$uid = current_user_id();
$pdo = db();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$in = body();

switch ("$method:$action") {

    // Public key the client needs to create a PushSubscription. Empty string
    // means push isn't configured yet — the client silently skips it.
    case 'GET:vapid-public-key':
        json_response(['key' => VAPID_PUBLIC_KEY]);
        break;

    // Returns (creating if needed) the private ICS subscribe URL
    case 'GET:calendar-link':
        $stmt = $pdo->prepare('SELECT token FROM calendar_feed_tokens WHERE user_id = ?');
        $stmt->execute([$uid]);
        $row = $stmt->fetch();
        if (!$row) {
            $token = bin2hex(random_bytes(24));
            $pdo->prepare('INSERT INTO calendar_feed_tokens (user_id, token) VALUES (?, ?)')->execute([$uid, $token]);
        } else {
            $token = $row['token'];
        }
        json_response(['url' => APP_URL . '/api/calendar-feed.php?token=' . $token]);
        break;

    // Save a browser's Web Push subscription so due-date reminders reach
    // this device even when Taskvel isn't open (send side needs a VAPID
    // keypair + a push library such as minishlink/web-push — wiring point
    // left here so it's a single place to plug that in).
    case 'POST:push-subscribe':
        $sub = $in['subscription'] ?? [];
        $endpoint = (string)($sub['endpoint'] ?? '');
        if ($endpoint === '' || !filter_var($endpoint, FILTER_VALIDATE_URL) || strlen($endpoint) > 500) {
            json_response(['error' => 'Invalid subscription'], 422);
        }
        $p256dh = clean_str($sub['keys']['p256dh'] ?? '', 255);
        $authKey = clean_str($sub['keys']['auth'] ?? '', 255);
        if ($p256dh === '' || $authKey === '') json_response(['error' => 'Invalid subscription keys'], 422);
        $stmt = $pdo->prepare('INSERT INTO push_subscriptions (user_id, endpoint, p256dh, auth, device_label)
                                VALUES (?, ?, ?, ?, ?)
                                ON DUPLICATE KEY UPDATE p256dh = VALUES(p256dh), auth = VALUES(auth)');
        $stmt->execute([$uid, $endpoint, $p256dh, $authKey, clean_str($in['device_label'] ?? '', 120) ?: null]);
        json_response(['ok' => true]);
        break;

    // Called once on app load per device — powers "synced across N devices" in Settings
    case 'POST:touch-device':
        $label = clean_str($in['device_label'] ?? 'Unknown device', 120) ?: 'Unknown device';
        $stmt = $pdo->prepare('SELECT id FROM user_devices WHERE user_id = ? AND device_label = ?');
        $stmt->execute([$uid, $label]);
        if ($row = $stmt->fetch()) {
            $pdo->prepare('UPDATE user_devices SET last_seen_at = NOW() WHERE id = ?')->execute([$row['id']]);
        } else {
            $pdo->prepare('INSERT INTO user_devices (user_id, device_label, user_agent) VALUES (?, ?, ?)')
                ->execute([$uid, $label, clean_str($_SERVER['HTTP_USER_AGENT'] ?? '', 255) ?: null]);
        }
        json_response(['ok' => true]);
        break;

    case 'GET:devices':
        $stmt = $pdo->prepare('SELECT device_label, last_seen_at FROM user_devices WHERE user_id = ? ORDER BY last_seen_at DESC');
        $stmt->execute([$uid]);
        json_response(['devices' => $stmt->fetchAll()]);
        break;

    default:
        json_response(['error' => 'Unknown route'], 404);
}
