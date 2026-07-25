<?php
require_once __DIR__ . '/../includes/admin.php';
require_admin();
require_once __DIR__ . '/../includes/mailer.php'; // must expose send_mail($to, $subject, $htmlBody)

$pdo = db();
const BATCH_SIZE = 5;      // emails sent per AJAX batch call
const BATCH_DELAY_MS = 1200; // pause between batches (client-side) — be gentle on your SMTP provider

// ── Actions ──────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $action = (string)($_POST['action'] ?? '');

    if ($action === 'create_campaign') {
        $subject = trim((string)($_POST['subject'] ?? ''));
        $body    = trim((string)($_POST['body'] ?? ''));
        if ($subject === '' || $body === '') {
            json_response(['error' => 'Subject and message body are required.'], 422);
        }

        $total = (int)$pdo->query('SELECT COUNT(*) FROM newsletter_subscribers')->fetchColumn();
        if ($total === 0) {
            json_response(['error' => 'There are no subscribers to send to.'], 422);
        }

        $stmt = $pdo->prepare(
            'INSERT INTO newsletter_campaigns (subject, body, total_recipients, status, created_at)
             VALUES (?, ?, ?, "sending", NOW())'
        );
        $stmt->execute([$subject, $body, $total]);
        $campaignId = (int)$pdo->lastInsertId();

        audit_log(current_user_id(), 'admin_newsletter_campaign_created', ['campaign_id' => $campaignId, 'total' => $total]);
        json_response(['ok' => true, 'campaign_id' => $campaignId, 'total' => $total]);
    }

    if ($action === 'send_batch') {
        $campaignId = (int)($_POST['campaign_id'] ?? 0);
        if ($campaignId <= 0) json_response(['error' => 'Invalid campaign'], 422);

        $campaign = $pdo->prepare('SELECT * FROM newsletter_campaigns WHERE id = ?');
        $campaign->execute([$campaignId]);
        $campaign = $campaign->fetch();
        if (!$campaign) json_response(['error' => 'Campaign not found'], 404);

        if ($campaign['status'] === 'completed') {
            json_response(['ok' => true, 'done' => true, 'sent' => $campaign['sent_count'], 'total' => $campaign['total_recipients']]);
        }

        // Fetch the next batch of subscribers NOT already sent this campaign
        $stmt = $pdo->prepare(
            "SELECT s.id, s.email
             FROM newsletter_subscribers s
             WHERE NOT EXISTS (
                 SELECT 1 FROM newsletter_sends ns
                 WHERE ns.campaign_id = ? AND ns.subscriber_id = s.id
             )
             ORDER BY s.id ASC
             LIMIT " . BATCH_SIZE
        );
        $stmt->execute([$campaignId]);
        $batch = $stmt->fetchAll();

        $sentThisBatch = 0;
        $failed = [];

        foreach ($batch as $sub) {
            try {
                send_mail($sub['email'], $campaign['subject'], nl2br(htmlspecialchars($campaign['body'])));
                $pdo->prepare('INSERT IGNORE INTO newsletter_sends (campaign_id, subscriber_id, sent_at) VALUES (?, ?, NOW())')
                    ->execute([$campaignId, $sub['id']]);
                $sentThisBatch++;
            } catch (\Throwable $e) {
                error_log("Newsletter send failed for {$sub['email']}: " . $e->getMessage());
                $failed[] = $sub['email'];
                // Still mark as attempted so a bad address doesn't block the batch forever.
                $pdo->prepare('INSERT IGNORE INTO newsletter_sends (campaign_id, subscriber_id, sent_at) VALUES (?, ?, NOW())')
                    ->execute([$campaignId, $sub['id']]);
            }
        }

        $newSentCount = (int)$pdo->prepare('SELECT COUNT(*) FROM newsletter_sends WHERE campaign_id = ?')
            ->execute([$campaignId]) ? (int)$pdo->query("SELECT COUNT(*) FROM newsletter_sends WHERE campaign_id = $campaignId")->fetchColumn() : 0;

        $done = $newSentCount >= $campaign['total_recipients'];
        $pdo->prepare('UPDATE newsletter_campaigns SET sent_count = ?, status = ?, completed_at = ? WHERE id = ?')
            ->execute([$newSentCount, $done ? 'completed' : 'sending', $done ? date('Y-m-d H:i:s') : null, $campaignId]);

        if ($done) {
            audit_log(current_user_id(), 'admin_newsletter_campaign_completed', ['campaign_id' => $campaignId, 'sent' => $newSentCount]);
        }

        json_response([
            'ok' => true,
            'done' => $done,
            'sent' => $newSentCount,
            'total' => $campaign['total_recipients'],
            'batch_sent' => $sentThisBatch,
            'failed' => $failed,
        ]);
    }

    json_response(['error' => 'Invalid request'], 400);
}

// ── Data for the page ────────────────────────────────────────
$subscribers = $pdo->query('SELECT id, email, created_at FROM newsletter_subscribers ORDER BY created_at DESC')->fetchAll();
$campaigns   = $pdo->query('SELECT * FROM newsletter_campaigns ORDER BY id DESC LIMIT 10')->fetchAll();

