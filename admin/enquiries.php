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

    $source = one_of((string)($_POST['source'] ?? 'event'), ['event', 'contact'], 'event');
    $table  = $source === 'contact' ? 'contact_messages' : 'enquiries';

    if ($action === 'set_status') {
        $status = one_of((string)($_POST['status'] ?? ''), array_keys($STATUS), 'seen');
        $pdo->prepare("UPDATE $table SET status = ? WHERE id = ?")->execute([$status, $id]);
        audit_log(current_user_id(), 'admin_enquiry_status', ['enquiry_id' => $id, 'source' => $source, 'status' => $status]);
        json_response(['ok' => true]);
    }
    if ($action === 'delete') {
        $pdo->prepare("DELETE FROM $table WHERE id = ?")->execute([$id]);
        audit_log(current_user_id(), 'admin_enquiry_deleted', ['enquiry_id' => $id, 'source' => $source]);
        json_response(['ok' => true]);
    }
    json_response(['error' => 'Invalid request'], 400);
}

// ── Listing ──────────────────────────────────────────────────
[$limit, $offset, $page] = paginate(15);
$filter  = one_of($_GET['filter'] ?? 'all', array_merge(['all'], array_keys($STATUS)), 'all');
$eventId = (int)($_GET['event_id'] ?? 0);

// Build filter clauses separately for each source table
$evWhere = ['1=1']; $evBind = [];
$ctWhere = ['1=1']; $ctBind = [];

if ($filter !== 'all') { $evWhere[] = 'q.status = ?'; $evBind[] = $filter;
                          $ctWhere[] = 'c.status = ?'; $ctBind[] = $filter; }
if ($eventId > 0)       { $evWhere[] = 'q.event_id = ?'; $evBind[] = $eventId;
                          $ctWhere[] = '1=0'; } // contact form has no event_id — exclude when filtering by event

$evWhereSql = 'WHERE ' . implode(' AND ', $evWhere);
$ctWhereSql = 'WHERE ' . implode(' AND ', $ctWhere);

// Count across both sources
$countSql = "SELECT
    (SELECT COUNT(*) FROM enquiries q $evWhereSql) +
    (SELECT COUNT(*) FROM contact_messages c $ctWhereSql) AS total";
$countStmt = $pdo->prepare($countSql);
$countStmt->execute(array_merge($evBind, $ctBind));
$total = (int)$countStmt->fetchColumn();
$pages = max(1, (int)ceil($total / $limit));

// Union both sources into one feed
$sql = "
    SELECT q.id, 'event' AS source, q.name, q.email, q.phone, q.message, q.status, q.created_at,
           e.title AS event_title
    FROM enquiries q
    LEFT JOIN events e ON e.id = q.event_id
    $evWhereSql

    UNION ALL

    SELECT c.id, 'contact' AS source, c.name, c.email, c.phone, c.message, c.status, c.created_at,
           NULL AS event_title
    FROM contact_messages c
    $ctWhereSql

    ORDER BY FIELD(status,'new','seen','replied','closed'), created_at DESC
    LIMIT $limit OFFSET $offset
";
$stmt = $pdo->prepare($sql);
$stmt->execute(array_merge($evBind, $ctBind));
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
    <thead><tr><th>From</th><th>Message</th><th>Event / Type</th><th>Status</th><th>When</th><th style="text-align:right">Actions</th></tr></thead>
    <tbody>
      <?php foreach ($items as $q): ?>
      <?php $uid = $q['source'] . '-' . $q['id']; // unique DOM id since ids collide across two tables ?>
      <tr>
        <td style="min-width:160px">
          <b><?= h($q['name']) ?></b>
          <div class="muted"><a href="mailto:<?= h($q['email']) ?>" style="color:var(--gold-2);text-decoration:none"><?= h($q['email']) ?></a></div>
          <?php if ($q['phone']): ?><div class="muted mono"><?= h($q['phone']) ?></div><?php endif; ?>
        </td>
        <td style="max-width:340px">
          <div style="max-height:66px;overflow:hidden" id="msg<?= $uid ?>"><?= nl2br(h($q['message'] ?? '')) ?></div>
          <?php if (mb_strlen((string)$q['message']) > 160): ?>
            <a href="#" class="muted" onclick="document.getElementById('msg<?= $uid ?>').style.maxHeight='none';this.remove();return false">Show more</a>
          <?php endif; ?>
        </td>
        <td>
          <?php if ($q['source'] === 'contact'): ?>
            <span class="badge b-lav">General Enquiry</span>
          <?php else: ?>
            <?= $q['event_title'] ? '<span class="badge b-mut">' . h($q['event_title']) . '</span>' : '<span class="muted">General</span>' ?>
          <?php endif; ?>
        </td>
        <td>
          <select style="width:auto;padding:6px 10px;font-size:12px" onchange="setStatus(<?= (int)$q['id'] ?>, '<?= $q['source'] ?>', this)">
            <?php foreach ($STATUS as $k => $l): ?>
              <option value="<?= $k ?>" <?= $q['status'] === $k ? 'selected' : '' ?>><?= $l ?></option>
            <?php endforeach; ?>
          </select>
        </td>
        <td class="muted" style="white-space:nowrap"><?= time_ago($q['created_at']) ?></td>
        <td style="text-align:right;white-space:nowrap">
          <a class="btn ghost sm" href="mailto:<?= h($q['email']) ?>?subject=<?= rawurlencode('Re: your enquiry — Samal Consultancy') ?>">Reply</a>
          <button class="btn danger sm" onclick="delEnq(<?= (int)$q['id'] ?>, '<?= $q['source'] ?>')">Delete</button>
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
async function setStatus(id, source, sel) {
    try { await post('enquiries.php', { action: 'set_status', id, source, status: sel.value }); toast('Marked as ' + sel.value); }
    catch (e) { toast(e.message); }
}
async function delEnq(id, source) {
    if (!confirm('Delete this enquiry permanently?')) return;
    try { await post('enquiries.php', { action: 'delete', id, source }); toast('Enquiry deleted'); setTimeout(() => location.reload(), 500); }
    catch (e) { toast(e.message); }
}
</script>
<?php admin_footer();
