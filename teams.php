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
    

    .teams-intro { margin-bottom:22px; }
    .teams-intro h1.page-title { margin-bottom:5px; }
    .teams-intro .sub { margin-bottom:0; }

    .teams-toolbar { display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap; margin-bottom:22px; }
    .teams-toolbar .btn { box-shadow:0 6px 16px -6px var(--accent-glow); }

    #team-limit-note { font-family:'JetBrains Mono',monospace; font-size:11px; color:var(--ink3); background:var(--bg-sunk);
        border:1px solid var(--line); padding:5px 11px; border-radius:20px; margin:0; }

    .card-list a.card {
        padding:16px 17px;
        border-radius:var(--r-lg);
        position:relative;
        overflow:hidden;
        box-shadow:var(--shadow-sm);
        transition:border-color .2s ease, box-shadow .25s ease, transform .2s var(--ease);
    }
    .card-list a.card::before {
        content:''; position:absolute; left:0; top:0; bottom:0; width:3px;
        background:var(--accent); opacity:.6; transition:width .2s ease, opacity .2s ease;
    }
    .card-list a.card:hover { transform:translateY(-2px); box-shadow:var(--shadow); border-color:var(--line2); }
    .card-list a.card:hover::before { width:5px; opacity:1; }

    .team-meta { font-size:12px; color:var(--ink3); display:flex; gap:14px; margin-top:5px; flex-wrap:wrap; }
    .team-meta b { color:var(--ink2); font-weight:600; }
    .team-meta span { display:inline-flex; align-items:center; gap:4px; }
    .team-name { font-family:var(--font-display); font-size:16.5px; font-weight:700; }
    .team-row { display:flex; justify-content:space-between; align-items:center; gap:12px; }

    .empty .ic { font-size:32px; }
</style>
</head>
<body>
<div class="wrap">
    <?php pro_header($user, 'teams'); ?>

    <div class="teams-intro">
        <h1 class="page-title">👥 Teams</h1>
        <div class="sub">Create a team so managers and teammates can share projects, assign tasks, and plan events together — all inside Taskvel Pro.</div>
    </div>

    <div class="teams-toolbar">
        <button class="btn" onclick="openCreateTeam()">+ Create a team</button>
        <div id="team-limit-note"></div>
    </div>

    <div class="card-list" id="team-list"></div>
    <?php pro_footer($user); ?>
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
            <button class="btn" id="ct-save-btn" onclick="submitCreateTeam()">Create</button>
        </div>
    </div>
</div>

<script src="js/api-client.js?v=2"></script>
<script>
function esc(s) { return (s || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;'); }

async function loadTeams() {
    const list = document.getElementById('team-list');
    try {
        const { teams } = await Taskvel.request('/api/teams.php?action=list');
        if (!teams.length) {
            list.innerHTML = `<div class="empty"><span class="ic">👥</span>No teams yet — create one to start assigning tasks and planning events with coworkers.</div>`;
            return;
        }
        list.innerHTML = teams.map((t, idx) => `
            <a class="card" href="team.php?id=${t.id}" style="animation:cardIn .4s var(--ease) backwards;animation-delay:${idx * 40}ms">
                <div class="team-row">
                    <div>
                        <div class="team-name">${esc(t.name)}</div>
                        <div class="team-meta">
                            <span>◴ <b>${t.member_count}</b> member${t.member_count == 1 ? '' : 's'}</span>
                            <span>▦ <b>${t.project_count}</b> project${t.project_count == 1 ? '' : 's'}</span>
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
async function loadLimits() {
    try {
        const { plan, teams_owned, max_teams } = await Taskvel.request('/api/teams.php?action=limits');
        const note = document.getElementById('team-limit-note');
        if (plan === 'pro') { note.textContent = ''; return; }
        note.textContent = `Free plan: ${teams_owned}/${max_teams} teams created.`;
    } catch (e) { /* non-critical */ }
}

async function submitCreateTeam() {
    const name = document.getElementById('ct-name').value.trim();
    if (!name) return;
    const btn = document.getElementById('ct-save-btn');
    if (btn.disabled) return;
    btn.disabled = true;
    const orig = btn.textContent;
    btn.textContent = 'Creating…';
    try {
        const res = await Taskvel.request('/api/teams.php?action=create', { method:'POST', body:{ name } });
        closeCreateTeam();
        window.location.href = 'team.php?id=' + res.team_id;
    } catch (e) {
        if ((e.message || '').includes('Upgrade')) {
            if (confirm(e.message + '\n\nGo to billing now?')) window.location.href = 'billing.php';
        } else { toast(e.message || 'Could not create team'); }
        btn.disabled = false;
        btn.textContent = orig;
    }
}
loadTeams();
loadLimits();
</script>
</body>
</html>