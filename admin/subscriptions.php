<?php
require_once __DIR__ . '/../includes/admin.php';
require_once __DIR__ . '/../includes/licensing.php';
require_admin();

$pdo = db();
$me  = current_user_id();

// ── Actions ────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $action = (string)($_POST['action'] ?? '');
    $id     = (int)($_POST['id'] ?? 0);

    $stmt = $pdo->prepare('SELECT id, name, admin_extended_until FROM users WHERE id = ?');
    $stmt->execute([$id]);
    $target = $stmt->fetch();
    if (!$target) json_response(['error' => 'User not found'], 404);

    if ($action === 'extend') {
        $months = (int)($_POST['months'] ?? 0);
        if (!in_array($months, [1, 2, 3, 6, 12], true)) json_response(['error' => 'Invalid duration'], 422);

        // Stacks on top of whatever time they already have left (if any),
        // rather than always counting from today — so granting "1 month"
        // twice in a row gives 2 months, not resets to 1.
        $base = ($target['admin_extended_until'] && $target['admin_extended_until'] > date('Y-m-d H:i:s'))
            ? $target['admin_extended_until'] : date('Y-m-d H:i:s');
        $stmt = $pdo->prepare('UPDATE users SET admin_extended_until = DATE_ADD(?, INTERVAL ? MONTH) WHERE id = ?');
        $stmt->execute([$base, $months, $id]);
        recompute_user_plan($id);

        audit_log($me, 'admin_subscription_extended', ['target' => $id, 'months' => $months]);

        $new = $pdo->prepare('SELECT admin_extended_until FROM users WHERE id = ?');
        $new->execute([$id]);
        json_response(['ok' => true, 'admin_extended_until' => $new->fetchColumn()]);
    }

    if ($action === 'revoke') {
        $pdo->prepare('UPDATE users SET admin_extended_until = NULL WHERE id = ?')->execute([$id]);
        recompute_user_plan($id);
        audit_log($me, 'admin_subscription_revoked', ['target' => $id]);
        json_response(['ok' => true]);
    }

    json_response(['error' => 'Invalid request'], 400);
}

// ── Listing: search + filter + pagination ────────────────────
[$limit, $offset, $page] = paginate(20);
$q      = clean_str($_GET['q'] ?? '', 120);
$filter = one_of($_GET['filter'] ?? 'all', ['all', 'free', 'trial', 'pro', 'enterprise'], 'all');

$where = [];
$bind  = [];
if ($q !== '') { $where[] = '(u.name LIKE ? OR u.email LIKE ?)'; $bind[] = "%$q%"; $bind[] = "%$q%"; }
if ($filter === 'free')       $where[] = "u.plan = 'free'";
if ($filter === 'trial')      $where[] = "u.plan_source = 'trial'";
if ($filter === 'pro')        $where[] = "u.plan_source IN ('stripe','admin')";
if ($filter === 'enterprise') $where[] = "u.plan_source = 'org_seat'";
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$count = $pdo->prepare("SELECT COUNT(*) FROM users u $whereSql");
$count->execute($bind);
$total = (int)$count->fetchColumn();
$pages = max(1, (int)ceil($total / $limit));

$stmt = $pdo->prepare(
    "SELECT u.id, u.name, u.email, u.plan, u.plan_source, u.trial_ends_at, u.admin_extended_until,
            o.name AS org_name, o.renewal_date AS org_renewal_date, o.grace_ends_at AS org_grace_ends_at, o.plan_status AS org_plan_status
     FROM users u
     LEFT JOIN organization_members om ON om.user_id = u.id AND om.status = 'active'
     LEFT JOIN organizations o ON o.id = om.organization_id
     $whereSql
     ORDER BY u.id DESC LIMIT $limit OFFSET $offset"
);
$stmt->execute($bind);
$rows = $stmt->fetchAll();

// Days-left + badge, computed once per row so the markup below stays plain.
function subscription_status(array $u): array
{
    $today = date('Y-m-d H:i:s');

    if ($u['plan_source'] === 'org_seat') {
        if ($u['org_plan_status'] === 'locked') {
            return ['label' => 'Enterprise · locked', 'sub' => $u['org_name'], 'badge' => 'b-bad', 'days' => 0];
        }
        if ($u['org_plan_status'] === 'past_due' && $u['org_grace_ends_at']) {
            $days = max(0, (int)ceil((strtotime($u['org_grace_ends_at']) - strtotime(date('Y-m-d'))) / 86400));
            return ['label' => 'Enterprise · grace', 'sub' => $u['org_name'] . " · {$days}d left", 'badge' => 'b-warn', 'days' => $days];
        }
        $days = $u['org_renewal_date'] ? max(0, (int)ceil((strtotime($u['org_renewal_date']) - strtotime(date('Y-m-d'))) / 86400)) : null;
        return ['label' => 'Enterprise', 'sub' => $u['org_name'] . ($days !== null ? " · {$days}d left" : ''), 'badge' => 'b-lav', 'days' => $days];
    }

    if ($u['plan_source'] === 'stripe') {
        return ['label' => 'Pro', 'sub' => 'Recurring · Stripe', 'badge' => 'b-ok', 'days' => null];
    }

    if ($u['plan_source'] === 'admin' && $u['admin_extended_until']) {
        $days = max(0, (int)ceil((strtotime($u['admin_extended_until']) - time()) / 86400));
        return ['label' => 'Pro', 'sub' => "Admin-granted · {$days}d left", 'badge' => 'b-ok', 'days' => $days];
    }

    if ($u['plan_source'] === 'trial' && $u['trial_ends_at'] && $u['trial_ends_at'] > $today) {
        $days = max(0, (int)ceil((strtotime($u['trial_ends_at']) - time()) / 86400));
        return ['label' => 'Trial', 'sub' => "{$days}d left", 'badge' => 'b-warn', 'days' => $days];
    }

    return ['label' => 'Free', 'sub' => '—', 'badge' => 'b-mut', 'days' => null];
}

