<?php
require_once __DIR__ . '/includes/teams.php';
if (!current_user_id()) { header('Location: login.php'); exit; }
$user = current_user();
$teamId = (int)($_GET['id'] ?? 0);
$role = team_role($teamId, current_user_id());
if (!$role) { header('Location: teams.php'); exit; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="csrf-token" content="<?= htmlspecialchars(csrf_token()) ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Team · Taskvel</title>
<style>
    :root {
        --bg:#f6f6f4; --bg-elev:#fff; --bg-sunk:#ededea; --ink:#0a0a0a; --ink2:#3d3d3b; --ink3:#7c7c78;
        --line:#e6e5e0; --line2:#d4d3cd; --accent:#4f46e5; --accent-soft:rgba(79,70,229,.1); --on-accent:#fff;
        --good:#059669; --bad:#dc2626;
        --shadow:0 10px 34px rgba(10,10,10,.08); --r:14px; --ease:cubic-bezier(.22,1,.36,1);
    }
    * { box-sizing:border-box; }
    body { margin:0; font-family:-apple-system,'Segoe UI',Arial,sans-serif; background:var(--bg); color:var(--ink); }
    .wrap { max-width:820px; margin:0 auto; padding:28px 20px 80px; }
    .topbar { display:flex; justify-content:space-between; align-items:center; margin-bottom:22px; }
    .topbar a.back { color:var(--ink3); text-decoration:none; font-size:13px; font-weight:600; }
    .topbar a.back:hover { color:var(--accent); }
    h1 { font-size:24px; font-weight:800; margin:0 0 4px; display:flex; align-items:center; gap:10px; }
    .role-badge { font-size:10px; font-weight:700; padding:4px 10px; border-radius:20px; text-transform:uppercase; letter-spacing:.5px; }
    .role-owner { background:#fef3c7; color:#92400e; }
    .role-manager { background:var(--accent-soft); color:var(--accent); }
    .role-member { background:var(--bg-sunk); color:var(--ink3); }
    section { margin-top:28px; }
    section h2 { font-size:15px; font-weight:700; margin-bottom:12px; display:flex; justify-content:space-between; align-items:center; }
    .btn { display:inline-flex; align-items:center; gap:6px; padding:9px 15px; border-radius:10px; border:none;
        background:var(--accent); color:var(--on-accent); font-weight:700; font-size:13px; cursor:pointer;
        transition:transform .2s var(--ease); }
    .btn:hover { transform:translateY(-2px); }
    .btn.ghost { background:var(--bg-elev); color:var(--ink); border:1px solid var(--line2); }
    .btn.danger { background:var(--bad); }
    .btn.sm { padding:6px 11px; font-size:11.5px; }
    .card-list { display:flex; flex-direction:column; gap:10px; }
    .member-row, .project-card {
        background:var(--bg-elev); border:1px solid var(--line); border-radius:var(--r); padding:14px 16px;
        display:flex; justify-content:space-between; align-items:center; gap:10px;
    }
    .project-card { text-decoration:none; color:inherit; transition:transform .2s var(--ease), box-shadow .2s; cursor:pointer; }
    .project-card:hover { transform:translateY(-2px); box-shadow:var(--shadow); border-color:var(--accent); }
    .member-name { font-weight:600; font-size:14px; }
    .member-email { font-size:12px; color:var(--ink3); }
    .member-actions { display:flex; gap:6px; align-items:center; }
    select.role-select { padding:6px 8px; border-radius:8px; border:1px solid var(--line2); font-size:12px; }
    .empty { text-align:center; padding:40px 20px; color:var(--ink3); font-size:13.5px; }
    .proj-meta { font-size:12px; color:var(--ink3); margin-top:3px; }
    .proj-bar { height:5px; border-radius:4px; background:var(--bg-sunk); width:140px; overflow:hidden; margin-top:6px; }
    .proj-bar i { display:block; height:100%; background:var(--good); }
    .modal-overlay { position:fixed; inset:0; background:rgba(10,10,10,.4); display:none; align-items:center; justify-content:center; z-index:100; }
    .modal-overlay.open { display:flex; }
    .modal { background:#fff; border-radius:18px; padding:26px; width:min(420px,90vw); box-shadow:0 30px 80px rgba(0,0,0,.25); }
    .modal h2 { margin:0 0 16px; font-size:19px; }
    .modal input, .modal select, .modal textarea { width:100%; padding:11px 13px; border:1px solid var(--line2); border-radius:10px;
        font-size:14px; margin-bottom:12px; font-family:inherit; }
    .modal-actions { display:flex; gap:8px; margin-top:6px; }
    .modal-actions .btn { flex:1; justify-content:center; }
</style>
</head>
<body>
<div class="wrap">
    <div class="topbar">
        <a class="back" href="teams.php">← All teams</a>
        <span style="font-size:12.5px;color:var(--ink3)"><?= htmlspecialchars($user['email']) ?></span>
    </div>
    <h1 id="team-name">Loading… <span class="role-badge role-<?= htmlspecialchars($role) ?>"><?= htmlspecialchars($role) ?></span></h1>

    <section>
        <h2>Projects <button class="btn sm" onclick="openCreateProject()">+ New project</button></h2>
        <div class="card-list" id="project-list"></div>
    </section>

    <section>
        <h2>Members <?php if ($role !== 'member'): ?><button class="btn sm" onclick="openInvite()">+ Invite</button><?php endif; ?></h2>
        <div class="card-list" id="member-list"></div>
    </section>

    <?php if ($role === 'owner'): ?>
    <section>
        <button class="btn danger sm" onclick="deleteTeam()">Delete this team</button>
    </section>
    <?php endif; ?>
</div>

<div class="modal-overlay" id="inv-overlay" onclick="if(event.target===this)closeInvite()">
    <div class="modal">
        <h2>Invite a teammate</h2>
        <input type="email" id="inv-email" placeholder="Their Taskvel account email" />
        <select id="inv-role">
            <option value="member">Member — can update their own assigned tasks</option>
            <option value="manager">Manager — can add/edit/assign/delete any task</option>
        </select>
        <div class="modal-actions">
            <button class="btn ghost" onclick="closeInvite()">Cancel</button>
            <button class="btn" onclick="submitInvite()">Send invite</button>
        </div>
    </div>
</div>

<div class="modal-overlay" id="proj-overlay" onclick="if(event.target===this)closeCreateProject()">
    <div class="modal">
        <h2>New project</h2>
        <input type="text" id="proj-name" placeholder="Project name" maxlength="150" />
        <textarea id="proj-desc" rows="3" placeholder="What's this project about? (optional)"></textarea>
        <div class="modal-actions">
            <button class="btn ghost" onclick="closeCreateProject()">Cancel</button>
            <button class="btn" onclick="submitCreateProject()">Create</button>
        </div>
    </div>
</div>

<script src="js/api-client.js"></script>
<script>
const TEAM_ID = <?= (int)$teamId ?>;
const MY_ROLE = '<?= htmlspecialchars($role) ?>';
function esc(s) { return (s || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

async function loadTeam() {
    try {
        const { members } = await Taskvel.request(`/api/teams.php?action=members&team_id=${TEAM_ID}`);
        renderMembers(members);
    } catch (e) { document.getElementById('member-list').innerHTML = `<div class="empty">Couldn't load members.</div>`; }
    try {
        const { projects } = await Taskvel.request(`/api/projects.php?action=list&team_id=${TEAM_ID}`);
        renderProjects(projects);
    } catch (e) { document.getElementById('project-list').innerHTML = `<div class="empty">Couldn't load projects.</div>`; }
}

function renderMembers(members) {
    document.getElementById('team-name').innerHTML = `👥 Team <span class="role-badge role-${MY_ROLE}">${MY_ROLE}</span>`;
    const list = document.getElementById('member-list');
    list.innerHTML = members.map(m => `
        <div class="member-row">
            <div>
                <div class="member-name">${esc(m.name)} ${m.role === 'owner' ? '👑' : ''}</div>
                <div class="member-email">${esc(m.email)}</div>
            </div>
            <div class="member-actions">
                ${(MY_ROLE === 'owner' && m.role !== 'owner') ? `
                    <select class="role-select" onchange="changeRole(${m.id}, this.value)">
                        <option value="member" ${m.role === 'member' ? 'selected' : ''}>Member</option>
                        <option value="manager" ${m.role === 'manager' ? 'selected' : ''}>Manager</option>
                    </select>
                    <button class="btn ghost sm" onclick="removeMember(${m.id})">Remove</button>
                ` : (m.role !== 'owner' ? `<span class="role-badge role-${m.role}">${m.role}</span>` : '')}
            </div>
        </div>
    `).join('');
}

function renderProjects(projects) {
    const list = document.getElementById('project-list');
    if (!projects.length) { list.innerHTML = `<div class="empty">No projects yet. Create one to start assigning tasks.</div>`; return; }
    list.innerHTML = projects.map(p => {
        const pct = p.task_count ? Math.round((p.done_count / p.task_count) * 100) : 0;
        return `
        <a class="project-card" href="project.php?id=${p.id}">
            <div>
                <div style="font-weight:700;font-size:15px">${esc(p.name)}</div>
                <div class="proj-meta">${p.done_count}/${p.task_count} tasks done</div>
                <div class="proj-bar"><i style="width:${pct}%"></i></div>
            </div>
            <span style="font-size:20px;color:var(--ink3)">→</span>
        </a>`;
    }).join('');
}

function openInvite() { document.getElementById('inv-overlay').classList.add('open'); }
function closeInvite() { document.getElementById('inv-overlay').classList.remove('open'); document.getElementById('inv-email').value=''; }
async function submitInvite() {
    const email = document.getElementById('inv-email').value.trim();
    const role = document.getElementById('inv-role').value;
    if (!email) return;
    try {
        const res = await Taskvel.request('/api/teams.php?action=invite', { method:'POST', body:{ team_id: TEAM_ID, email, role } });
        closeInvite();
        alert(`${res.name} added to the team ✓`);
        loadTeam();
    // } catch (e) { alert(e.message || 'Could not invite'); }
    } catch (e) {
        if (e.message.includes('Upgrade')) {
            if (confirm(e.message + '\n\nGo to billing now?')) window.location.href = 'billing.php?team_id=' + TEAM_ID;
        } else {
            alert(e.message || 'Could not invite');
        }
    }
    
}
async function changeRole(userId, role) {
    try { await Taskvel.request('/api/teams.php?action=update-role', { method:'POST', body:{ team_id: TEAM_ID, user_id: userId, role } }); loadTeam(); }
    catch (e) { alert(e.message); loadTeam(); }
}
async function removeMember(userId) {
    if (!confirm('Remove this person from the team? Their assigned tasks will become unassigned.')) return;
    try { await Taskvel.request('/api/teams.php?action=remove-member', { method:'POST', body:{ team_id: TEAM_ID, user_id: userId } }); loadTeam(); }
    catch (e) { alert(e.message); }
}
async function deleteTeam() {
    if (!confirm('Delete this entire team, including all its projects and tasks? This cannot be undone.')) return;
    try { await Taskvel.request(`/api/teams.php?action=delete&team_id=${TEAM_ID}`, { method:'DELETE' }); window.location.href = 'teams.php'; }
    catch (e) { alert(e.message); }
}
function openCreateProject() { document.getElementById('proj-overlay').classList.add('open'); document.getElementById('proj-name').focus(); }
function closeCreateProject() { document.getElementById('proj-overlay').classList.remove('open'); document.getElementById('proj-name').value=''; document.getElementById('proj-desc').value=''; }
async function submitCreateProject() {
    const name = document.getElementById('proj-name').value.trim();
    const description = document.getElementById('proj-desc').value.trim();
    if (!name) return;
    try {
        const res = await Taskvel.request('/api/projects.php?action=create', { method:'POST', body:{ team_id: TEAM_ID, name, description } });
        window.location.href = 'project.php?id=' + res.project_id;
    } catch (e) { alert(e.message || 'Could not create project'); }
}
loadTeam();
</script>
</body>
</html>
