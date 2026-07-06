<?php
require_once __DIR__ . '/includes/auth.php';
if (!current_user_id()) { header('Location: login.php'); exit; }
$user = current_user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="csrf-token" content="<?= htmlspecialchars(csrf_token()) ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Teams · Taskvel</title>
<style>
    :root {
        --bg:#f6f6f4; --bg-elev:#fff; --bg-sunk:#ededea; --ink:#0a0a0a; --ink2:#3d3d3b; --ink3:#7c7c78;
        --line:#e6e5e0; --line2:#d4d3cd; --accent:#4f46e5; --accent-soft:rgba(79,70,229,.1); --on-accent:#fff;
        --shadow:0 10px 34px rgba(10,10,10,.08); --r:14px; --ease:cubic-bezier(.22,1,.36,1);
    }
    * { box-sizing:border-box; }
    body { margin:0; font-family:-apple-system,'Segoe UI',Arial,sans-serif; background:var(--bg); color:var(--ink); }
    .wrap { max-width:760px; margin:0 auto; padding:28px 20px 80px; }
    .topbar { display:flex; justify-content:space-between; align-items:center; margin-bottom:26px; }
    .topbar a.back { color:var(--ink3); text-decoration:none; font-size:13px; font-weight:600; }
    .topbar a.back:hover { color:var(--accent); }
    h1 { font-size:24px; font-weight:800; margin:0 0 4px; }
    .sub { color:var(--ink3); font-size:14px; margin-bottom:22px; }
    .btn { display:inline-flex; align-items:center; gap:6px; padding:11px 18px; border-radius:11px; border:none;
        background:var(--accent); color:var(--on-accent); font-weight:700; font-size:14px; cursor:pointer;
        box-shadow:0 8px 20px -8px rgba(79,70,229,.4); transition:transform .2s var(--ease); }
    .btn:hover { transform:translateY(-2px); }
    .btn.ghost { background:var(--bg-elev); color:var(--ink); border:1px solid var(--line2); box-shadow:none; }
    .team-grid { display:flex; flex-direction:column; gap:12px; margin-top:20px; }
    .team-card { background:var(--bg-elev); border:1px solid var(--line); border-radius:var(--r); padding:18px 20px;
        display:flex; justify-content:space-between; align-items:center; text-decoration:none; color:inherit;
        transition:transform .2s var(--ease), box-shadow .2s; cursor:pointer; }
    .team-card:hover { transform:translateY(-3px); box-shadow:var(--shadow); border-color:var(--accent); }
    .team-name { font-size:16px; font-weight:700; margin-bottom:4px; }
    .team-meta { font-size:12.5px; color:var(--ink3); display:flex; gap:12px; }
    .role-badge { font-size:10.5px; font-weight:700; padding:4px 10px; border-radius:20px; text-transform:uppercase;
        letter-spacing:.5px; }
    .role-owner { background:#fef3c7; color:#92400e; }
    .role-manager { background:var(--accent-soft); color:var(--accent); }
    .role-member { background:var(--bg-sunk); color:var(--ink3); }
    .empty { text-align:center; padding:60px 20px; color:var(--ink3); }
    .empty .ic { font-size:44px; margin-bottom:10px; opacity:.5; }
    .modal-overlay { position:fixed; inset:0; background:rgba(10,10,10,.4); display:none; align-items:center; justify-content:center; z-index:100; }
    .modal-overlay.open { display:flex; }
    .modal { background:#fff; border-radius:18px; padding:26px; width:min(400px,90vw); box-shadow:0 30px 80px rgba(0,0,0,.25); }
    .modal h2 { margin:0 0 16px; font-size:19px; }
    .modal input, .modal select { width:100%; padding:11px 13px; border:1px solid var(--line2); border-radius:10px;
        font-size:14px; margin-bottom:12px; font-family:inherit; }
    .modal-actions { display:flex; gap:8px; margin-top:6px; }
    .modal-actions .btn { flex:1; justify-content:center; }
</style>
</head>
<body>
<div class="wrap">
    <div class="topbar">
        <a class="back" href="index.php">← Back to Taskvel</a>
        <span style="font-size:12.5px;color:var(--ink3)"><?= htmlspecialchars($user['email']) ?></span>
    </div>
    <h1>👥 Teams</h1>
    <div class="sub">Create a team so managers and teammates can share projects, assign tasks, and track progress together.</div>
    <button class="btn" onclick="openCreateTeam()">+ Create a team</button>

    <div class="team-grid" id="team-list"></div>
</div>

<div class="modal-overlay" id="ct-overlay" onclick="if(event.target===this)closeCreateTeam()">
    <div class="modal">
        <h2>Create a team</h2>
        <input type="text" id="ct-name" placeholder="e.g. Marketing, Engineering, Ops" maxlength="120" />
        <div class="modal-actions">
            <button class="btn ghost" onclick="closeCreateTeam()">Cancel</button>
            <button class="btn" onclick="submitCreateTeam()">Create</button>
        </div>
    </div>
</div>

<script src="js/api-client.js"></script>
<script>
async function loadTeams() {
    const list = document.getElementById('team-list');
    try {
        const { teams } = await Taskvel.request('/api/teams.php?action=list');
        if (!teams.length) {
            list.innerHTML = `<div class="empty"><div class="ic">👥</div><div>No teams yet — create one to start assigning tasks with coworkers.</div></div>`;
            return;
        }
        list.innerHTML = teams.map(t => `
            <a class="team-card" href="team.php?id=${t.id}">
                <div>
                    <div class="team-name">${esc(t.name)}</div>
                    <div class="team-meta">
                        <span>${t.member_count} member${t.member_count == 1 ? '' : 's'}</span>
                        <span>${t.project_count} project${t.project_count == 1 ? '' : 's'}</span>
                    </div>
                </div>
                <span class="role-badge role-${t.role}">${t.role}</span>
            </a>
        `).join('');
    } catch (e) {
        list.innerHTML = `<div class="empty">Couldn't load teams. <a href="#" onclick="loadTeams();return false;">Retry</a></div>`;
    }
}
function esc(s) { return (s || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
function openCreateTeam() { document.getElementById('ct-overlay').classList.add('open'); document.getElementById('ct-name').focus(); }
function closeCreateTeam() { document.getElementById('ct-overlay').classList.remove('open'); document.getElementById('ct-name').value=''; }
async function submitCreateTeam() {
    const name = document.getElementById('ct-name').value.trim();
    if (!name) return;
    try {
        const res = await Taskvel.request('/api/teams.php?action=create', { method:'POST', body:{ name } });
        closeCreateTeam();
        window.location.href = 'team.php?id=' + res.team_id;
    } catch (e) { alert(e.message || 'Could not create team'); }
}
loadTeams();
</script>
</body>
</html>
