<?php
require_once __DIR__ . '/../includes/admin.php';
require_admin();
require_once __DIR__ . '/_layout.php';

$pdo = db();

// ── KPIs ─────────────────────────────────────────────────────
$totalUsers  = (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
$activeUsers = (int)$pdo->query('SELECT COUNT(*) FROM users WHERE is_active = 1')->fetchColumn();
$new7d       = (int)$pdo->query('SELECT COUNT(*) FROM users WHERE created_at >= NOW() - INTERVAL 7 DAY')->fetchColumn();

$totalEvents = $upcoming = 0;
try {
    $totalEvents = (int)$pdo->query('SELECT COUNT(*) FROM events')->fetchColumn();
    $upcoming    = (int)$pdo->query("SELECT COUNT(*) FROM events WHERE status = 'upcoming'")->fetchColumn();
} catch (Throwable $e) {}

$newEnquiries = 0;
try { $newEnquiries = (int)$pdo->query("SELECT COUNT(*) FROM enquiries WHERE status = 'new'")->fetchColumn(); }
catch (Throwable $e) {}

// Logins in the last 24h from the audit trail
$logins24h = (int)$pdo->query("SELECT COUNT(*) FROM security_audit_log
                                WHERE event = 'login_success' AND created_at >= NOW() - INTERVAL 1 DAY")->fetchColumn();

// ── Signups per day, last 30 days (for the sparkline) ────────
$rows = $pdo->query("SELECT DATE(created_at) d, COUNT(*) c FROM users
                      WHERE created_at >= CURDATE() - INTERVAL 29 DAY
                      GROUP BY DATE(created_at)")->fetchAll(PDO::FETCH_KEY_PAIR);
$series = [];
for ($i = 29; $i >= 0; $i--) {
    $day = date('Y-m-d', strtotime("-$i days"));
    $series[$day] = (int)($rows[$day] ?? 0);
}
$max = max(1, max($series));

// Build the SVG polyline points (viewBox 0 0 600 120)
$pts = [];
$i = 0;
foreach ($series as $c) {
    $x = round($i * (600 / 29), 1);
    $y = round(112 - ($c / $max) * 96, 1);
    $pts[] = "$x,$y";
    $i++;
}
$line = implode(' ', $pts);
$area = "0,120 $line 600,120";

// ── Recent security events + newest users ────────────────────
$recentAudit = $pdo->query("SELECT a.*, u.name FROM security_audit_log a
                             LEFT JOIN users u ON u.id = a.user_id
                             ORDER BY a.id DESC LIMIT 8")->fetchAll();
$recentUsers = $pdo->query('SELECT id, name, email, created_at, is_active FROM users
                             ORDER BY id DESC LIMIT 6')->fetchAll();

$eventBadge = function (string $e): string {
    if (str_contains($e, 'failed') || str_contains($e, 'denied') || str_contains($e, 'rejected')) return 'b-bad';
    if (str_contains($e, 'rate_limited') || str_contains($e, 'expired')) return 'b-warn';
    if (str_contains($e, 'success')) return 'b-ok';
    return 'b-mut';
};

admin_header('Dashboard', 'dashboard');
?>
<div class="tophead">
  <div>
    <h2>Good <?= (int)date('G') < 12 ? 'morning' : ((int)date('G') < 17 ? 'afternoon' : 'evening') ?>, <em><?= h(explode(' ', current_user()['name'])[0]) ?></em></h2>
    <div class="sub"><?= date('l, j F Y') ?> — here's where Taskvel stands.</div>
  </div>
  <a href="event-form.php" class="btn">＋ Add event</a>
</div>

<div class="grid g4" style="margin-bottom:16px">
  <div class="card kpi">
    <div class="lbl">Total users</div>
    <div class="num" data-count="<?= $totalUsers ?>">0</div>
    <div class="hint"><b>+<?= $new7d ?></b> in the last 7 days</div>
  </div>
  <div class="card kpi">
    <div class="lbl">Active accounts</div>
    <div class="num" data-count="<?= $activeUsers ?>">0</div>
    <div class="hint"><?= $totalUsers - $activeUsers ?> disabled</div>
  </div>
  <div class="card kpi">
    <div class="lbl">Events</div>
    <div class="num" data-count="<?= $totalEvents ?>">0</div>
    <div class="hint"><?= $upcoming ?> upcoming</div>
  </div>
  <div class="card kpi">
    <div class="lbl">Logins · 24h</div>
    <div class="num" data-count="<?= $logins24h ?>">0</div>
    <div class="hint"><?= $newEnquiries ?> new <a href="enquiries.php" style="color:var(--gold-2)">enquiries</a></div>
  </div>
</div>

<div class="grid g2">
  <div class="card">
    <div class="lbl" style="font-size:11px;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:var(--ink-45)">Signups — last 30 days</div>
    <svg viewBox="0 0 600 130" style="width:100%;height:150px;margin-top:12px" preserveAspectRatio="none" role="img" aria-label="Signups per day, last 30 days">
      <defs>
        <linearGradient id="gfill" x1="0" y1="0" x2="0" y2="1">
          <stop offset="0" stop-color="#E8C766" stop-opacity=".35"/>
          <stop offset="1" stop-color="#E8C766" stop-opacity="0"/>
        </linearGradient>
        <linearGradient id="gline" x1="0" y1="0" x2="1" y2="0">
          <stop offset="0" stop-color="#C9A227"/><stop offset="1" stop-color="#E8C766"/>
        </linearGradient>
      </defs>
      <polygon points="<?= $area ?>" fill="url(#gfill)"/>
      <polyline points="<?= $line ?>" fill="none" stroke="url(#gline)" stroke-width="2.5"
                stroke-linejoin="round" stroke-linecap="round"/>
    </svg>
    <div class="muted" style="display:flex;justify-content:space-between;margin-top:4px">
      <span><?= date('j M', strtotime('-29 days')) ?></span>
      <span>peak <?= $max ?>/day</span>
      <span>today</span>
    </div>
  </div>

  <div class="card">
    <div class="lbl" style="font-size:11px;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:var(--ink-45);margin-bottom:12px">Newest users</div>
    <table>
      <?php foreach ($recentUsers as $u): ?>
      <tr>
        <td>
          <b><?= h($u['name']) ?></b>
          <div class="muted"><?= h($u['email']) ?></div>
        </td>
        <td class="muted"><?= time_ago($u['created_at']) ?></td>
        <td style="text-align:right">
          <span class="badge <?= $u['is_active'] ? 'b-ok' : 'b-bad' ?>"><?= $u['is_active'] ? 'Active' : 'Disabled' ?></span>
        </td>
      </tr>
      <?php endforeach; ?>
    </table>
    <div style="margin-top:12px;text-align:right"><a class="btn ghost sm" href="users.php">All users →</a></div>
  </div>
</div>

<div class="card" style="margin-top:16px">
  <div class="lbl" style="font-size:11px;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:var(--ink-45);margin-bottom:12px">Recent security activity</div>
  <table>
    <thead><tr><th>Event</th><th>User</th><th>IP</th><th>When</th></tr></thead>
    <tbody>
      <?php foreach ($recentAudit as $a): ?>
      <tr>
        <td><span class="badge <?= $eventBadge($a['event']) ?>"><?= h($a['event']) ?></span></td>
        <td><?= $a['name'] ? h($a['name']) : '<span class="muted">—</span>' ?></td>
        <td class="mono"><?= h($a['ip_address'] ?? '—') ?></td>
        <td class="muted"><?= time_ago($a['created_at']) ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <div style="margin-top:12px;text-align:right"><a class="btn ghost sm" href="audit.php">Full audit log →</a></div>
</div>
<?php admin_footer();
