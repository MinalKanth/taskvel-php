<?php
require_once __DIR__ . '/../includes/admin.php';
require_admin();

$pdo = db();
$STATUS = ['new' => 'New', 'read' => 'Read', 'replied' => 'Replied'];

// ── Actions ──────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $action = (string)($_POST['action'] ?? '');
    $id     = (int)($_POST['id'] ?? 0);
    if ($id <= 0) json_response(['error' => 'Invalid message'], 422);

    if ($action === 'set_status') {
        $status = one_of((string)($_POST['status'] ?? ''), array_keys($STATUS), 'read');
        $pdo->prepare('UPDATE contact_messages SET status = ? WHERE id = ?')->execute([$status, $id]);
        audit_log(current_user_id(), 'admin_contact_status', ['message_id' => $id, 'status' => $status]);
        json_response(['ok' => true]);
    }
    if ($action === 'delete') {
        $pdo->prepare('DELETE FROM contact_messages WHERE id = ?')->execute([$id]);
        audit_log(current_user_id(), 'admin_contact_deleted', ['message_id' => $id]);
        json_response(['ok' => true]);
    }
    json_response(['error' => 'Invalid request'], 400);
}

// ── Listing ──────────────────────────────────────────────────
$stmt = $pdo->query("SELECT * FROM contact_messages ORDER BY FIELD(status,'new','read','replied'), id DESC");
$items = $stmt->fetchAll();

require_once __DIR__ . '/_layout.php';
admin_header('Contact Messages', 'contact-messages');
?>
<div class="tophead">
  <div>
    <h2>Contact Messages <em>· <?= number_format(count($items)) ?></em></h2>
    <div class="sub">Submissions from the "Get In Touch" form on the homepage.</div>
  </div>
</div>

<div class="card">
  <?php if (!$items): ?>
    <div class="empty"><div class="ic">✉</div>No messages yet.</div>
  <?php else: ?>
  <table>
    <thead><tr><th>From</th><th>Message</th><th>Status</th><th>When</th><th style="text-align:right">Actions</th></tr></thead>
    <tbody>
      <?php foreach ($items as $m): ?>
      <tr>
        <td style="min-width:160px">
          <b><?= h($m['name']) ?></b>
          <div class="muted"><a href="mailto:<?= h($m['email']) ?>" style="color:var(--gold-2);text-decoration:none"><?= h($m['email']) ?></a></div>
          <?php if ($m['phone']): ?><div class="muted mono"><?= h($m['phone']) ?></div><?php endif; ?>
        </td>
        <td style="max-width:340px">
          <div style="max-height:66px;overflow:hidden" id="msg<?= $m['id'] ?>"><?= nl2br(h($m['message'] ?? '')) ?></div>
          <?php if (mb_strlen((string)$m['message']) > 160): ?>
            <a href="#" class="muted" onclick="document.getElementById('msg<?= $m['id'] ?>').style.maxHeight='none';this.remove();return false">Show more</a>
          <?php endif; ?>
        </td>
        <td>
          <select style="width:auto;padding:6px 10px;font-size:12px" onchange="setStatus(<?= (int)$m['id'] ?>, this)">
            <?php foreach ($STATUS as $k => $l): ?>
              <option value="<?= $k ?>" <?= $m['status'] === $k ? 'selected' : '' ?>><?= $l ?></option>
            <?php endforeach; ?>
          </select>
        </td>
        <td class="muted" style="white-space:nowrap"><?= time_ago($m['created_at']) ?></td>
        <td style="text-align:right;white-space:nowrap">
          <a class="btn ghost sm" href="mailto:<?= h($m['email']) ?>?subject=<?= rawurlencode('Re: your enquiry — Samal Consultancy') ?>">Reply</a>
          <button class="btn danger sm" onclick="delMsg(<?= (int)$m['id'] ?>)">Delete</button>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
</div>

<script>
async function setStatus(id, sel) {
    try { await post('contact-messages.php', { action: 'set_status', id, status: sel.value }); toast('Marked as ' + sel.value); }
    catch (e) { toast(e.message); }
}
async function delMsg(id) {
    if (!confirm('Delete this message permanently?')) return;
    try { await post('contact-messages.php', { action: 'delete', id }); toast('Message deleted'); setTimeout(() => location.reload(), 500); }
    catch (e) { toast(e.message); }
}
</script>
<?php admin_footer();