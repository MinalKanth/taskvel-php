<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();

$uid    = current_user_id();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$in     = body();
$pdo    = db();

switch ("$method:$action") {

    // ---------------- LIST ----------------
    case 'GET:list':
        $stmt = $pdo->prepare('SELECT * FROM personal_tasks WHERE user_id = ? ORDER BY order_num ASC, id ASC');
        $stmt->execute([$uid]);
        json_response(['tasks' => array_map('personal_task_out', $stmt->fetchAll())]);
        break;

    // ---------------- CREATE / UPDATE (upsert by client_id) ----------------
    case 'POST:upsert':
    case 'POST:create':
    case 'PUT:update':
        $clientId = (int)($in['id'] ?? $_GET['id'] ?? 0);
        if ($clientId <= 0) json_response(['error' => 'Missing task id'], 422);
        upsert_personal_task($pdo, $uid, $clientId, $in);
        json_response(['ok' => true]);
        break;

    // ---------------- DELETE ----------------
    case 'DELETE:delete':
        $clientId = (int)($_GET['id'] ?? 0);
        if ($clientId <= 0) json_response(['error' => 'Missing task id'], 422);
        $pdo->prepare('DELETE FROM personal_tasks WHERE user_id = ? AND client_id = ?')->execute([$uid, $clientId]);
        json_response(['ok' => true]);
        break;

    // ---------------- BULK DELETE ----------------
    case 'POST:bulk-delete':
        $ids = array_slice(array_map('intval', $in['ids'] ?? []), 0, 500);
        if (!$ids) json_response(['error' => 'No ids'], 422);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $pdo->prepare("DELETE FROM personal_tasks WHERE user_id = ? AND client_id IN ($placeholders)")
            ->execute([$uid, ...$ids]);
        json_response(['ok' => true]);
        break;

    // ---------------- REORDER ----------------
    case 'POST:reorder':
        // body: { order: [clientId, clientId, ...] } — array position becomes order_num
        $order = array_slice(array_map('intval', $in['order'] ?? []), 0, 2000);
        foreach ($order as $i => $cid) {
            $pdo->prepare('UPDATE personal_tasks SET order_num = ? WHERE user_id = ? AND client_id = ?')
                ->execute([$i, $uid, $cid]);
        }
        json_response(['ok' => true]);
        break;

    default:
        json_response(['error' => 'Unknown route'], 404);
}

function personal_task_out(array $r): array
{
    return [
        'id'        => (int)$r['client_id'],
        'name'      => $r['name'],
        'person'    => $r['person'],
        'collab'    => $r['collab'],
        'urgency'   => $r['urgency'],
        'damage'    => $r['damage'],
        'rank'      => $r['rank_val'],
        'score'     => (int)$r['score'],
        'deadline'  => $r['deadline'],
        'recur'     => $r['recur'],
        'tags'      => json_decode($r['tags']  ?? '[]', true) ?: [],
        'links'     => json_decode($r['links'] ?? '[]', true) ?: [],
        'steps'     => json_decode($r['steps'] ?? '[]', true) ?: [],
        'pinned'    => (bool)$r['pinned'],
        'done'      => (bool)$r['done'],
        'doneAt'    => $r['done_at'],
        'addedOn'   => $r['added_on'],
        'order'     => (int)$r['order_num'],
        'timeSpent' => (int)$r['time_spent'],
        'timeTrackingStarted' => $r['time_tracking_started'] !== null ? (int)$r['time_tracking_started'] : null,
        'updatedAt' => (int)$r['updated_at'],
        'status'    => $r['status'] ?? 'todo',
    ];
}

/**
 * Insert-or-update by (user_id, client_id). updated_at is a simple
 * last-write-wins guard: a stale device replaying an old copy of a task
 * can't clobber a newer edit that already landed from another device.
 */
function upsert_personal_task(PDO $pdo, int $uid, int $clientId, array $in): void
{
    $updatedAt = (int)($in['updatedAt'] ?? round(microtime(true) * 1000));

    $stmt = $pdo->prepare('SELECT updated_at FROM personal_tasks WHERE user_id = ? AND client_id = ?');
    $stmt->execute([$uid, $clientId]);
    $existing = $stmt->fetch();
    if ($existing && (int)$existing['updated_at'] > $updatedAt) {
        return; // a newer version is already stored — ignore this stale write
    }

    $fields = [
        'name'      => clean_str($in['name'] ?? '', 500),
        'person'    => clean_str($in['person'] ?? '', 255),
        'collab'    => clean_str($in['collab'] ?? '', 255),
        'urgency'   => one_of($in['urgency'] ?? 'medium', ['critical', 'high', 'medium', 'low'], 'medium'),
        'damage'    => one_of($in['damage'] ?? 'moderate', ['severe', 'moderate', 'minor'], 'moderate'),
        'rank_val'  => one_of($in['rank'] ?? 'medium', ['critical', 'high', 'medium', 'low'], 'medium'),
        'score'     => (int)($in['score'] ?? 0),
        'deadline'  => !empty($in['deadline']) ? clean_str($in['deadline'], 10) : null,
        'recur'     => clean_str($in['recur'] ?? 'none', 30),
        'tags'      => json_encode(array_slice($in['tags'] ?? [], 0, 60)),
        'links'     => json_encode(array_slice($in['links'] ?? [], 0, 60)),
        'steps'     => json_encode(array_slice($in['steps'] ?? [], 0, 200)),
        'pinned'    => !empty($in['pinned']) ? 1 : 0,
        'done'      => !empty($in['done']) ? 1 : 0,
        'done_at'   => $in['doneAt'] ?? null,
        'added_on'  => !empty($in['addedOn']) ? date('Y-m-d H:i:s', strtotime($in['addedOn'])) : date('Y-m-d H:i:s'),
        'order_num' => (int)($in['order'] ?? 0),
        'time_spent' => (int)($in['timeSpent'] ?? 0),
        'time_tracking_started' => isset($in['timeTrackingStarted']) && $in['timeTrackingStarted'] !== null ? (int)$in['timeTrackingStarted'] : null,
        'updated_at' => $updatedAt,
        'status'    => one_of($in['status'] ?? 'todo', ['todo', 'doing', 'done'], 'todo'),
    ];

    if ($existing) {
        $set = implode(', ', array_map(fn($k) => "$k = ?", array_keys($fields)));
        $pdo->prepare("UPDATE personal_tasks SET $set WHERE user_id = ? AND client_id = ?")
            ->execute([...array_values($fields), $uid, $clientId]);
    } else {
        $cols = implode(', ', array_keys($fields));
        $qs   = implode(', ', array_fill(0, count($fields) + 2, '?'));
        $pdo->prepare("INSERT INTO personal_tasks (user_id, client_id, $cols) VALUES ($qs)")
            ->execute([$uid, $clientId, ...array_values($fields)]);
    }
}