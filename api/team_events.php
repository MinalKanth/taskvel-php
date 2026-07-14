<?php
require_once __DIR__ . '/../includes/teams.php';
require_login();

$uid = current_user_id();
$pdo = db();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$in = body();

// ────────────────────────────────────────────────────────────
// Helpers
// ────────────────────────────────────────────────────────────

// Load one event row or 404.
function load_event(int $id): array
{
    $stmt = db()->prepare('SELECT * FROM team_events WHERE id = ?');
    $stmt->execute([$id]);
    $ev = $stmt->fetch();
    if (!$ev) json_response(['error' => 'Event not found'], 404);
    return $ev;
}

// Attach attendees (id, name, email, role-in-team, rsvp status) to a list of events.
function attach_attendees(array $events): array
{
    if (!$events) return $events;
    $ids = array_column($events, 'id');
    $ph = implode(',', array_fill(0, count($ids), '?'));
    $stmt = db()->prepare(
        "SELECT tea.event_id, tea.status AS rsvp, u.id, u.name, u.email, tm.role
         FROM team_event_attendees tea
         JOIN users u ON u.id = tea.user_id
         JOIN team_events te ON te.id = tea.event_id
         LEFT JOIN team_members tm ON tm.team_id = te.team_id AND tm.user_id = u.id
         WHERE tea.event_id IN ($ph)
         ORDER BY FIELD(COALESCE(tm.role,'member'),'owner','manager','member'), u.name"
    );
    $stmt->execute($ids);
    $byEvent = [];
    foreach ($stmt->fetchAll() as $row) {
        $byEvent[$row['event_id']][] = [
            'id' => (int)$row['id'], 'name' => $row['name'], 'email' => $row['email'],
            'role' => $row['role'] ?: 'member', 'rsvp' => $row['rsvp'],
        ];
    }
    foreach ($events as &$e) {
        $e['attendees'] = $byEvent[$e['id']] ?? [];
        $e['attendee_count'] = count($e['attendees']);
    }
    return $events;
}

// Validate + save the attendee list for an event. Every attendee must be a
// member of the event's team — this is the guarantee that an event always
// shows real team members.
function save_attendees(int $eventId, int $teamId, array $attendeeIds, int $creatorId): void
{
    $pdo = db();
    $attendeeIds = array_values(array_unique(array_map('intval', $attendeeIds)));
    // The creator is always on the list (and defaults to "going").
    if (!in_array($creatorId, $attendeeIds, true)) $attendeeIds[] = $creatorId;

    foreach ($attendeeIds as $aid) {
        if (!team_role($teamId, $aid)) {
            json_response(['error' => 'One of the selected attendees is not a member of this team'], 422);
        }
    }
    $pdo->prepare('DELETE FROM team_event_attendees WHERE event_id = ?')->execute([$eventId]);
    $ins = $pdo->prepare('INSERT INTO team_event_attendees (event_id, user_id, status) VALUES (?, ?, ?)');
    foreach ($attendeeIds as $aid) {
        $ins->execute([$eventId, $aid, $aid === $creatorId ? 'going' : 'invited']);
    }
}

function valid_date(?string $d): ?string
{
    $d = trim((string)$d);
    return preg_match('/^\d{4}-\d{2}-\d{2}$/', $d) ? $d : null;
}

function valid_time(?string $t): ?string
{
    $t = trim((string)$t);
    if (preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $t)) return substr($t, 0, 5) . ':00';
    return null;
}

