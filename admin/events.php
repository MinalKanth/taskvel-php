<?php
require_once __DIR__ . '/../includes/admin.php';
require_admin();

$pdo = db();
$STATUS = ['upcoming' => 'Upcoming', 'ongoing' => 'Ongoing', 'completed' => 'Completed'];

// ── Actions ──────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $action = (string)($_POST['action'] ?? '');
    $id     = (int)($_POST['id'] ?? 0);
    if ($id <= 0) json_response(['error' => 'Invalid event'], 422);

    if ($action === 'delete') {
        $pdo->prepare('DELETE FROM events WHERE id = ?')->execute([$id]);
        audit_log(current_user_id(), 'admin_event_deleted', ['event_id' => $id]);
        json_response(['ok' => true]);
    }
    if ($action === 'set_status') {
        $status = one_of((string)($_POST['status'] ?? ''), array_keys($STATUS), 'upcoming');
        $pdo->prepare('UPDATE events SET status = ? WHERE id = ?')->execute([$status, $id]);
        audit_log(current_user_id(), 'admin_event_status', ['event_id' => $id, 'status' => $status]);
        json_response(['ok' => true]);
    }
    json_response(['error' => 'Invalid request'], 400);
}

// ── Listing ──────────────────────────────────────────────────
[$limit, $offset, $page] = paginate(12);
$filter = one_of($_GET['filter'] ?? 'all', array_merge(['all'], array_keys($STATUS)), 'all');
$q      = clean_str($_GET['q'] ?? '', 120);

$where = []; $bind = [];
if ($filter !== 'all') { $where[] = 'e.status = ?'; $bind[] = $filter; }
if ($q !== '')         { $where[] = '(e.title LIKE ? OR e.location LIKE ?)'; $bind[] = "%$q%"; $bind[] = "%$q%"; }
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$count = $pdo->prepare("SELECT COUNT(*) FROM events e $whereSql");
$count->execute($bind);
$total = (int)$count->fetchColumn();
$pages = max(1, (int)ceil($total / $limit));

$stmt = $pdo->prepare("SELECT e.*, c.name AS category_name,
                              (SELECT COUNT(*) FROM enquiries q WHERE q.event_id = e.id) AS enquiry_count
                         FROM events e
                         LEFT JOIN event_categories c ON c.id = e.category_id
                         $whereSql
                        ORDER BY e.event_date DESC, e.id DESC
                        LIMIT $limit OFFSET $offset");
$stmt->execute($bind);
$events = $stmt->fetchAll();

require_once __DIR__ . '/_layout.php';
admin_header('Events', 'events');

$badge = ['upcoming' => 'b-lav', 'ongoing' => 'b-warn', 'completed' => 'b-ok'];
?>
<div class="tophead">
  <div>
    <h2>Events <em>· <?= number_format($total) ?></em></h2>
    <div class="sub">Everything published on the public events page.</div>
  </div>
  <a href="event-form.php" class="btn">＋ Add event</a>
</div>

<div class="card">
  <div class="toolbar">
    <form method="get">
      <input type="search" name="q" value="<?= h($q) ?>" placeholder="Search title or location…">
      <select name="filter" onchange="this.form.submit()" style="width:auto">
        <option value="all" <?= $filter === 'all' ? 'selected' : '' ?>>All statuses</option>
        <?php foreach ($STATUS as $k => $label): ?>
          <option value="<?= $k ?>" <?= $filter === $k ? 'selected' : '' ?>><?= $label ?></option>
        <?php endforeach; ?>
      </select>
      <button class="btn sm" type="submit">Search</button>
    </form>
  </div>

  <?php if (!$events): ?>
    <div class="empty"><div class="ic">✦</div>No events yet. <a href="event-form.php" style="color:var(--gold-2)">Add the first one.</a></div>
  <?php else: ?>
  <table>
    <thead><tr><th>Event</th><th>Date</th><th>Category</th><th>Status</th><th>Enquiries</th><th style="text-align:right">Actions</th></tr></thead>
    <tbody>
      <?php foreach ($events as $ev): ?>
      <tr>
        <td>
          <b><?= h($ev['title']) ?></b>
          <div class="muted"><?= h($ev['location'] ?: '—') ?> · <span class="mono">/<?= h($ev['slug']) ?></span></div>
        </td>
        <td class="muted" style="white-space:nowrap">
          <?= date('j M Y', strtotime($ev['event_date'])) ?>
          <?php if ($ev['event_time']): ?><div class="muted"><?= h($ev['event_time']) ?></div><?php endif; ?>
        </td>
        <td><?= $ev['category_name'] ? '<span class="badge b-mut">' . h($ev['category_name']) . '</span>' : '<span class="muted">—</span>' ?></td>
        <td>
          <select style="width:auto;padding:6px 10px;font-size:12px" onchange="setStatus(<?= (int)$ev['id'] ?>, this)">
            <?php foreach ($STATUS as $k => $label): ?>
              <option value="<?= $k ?>" <?= $ev['status'] === $k ? 'selected' : '' ?>><?= $label ?></option>
            <?php endforeach; ?>
          </select>
        </td>
        <td>
          <?php if ($ev['enquiry_count'] > 0): ?>
            <a href="enquiries.php?event_id=<?= (int)$ev['id'] ?>" class="badge b-warn" style="text-decoration:none"><?= (int)$ev['enquiry_count'] ?></a>
          <?php else: ?><span class="muted">0</span><?php endif; ?>
        </td>
        <td style="text-align:right;white-space:nowrap">
          <a class="btn ghost sm" href="../event.php?slug=<?= h($ev['slug']) ?>" target="_blank" rel="noopener">View</a>
          <a class="btn ghost sm" href="event-form.php?id=<?= (int)$ev['id'] ?>">Edit</a>
          <button class="btn danger sm" onclick="delEvent(<?= (int)$ev['id'] ?>, <?= h(json_encode($ev['title'])) ?>)">Delete</button>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>

  <?php if ($pages > 1): ?>
  <div class="pager">
    <?php for ($p = 1; $p <= $pages; $p++):
        $qs = h('?' . http_build_query(array_filter(['q' => $q, 'filter' => $filter !== 'all' ? $filter : '', 'page' => $p]))); ?>
      <?php if ($p === $page): ?><span class="cur"><?= $p ?></span>
      <?php else: ?><a href="<?= $qs ?>"><?= $p ?></a><?php endif; ?>
    <?php endfor; ?>
  </div>
  <?php endif; ?>
</div>

<script>
async function setStatus(id, sel) {
    try { await post('events.php', { action: 'set_status', id, status: sel.value }); toast('Status updated'); }
    catch (e) { toast(e.message); }
}
async function delEvent(id, title) {
    if (!confirm(`Delete "${title}"? This also removes its images, hotels, and unlinks its enquiries.`)) return;
    try {
        await post('events.php', { action: 'delete', id });
        toast('Event deleted');
        setTimeout(() => location.reload(), 500);
    } catch (e) { toast(e.message); }
}
</script>
<?php admin_footer();
