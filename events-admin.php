<?php
require_once __DIR__ . '/includes/auth.php';
if (!current_user_id()) { header('Location: login.php'); exit; }
$user = current_user();

$STATUS_LABELS = ['upcoming' => 'Upcoming', 'ongoing' => 'Ongoing', 'completed' => 'Completed'];

/* ---- Handle delete / quick status change ----
   Posted via fetch() with an X-CSRF-Token header (same convention as
   js/api-client.js uses against api/*.php), not a classic form field. */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf(); // checks X-CSRF-Token header, responds with JSON + exits on failure
    if (!current_user_id()) json_response(['error' => 'Unauthenticated'], 401);

    $action = (string) ($_POST['action'] ?? '');
    $id     = (int) ($_POST['id'] ?? 0);

    if ($action === 'delete' && $id > 0) {
        db()->prepare('DELETE FROM events WHERE id = :id')->execute([':id' => $id]);
        audit_log(current_user_id(), 'event_deleted', ['event_id' => $id]);
        json_response(['ok' => true]);
    } elseif ($action === 'set_status' && $id > 0) {
        $status = (string) ($_POST['status'] ?? '');
        if (array_key_exists($status, $STATUS_LABELS)) {
            db()->prepare('UPDATE events SET status = :s, updated_at = NOW() WHERE id = :id')
                ->execute([':s' => $status, ':id' => $id]);
            audit_log(current_user_id(), 'event_status_changed', ['event_id' => $id, 'status' => $status]);
            json_response(['ok' => true]);
        }
    }
    json_response(['error' => 'Invalid request'], 400);
}

