<?php
require_once __DIR__ . '/includes/teams.php';
require_once __DIR__ . '/includes/pro-shell.php';
if (!current_user_id()) { header('Location: login.php'); exit; }
$user = current_user();
$projectId = (int)($_GET['id'] ?? 0);
$teamId = project_team_id($projectId);
if (!$teamId) { header('Location: teams.php'); exit; }
$role = team_role($teamId, current_user_id());
if (!$role) { header('Location: teams.php'); exit; }
$isManager = ($role === 'owner' || $role === 'manager');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="csrf-token" content="<?= htmlspecialchars(csrf_token()) ?>">
<?php pro_head('Project'); ?>
<style>
    .wrap { max-width:1020px; }
    /* Per-person progress strip */
    .summary-strip { display:flex; gap:10px; overflow-x:auto; padding-bottom:6px; margin:18px 0 22px; }
    .summary-chip { background:var(--bg-elev); border:1px solid var(--line); border-radius:13px; padding:11px 15px;
        min-width:132px; flex-shrink:0; }
    .summary-chip .name { font-family:var(--font-display); font-size:12.5px; font-weight:700; margin-bottom:7px;
        white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .summary-chip .bar { height:5px; border-radius:4px; background:var(--bg-sunk); overflow:hidden; margin-bottom:6px; }
    .summary-chip .bar i { display:block; height:100%; background:var(--accent); border-radius:4px; }
    .summary-chip .nums { font-size:10.5px; color:var(--ink3); font-family:'JetBrains Mono',monospace; }

    /* Kanban board */
    .board { display:grid; grid-template-columns:repeat(3,1fr); gap:16px; }
    @media (max-width:780px) { .board { grid-template-columns:1fr; } }
    .col { background:var(--bg-sunk); border:1px solid var(--line); border-radius:var(--r); padding:12px; min-height:130px; }
    .col h3 { font-family:var(--font-display); font-size:11.5px; text-transform:uppercase; letter-spacing:.9px;
        color:var(--ink3); margin:4px 6px 11px; display:flex; justify-content:space-between; }
    .col h3 span { background:var(--bg-elev); border:1px solid var(--line); border-radius:999px; padding:1px 9px; font-size:10.5px; }
    .task-card { background:var(--bg-elev); border:1px solid var(--line); border-radius:13px; padding:13px 14px;
        margin-bottom:10px; cursor:pointer; transition:transform .2s var(--ease), box-shadow .2s, border-color .2s; }
    .task-card:hover { transform:translateY(-2px); box-shadow:var(--shadow); border-color:var(--accent); }
    .task-title { font-family:var(--font-display); font-size:13.5px; font-weight:600; margin-bottom:7px; line-height:1.4; }
    .task-meta { display:flex; flex-wrap:wrap; gap:6px; align-items:center; }
    .pill { font-family:var(--font-display); font-size:9px; font-weight:700; padding:3px 8px; border-radius:6px;
        text-transform:uppercase; letter-spacing:.5px; }
    .pri-critical { background:var(--bad); color:#fff; }
    .pri-high { background:var(--accent-soft); color:var(--accent); border:1px solid var(--accent-glow); }
    .pri-medium { background:var(--bg-sunk); color:var(--ink2); border:1px solid var(--line); }
    .pri-low { background:var(--bg-sunk); color:var(--ink3); border:1px solid var(--line); }
    .due { font-size:10.5px; color:var(--ink3); font-family:'JetBrains Mono',monospace; }
    .due.overdue { color:var(--bad); font-weight:700; }
    .empty-col { text-align:center; color:var(--ink4); font-size:12px; padding:22px 6px; }

    /* Upcoming events strip for this project */
    .proj-events { display:flex; gap:10px; overflow-x:auto; margin:0 0 20px; padding-bottom:4px; }
    .proj-event-chip { flex-shrink:0; display:flex; align-items:center; gap:9px; background:var(--bg-elev);
        border:1px solid var(--line); border-radius:12px; padding:9px 13px; font-size:12px; }
    .proj-event-chip b { font-family:var(--font-display); font-size:12.5px; }
    .proj-event-chip .dt { color:var(--accent); font-family:'JetBrains Mono',monospace; font-size:10.5px; font-weight:700; }

    .comments { margin-top:16px; border-top:1px solid var(--line); padding-top:14px; }
    .comment { font-size:12.5px; margin-bottom:8px; padding:9px 11px; background:var(--bg-sunk); border-radius:9px; line-height:1.5; }
    .comment b { font-family:var(--font-display); font-size:11.5px; color:var(--accent-2); }
    :root[data-theme="dark"] .comment b { color:var(--accent-2); }
</style>
</head>
<body>
<div class="wrap">
    <?php pro_header($user, 'teams', '<a href="teams.php">Teams</a> <span style="opacity:.5">/</span> <a href="team.php?id=' . (int)$teamId . '" id="crumb-team-link">Team</a> <span style="opacity:.5">/</span> <span id="crumb-proj">…</span>'); ?>

    <h1 class="page-title" id="proj-title">Loading…</h1>
    <div class="sub" id="proj-desc"></div>
    <button class="btn" onclick="openCreateTask()">+ Add task</button>

    <div class="summary-strip" id="summary-strip"></div>
    <div class="proj-events" id="proj-events" style="display:none"></div>

    <div class="board">
        <div class="col"><h3>To do <span id="cnt-todo">0</span></h3><div id="col-todo"></div></div>
        <div class="col"><h3>In progress <span id="cnt-in_progress">0</span></h3><div id="col-in_progress"></div></div>
        <div class="col"><h3>Done <span id="cnt-done">0</span></h3><div id="col-done"></div></div>
    </div>
</div>

<!-- Create/Edit task modal -->
<div class="modal-overlay" id="task-overlay" onclick="if(event.target===this)closeTaskModal()">
    <div class="modal">
        <h2 id="task-modal-title">New task</h2>
        <input type="hidden" id="t-id" />
        <div class="fg"><label>Title</label><input type="text" id="t-title" placeholder="What needs to be done?" /></div>
        <div class="fg"><label>Description</label><textarea id="t-desc" placeholder="Details, links, context…"></textarea></div>
        <div class="row2">
            <div class="fg"><label>Priority</label>
                <select id="t-priority"><option value="low">Low</option><option value="medium" selected>Medium</option><option value="high">High</option><option value="critical">Critical</option></select>
            </div>
            <div class="fg"><label>Due date</label><input type="date" id="t-due" /></div>
        </div>
        <div class="row2">
            <div class="fg"><label>Assign to</label><select id="t-assignee"></select></div>
            <div class="fg"><label>Status</label>
                <select id="t-status"><option value="todo">To do</option><option value="in_progress">In progress</option><option value="done">Done</option></select>
            </div>
        </div>
        <div class="modal-actions">
            <button class="btn ghost" onclick="closeTaskModal()">Cancel</button>
            <button class="btn" id="t-save-btn" onclick="saveTask()">Save task</button>
        </div>
        <button class="btn danger sm" id="t-delete-btn" style="margin-top:10px;display:none" onclick="deleteTask()">Delete task</button>

        <div class="comments" id="comments-section" style="display:none">
            <label>Comments</label>
            <div id="comments-list"></div>
            <div class="fg" style="margin-top:8px"><input type="text" id="comment-input" placeholder="Write a comment and press Enter…" onkeydown="if(event.key==='Enter')submitComment()" /></div>
        </div>
    </div>
</div>

<script src="js/api-client.js"></script>
<script>
const PROJECT_ID = <?= (int)$projectId ?>;
const TEAM_ID = <?= (int)$teamId ?>;
const MY_ROLE = '<?= htmlspecialchars($role) ?>';
const IS_MANAGER = <?= $isManager ? 'true' : 'false' ?>;
const MY_USER_ID = <?= (int)current_user_id() ?>;
let members = [];
let currentTaskId = null;

function esc(s) { return (s || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
function initials(name) { return (name || '?').split(' ').map(w => w[0]).join('').slice(0,2).toUpperCase(); }

async function loadProject() {
    try {
        const { project } = await Taskvel.request(`/api/projects.php?action=get&id=${PROJECT_ID}`);
        document.getElementById('proj-title').textContent = project.name;
        document.getElementById('crumb-proj').textContent = project.name;
        document.getElementById('proj-desc').textContent = project.description || '';
        document.title = project.name + ' · Taskvel Pro';
    } catch (e) {}
    try {
        const { team } = await Taskvel.request(`/api/teams.php?action=get&team_id=${TEAM_ID}`);
        document.getElementById('crumb-team-link').textContent = team.name;
    } catch (e) {}
    const { members: m } = await Taskvel.request(`/api/teams.php?action=members&team_id=${TEAM_ID}`);
    members = m;
    document.getElementById('t-assignee').innerHTML = '<option value="">Unassigned</option>' +
        members.map(mm => `<option value="${mm.id}">${esc(mm.name)}</option>`).join('');
    await Promise.all([loadTasks(), loadSummary(), loadProjectEvents()]);
}

// Upcoming events tied to THIS project — visible right on the board.
async function loadProjectEvents() {
    try {
        const { events } = await Taskvel.request(`/api/team_events.php?action=list&team_id=${TEAM_ID}`);
        const todayStr = new Date().toISOString().slice(0,10);
        const mine = events.filter(ev => ev.project_id == PROJECT_ID && ev.event_date >= todayStr).slice(0, 6);
        if (!mine.length) return;
        const box = document.getElementById('proj-events');
        box.style.display = 'flex';
        box.innerHTML = mine.map(ev => `
            <a class="proj-event-chip" href="team.php?id=${TEAM_ID}" style="text-decoration:none;color:inherit">
                <span>📅</span>
                <span><b>${esc(ev.title)}</b><br><span class="dt">${ev.event_date}${ev.start_time ? ' · ' + ev.start_time.slice(0,5) : ''}</span></span>
                <span class="avatar-stack">${(ev.attendees || []).slice(0,4).map(a => `<span class="avatar" title="${esc(a.name)}">${initials(a.name)}</span>`).join('')}</span>
            </a>
        `).join('');
    } catch (e) { /* events table may not exist yet — board still works */ }
}

async function loadTasks() {
    const { tasks } = await Taskvel.request(`/api/project_tasks.php?action=list&project_id=${PROJECT_ID}`);
    const cols = { todo: [], in_progress: [], done: [] };
    tasks.forEach(t => cols[t.status].push(t));
    ['todo', 'in_progress', 'done'].forEach(status => {
        document.getElementById('cnt-' + status).textContent = cols[status].length;
        const el = document.getElementById('col-' + status);
        if (!cols[status].length) { el.innerHTML = `<div class="empty-col">Nothing here</div>`; return; }
        el.innerHTML = cols[status].map(t => {
            const today = new Date(); today.setHours(0,0,0,0);
            const due = t.due_date ? new Date(t.due_date) : null;
            const overdue = due && due < today && t.status !== 'done';
            return `<div class="task-card" onclick="openEditTask(${t.id})">
                <div class="task-title">${esc(t.title)}</div>
                <div class="task-meta">
                    <span class="pill pri-${t.priority}">${t.priority}</span>
                    ${t.assignee_name ? `<span class="avatar" title="${esc(t.assignee_name)}">${initials(t.assignee_name)}</span>` : ''}
                    ${t.due_date ? `<span class="due ${overdue ? 'overdue' : ''}">${t.due_date}</span>` : ''}
                </div>
            </div>`;
        }).join('');
    });
}

async function loadSummary() {
    const { summary } = await Taskvel.request(`/api/project_tasks.php?action=summary&project_id=${PROJECT_ID}`);
    const strip = document.getElementById('summary-strip');
    strip.innerHTML = summary.map(s => {
        const pct = s.total ? Math.round((s.done / s.total) * 100) : 0;
        return `<div class="summary-chip">
            <div class="name">${esc(s.name)}</div>
            <div class="bar"><i style="width:${pct}%"></i></div>
            <div class="nums">${s.done || 0}/${s.total || 0} done</div>
        </div>`;
    }).join('');
}

function openCreateTask() {
    currentTaskId = null;
    document.getElementById('task-modal-title').textContent = 'New task';
    document.getElementById('t-id').value = '';
    document.getElementById('t-title').value = '';
    document.getElementById('t-title').disabled = false;
    document.getElementById('t-desc').value = '';
    document.getElementById('t-desc').disabled = false;
    document.getElementById('t-priority').value = 'medium';
    document.getElementById('t-priority').disabled = false;
    document.getElementById('t-due').value = '';
    document.getElementById('t-due').disabled = false;
    document.getElementById('t-assignee').value = IS_MANAGER ? '' : MY_USER_ID;
    document.getElementById('t-assignee').disabled = !IS_MANAGER;
    document.getElementById('t-status').value = 'todo';
    document.getElementById('t-status').disabled = false;
    document.getElementById('t-delete-btn').style.display = 'none';
    document.getElementById('comments-section').style.display = 'none';
    document.getElementById('task-overlay').classList.add('open');
    document.getElementById('t-title').focus();
}

async function openEditTask(id) {
    currentTaskId = id;
    const { tasks } = await Taskvel.request(`/api/project_tasks.php?action=list&project_id=${PROJECT_ID}`);
    const t = tasks.find(x => x.id === id);
    if (!t) return;
    const isMine = t.assignee_id == MY_USER_ID;
    const canEditFully = IS_MANAGER;
    document.getElementById('task-modal-title').textContent = canEditFully || isMine ? 'Edit task' : 'View task';
    document.getElementById('t-id').value = t.id;
    document.getElementById('t-title').value = t.title;
    document.getElementById('t-title').disabled = !canEditFully;
    document.getElementById('t-desc').value = t.description || '';
    document.getElementById('t-desc').disabled = !canEditFully;
    document.getElementById('t-priority').value = t.priority;
    document.getElementById('t-priority').disabled = !canEditFully;
    document.getElementById('t-due').value = t.due_date || '';
    document.getElementById('t-due').disabled = !canEditFully;
    document.getElementById('t-assignee').value = t.assignee_id || '';
    document.getElementById('t-assignee').disabled = !canEditFully;
    document.getElementById('t-status').value = t.status;
    document.getElementById('t-status').disabled = !(canEditFully || isMine);
    document.getElementById('t-delete-btn').style.display = (canEditFully || t.created_by == MY_USER_ID) ? 'inline-flex' : 'none';
    document.getElementById('comments-section').style.display = 'block';
    await loadComments(id);
    document.getElementById('task-overlay').classList.add('open');
}

function closeTaskModal() { document.getElementById('task-overlay').classList.remove('open'); }

async function saveTask() {
    const title = document.getElementById('t-title').value.trim();
    const payload = {
        title,
        description: document.getElementById('t-desc').value.trim(),
        priority: document.getElementById('t-priority').value,
        due_date: document.getElementById('t-due').value || null,
        assignee_id: document.getElementById('t-assignee').value || null,
        status: document.getElementById('t-status').value,
    };
    try {
        if (currentTaskId) {
            payload.id = currentTaskId;
            await Taskvel.request('/api/project_tasks.php?action=update', { method:'POST', body: payload });
        } else {
            if (!title) { toast('Enter a task title'); return; }
            payload.project_id = PROJECT_ID;
            await Taskvel.request('/api/project_tasks.php?action=create', { method:'POST', body: payload });
        }
        closeTaskModal();
        loadTasks(); loadSummary();
    } catch (e) { toast(e.message || 'Could not save task'); }
}

async function deleteTask() {
    if (!currentTaskId || !confirm('Delete this task?')) return;
    try {
        await Taskvel.request(`/api/project_tasks.php?action=delete&id=${currentTaskId}`, { method:'DELETE' });
        closeTaskModal();
        loadTasks(); loadSummary();
    } catch (e) { toast(e.message); }
}

async function loadComments(taskId) {
    const list = document.getElementById('comments-list');
    list.innerHTML = 'Loading…';
    try {
        const { comments } = await Taskvel.request(`/api/project_tasks.php?action=comments&task_id=${taskId}`);
        list.innerHTML = comments.length ? comments.map(c => `<div class="comment"><b>${esc(c.user_name)}</b>: ${esc(c.body)}</div>`).join('') : '<div class="comment" style="color:var(--ink3)">No comments yet</div>';
    } catch (e) { list.innerHTML = ''; }
}
async function submitComment() {
    const inp = document.getElementById('comment-input');
    const body = inp.value.trim();
    if (!body || !currentTaskId) return;
    try {
        await Taskvel.request('/api/project_tasks.php?action=comment', { method:'POST', body:{ task_id: currentTaskId, body } });
        inp.value = '';
        loadComments(currentTaskId);
    } catch (e) { toast(e.message); }
}

loadProject();
</script>
</body>
</html>