require_once __DIR__ . '/_layout.php';
admin_header('Newsletter', 'newsletter');
?>
<div class="tophead">
  <div>
    <h2>Newsletter <em>· <?= number_format(count($subscribers)) ?> subscribers</em></h2>
    <div class="sub">Compose an update and send it to everyone on your subscriber list.</div>
  </div>
</div>

<div class="grid g2" style="margin-bottom:20px;">

  <!-- COMPOSE -->
  <div class="card">
    <label style="margin-bottom:14px;display:block;">Compose Campaign</label>
    <form id="composeForm">
      <div class="field">
        <label>Subject</label>
        <input type="text" id="subject" maxlength="200" placeholder="e.g. New GST deadlines this month" required>
      </div>
      <div class="field">
        <label>Message</label>
        <textarea id="body" placeholder="Write your update here…" required></textarea>
      </div>
      <button type="submit" class="btn" id="startBtn">Send to <?= count($subscribers) ?> subscribers</button>
    </form>

    <!-- Progress UI (hidden until sending starts) -->
    <div id="progressBox" style="display:none;margin-top:18px;">
      <div class="muted" id="progressText">Preparing…</div>
      <div style="height:8px;border-radius:99px;background:rgba(255,255,255,.08);margin-top:8px;overflow:hidden;">
        <div id="progressBar" style="height:100%;width:0%;background:linear-gradient(120deg,var(--gold-2),var(--gold));transition:width .3s ease;"></div>
      </div>
    </div>
  </div>

  <!-- RECENT CAMPAIGNS -->
  <div class="card">
    <label style="margin-bottom:14px;display:block;">Recent Campaigns</label>
    <?php if (!$campaigns): ?>
      <div class="empty"><div class="ic">✉</div>No campaigns sent yet.</div>
    <?php else: ?>
      <table>
        <thead><tr><th>Subject</th><th>Progress</th><th>Status</th><th>Sent</th></tr></thead>
        <tbody>
          <?php foreach ($campaigns as $c): ?>
          <tr>
            <td style="max-width:220px;"><?= h($c['subject']) ?></td>
            <td class="mono"><?= (int)$c['sent_count'] ?> / <?= (int)$c['total_recipients'] ?></td>
            <td>
              <?php if ($c['status'] === 'completed'): ?>
                <span class="badge b-ok">Completed</span>
              <?php else: ?>
                <span class="badge b-warn">Sending…</span>
              <?php endif; ?>
            </td>
            <td class="muted" style="white-space:nowrap"><?= time_ago($c['created_at']) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>

</div>

<!-- SUBSCRIBERS LIST -->
<div class="card">
  <label style="margin-bottom:14px;display:block;">All Subscribers</label>
  <?php if (!$subscribers): ?>
    <div class="empty"><div class="ic">✉</div>No subscribers yet.</div>
  <?php else: ?>
  <table>
    <thead><tr><th>Email</th><th>Subscribed</th></tr></thead>
    <tbody>
      <?php foreach ($subscribers as $s): ?>
      <tr>
        <td><?= h($s['email']) ?></td>
        <td class="muted" style="white-space:nowrap"><?= time_ago($s['created_at']) ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
</div>

<script>
const BATCH_DELAY_MS = <?= BATCH_DELAY_MS ?>;

document.getElementById('composeForm').addEventListener('submit', async function (e) {
    e.preventDefault();
    const subject = document.getElementById('subject').value.trim();
    const body = document.getElementById('body').value.trim();
    if (!subject || !body) return;

    if (!confirm(`Send this to <?= count($subscribers) ?> subscribers now? This can't be undone.`)) return;

    const startBtn = document.getElementById('startBtn');
    const progressBox = document.getElementById('progressBox');
    const progressText = document.getElementById('progressText');
    const progressBar = document.getElementById('progressBar');

    startBtn.disabled = true;
    startBtn.textContent = 'Starting…';
    progressBox.style.display = 'block';

    try {
        const created = await post('newsletter.php', { action: 'create_campaign', subject, body });
        const campaignId = created.campaign_id;
        const total = created.total;

        async function sendNextBatch() {
            const res = await post('newsletter.php', { action: 'send_batch', campaign_id: campaignId });
            const pct = total > 0 ? Math.round((res.sent / total) * 100) : 100;
            progressBar.style.width = pct + '%';
            progressText.textContent = `Sent ${res.sent} of ${res.total}…`;

            if (res.failed && res.failed.length) {
                console.warn('Failed to send to:', res.failed);
            }

            if (!res.done) {
                setTimeout(sendNextBatch, BATCH_DELAY_MS);
            } else {
                progressText.textContent = `Done — sent to ${res.sent} of ${res.total} subscribers.`;
                toast('Newsletter sent successfully!');
                startBtn.disabled = false;
                startBtn.textContent = 'Send to <?= count($subscribers) ?> subscribers';
                document.getElementById('subject').value = '';
                document.getElementById('body').value = '';
                setTimeout(() => location.reload(), 1500);
            }
        }

        sendNextBatch();
    } catch (err) {
        toast(err.message);
        startBtn.disabled = false;
        startBtn.textContent = 'Send to <?= count($subscribers) ?> subscribers';
        progressBox.style.display = 'none';
    }
});
</script>
<?php admin_footer(); ?>