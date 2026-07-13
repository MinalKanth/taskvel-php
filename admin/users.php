<?php
require_once __DIR__ . '/../includes/admin.php';
require_admin();

$pdo = db();
$me  = current_user_id();

// ── Actions (fetch POSTs with X-CSRF-Token, JSON responses) ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $action = (string)($_POST['action'] ?? '');
    $id     = (int)($_POST['id'] ?? 0);

    if ($id === $me && in_array($action, ['toggle_active', 'set_role'], true)) {
        json_response(['error' => "You can't change your own account from here."], 422);
    }
    $stmt = $pdo->prepare('SELECT id, is_active, role FROM users WHERE id = ?');
    $stmt->execute([$id]);
    $target = $stmt->fetch();
    if (!$target) json_response(['error' => 'User not found'], 404);

    switch ($action) {
        case 'toggle_active':
            $new = $target['is_active'] ? 0 : 1;
            $pdo->prepare('UPDATE users SET is_active = ? WHERE id = ?')->execute([$new, $id]);
            audit_log($me, 'admin_user_toggled', ['target' => $id, 'is_active' => $new]);
            json_response(['ok' => true, 'is_active' => $new]);

        case 'set_role':
            $role = one_of((string)($_POST['role'] ?? ''), ['user', 'admin'], 'user');
            // Never allow demoting the last remaining admin — that would lock
            // everyone (including you) out of this panel permanently.
            if ($target['role'] === 'admin' && $role === 'user') {
                $admins = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin' AND is_active = 1")->fetchColumn();
                if ($admins <= 1) json_response(['error' => 'Cannot demote the last active admin.'], 422);
            }
            $pdo->prepare('UPDATE users SET role = ? WHERE id = ?')->execute([$role, $id]);
            audit_log($me, 'admin_role_changed', ['target' => $id, 'role' => $role]);
            json_response(['ok' => true, 'role' => $role]);
    }
    json_response(['error' => 'Invalid request'], 400);
}

