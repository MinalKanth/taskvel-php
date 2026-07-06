<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();

$uid    = current_user_id();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$in     = body();

/** Tasks visible to the user: owned OR shared-with (accepted) */
function visible_task_ids(int $uid): array
{
    // Returns [sqlFragment, params] — parameterized rather than interpolating
    // $uid directly into the SQL string, even though the int type-hint already
    // closes off injection here; kept consistent with every other query.
    return [
        '(SELECT id FROM tasks WHERE owner_id = ?
          UNION
          SELECT ts.task_id FROM task_shares ts WHERE ts.shared_with_user_id = ? AND ts.status = \'accepted\')',
        [$uid, $uid],
    ];
}

function user_can_view(PDO $pdo, int $taskId, int $uid): bool
{
    $stmt = $pdo->prepare("SELECT 1 FROM tasks WHERE id = ? AND owner_id = ?
                            UNION
                            SELECT 1 FROM task_shares WHERE task_id = ? AND shared_with_user_id = ? AND status = 'accepted'");
    $stmt->execute([$taskId, $uid, $taskId, $uid]);
    return (bool)$stmt->fetch();
}

function user_can_edit(PDO $pdo, int $taskId, int $uid): bool
{
    $stmt = $pdo->prepare('SELECT owner_id FROM tasks WHERE id = ?');
    $stmt->execute([$taskId]);
    $t = $stmt->fetch();
    if (!$t) return false;
    if ((int)$t['owner_id'] === $uid) return true;

    $stmt = $pdo->prepare("SELECT permission FROM task_shares WHERE task_id = ? AND shared_with_user_id = ? AND status = 'accepted'");
    $stmt->execute([$taskId, $uid]);
    $share = $stmt->fetch();
    return $share && $share['permission'] === 'edit';
}

function full_task(PDO $pdo, int $taskId): array
{
    $stmt = $pdo->prepare('SELECT t.*, u.name AS owner_name FROM tasks t JOIN users u ON u.id = t.owner_id WHERE t.id = ?');
    $stmt->execute([$taskId]);
    $task = $stmt->fetch();
    if (!$task) return [];

    $steps = $pdo->prepare('SELECT id, title, done, position FROM task_steps WHERE task_id = ? ORDER BY position');
    $steps->execute([$taskId]);
    $task['steps'] = $steps->fetchAll();

    $tags = $pdo->prepare('SELECT tg.id, tg.name, tg.color FROM tags tg JOIN task_tags tt ON tt.tag_id = tg.id WHERE tt.task_id = ?');
    $tags->execute([$taskId]);
    $task['tags'] = $tags->fetchAll();

    $shares = $pdo->prepare("SELECT id, invite_email, permission, status, shared_with_user_id FROM task_shares WHERE task_id = ?");
    $shares->execute([$taskId]);
    $task['shares'] = $shares->fetchAll();

    return $task;
}

$pdo = db();

switch ("$method:$action") {

    // ---------------- LIST (with filters) ----------------
    case 'GET:list':
        [$visibleSql, $visibleParams] = visible_task_ids($uid);
        $sql = "SELECT t.* FROM tasks t WHERE t.id IN $visibleSql";
        $params = $visibleParams;

        $status = one_of($_GET['status'] ?? '', ['todo', 'in_progress', 'done'], '');
        $priority = one_of($_GET['priority'] ?? '', ['low', 'medium', 'high', 'critical'], '');
        if ($status !== '') { $sql .= ' AND t.status = ?'; $params[] = $status; }
        if ($priority !== '') { $sql .= ' AND t.priority = ?'; $params[] = $priority; }
        if (isset($_GET['pinned'])) { $sql .= ' AND t.pinned = ?'; $params[] = (int)$_GET['pinned']; }
        if (!empty($_GET['due_from'])) { $sql .= ' AND t.due_date >= ?'; $params[] = clean_str($_GET['due_from'], 10); }
        if (!empty($_GET['due_to']))   { $sql .= ' AND t.due_date <= ?'; $params[] = clean_str($_GET['due_to'], 10); }
        if (!empty($_GET['search']))   { $sql .= ' AND (t.title LIKE ? OR t.description LIKE ?)'; $needle = '%' . clean_str($_GET['search'], 120) . '%'; $params[] = $needle; $params[] = $needle; }
        if (!empty($_GET['tag'])) {
            $sql .= ' AND t.id IN (SELECT tt.task_id FROM task_tags tt JOIN tags tg ON tg.id = tt.tag_id WHERE tg.name = ?)';
            $params[] = clean_str($_GET['tag'], 24);
        }

        $sql .= ' ORDER BY t.pinned DESC, t.position ASC, t.created_at DESC';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $tasks = $stmt->fetchAll();

        // attach steps + tags in bulk (avoid N+1 for large lists)
        $ids = array_column($tasks, 'id');
        if ($ids) {
            $in_ids = implode(',', array_fill(0, count($ids), '?'));
            $steps = $pdo->prepare("SELECT * FROM task_steps WHERE task_id IN ($in_ids) ORDER BY position");
            $steps->execute($ids);
            $stepsByTask = [];
            foreach ($steps->fetchAll() as $s) $stepsByTask[$s['task_id']][] = $s;

            $tags = $pdo->prepare("SELECT tt.task_id, tg.id, tg.name, tg.color FROM task_tags tt JOIN tags tg ON tg.id = tt.tag_id WHERE tt.task_id IN ($in_ids)");
            $tags->execute($ids);
            $tagsByTask = [];
            foreach ($tags->fetchAll() as $t) $tagsByTask[$t['task_id']][] = ['id' => $t['id'], 'name' => $t['name'], 'color' => $t['color']];

            foreach ($tasks as &$t) {
                $t['steps'] = $stepsByTask[$t['id']] ?? [];
                $t['tags']  = $tagsByTask[$t['id']] ?? [];
            }
        }

        json_response(['tasks' => $tasks]);
        break;

    // ---------------- SINGLE ----------------
    case 'GET:show':
        $id = (int)($_GET['id'] ?? 0);
        // SECURITY FIX: this previously returned any task by ID with no
        // ownership/visibility check at all — a straightforward IDOR that
        // let any authenticated user read any other user's task. Now scoped
        // to tasks the caller owns or has an accepted share on.
        if (!user_can_view($pdo, $id, $uid)) json_response(['error' => 'Not found'], 404);
        $task = full_task($pdo, $id);
        if (!$task) json_response(['error' => 'Not found'], 404);
        json_response(['task' => $task]);
        break;

    // ---------------- CREATE ----------------
    case 'POST:create':
        $status = one_of($in['status'] ?? 'todo', ['todo', 'in_progress', 'done'], 'todo');
        $priority = one_of($in['priority'] ?? 'medium', ['low', 'medium', 'high', 'critical'], 'medium');
        $stmt = $pdo->prepare('INSERT INTO tasks (owner_id, title, description, status, priority, urgent, important, due_date, due_time, recurrence_rule, estimate_minutes)
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([
            $uid,
            clean_str($in['title'] ?? 'Untitled task', 255),
            clean_str($in['description'] ?? '', 5000) ?: null,
            $status,
            $priority,
            !empty($in['urgent']) ? 1 : 0,
            !empty($in['important']) ? 1 : 0,
            clean_str($in['due_date'] ?? '', 10) ?: null,
            clean_str($in['due_time'] ?? '', 8) ?: null,
            clean_str($in['recurrence_rule'] ?? '', 100) ?: null,
            isset($in['estimate_minutes']) ? max(0, (int)$in['estimate_minutes']) : null,
        ]);
        $taskId = (int)$pdo->lastInsertId();

        foreach (array_slice($in['steps'] ?? [], 0, 100) as $i => $step) { // cap step count
            $stepTitle = is_array($step) ? ($step['title'] ?? '') : $step;
            $pdo->prepare('INSERT INTO task_steps (task_id, title, position) VALUES (?, ?, ?)')
                ->execute([$taskId, clean_str($stepTitle, 255), $i]);
        }
        foreach (array_slice($in['tags'] ?? [], 0, 30) as $tagName) { // cap tag count
            $tagId = upsert_tag($pdo, $uid, clean_str($tagName, 24));
            $pdo->prepare('INSERT IGNORE INTO task_tags (task_id, tag_id) VALUES (?, ?)')->execute([$taskId, $tagId]);
        }

        log_activity($pdo, $uid, $taskId, 'created', ['title' => clean_str($in['title'] ?? '', 255)]);
        json_response(['task' => full_task($pdo, $taskId)], 201);
        break;

    // ---------------- UPDATE ----------------
    case 'PUT:update':
        $id = (int)($_GET['id'] ?? 0);
        if (!user_can_edit($pdo, $id, $uid)) json_response(['error' => 'Forbidden'], 403);

        // Allowlisted columns only (prevents mass-assignment of arbitrary
        // fields like owner_id) — each value is also validated/sanitized
        // per its type before being bound as a parameter.
        $fieldRules = [
            'title'            => fn($v) => clean_str($v, 255),
            'description'      => fn($v) => clean_str($v, 5000),
            'status'           => fn($v) => one_of($v, ['todo', 'in_progress', 'done'], 'todo'),
            'priority'         => fn($v) => one_of($v, ['low', 'medium', 'high', 'critical'], 'medium'),
            'urgent'           => fn($v) => !empty($v) ? 1 : 0,
            'important'        => fn($v) => !empty($v) ? 1 : 0,
            'due_date'         => fn($v) => clean_str($v, 10),
            'due_time'         => fn($v) => clean_str($v, 8),
            'recurrence_rule'  => fn($v) => clean_str($v, 100),
            'pinned'           => fn($v) => !empty($v) ? 1 : 0,
            'position'         => fn($v) => max(0, (int)$v),
            'estimate_minutes' => fn($v) => max(0, (int)$v),
        ];
        $set = []; $params = [];
        foreach ($fieldRules as $f => $sanitize) {
            if (array_key_exists($f, $in)) { $set[] = "$f = ?"; $params[] = $sanitize($in[$f]); }
        }
        if ($set) {
            $params[] = $id;
            $pdo->prepare('UPDATE tasks SET ' . implode(', ', $set) . ' WHERE id = ?')->execute($params);
        }

        if (($in['status'] ?? null) === 'done') {
            $pdo->prepare("UPDATE tasks SET completed_at = NOW() WHERE id = ? AND completed_at IS NULL")->execute([$id]);
            log_activity($pdo, $uid, $id, 'completed', []);
            spawn_next_recurrence($pdo, $uid, $id);
        }

        if (isset($in['steps'])) {
            $pdo->prepare('DELETE FROM task_steps WHERE task_id = ?')->execute([$id]);
            foreach (array_slice($in['steps'], 0, 100) as $i => $step) {
                $pdo->prepare('INSERT INTO task_steps (task_id, title, done, position) VALUES (?, ?, ?, ?)')
                    ->execute([$id, clean_str($step['title'] ?? '', 255), !empty($step['done']) ? 1 : 0, $i]);
            }
        }

        if (isset($in['tags'])) {
            $pdo->prepare('DELETE FROM task_tags WHERE task_id = ?')->execute([$id]);
            foreach (array_slice($in['tags'], 0, 30) as $tagName) {
                $tagId = upsert_tag($pdo, $uid, clean_str($tagName, 24));
                $pdo->prepare('INSERT IGNORE INTO task_tags (task_id, tag_id) VALUES (?, ?)')->execute([$id, $tagId]);
            }
        }

        log_activity($pdo, $uid, $id, 'updated', array_keys($in));
        json_response(['task' => full_task($pdo, $id)]);
        break;

    // ---------------- DELETE ----------------
    case 'DELETE:delete':
        $id = (int)($_GET['id'] ?? 0);
        $stmt = $pdo->prepare('SELECT owner_id, title FROM tasks WHERE id = ?');
        $stmt->execute([$id]);
        $t = $stmt->fetch();
        if (!$t || (int)$t['owner_id'] !== $uid) json_response(['error' => 'Forbidden'], 403);

        $pdo->prepare('DELETE FROM tasks WHERE id = ?')->execute([$id]);
        log_activity($pdo, $uid, null, 'deleted', ['title' => $t['title']]);
        json_response(['ok' => true]);
        break;

    // ---------------- BULK ACTIONS ----------------
    case 'POST:bulk':
        $ids = array_slice(array_map('intval', $in['ids'] ?? []), 0, 500); // cap batch size
        $op  = $in['op'] ?? '';
        if (!$ids) json_response(['error' => 'No ids'], 422);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        if ($op === 'done') {
            $pdo->prepare("UPDATE tasks SET status='done', completed_at = NOW() WHERE id IN ($placeholders) AND owner_id = ?")
                ->execute([...$ids, $uid]);
        } elseif ($op === 'delete') {
            $pdo->prepare("DELETE FROM tasks WHERE id IN ($placeholders) AND owner_id = ?")->execute([...$ids, $uid]);
        } elseif ($op === 'pin') {
            $pdo->prepare("UPDATE tasks SET pinned = 1 WHERE id IN ($placeholders) AND owner_id = ?")->execute([...$ids, $uid]);
        } else {
            json_response(['error' => 'Unknown bulk op'], 422);
        }
        json_response(['ok' => true]);
        break;

    // ---------------- MATRIX (Eisenhower) ----------------
    case 'GET:matrix':
        [$visibleSql, $visibleParams] = visible_task_ids($uid);
        $stmt = $pdo->prepare("SELECT id, title, priority, urgent, important, status FROM tasks WHERE id IN $visibleSql AND status != 'done'");
        $stmt->execute($visibleParams);
        $tasks = $stmt->fetchAll();
        $quads = ['do' => [], 'schedule' => [], 'delegate' => [], 'delete' => []];
        foreach ($tasks as $t) {
            if ($t['urgent'] && $t['important']) $quads['do'][] = $t;
            elseif (!$t['urgent'] && $t['important']) $quads['schedule'][] = $t;
            elseif ($t['urgent'] && !$t['important']) $quads['delegate'][] = $t;
            else $quads['delete'][] = $t;
        }
        json_response(['matrix' => $quads]);
        break;

    default:
        json_response(['error' => 'Unknown route'], 404);
}

function upsert_tag(PDO $pdo, int $uid, string $name): int
{
    $stmt = $pdo->prepare('SELECT id FROM tags WHERE user_id = ? AND name = ?');
    $stmt->execute([$uid, $name]);
    $row = $stmt->fetch();
    if ($row) return (int)$row['id'];
    $pdo->prepare('INSERT INTO tags (user_id, name) VALUES (?, ?)')->execute([$uid, $name]);
    return (int)$pdo->lastInsertId();
}

function log_activity(PDO $pdo, int $uid, ?int $taskId, string $action, $meta): void
{
    $pdo->prepare('INSERT INTO activity_log (user_id, task_id, action, meta) VALUES (?, ?, ?, ?)')
        ->execute([$uid, $taskId, $action, json_encode($meta)]);
}

/**
 * If the completed task has a recurrence_rule (e.g. "FREQ=WEEKLY;INTERVAL=1" or
 * "FREQ=DAILY;INTERVAL=3" or "FREQ=MONTHLY;INTERVAL=1"), create the next
 * occurrence server-side so it appears on every device immediately —
 * no client needs to be open for the recurrence to fire.
 */
function spawn_next_recurrence(PDO $pdo, int $uid, int $taskId): void
{
    $stmt = $pdo->prepare('SELECT * FROM tasks WHERE id = ?');
    $stmt->execute([$taskId]);
    $task = $stmt->fetch();
    if (!$task || empty($task['recurrence_rule']) || !$task['due_date']) return;

    parse_str(str_replace(';', '&', $task['recurrence_rule']), $rule);
    $freq = strtoupper($rule['FREQ'] ?? 'WEEKLY');
    $interval = max(1, (int)($rule['INTERVAL'] ?? 1));

    $map = ['DAILY' => "+{$interval} day", 'WEEKLY' => "+{$interval} week", 'MONTHLY' => "+{$interval} month"];
    $modifier = $map[$freq] ?? "+{$interval} week";
    $nextDue = date('Y-m-d', strtotime($task['due_date'] . ' ' . $modifier));

    $ins = $pdo->prepare('INSERT INTO tasks (owner_id, title, description, status, priority, urgent, important,
                            due_date, due_time, recurrence_rule, recurrence_parent_id, estimate_minutes)
                            VALUES (?, ?, ?, "todo", ?, ?, ?, ?, ?, ?, ?, ?)');
    $ins->execute([
        $task['owner_id'], $task['title'], $task['description'], $task['priority'],
        $task['urgent'], $task['important'], $nextDue, $task['due_time'],
        $task['recurrence_rule'], $task['recurrence_parent_id'] ?: $taskId, $task['estimate_minutes'],
    ]);
    $newId = (int)$pdo->lastInsertId();

    // carry over checklist steps (unchecked) and tags
    $steps = $pdo->prepare('SELECT title, position FROM task_steps WHERE task_id = ?');
    $steps->execute([$taskId]);
    foreach ($steps->fetchAll() as $s) {
        $pdo->prepare('INSERT INTO task_steps (task_id, title, position) VALUES (?, ?, ?)')
            ->execute([$newId, $s['title'], $s['position']]);
    }
    $tags = $pdo->prepare('SELECT tag_id FROM task_tags WHERE task_id = ?');
    $tags->execute([$taskId]);
    foreach ($tags->fetchAll() as $t) {
        $pdo->prepare('INSERT IGNORE INTO task_tags (task_id, tag_id) VALUES (?, ?)')->execute([$newId, $t['tag_id']]);
    }

    log_activity($pdo, $uid, $newId, 'recurred', ['from_task_id' => $taskId, 'due_date' => $nextDue]);
}
