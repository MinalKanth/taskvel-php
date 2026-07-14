<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/pro-shell.php';
if (!current_user_id()) { header('Location: login.php'); exit; }
$user = current_user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="csrf-token" content="<?= htmlspecialchars(csrf_token()) ?>">
<?php pro_head('Teams'); ?>
<style>
    .team-meta { font-size:12px; color:var(--ink3); display:flex; gap:14px; margin-top:5px; flex-wrap:wrap; }
    .team-meta b { color:var(--ink2); font-weight:600; }
    .team-name { font-family:var(--font-display); font-size:16.5px; font-weight:700; }
    .team-row { display:flex; justify-content:space-between; align-items:center; gap:12px; }
</style>
</head>
<body>
<div class="wrap">
    <?php pro_header($user, 'teams'); ?>

    <h1 class="page-title">👥 Teams</h1>
    <div class="sub">Create a team so managers and teammates can share projects, assign tasks, and plan events together — all inside Taskvel Pro.</div>
    <button class="btn" onclick="openCreateTeam()">+ Create a team</button>

    <div class="card-list" id="team-list" style="margin-top:22px"></div>
</div>

<div class="modal-overlay" id="ct-overlay" onclick="if(event.target===this)closeCreateTeam()">
    <div class="modal">
        <h2>Create a team</h2>
        <div class="fg">
            <label>Team name</label>
            <input type="text" id="ct-name" placeholder="e.g. Marketing, Engineering, Ops" maxlength="120"
                   onkeydown="if(event.key==='Enter')submitCreateTeam()" />
        </div>
        <div class="modal-actions">
            <button class="btn ghost" onclick="closeCreateTeam()">Cancel</button>
            <button class="btn" onclick="submitCreateTeam()">Create</button>
        </div>
    </div>
</div>

<script src="js/api-client.js"></script>
<script>
function esc(s) { return (s || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

async function loadTeams() {
    const list = document.getElementById('team-list');
    try {
        const { teams } = await Taskvel.request('/api/teams.php?action=list');
        if (!teams.length) {
            list.innerHTML = `<div class="empty"><span class="ic">👥</span>No teams yet — create one to start assigning tasks and planning events with coworkers.</div>`;
            return;
        }
        list.innerHTML = teams.map(t => `
            <a class="card" href="team.php?id=${t.id}">
                <div class="team-row">
                    <div>
                        <div class="team-name">${esc(t.name)}</div>
                        <div class="team-meta">
                            <span><b>${t.member_count}</b> member${t.member_count == 1 ? '' : 's'}</span>
                            <span><b>${t.project_count}</b> project${t.project_count == 1 ? '' : 's'}</span>
                        </div>
                    </div>
                    <span class="role-badge role-${t.role}">${t.role}</span>
                </div>
            </a>
        `).join('');
    } catch (e) {
        list.innerHTML = `<div class="empty">Couldn't load teams — ${esc(e.message)}. <a href="#" onclick="loadTeams();return false;" style="color:var(--accent)">Retry</a></div>`;
    }
}
function openCreateTeam() { document.getElementById('ct-overlay').classList.add('open'); document.getElementById('ct-name').focus(); }
function closeCreateTeam() { document.getElementById('ct-overlay').classList.remove('open'); document.getElementById('ct-name').value=''; }
async function submitCreateTeam() {
    const name = document.getElementById('ct-name').value.trim();
    if (!name) return;
    try {
        const res = await Taskvel.request('/api/teams.php?action=create', { method:'POST', body:{ name } });
        closeCreateTeam();
        window.location.href = 'team.php?id=' + res.team_id;
    } catch (e) { toast(e.message || 'Could not create team'); }
}
loadTeams();
</script>
</body>
</html>