// ── Detail panel (loaded inline via ?view=ID) ────────────────
$detail = null;
if (!empty($_GET['view'])) {
    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([(int)$_GET['view']]);
    $detail = $stmt->fetch();
    if ($detail) {
        $d = $pdo->prepare("SELECT event, ip_address, created_at FROM security_audit_log
                             WHERE user_id = ? ORDER BY id DESC LIMIT 10");
        $d->execute([$detail['id']]);
        $detail['audit'] = $d->fetchAll();

        $detail['state_size'] = null;
        try {
            $s = $pdo->prepare('SELECT LENGTH(state_json), updated_at FROM user_state WHERE user_id = ?');
            $s->execute([$detail['id']]);
            if ($row = $s->fetch(PDO::FETCH_NUM)) $detail['state_size'] = ['bytes' => (int)$row[0], 'at' => $row[1]];
        } catch (Throwable $e) {}
    }
}

// ── Listing: search + filter + pagination ────────────────────
[$limit, $offset, $page] = paginate(15);
$q      = clean_str($_GET['q'] ?? '', 120);
$filter = one_of($_GET['filter'] ?? 'all', ['all', 'active', 'disabled', 'admins'], 'all');

$where = [];
$bind  = [];
if ($q !== '') { $where[] = '(name LIKE ? OR email LIKE ?)'; $bind[] = "%$q%"; $bind[] = "%$q%"; }
if ($filter === 'active')   $where[] = 'is_active = 1';
if ($filter === 'disabled') $where[] = 'is_active = 0';
if ($filter === 'admins')   $where[] = "role = 'admin'";
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$count = $pdo->prepare("SELECT COUNT(*) FROM users $whereSql");
$count->execute($bind);
$total = (int)$count->fetchColumn();
$pages = max(1, (int)ceil($total / $limit));

$stmt = $pdo->prepare("SELECT id, name, email, role, is_active, created_at, last_login_at
                        FROM users $whereSql ORDER BY id DESC LIMIT $limit OFFSET $offset");
$stmt->execute($bind);
$users = $stmt->fetchAll();

require_once __DIR__ . '/_layout.php';
admin_header('Users', 'users');

function keep_qs(array $extra): string {
    return h('?' . http_build_query(array_merge(
        array_filter(['q' => $_GET['q'] ?? '', 'filter' => $_GET['filter'] ?? '']),
        $extra
    )));
}
?>
<div class="tophead">
  <div>
    <h2>Users <em>· <?= number_format($total) ?></em></h2>
    <div class="sub">Search, review activity, and manage account access.</div>
  </div>
</div>

<?php if ($detail): ?>
<div class="card" style="margin-bottom:16px;border-color:rgba(232,199,102,.3)">
  <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:14px;flex-wrap:wrap">
    <div style="display:flex;gap:14px;align-items:center">
      <div class="avatar" style="width:46px;height:46px;font-size:17px"><?= h(strtoupper(mb_substr($detail['name'], 0, 1))) ?></div>
      <div>
        <b style="font-size:16px"><?= h($detail['name']) ?></b>
        <span class="badge <?= $detail['role'] === 'admin' ? 'b-lav' : 'b-mut' ?>" style="margin-left:8px"><?= h($detail['role']) ?></span>
        <span class="badge <?= $detail['is_active'] ? 'b-ok' : 'b-bad' ?>" style="margin-left:6px"><?= $detail['is_active'] ? 'Active' : 'Disabled' ?></span>
        <div class="muted" style="margin-top:4px">
          <?= h($detail['email']) ?> · joined <?= date('j M Y', strtotime($detail['created_at'])) ?>
          · last login <?= time_ago($detail['last_login_at']) ?>
          <?php if ($detail['state_size']): ?>
            · synced data <?= number_format($detail['state_size']['bytes'] / 1024, 1) ?> KB (<?= time_ago($detail['state_size']['at']) ?>)
          <?php endif; ?>
        </div>
      </div>
    </div>
    <a class="btn ghost sm" href="users.php<?= keep_qs([]) === '?' ? '' : keep_qs([]) ?>">✕ Close</a>
  </div>
  <?php if ($detail['audit']): ?>
  <table style="margin-top:16px">
    <thead><tr><th>Recent activity</th><th>IP</th><th>When</th></tr></thead>
    <tbody>
      <?php foreach ($detail['audit'] as $a): ?>
      <tr>
        <td class="mono"><?= h($a['event']) ?></td>
        <td class="mono muted"><?= h($a['ip_address'] ?? '—') ?></td>
        <td class="muted"><?= time_ago($a['created_at']) ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
</div>
<?php endif; ?>

<div class="card">
  <div class="toolbar">
    <form method="get">
      <input type="search" name="q" value="<?= h($q) ?>" placeholder="Search name or email…">
      <select name="filter" onchange="this.form.submit()" style="width:auto">
        <option value="all"      <?= $filter === 'all' ? 'selected' : '' ?>>All users</option>
        <option value="active"   <?= $filter === 'active' ? 'selected' : '' ?>>Active</option>
        <option value="disabled" <?= $filter === 'disabled' ? 'selected' : '' ?>>Disabled</option>
        <option value="admins"   <?= $filter === 'admins' ? 'selected' : '' ?>>Admins</option>
      </select>
      <button class="btn sm" type="submit">Search</button>
    </form>
  </div>

  <?php if (!$users): ?>
    <div class="empty"><div class="ic">◔</div>No users match this search.</div>
  <?php else: ?>
  <table>
    <thead><tr><th>User</th><th>Role</th><th>Status</th><th>Joined</th><th>Last login</th><th style="text-align:right">Actions</th></tr></thead>
    <tbody>
      <?php foreach ($users as $u): $self = $u['id'] === $me; ?>
      <tr id="u<?= $u['id'] ?>">
        <td>
          <b><?= h($u['name']) ?></b><?= $self ? ' <span class="muted">(you)</span>' : '' ?>
          <div class="muted"><?= h($u['email']) ?></div>
        </td>
        <td><span class="badge <?= $u['role'] === 'admin' ? 'b-lav' : 'b-mut' ?>" data-role><?= h($u['role']) ?></span></td>
        <td><span class="badge <?= $u['is_active'] ? 'b-ok' : 'b-bad' ?>" data-status><?= $u['is_active'] ? 'Active' : 'Disabled' ?></span></td>
        <td class="muted"><?= date('j M Y', strtotime($u['created_at'])) ?></td>
        <td class="muted"><?= time_ago($u['last_login_at']) ?></td>
        <td style="text-align:right;white-space:nowrap">
          <a class="btn ghost sm" href="<?= keep_qs(['view' => $u['id'], 'page' => $page]) ?>">View</a>
          <?php if (!$self): ?>
            <button class="btn ghost sm" onclick="setRole(<?= $u['id'] ?>, '<?= $u['role'] === 'admin' ? 'user' : 'admin' ?>')">
              <?= $u['role'] === 'admin' ? 'Demote' : 'Make admin' ?>
            </button>
            <button class="btn <?= $u['is_active'] ? 'danger' : '' ?> sm" onclick="toggleActive(<?= $u['id'] ?>)">
              <?= $u['is_active'] ? 'Disable' : 'Enable' ?>
            </button>
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
async function toggleActive(id) {
    try {
        const r = await post('users.php', { action: 'toggle_active', id });
        toast(r.is_active ? 'Account enabled' : 'Account disabled');
        setTimeout(() => location.reload(), 500);
    } catch (e) { toast(e.message); }
}
async function setRole(id, role) {
    if (role === 'admin' && !confirm('Give this user full admin access?')) return;
    try {
        await post('users.php', { action: 'set_role', id, role });
        toast(role === 'admin' ? 'Promoted to admin' : 'Demoted to user');
        setTimeout(() => location.reload(), 500);
    } catch (e) { toast(e.message); }
}
</script>
<?php admin_footer();