// ────────────────────────────────────────────────────────────
// Routes
// ────────────────────────────────────────────────────────────
switch ("$method:$action") {

    // All events for one team (any member can view), soonest first.
    case 'GET:list':
        $teamId = (int)($_GET['team_id'] ?? 0);
        require_team_member($teamId);
        $stmt = $pdo->prepare(
            'SELECT te.*, p.name AS project_name, p.color AS project_color, u.name AS creator_name
             FROM team_events te
             LEFT JOIN projects p ON p.id = te.project_id
             LEFT JOIN users u ON u.id = te.created_by
             WHERE te.team_id = ?
             ORDER BY te.event_date ASC, te.start_time IS NULL, te.start_time ASC, te.id ASC'
        );
        $stmt->execute([$teamId]);
        json_response(['events' => attach_attendees($stmt->fetchAll())]);
        break;

    // Every event across ALL teams the user belongs to — powers the
    // "check all the events at once" hub inside Taskvel Pro.
    case 'GET:all':
        $stmt = $pdo->prepare(
            'SELECT te.*, t.name AS team_name, p.name AS project_name, p.color AS project_color, u.name AS creator_name
             FROM team_events te
             JOIN teams t ON t.id = te.team_id
             JOIN team_members tm ON tm.team_id = te.team_id AND tm.user_id = ?
             LEFT JOIN projects p ON p.id = te.project_id
             LEFT JOIN users u ON u.id = te.created_by
             WHERE te.event_date >= (CURDATE() - INTERVAL 1 DAY)
             ORDER BY te.event_date ASC, te.start_time IS NULL, te.start_time ASC
             LIMIT 60'
        );
        $stmt->execute([$uid]);
        json_response(['events' => attach_attendees($stmt->fetchAll())]);
        break;

    case 'GET:get':
        $ev = load_event((int)($_GET['id'] ?? 0));
        require_team_member((int)$ev['team_id']);
        $events = attach_attendees([$ev]);
        json_response(['event' => $events[0]]);
        break;

    // Any team member can create an event for their team.
    case 'POST:create':
        $teamId = (int)($in['team_id'] ?? 0);
        require_team_member($teamId);
        enforce_rate_limit("event-create:$teamId:$uid", 30, 3600, 'Too many events created. Please wait a bit.');

        $title = clean_str($in['title'] ?? '', 190);
        $date  = valid_date($in['event_date'] ?? '');
        if ($title === '') json_response(['error' => 'Event title is required'], 422);
        if (!$date) json_response(['error' => 'A valid event date is required'], 422);

        $projectId = !empty($in['project_id']) ? (int)$in['project_id'] : null;
        if ($projectId && project_team_id($projectId) !== $teamId) {
            json_response(['error' => 'That project does not belong to this team'], 422);
        }

        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare(
                'INSERT INTO team_events (team_id, project_id, title, description, location, event_date, start_time, end_time, created_by)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $teamId, $projectId, $title,
                clean_str($in['description'] ?? '', 3000) ?: null,
                clean_str($in['location'] ?? '', 190) ?: null,
                $date, valid_time($in['start_time'] ?? ''), valid_time($in['end_time'] ?? ''), $uid,
            ]);
            $eventId = (int)$pdo->lastInsertId();
            save_attendees($eventId, $teamId, (array)($in['attendee_ids'] ?? []), $uid);
            if ($projectId) log_project_activity($projectId, $uid, "scheduled event \"$title\" ($date)");
            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }
        json_response(['ok' => true, 'event_id' => $eventId]);
        break;

    // Creator, or a team owner/manager, can edit.
    case 'POST:update':
        $ev = load_event((int)($in['id'] ?? 0));
        $teamId = (int)$ev['team_id'];
        require_team_member($teamId);
        if ((int)$ev['created_by'] !== $uid && !can_manage_team($teamId, $uid)) {
            json_response(['error' => 'Only the event creator or a team manager can edit this event'], 403);
        }

        $title = clean_str($in['title'] ?? $ev['title'], 190);
        $date  = valid_date($in['event_date'] ?? $ev['event_date']) ?: $ev['event_date'];
        if ($title === '') json_response(['error' => 'Event title is required'], 422);

        $projectId = array_key_exists('project_id', $in)
            ? (!empty($in['project_id']) ? (int)$in['project_id'] : null)
            : ($ev['project_id'] !== null ? (int)$ev['project_id'] : null);
        if ($projectId && project_team_id($projectId) !== $teamId) {
            json_response(['error' => 'That project does not belong to this team'], 422);
        }

        $stmt = $pdo->prepare(
            'UPDATE team_events SET title = ?, description = ?, location = ?, event_date = ?, start_time = ?, end_time = ?, project_id = ? WHERE id = ?'
        );
        $stmt->execute([
            $title,
            array_key_exists('description', $in) ? (clean_str($in['description'] ?? '', 3000) ?: null) : $ev['description'],
            array_key_exists('location', $in) ? (clean_str($in['location'] ?? '', 190) ?: null) : $ev['location'],
            $date,
            array_key_exists('start_time', $in) ? valid_time($in['start_time'] ?? '') : $ev['start_time'],
            array_key_exists('end_time', $in) ? valid_time($in['end_time'] ?? '') : $ev['end_time'],
            $projectId, (int)$ev['id'],
        ]);
        if (isset($in['attendee_ids']) && is_array($in['attendee_ids'])) {
            save_attendees((int)$ev['id'], $teamId, $in['attendee_ids'], (int)$ev['created_by']);
        }
        json_response(['ok' => true]);
        break;

    // Any attendee can RSVP for themself.
    case 'POST:rsvp':
        $ev = load_event((int)($in['event_id'] ?? 0));
        require_team_member((int)$ev['team_id']);
        $status = one_of($in['status'] ?? '', ['going', 'declined', 'invited'], 'invited');
        $upd = $pdo->prepare('UPDATE team_event_attendees SET status = ? WHERE event_id = ? AND user_id = ?');
        $upd->execute([$status, (int)$ev['id'], $uid]);
        if ($upd->rowCount() === 0) {
            // Not on the list yet — a team member RSVPing joins the attendee list.
            $pdo->prepare('INSERT IGNORE INTO team_event_attendees (event_id, user_id, status) VALUES (?, ?, ?)')
                ->execute([(int)$ev['id'], $uid, $status]);
        }
        json_response(['ok' => true]);
        break;

    case 'DELETE:delete':
        $ev = load_event((int)($_GET['id'] ?? 0));
        $teamId = (int)$ev['team_id'];
        require_team_member($teamId);
        if ((int)$ev['created_by'] !== $uid && !can_manage_team($teamId, $uid)) {
            json_response(['error' => 'Only the event creator or a team manager can delete this event'], 403);
        }
        $pdo->prepare('DELETE FROM team_events WHERE id = ?')->execute([(int)$ev['id']]);
        json_response(['ok' => true]);
        break;

    default:
        json_response(['error' => 'Unknown route'], 404);
}