$events = db()->query(
    "SELECT e.*, c.name AS category_name
     FROM events e
     LEFT JOIN event_categories c ON c.id = e.category_id
     ORDER BY e.event_date DESC, e.id DESC"
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="csrf-token" content="<?= htmlspecialchars(csrf_token()) ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Manage Events · Taskvel</title>
<style>
    :root {
        --bg:#f6f6f4; --bg-elev:#fff; --bg-sunk:#ededea; --ink:#0a0a0a; --ink2:#3d3d3b; --ink3:#7c7c78;
        --line:#e6e5e0; --line2:#d4d3cd; --accent:#4f46e5; --accent-soft:rgba(79,70,229,.1); --on-accent:#fff;
        --good:#059669; --good-soft:rgba(5,150,105,.1); --warn:#d97706; --warn-soft:rgba(217,119,6,.1); --bad:#dc2626; --bad-soft:rgba(220,38,38,.1);
        --shadow:0 10px 34px rgba(10,10,10,.08); --r:14px; --ease:cubic-bezier(.22,1,.36,1);
    }
    * { box-sizing:border-box; }
    body { margin:0; font-family:-apple-system,'Segoe UI',Arial,sans-serif; background:var(--bg); color:var(--ink); }
    .wrap { max-width:1080px; margin:0 auto; padding:24px 18px 90px; }
    .topbar { display:flex; justify-content:space-between; align-items:center; margin-bottom:18px; flex-wrap:wrap; gap:10px; }
    .topbar a.back { color:var(--ink3); text-decoration:none; font-size:13px; font-weight:600; }
    h1 { font-size:22px; font-weight:800; margin:0 0 4px; }
    .sub { color:var(--ink3); font-size:13.5px; margin-bottom:18px; }
    .btn { display:inline-flex; align-items:center; gap:6px; padding:9px 16px; border-radius:10px; border:none;
        background:var(--accent); color:var(--on-accent); font-weight:700; font-size:13px; cursor:pointer; text-decoration:none; }
    .btn.ghost { background:var(--bg-elev); color:var(--ink); border:1px solid var(--line2); }
    .btn.bad { background:var(--bad); }
    .btn.sm { padding:6px 11px; font-size:11.5px; }

    table { width:100%; border-collapse:collapse; background:var(--bg-elev); border-radius:var(--r); overflow:hidden; border:1px solid var(--line); }
    th, td { padding:10px 12px; font-size:13px; text-align:left; border-bottom:1px solid var(--line); vertical-align:middle; }
    th { background:var(--bg-sunk); font-size:10.5px; text-transform:uppercase; color:var(--ink3); letter-spacing:.4px; }
    tr:last-child td { border-bottom:none; }

    .pill { font-size:9.5px; font-weight:700; padding:3px 9px; border-radius:20px; text-transform:uppercase; letter-spacing:.4px; }
    .pill-upcoming { background:var(--accent-soft); color:var(--accent); }
    .pill-ongoing { background:var(--warn-soft); color:var(--warn); }
    .pill-completed { background:var(--good-soft); color:var(--good); }

    .row-actions { display:flex; gap:6px; align-items:center; flex-wrap:wrap; }
    .row-actions select { padding:5px 7px; border:1px solid var(--line2); border-radius:7px; font-size:11.5px; }
    .empty-state { text-align:center; padding:50px 20px; color:var(--ink3); font-size:13.5px; }
</style>
</head>
<body>
<div class="wrap">

    <div class="topbar">
        <div>
            <a href="events.php" class="back">&larr; View public page</a>
            <h1>Manage events</h1>
            <div class="sub">Signed in as <?= htmlspecialchars($user['name']) ?></div>
        </div>
        <a href="events-admin-form.php" class="btn"><i>+</i> Add event</a>
    </div>

    <?php if (!$events): ?>
        <div class="empty-state">No events yet. Click "Add event" to create your first one.</div>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Date</th>
                    <th>Location</th>
                    <th>Category</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($events as $ev): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($ev['title']) ?></strong></td>
                        <td><?= htmlspecialchars(date('d M Y', strtotime($ev['event_date']))) ?></td>
                        <td><?= htmlspecialchars($ev['location']) ?></td>
                        <td><?= htmlspecialchars($ev['category_name'] ?: '—') ?></td>
                        <td><span class="pill pill-<?= htmlspecialchars($ev['status']) ?>"><?= htmlspecialchars($STATUS_LABELS[$ev['status']] ?? ucfirst($ev['status'])) ?></span></td>
                        <td>
                            <div class="row-actions">
                                <a href="event.php?slug=<?= urlencode($ev['slug']) ?>" class="btn ghost sm" target="_blank">View</a>
                                <a href="events-admin-form.php?id=<?= (int) $ev['id'] ?>" class="btn ghost sm">Edit</a>

                                <select data-event-id="<?= (int) $ev['id'] ?>" class="status-select" onchange="updateStatus(this)">
                                    <?php foreach ($STATUS_LABELS as $k => $lbl): ?>
                                        <option value="<?= $k ?>" <?= $ev['status'] === $k ? 'selected' : '' ?>><?= $lbl ?></option>
                                    <?php endforeach; ?>
                                </select>

                                <button type="button" class="btn bad sm" onclick="deleteEvent(<?= (int) $ev['id'] ?>)">Delete</button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

</div>

<script>
const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]').content;

async function postAction(body) {
    const res = await fetch('events-admin.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-CSRF-Token': CSRF_TOKEN,
        },
        body: new URLSearchParams(body),
    });
    const data = await res.json().catch(() => ({}));
    if (!res.ok || data.error) {
        alert(data.error || 'Something went wrong. Please try again.');
        return false;
    }
    return true;
}

async function updateStatus(selectEl) {
    const ok = await postAction({
        action: 'set_status',
        id: selectEl.dataset.eventId,
        status: selectEl.value,
    });
    if (ok) location.reload();
}

async function deleteEvent(id) {
    if (!confirm('Delete this event? This cannot be undone.')) return;
    const ok = await postAction({ action: 'delete', id });
    if (ok) location.reload();
}
</script>
</body>
</html>