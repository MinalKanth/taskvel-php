<?php
require_once __DIR__ . '/../includes/admin.php';
require_admin();

$pdo = db();
[$limit, $offset, $page] = paginate(30);

$event = clean_str($_GET['event'] ?? '', 64);
$user  = clean_str($_GET['user'] ?? '', 190);
$days  = one_of($_GET['days'] ?? '7', ['1', '7', '30', '90', 'all'], '7');

$where = []; $bind = [];
if ($event !== '')  { $where[] = 'a.event LIKE ?'; $bind[] = "%$event%"; }
if ($user !== '')   { $where[] = '(u.name LIKE ? OR u.email LIKE ?)'; $bind[] = "%$user%"; $bind[] = "%$user%"; }
if ($days !== 'all'){ $where[] = 'a.created_at >= NOW() - INTERVAL ' . (int)$days . ' DAY'; }
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$count = $pdo->prepare("SELECT COUNT(*) FROM security_audit_log a LEFT JOIN users u ON u.id = a.user_id $whereSql");
$count->execute($bind);
$total = (int)$count->fetchColumn();
$pages = max(1, (int)ceil($total / $limit));

$stmt = $pdo->prepare("SELECT a.*, u.name, u.email
                         FROM security_audit_log a
                         LEFT JOIN users u ON u.id = a.user_id
                         $whereSql
                        ORDER BY a.id DESC LIMIT $limit OFFSET $offset");
$stmt->execute($bind);
$rows = $stmt->fetchAll();

// Distinct event names for the quick-filter chips (top 12 by volume in range)
$chips = $pdo->query("SELECT event, COUNT(*) c FROM security_audit_log
                       WHERE created_at >= NOW() - INTERVAL 30 DAY
                       GROUP BY event ORDER BY c DESC LIMIT 12")->fetchAll();

require_once __DIR__ . '/_layout.php';
admin_header('Audit log', 'audit');

$badgeFor = function (string $e): string {
    if (str_contains($e, 'failed') || str_contains($e, 'denied') || str_contains($e, 'rejected')) return 'b-bad';
    if (str_contains($e, 'rate_limited') || str_contains($e, 'expired')) return 'b-warn';
    if (str_contains($e, 'success') || str_contains($e, 'created')) return 'b-ok';
    if (str_starts_with($e, 'admin_')) return 'b-lav';
    return 'b-mut';
};
$qs = fn(array $extra) => h('?' . http_build_query(array_merge(
    array_filter(['event' => $event, 'user' => $user, 'days' => $days !== '7' ? $days : '']), $extra)));
?>
<div class="tophead">
  <div>
    <h2>Audit <em>log</em> · <?= number_format($total) ?></h2>
    <div class="sub">Every auth event, permission denial, and admin action — your paper trail.</div>
  </div>
</div>

<div class="card">
  <div class="toolbar">
    <form method="get">
      <input type="search" name="event" value="<?= h($event) ?>" placeholder="Event name… (login_failed, admin_…)">
      <input type="search" name="user" value="<?= h($user) ?>" placeholder="User name or email…" style="max-width:220px">
      <select name="days" style="width:auto">
        <option value="1"  <?= $days === '1' ? 'selected' : '' ?>>Last 24h</option>
        <option value="7"  <?= $days === '7' ? 'selected' : '' ?>>Last 7 days</option>
        <option value="30" <?= $days === '30' ? 'selected' : '' ?>>Last 30 days</option>
        <option value="90" <?= $days === '90' ? 'selected' : '' ?>>Last 90 days</option>
        <option value="all"<?= $days === 'all' ? 'selected' : '' ?>>All time</option>
      </select>
      <button class="btn sm" type="submit">Filter</button>
    </form>
  </div>

  <?php if ($chips): ?>
  <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px">
    <?php foreach ($chips as $c): ?>
      <a href="<?= $qs(['event' => $c['event']]) ?>" class="badge <?= $badgeFor($c['event']) ?>" style="text-decoration:none">
        <?= h($c['event']) ?> · <?= (int)$c['c'] ?>
      </a>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <?php if (!$rows): ?>
    <div class="empty"><div class="ic">☰</div>No audit entries match these filters.</div>
  <?php else: ?>
  <table>
    <thead><tr><th>Event</th><th>User</th><th>IP</th><th>Details</th><th>When</th></tr></thead>
    <tbody>
      <?php foreach ($rows as $r): ?>
      <tr>
        <td><span class="badge <?= $badgeFor($r['event']) ?>"><?= h($r['event']) ?></span></td>
        <td>
          <?php if ($r['name']): ?>
            <b><?= h($r['name']) ?></b><div class="muted"><?= h($r['email']) ?></div>
          <?php else: ?><span class="muted">—</span><?php endif; ?>
        </td>
        <td class="mono"><?= h($r['ip_address'] ?? '—') ?></td>
        <td class="mono muted" style="max-width:280px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"
            title="<?= h($r['meta'] ?? '') ?>"><?= h($r['meta'] ?: '—') ?></td>
        <td class="muted" style="white-space:nowrap"><?= date('j M · H:i', strtotime($r['created_at'])) ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>

  <?php if ($pages > 1): ?>
  <div class="pager">
    <?php
      $start = max(1, $page - 3); $end = min($pages, $page + 3);
      if ($start > 1) echo '<a href="' . $qs(['page' => 1]) . '">1</a><span>…</span>';
      for ($p = $start; $p <= $end; $p++) {
          echo $p === $page ? "<span class=\"cur\">$p</span>" : '<a href="' . $qs(['page' => $p]) . "\">$p</a>";
      }
      if ($end < $pages) echo '<span>…</span><a href="' . $qs(['page' => $pages]) . "\">$pages</a>";
    ?>
  </div>
  <?php endif; ?>
</div>
<?php admin_footer();