require_once __DIR__ . '/_layout.php';
admin_header('Subscriptions', 'subscriptions');

function keep_qs(array $extra): string {
    return h('?' . http_build_query(array_merge(
        array_filter(['q' => $_GET['q'] ?? '', 'filter' => $_GET['filter'] ?? '']),
        $extra
    )));
}
?>
<div class="tophead">
  <div>
    <h2>Subscriptions <em>· <?= number_format($total) ?></em></h2>
    <div class="sub">Every user's plan at a glance — grant extra time for offline/cash payments any time.</div>
  </div>
</div>

<div class="card">
  <div class="toolbar">
    <form method="get">
      <input type="search" name="q" value="<?= h($q) ?>" placeholder="Search name or email…">
      <select name="filter" onchange="this.form.submit()" style="width:auto">
        <option value="all"        <?= $filter === 'all' ? 'selected' : '' ?>>All plans</option>
        <option value="free"       <?= $filter === 'free' ? 'selected' : '' ?>>Free</option>
        <option value="trial"      <?= $filter === 'trial' ? 'selected' : '' ?>>Trial</option>
        <option value="pro"        <?= $filter === 'pro' ? 'selected' : '' ?>>Pro</option>
        <option value="enterprise" <?= $filter === 'enterprise' ? 'selected' : '' ?>>Enterprise</option>
      </select>
      <button class="btn sm" type="submit">Search</button>
    </form>
  </div>

  <?php if (!$rows): ?>
    <div class="empty"><div class="ic">⏳</div>No users match this search.</div>
  <?php else: ?>
  <table>
    <thead><tr><th>User</th><th>Plan</th><th>Details</th><th style="text-align:right">Actions</th></tr></thead>
    <tbody>
      <?php foreach ($rows as $u): $s = subscription_status($u); ?>
      <tr id="s<?= $u['id'] ?>">
        <td>
          <b><?= h($u['name']) ?></b>
          <div class="muted"><?= h($u['email']) ?></div>
        </td>
        <td><span class="badge <?= $s['badge'] ?>" data-label><?= h($s['label']) ?></span></td>
        <td class="muted" data-sub><?= h($s['sub']) ?></td>
        <td style="text-align:right;white-space:nowrap">
          <select id="months<?= $u['id'] ?>" style="width:auto;display:inline-block;padding:6px 9px;font-size:12px">
            <option value="1">+1 month</option>
            <option value="2">+2 months</option>
            <option value="3">+3 months</option>
            <option value="6">+6 months</option>
            <option value="12">+1 year</option>
          </select>
          <button class="btn sm" onclick="extendUser(<?= $u['id'] ?>)">Grant</button>
          <?php if ($u['plan_source'] === 'admin'): ?>
            <button class="btn ghost sm" onclick="revokeUser(<?= $u['id'] ?>)">Revoke</button>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>

  <?php if ($pages > 1): ?>
  <div class="pager">
    <?php for ($p = 1; $p <= $pages; $p++): ?>
      <?php if ($p === $page): ?><span class="cur"><?= $p ?></span>
      <?php else: ?><a href="<?= keep_qs(['page' => $p]) ?>"><?= $p ?></a><?php endif; ?>
    <?php endfor; ?>
  </div>
  <?php endif; ?>
</div>

<script>
async function extendUser(id) {
    const months = document.getElementById('months' + id).value;
    try {
        await post('subscriptions.php', { action: 'extend', id, months });
        toast(`Granted +${months} month(s) ✓`);
        setTimeout(() => location.reload(), 500);
    } catch (e) { toast(e.message); }
}
async function revokeUser(id) {
    if (!confirm('Revoke this admin-granted access now?')) return;
    try {
        await post('subscriptions.php', { action: 'revoke', id });
        toast('Access revoked');
        setTimeout(() => location.reload(), 500);
    } catch (e) { toast(e.message); }
}
</script>
<?php admin_footer();