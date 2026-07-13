<?php
require_once __DIR__ . '/../includes/admin.php';
require_admin();

$pdo = db();
$STATUS = ['new' => 'New', 'seen' => 'Seen', 'replied' => 'Replied', 'closed' => 'Closed'];

// ── Actions ──────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $action = (string)($_POST['action'] ?? '');
    $id     = (int)($_POST['id'] ?? 0);
    if ($id <= 0) json_response(['error' => 'Invalid enquiry'], 422);

    if ($action === 'set_status') {
        $status = one_of((string)($_POST['status'] ?? ''), array_keys($STATUS), 'seen');
        $pdo->prepare('UPDATE enquiries SET status = ? WHERE id = ?')->execute([$status, $id]);
        audit_log(current_user_id(), 'admin_enquiry_status', ['enquiry_id' => $id, 'status' => $status]);
        json_response(['ok' => true]);
    }
    if ($action === 'delete') {
        $pdo->prepare('DELETE FROM enquiries WHERE id = ?')->execute([$id]);
        audit_log(current_user_id(), 'admin_enquiry_deleted', ['enquiry_id' => $id]);
        json_response(['ok' => true]);
    }
    json_response(['error' => 'Invalid request'], 400);
}

// ── Listing ──────────────────────────────────────────────────
[$limit, $offset, $page] = paginate(15);
$filter  = one_of($_GET['filter'] ?? 'all', array_merge(['all'], array_keys($STATUS)), 'all');
$eventId = (int)($_GET['event_id'] ?? 0);

$where = []; $bind = [];
if ($filter !== 'all') { $where[] = 'q.status = ?';   $bind[] = $filter; }
if ($eventId > 0)      { $where[] = 'q.event_id = ?'; $bind[] = $eventId; }
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$count = $pdo->prepare("SELECT COUNT(*) FROM enquiries q $whereSql");
$count->execute($bind);
$total = (int)$count->fetchColumn();
$pages = max(1, (int)ceil($total / $limit));

$stmt = $pdo->prepare("SELECT q.*, e.title AS event_title
                         FROM enquiries q
                         LEFT JOIN events e ON e.id = q.event_id
                         $whereSql
                        ORDER BY FIELD(q.status,'new','seen','replied','closed'), q.id DESC
                        LIMIT $limit OFFSET $offset");
$stmt->execute($bind);
$items = $stmt->fetchAll();

require_once __DIR__ . '/_layout.php';
admin_header('Enquiries', 'enquiries');
$badge = ['new' => 'b-bad', 'seen' => 'b-warn', 'replied' => 'b-lav', 'closed' => 'b-ok'];
?>
<div class="tophead">
  <div>
    <h2>Enquiries <em>· <?= number_format($total) ?></em></h2>
    <div class="sub">Submissions from the public event and contact forms.</div>
  </div>
</div>

<div class="card">
  <div class="toolbar">
    <form method="get">
      <?php if ($eventId): ?><input type="hidden" name="event_id" value="<?= $eventId ?>"><?php endif; ?>
      <select name="filter" onchange="this.form.submit()" style="width:auto">
        <option value="all" <?= $filter === 'all' ? 'selected' : '' ?>>All statuses</option>
        <?php foreach ($STATUS as $k => $l): ?>
          <option value="<?= $k ?>" <?= $filter === $k ? 'selected' : '' ?>><?= $l ?></option>
        <?php endforeach; ?>
      </select>
      <?php if ($eventId): ?><a class="btn ghost sm" href="enquiries.php">✕ Clear event filter</a><?php endif; ?>
    </form>
  </div>

  <?php if (!$items): ?>
    <div class="empty"><div class="ic">✉</div>No enquiries here — the inbox is clear.</div>
  <?php else: ?>
  <table>
    <thead><tr><th>From</th><th>Message</th><th>Event</th><th>Status</th><th>When</th><th style="text-align:right">Actions</th></tr></thead>
    <tbody>
      <?php foreach ($items as $q): ?>
      <tr>
        <td style="min-width:160px">
          <b><?= h($q['name']) ?></b>
          <div class="muted"><a href="mailto:<?= h($q['email']) ?>" style="color:var(--gold-2);text-decoration:none"><?= h($q['email']) ?></a></div>
          <?php if ($q['phone']): ?><div class="muted mono"><?= h($q['phone']) ?></div><?php endif; ?>
        </td>
        <td style="max-width:340px">
          <div style="max-height:66px;overflow:hidden" id="msg<?= $q['id'] ?>"><?= nl2br(h($q['message'] ?? '')) ?></div>
          <?php if (mb_strlen((string)$q['message']) > 160): ?>
            <a href="#" class="muted" onclick="document.getElementById('msg<?= $q['id'] ?>').style.maxHeight='none';this.remove();return false">Show more</a>
          <?php endif; ?>
        </td>
        <td><?= $q['event_title'] ? '<span class="badge b-mut">' . h($q['event_title']) . '</span>' : '<span class="muted">General</span>' ?></td>
        <td>
          <select style="width:auto;padding:6px 10px;font-size:12px" onchange="setStatus(<?= (int)$q['id'] ?>, this)">
            <?php foreach ($STATUS as $k => $l): ?>
              <option value="<?= $k ?>" <?= $q['status'] === $k ? 'selected' : '' ?>><?= $l ?></option>
            <?php endforeach; ?>
          </select>
        </td>
        <td class="muted" style="white-space:nowrap"><?= time_ago($q['created_at']) ?></td>
        <td style="text-align:right;white-space:nowrap">
          <a class="btn ghost sm" href="mailto:<?= h($q['email']) ?>?subject=<?= rawurlencode('Re: your enquiry — Samal Consultancy') ?>">Reply</a>
          <button class="btn danger sm" onclick="delEnq(<?= (int)$q['id'] ?>)">Delete</button>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>

  <?php if ($pages > 1): ?>
  <div class="pager">
    <?php for ($p = 1; $p <= $pages; $p++):
        $qs = h('?' . http_build_query(array_filter(['filter' => $filter !== 'all' ? $filter : '', 'event_id' => $eventId ?: '', 'page' => $p]))); ?>
      <?php if ($p === $page): ?><span class="cur"><?= $p ?></span>
      <?php else: ?><a href="<?= $qs ?>"><?= $p ?></a><?php endif; ?>
    <?php endfor; ?>
  </div>
  <?php endif; ?>
</div>

<script>
async function setStatus(id, sel) {
    try { await post('enquiries.php', { action: 'set_status', id, status: sel.value }); toast('Marked as ' + sel.value); }
    catch (e) { toast(e.message); }
}
async function delEnq(id) {
    if (!confirm('Delete this enquiry permanently?')) return;
    try { await post('enquiries.php', { action: 'delete', id }); toast('Enquiry deleted'); setTimeout(() => location.reload(), 500); }
    catch (e) { toast(e.message); }
}
</script>
<?php admin_footer();
