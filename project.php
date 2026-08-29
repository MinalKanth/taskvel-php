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
    /* Board pages need more breathing room than the default list-page wrap */
    .wrap { max-width:1180px; }
    @media (min-width:720px) { .wrap { max-width:1180px; } }
    @media (min-width:980px) { .wrap { max-width:1180px; } }

    .topbar { display:flex; justify-content:space-between; align-items:flex-start; gap:12px; margin-bottom:6px; flex-wrap:wrap; }
    .topbar .actions { display:flex; gap:8px; flex-wrap:wrap; }

    /* Per-person progress strip */
    .summary-strip { display:flex; gap:10px; overflow-x:auto; padding-bottom:6px; margin:18px 0 22px; }
    .summary-chip { background:var(--bg-elev); border:1px solid var(--line); border-radius:var(--r-sm); padding:10px 14px;
        min-width:130px; flex-shrink:0; }
    .summary-chip .name { font-family:var(--font-display); font-size:12.5px; font-weight:700; margin-bottom:6px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .summary-chip .bar { height:5px; border-radius:4px; background:var(--bg-sunk); overflow:hidden; margin-bottom:5px; }
    .summary-chip .bar i { display:block; height:100%; background:var(--good); }
    .summary-chip .nums { font-size:10.5px; color:var(--ink3); font-family:var(--font-display); }

    .board { display:grid; grid-template-columns:repeat(3,1fr); gap:16px; }
    @media (max-width:820px) { .board { grid-template-columns:1fr; } }
    .col { background:var(--bg-sunk); border-radius:var(--r); padding:12px; min-height:120px; }
    .col h3 { font-family:var(--font-display); font-size:12px; text-transform:uppercase; letter-spacing:.7px; color:var(--ink3); margin:4px 6px 10px;
        display:flex; justify-content:space-between; font-weight:700; }
    .task-card { background:var(--bg-elev); border:1px solid var(--line); border-radius:var(--r-sm); padding:12px 13px;
        margin-bottom:10px; cursor:pointer; transition:transform .2s var(--ease), box-shadow .2s, border-color .2s; }
    .task-card:hover { transform:translateY(-2px); box-shadow:var(--shadow); border-color:var(--accent); }
    .task-title { font-size:14px; font-weight:700; margin-bottom:7px; line-height:1.35; }
    .task-labels { display:flex; flex-wrap:wrap; gap:4px; margin-bottom:7px; }
    .label-dot-chip { font-size:10px; font-weight:700; padding:2px 8px 2px 7px; border-radius:999px; display:inline-flex; align-items:center; gap:5px; }
    .task-meta { display:flex; flex-wrap:wrap; gap:6px; align-items:center; }
    .pill { font-size:9.5px; font-weight:700; padding:3px 8px; border-radius:6px; text-transform:uppercase; letter-spacing:.4px; font-family:var(--font-display); }
    .pri-critical { background:var(--ink); color:var(--bg); }
    :root[data-theme="dark"] .pri-critical { background:var(--accent); color:var(--on-accent); }
    .pri-high { background:var(--bad-soft); color:var(--bad); }
    .pri-medium { background:var(--accent-soft); color:var(--accent); }
    .pri-low { background:var(--bg-sunk); color:var(--ink3); }
    .mini-stat { font-size:10.5px; color:var(--ink3); font-family:var(--font-display); font-weight:600; display:inline-flex; align-items:center; gap:3px; }
    .mini-stat.blocked { color:var(--bad); }
    .mini-stat.checked { color:var(--good); }
    .due { font-size:10.5px; color:var(--ink3); font-family:var(--font-display); font-weight:600; }
    .due.overdue { color:var(--bad); font-weight:700; }
    .empty-col { text-align:center; color:var(--ink3); font-size:12px; padding:20px 6px; }

    /* ── Task modal detail sections ── */
    .tsec { margin-top:18px; padding-top:16px; border-top:1px solid var(--line); }
    .tsec-head { display:flex; justify-content:space-between; align-items:center; margin-bottom:10px; }
    .tsec-head label { margin:0; }
    .tsec-head .link-btn { font-family:var(--font-display); font-size:11px; font-weight:700; color:var(--accent); background:none; border:none; cursor:pointer; padding:2px; }
    .tsec-locked { font-size:12px; color:var(--ink3); background:var(--bg-sunk); border:1px dashed var(--line2); border-radius:var(--r-sm); padding:12px; text-align:center; }

    .label-chip-row { display:flex; flex-wrap:wrap; gap:7px; }
    .label-chip { font-family:var(--font-display); font-size:11.5px; font-weight:700; padding:6px 12px; border-radius:999px;
        border:1.5px solid transparent; cursor:pointer; transition:all .15s var(--ease); display:inline-flex; align-items:center; gap:6px; opacity:.55; }
    .label-chip.on { opacity:1; box-shadow:0 4px 12px -6px rgba(0,0,0,.3); }
    .label-chip .dot { width:8px; height:8px; border-radius:50%; }
    .label-swatches { display:flex; gap:7px; flex-wrap:wrap; margin:8px 0; }
    .swatch { width:24px; height:24px; border-radius:50%; cursor:pointer; border:2px solid transparent; }
    .swatch.on { border-color:var(--ink); transform:scale(1.15); }
    .manage-labels-list { display:flex; flex-direction:column; gap:6px; max-height:220px; overflow-y:auto; }
    .manage-labels-row { display:flex; align-items:center; gap:8px; padding:7px 9px; border-radius:9px; background:var(--bg-sunk); }
    .manage-labels-row .lbl-name { flex:1; font-size:13px; font-weight:600; }
    .manage-labels-row button { border:none; background:none; color:var(--ink3); cursor:pointer; font-size:13px; }
    .manage-labels-row button:hover { color:var(--bad); }

    .subtask-progress { height:6px; border-radius:4px; background:var(--bg-sunk); overflow:hidden; margin-bottom:10px; }
    .subtask-progress i { display:block; height:100%; background:var(--good); transition:width .25s var(--ease); }
    .subtask-row { display:flex; align-items:center; gap:9px; padding:7px 2px; border-bottom:1px solid var(--line); }
    .subtask-row:last-child { border-bottom:none; }
    .subtask-row input[type=checkbox] { width:17px; height:17px; accent-color:var(--accent); flex-shrink:0; cursor:pointer; }
    .subtask-row span { flex:1; font-size:13.5px; }
    .subtask-row.done span { text-decoration:line-through; color:var(--ink3); }
    .subtask-row button { border:none; background:none; color:var(--ink4); cursor:pointer; font-size:13px; opacity:0; transition:opacity .15s; }
    .subtask-row:hover button { opacity:1; }
    .subtask-row button:hover { color:var(--bad); }
    .add-inline { display:flex; gap:7px; margin-top:8px; }
    .add-inline input { flex:1; }
    .add-inline button { flex-shrink:0; }

    .dep-chip { display:flex; align-items:center; gap:8px; padding:8px 10px; border-radius:10px; background:var(--bg-sunk); margin-bottom:7px; font-size:13px; }
    .dep-chip .status-dot { width:8px; height:8px; border-radius:50%; flex-shrink:0; }
    .dep-chip .status-dot.todo { background:var(--ink4); }
    .dep-chip .status-dot.in_progress { background:var(--warn); }
    .dep-chip .status-dot.done { background:var(--good); }
    .dep-chip span.t { flex:1; }
    .dep-chip.blocked-warn { background:var(--bad-soft); }
    .dep-chip button { border:none; background:none; color:var(--ink3); cursor:pointer; font-size:12.5px; }
    .dep-chip button:hover { color:var(--bad); }
    .blocks-note { font-size:11.5px; color:var(--ink3); margin-top:4px; }

    .attach-row { display:flex; align-items:center; gap:10px; padding:8px 9px; border-radius:10px; background:var(--bg-sunk); margin-bottom:7px; }
    .attach-row .ic { font-size:17px; flex-shrink:0; }
    .attach-row .info { flex:1; min-width:0; }
    .attach-row a.fname { font-size:13px; font-weight:600; text-decoration:none; color:var(--ink); display:block; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .attach-row a.fname:hover { color:var(--accent); }
    .attach-row .fsize { font-size:10.5px; color:var(--ink3); }
    .attach-row button { border:none; background:none; color:var(--ink3); cursor:pointer; font-size:13px; flex-shrink:0; }
    .attach-row button:hover { color:var(--bad); }
    .upload-zone { border:1.5px dashed var(--line2); border-radius:var(--r-sm); padding:16px; text-align:center; cursor:pointer;
        transition:border-color .2s; color:var(--ink3); font-size:12.5px; }
    .upload-zone:hover { border-color:var(--accent); color:var(--accent); }

    .comments { margin-top:0; }
    .comment { font-size:12.5px; margin-bottom:8px; padding:9px 11px; background:var(--bg-sunk); border-radius:9px; }
    .comment b { font-family:var(--font-display); font-size:11.5px; }

    /* ── View toggle (Board / List) ── */
    .view-toggle { display:flex; gap:2px; padding:3px; background:var(--bg-elev); border:1px solid var(--line); border-radius:10px; }
    .vt-btn { border:none; background:transparent; color:var(--ink3); font-family:var(--font-display); font-size:12.5px; font-weight:600;
        padding:6px 12px; border-radius:7px; cursor:pointer; transition:background .15s var(--ease), color .15s var(--ease); }
    .vt-btn:hover { color:var(--ink); }
    .vt-btn.active { background:var(--accent); color:var(--on-accent); }

    /* ── List / table view ── */
    .list-toolbar { display:flex; gap:8px; flex-wrap:wrap; align-items:center; margin-bottom:14px; }
    .list-toolbar input[type="text"] { flex:1; min-width:160px; }
    .list-toolbar select { min-width:120px; }
    .list-table-wrap { background:var(--bg-elev); border:1px solid var(--line); border-radius:var(--r); overflow:hidden; overflow-x:auto; }
    table.list-table { width:100%; border-collapse:collapse; font-size:13px; min-width:760px; }
    table.list-table th { text-align:left; font-family:var(--font-display); font-size:10.5px; text-transform:uppercase; letter-spacing:.5px;
        color:var(--ink3); font-weight:700; padding:11px 14px; background:var(--bg-sunk); border-bottom:1px solid var(--line);
        cursor:pointer; user-select:none; white-space:nowrap; }
    table.list-table th:hover { color:var(--ink); }
    table.list-table th .sort-arrow { opacity:.4; margin-left:3px; font-size:9px; }
    table.list-table th.sorted .sort-arrow { opacity:1; color:var(--accent); }
    table.list-table td { padding:10px 14px; border-bottom:1px solid var(--line); vertical-align:middle; }
    table.list-table tbody tr { cursor:pointer; transition:background .12s var(--ease); }
    table.list-table tbody tr:hover { background:var(--bg-sunk); }
    table.list-table tbody tr:last-child td { border-bottom:none; }
    .lt-title { font-weight:700; display:flex; align-items:center; gap:7px; }
    .lt-status-dot { width:8px; height:8px; border-radius:50%; flex-shrink:0; }
    .lt-status-dot.todo { background:var(--ink4); }
    .lt-status-dot.in_progress { background:var(--warn); }
    .lt-status-dot.done { background:var(--good); }
    .lt-assignee { display:flex; align-items:center; gap:7px; font-size:12.5px; color:var(--ink2); }
    .lt-empty { text-align:center; color:var(--ink3); font-size:13px; padding:40px 20px; }
    .lt-labels { display:flex; flex-wrap:wrap; gap:4px; }
    .list-count { font-size:11.5px; color:var(--ink3); font-family:var(--font-display); margin-bottom:10px; }
</style>
</head>
<body>
<div class="aurora"><span class="a1"></span><span class="a2"></span></div>
<div class="wrap">
    <?php pro_header($user, 'teams'); ?>

    <div class="topbar">
        <div>
            <div class="crumb"><a href="team.php?id=<?= (int)$teamId ?>">← Back to team</a></div>
            <h1 class="page-title" id="proj-title">Loading…</h1>
            <div class="sub" id="proj-desc" style="margin-bottom:0"></div>
        </div>
        <div class="actions">
            <div class="view-toggle" id="view-toggle">
                <button class="vt-btn active" id="vt-board" onclick="switchProjectView('board')">▥ Board</button>
                <button class="vt-btn" id="vt-list" onclick="switchProjectView('list')">☰ List</button>
            </div>
            <button class="btn ghost" onclick="openManageLabels()">🏷 Labels</button>
            <button class="btn" onclick="openCreateTask()">+ Add task</button>
        </div>
    </div>

    <div class="summary-strip" id="summary-strip"></div>

    <div class="board" id="board-view">
        <div class="col"><h3>To do <span id="cnt-todo">0</span></h3><div id="col-todo"></div></div>
        <div class="col"><h3>In progress <span id="cnt-in_progress">0</span></h3><div id="col-in_progress"></div></div>
        <div class="col"><h3>Done <span id="cnt-done">0</span></h3><div id="col-done"></div></div>
    </div>

    <div id="list-view" style="display:none">
        <div class="list-toolbar">
            <input type="text" id="lt-search" placeholder="Search tasks…" oninput="renderListView()" />
            <select id="lt-filter-status" onchange="renderListView()">
                <option value="">All statuses</option>
                <option value="todo">To do</option>
                <option value="in_progress">In progress</option>
                <option value="done">Done</option>
            </select>
            <select id="lt-filter-assignee" onchange="renderListView()">
                <option value="">Everyone</option>
                <option value="unassigned">Unassigned</option>
            </select>
            <select id="lt-filter-priority" onchange="renderListView()">
                <option value="">All priorities</option>
                <option value="critical">Critical</option>
                <option value="high">High</option>
                <option value="medium">Medium</option>
                <option value="low">Low</option>
            </select>
        </div>
        <div class="list-count" id="lt-count"></div>
        <div class="list-table-wrap">
            <table class="list-table">
                <thead>
                    <tr>
                        <th data-sort="title" onclick="setListSort('title')">Task <span class="sort-arrow">▲</span></th>
                        <th data-sort="status" onclick="setListSort('status')">Status <span class="sort-arrow">▲</span></th>
                        <th data-sort="priority" onclick="setListSort('priority')">Priority <span class="sort-arrow">▲</span></th>
                        <th data-sort="assignee_name" onclick="setListSort('assignee_name')">Assignee <span class="sort-arrow">▲</span></th>
                        <th data-sort="due_date" onclick="setListSort('due_date')">Due <span class="sort-arrow">▲</span></th>
                        <th>Labels</th>
                    </tr>
                </thead>
                <tbody id="lt-body"></tbody>
            </table>
        </div>
    </div>
</div>

<!-- Create/Edit task modal -->
<div class="modal-overlay" id="task-overlay" onclick="if(event.target===this)closeTaskModal()">
    <div class="modal">
        <h2 id="task-modal-title">New task</h2>
        <input type="hidden" id="t-id" />
        <div class="fg"><label>Title</label><input type="text" id="t-title" placeholder="What needs to be done?" /></div>
        <div class="fg"><label>Description</label><textarea id="t-desc" placeholder="Details, links, context…"></textarea>
            <button type="button" id="ai-suggest-btn" onclick="aiSuggestProjectTask()"
                style="margin-top:8px;font-size:12px;padding:7px 12px;border-radius:8px;border:1px solid var(--line,#ddd);background:var(--card,#fff);cursor:pointer">
                🤖 AI Suggest</button>
            <div id="ai-suggest-status" style="font-size:11px;color:var(--ink4,#888);margin-top:6px;display:none"></div>
        </div>
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

        <!-- Labels -->
        <div class="tsec">
            <div class="tsec-head"><label style="margin:0">Labels</label></div>
            <div id="labels-locked" class="tsec-locked" style="display:none">Save the task to add labels</div>
            <div id="labels-picker" class="label-chip-row" style="display:none"></div>
        </div>

        <!-- Subtasks -->
        <div class="tsec">
            <div class="tsec-head"><label style="margin:0">Subtasks</label><span class="mini-stat" id="subtask-count"></span></div>
            <div id="subtasks-locked" class="tsec-locked" style="display:none">Save the task to add subtasks</div>
            <div id="subtasks-body" style="display:none">
                <div class="subtask-progress"><i id="subtask-bar" style="width:0%"></i></div>
                <div id="subtasks-list"></div>
                <div class="add-inline">
                    <input type="text" id="subtask-input" placeholder="Add a subtask and press Enter…" onkeydown="if(event.key==='Enter')addSubtask()" />
                    <button class="btn sm" onclick="addSubtask()">Add</button>
                </div>
            </div>
        </div>

        <!-- Dependencies -->
        <div class="tsec">
            <div class="tsec-head"><label style="margin:0">Blocked by</label></div>
            <div id="deps-locked" class="tsec-locked" style="display:none">Save the task to link dependencies</div>
            <div id="deps-body" style="display:none">
                <div id="deps-list"></div>
                <div class="add-inline">
                    <select id="dep-select" style="flex:1"></select>
                    <button class="btn sm" onclick="addDependency()">Link</button>
                </div>
                <div class="blocks-note" id="blocks-note"></div>
            </div>
        </div>

        <!-- Attachments -->
        <div class="tsec">
            <div class="tsec-head"><label style="margin:0">Attachments</label></div>
            <div id="attach-locked" class="tsec-locked" style="display:none">Save the task to attach files</div>
            <div id="attach-body" style="display:none">
                <div id="attach-list"></div>
                <div class="upload-zone" onclick="document.getElementById('attach-input').click()">📎 Click to upload a file (max 10MB — images, PDF, or .txt)</div>
                <input type="file" id="attach-input" style="display:none" onchange="uploadAttachment(this.files[0])" />
            </div>
        </div>

        <!-- Comments -->
        <div class="tsec comments" id="comments-section" style="display:none">
            <div class="tsec-head"><label style="margin:0">Comments</label></div>
            <div id="comments-list"></div>
            <div class="add-inline"><input type="text" id="comment-input" placeholder="Write a comment and press Enter…" onkeydown="if(event.key==='Enter')submitComment()" /></div>
        </div>
    </div>
</div>

<!-- Manage labels modal -->
<div class="modal-overlay" id="ml-overlay" onclick="if(event.target===this)closeManageLabels()">
    <div class="modal" style="width:min(400px,94vw)">
        <h2>Project labels</h2>
        <div class="manage-labels-list" id="ml-list"></div>
        <div class="fg" style="margin-top:16px">
            <label>New label</label>
            <input type="text" id="ml-name" placeholder="e.g. Bug, Design, Blocked" maxlength="40" />
            <div class="label-swatches" id="ml-swatches"></div>
        </div>
        <div class="modal-actions">
            <button class="btn ghost" onclick="closeManageLabels()">Close</button>
            <button class="btn" onclick="createLabelFromManager()">Create label</button>
        </div>
    </div>
</div>

<script src="js/api-client.js?v=3"></script>
<script>
const PROJECT_ID = <?= (int)$projectId ?>;
const TEAM_ID = <?= (int)$teamId ?>;
const MY_ROLE = '<?= htmlspecialchars($role) ?>';
const IS_MANAGER = <?= $isManager ? 'true' : 'false' ?>;
const MY_USER_ID = <?= (int)current_user_id() ?>;
const LABEL_PALETTE = ['#4f46e5','#dc2626','#d97706','#16a34a','#0891b2','#7c3aed','#db2777','#78716c'];
let members = [];
let projectLabels = [];
let tasksCache = [];
let currentTaskId = null;
let newTaskLabelSelection = []; // labels picked before a NEW task has been saved yet (applied right after creation)

function esc(s) { return (s || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;'); }

function initials(name) {
    return (name || '?').split(' ').map(w => w[0]).join('').slice(0,2).toUpperCase();
}

async function loadProject() {
    try {
        const { project } = await Taskvel.request(`/api/projects.php?action=get&id=${PROJECT_ID}`);
        document.getElementById('proj-title').textContent = project.name;
        document.getElementById('proj-desc').textContent = project.description || '';
        document.title = project.name + ' · Taskvel Pro';
    } catch (e) {}
    const { members: m } = await Taskvel.request(`/api/teams.php?action=members&team_id=${TEAM_ID}`);
    members = m;
    document.getElementById('t-assignee').innerHTML = '<option value="">Unassigned</option>' +
        members.map(mm => `<option value="${mm.id}">${esc(mm.name)}</option>`).join('');
    document.getElementById('lt-filter-assignee').innerHTML = '<option value="">Everyone</option><option value="unassigned">Unassigned</option>' +
        members.map(mm => `<option value="${mm.id}">${esc(mm.name)}</option>`).join('');
    await Promise.all([loadTasks(), loadSummary(), loadProjectLabels()]);
    initListView();
}

// ═══════════════════════ VIEW SWITCH (Board / List) ═══════════════════════
let currentProjectView = localStorage.getItem('tv_project_view') || 'board';
let listSortKey = 'due_date';
let listSortDir = 'asc';

function switchProjectView(v) {
    currentProjectView = v;
    localStorage.setItem('tv_project_view', v);
    document.getElementById('board-view').style.display = v === 'board' ? 'grid' : 'none';
    document.getElementById('list-view').style.display = v === 'list' ? 'block' : 'none';
    document.getElementById('vt-board').classList.toggle('active', v === 'board');
    document.getElementById('vt-list').classList.toggle('active', v === 'list');
    if (v === 'list') renderListView();
}

function initListView() {
    switchProjectView(currentProjectView);
}

function setListSort(key) {
    if (listSortKey === key) { listSortDir = listSortDir === 'asc' ? 'desc' : 'asc'; }
    else { listSortKey = key; listSortDir = 'asc'; }
    renderListView();
}

const PRIORITY_RANK = { critical: 0, high: 1, medium: 2, low: 3 };
const STATUS_RANK = { todo: 0, in_progress: 1, done: 2 };

function renderListView() {
    const q = (document.getElementById('lt-search').value || '').trim().toLowerCase();
    const fStatus = document.getElementById('lt-filter-status').value;
    const fAssignee = document.getElementById('lt-filter-assignee').value;
    const fPriority = document.getElementById('lt-filter-priority').value;

    let rows = tasksCache.filter(t => {
        if (q && !t.title.toLowerCase().includes(q) && !(t.description || '').toLowerCase().includes(q)) return false;
        if (fStatus && t.status !== fStatus) return false;
        if (fPriority && t.priority !== fPriority) return false;
        if (fAssignee === 'unassigned' && t.assignee_id) return false;
        if (fAssignee && fAssignee !== 'unassigned' && String(t.assignee_id) !== fAssignee) return false;
        return true;
    });

    rows.sort((a, b) => {
        let av, bv;
        if (listSortKey === 'priority') { av = PRIORITY_RANK[a.priority]; bv = PRIORITY_RANK[b.priority]; }
        else if (listSortKey === 'status') { av = STATUS_RANK[a.status]; bv = STATUS_RANK[b.status]; }
        else if (listSortKey === 'due_date') { av = a.due_date || '9999-99-99'; bv = b.due_date || '9999-99-99'; }
        else if (listSortKey === 'assignee_name') { av = (a.assignee_name || '\uffff').toLowerCase(); bv = (b.assignee_name || '\uffff').toLowerCase(); }
        else { av = (a.title || '').toLowerCase(); bv = (b.title || '').toLowerCase(); }
        if (av < bv) return listSortDir === 'asc' ? -1 : 1;
        if (av > bv) return listSortDir === 'asc' ? 1 : -1;
        return 0;
    });

    document.querySelectorAll('.list-table th[data-sort]').forEach(th => {
        th.classList.toggle('sorted', th.dataset.sort === listSortKey);
        th.querySelector('.sort-arrow').textContent = (th.dataset.sort === listSortKey && listSortDir === 'desc') ? '▼' : '▲';
    });

    const total = tasksCache.length;
    document.getElementById('lt-count').textContent = rows.length === total
        ? `${total} task${total === 1 ? '' : 's'}` : `${rows.length} of ${total} tasks`;

    const body = document.getElementById('lt-body');
    if (!rows.length) {
        body.innerHTML = `<tr><td colspan="6"><div class="lt-empty">${total ? 'No tasks match your filters.' : 'No tasks yet — add one to get started.'}</div></td></tr>`;
        return;
    }
    const today = new Date(); today.setHours(0,0,0,0);
    const statusLabel = { todo: 'To do', in_progress: 'In progress', done: 'Done' };
    body.innerHTML = rows.map(t => {
        const due = t.due_date ? new Date(t.due_date) : null;
        const overdue = due && due < today && t.status !== 'done';
        const labels = (t.labels || []).map(l => `<span class="label-dot-chip" style="${labelChipStyle(l.color)}">${esc(l.name)}</span>`).join('');
        return `<tr onclick="openEditTask(${t.id})">
            <td><div class="lt-title"><span class="lt-status-dot ${t.status}"></span>${esc(t.title)}${t.blocked_by_open_count > 0 ? ' <span class="mini-stat blocked" title="Blocked">🔒</span>' : ''}</div></td>
            <td>${statusLabel[t.status]}</td>
            <td><span class="pill pri-${t.priority}">${t.priority}</span></td>
            <td>${t.assignee_name ? `<span class="lt-assignee"><span class="avatar" title="${esc(t.assignee_name)}">${initials(t.assignee_name)}</span>${esc(t.assignee_name)}</span>` : '<span style="color:var(--ink3)">—</span>'}</td>
            <td><span class="due ${overdue ? 'overdue' : ''}">${t.due_date || '—'}</span></td>
            <td><div class="lt-labels">${labels || ''}</div></td>
        </tr>`;
    }).join('');
}

async function loadProjectLabels() {
    const { labels } = await Taskvel.request(`/api/project_tasks.php?action=labels&project_id=${PROJECT_ID}`);
    projectLabels = labels;
}

function labelChipStyle(color) {
    return `background:${color}22;color:${color};border-color:${color}55`;
}

async function loadTasks() {
    const { tasks } = await Taskvel.request(`/api/project_tasks.php?action=list&project_id=${PROJECT_ID}`);
    tasksCache = tasks;
    const cols = { todo: [], in_progress: [], done: [] };
    tasks.forEach(t => cols[t.status].push(t));
    ['todo', 'in_progress', 'done'].forEach(status => {
        document.getElementById('cnt-' + status).textContent = cols[status].length;
        const el = document.getElementById('col-' + status);
        if (!cols[status].length) { el.innerHTML = `<div class="empty-col">Nothing here</div>`; return; }
        el.innerHTML = cols[status].map(t => cardHTML(t)).join('');
    });
    if (currentProjectView === 'list') renderListView();
}

function cardHTML(t) {
    const today = new Date(); today.setHours(0,0,0,0);
    const due = t.due_date ? new Date(t.due_date) : null;
    const overdue = due && due < today && t.status !== 'done';
    const labels = (t.labels || []).map(l => `<span class="label-dot-chip" style="${labelChipStyle(l.color)}">${esc(l.name)}</span>`).join('');
    const subtaskStat = t.subtask_total > 0
        ? `<span class="mini-stat ${t.subtask_done == t.subtask_total ? 'checked' : ''}">☑ ${t.subtask_done}/${t.subtask_total}</span>` : '';
    const attachStat = t.attachment_count > 0 ? `<span class="mini-stat">📎 ${t.attachment_count}</span>` : '';
    const blockedStat = t.blocked_by_open_count > 0 ? `<span class="mini-stat blocked">🔒 Blocked</span>` : '';
    return `<div class="task-card" onclick="openEditTask(${t.id})">
        ${labels ? `<div class="task-labels">${labels}</div>` : ''}
        <div class="task-title">${esc(t.title)}</div>
        <div class="task-meta">
            <span class="pill pri-${t.priority}">${t.priority}</span>
            ${t.assignee_name ? `<span class="avatar" title="${esc(t.assignee_name)}">${initials(t.assignee_name)}</span>` : ''}
            ${t.due_date ? `<span class="due ${overdue ? 'overdue' : ''}">${t.due_date}</span>` : ''}
            ${subtaskStat}${attachStat}${blockedStat}
        </div>
    </div>`;
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
    newTaskLabelSelection = [];
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
    lockDetailSections(true);
    document.getElementById('task-overlay').classList.add('open');
    document.getElementById('t-title').focus();
}

function lockDetailSections(locked) {
    document.getElementById('labels-locked').style.display = locked ? 'block' : 'none';
    document.getElementById('labels-picker').style.display = locked ? 'none' : 'flex';
    document.getElementById('subtasks-locked').style.display = locked ? 'block' : 'none';
    document.getElementById('subtasks-body').style.display = locked ? 'none' : 'block';
    document.getElementById('deps-locked').style.display = locked ? 'block' : 'none';
    document.getElementById('deps-body').style.display = locked ? 'none' : 'block';
    document.getElementById('attach-locked').style.display = locked ? 'block' : 'none';
    document.getElementById('attach-body').style.display = locked ? 'none' : 'block';
    if (locked) {
        // A brand-new (unsaved) task can still preview label selection —
        // it's applied automatically the moment the task is created.
        document.getElementById('labels-locked').style.display = 'none';
        document.getElementById('labels-picker').style.display = 'flex';
        renderLabelPicker([]);
    }
}

async function openEditTask(id) {
    currentTaskId = id;
    const t = tasksCache.find(x => x.id === id) || (await Taskvel.request(`/api/project_tasks.php?action=list&project_id=${PROJECT_ID}`)).tasks.find(x => x.id === id);
    if (!t) return;
    const isMine = t.assignee_id == MY_USER_ID;
    const canEditFully = IS_MANAGER;
    const canEditSome = canEditFully || isMine || t.created_by == MY_USER_ID;
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
    lockDetailSections(false);
    renderLabelPicker(t.labels || []);
    await Promise.all([loadComments(id), loadSubtasks(id), loadDependencies(id), loadAttachments(id)]);
    document.getElementById('task-overlay').classList.add('open');
}

function closeTaskModal() { document.getElementById('task-overlay').classList.remove('open'); }

async function aiSuggestProjectTask() {
    const title = document.getElementById('t-title').value.trim();
    const statusEl = document.getElementById('ai-suggest-status');
    const btn = document.getElementById('ai-suggest-btn');
    if (!title) {
        alert('Enter a task title first');
        return;
    }
    btn.disabled = true;
    btn.textContent = '🤖 Thinking…';
    statusEl.style.display = 'block';
    statusEl.textContent = 'Asking AI for suggestions…';
    try {
        const { suggestion } = await Taskvel.request('/api/ai_suggest.php?action=suggest', {
            method: 'POST',
            body: { name: title, note: document.getElementById('t-desc').value.trim() }
        });

        if (suggestion.urgency) document.getElementById('t-priority').value = suggestion.urgency;
        if (suggestion.deadline) document.getElementById('t-due').value = suggestion.deadline;

        if ((suggestion.steps || []).length) {
            const descEl = document.getElementById('t-desc');
            const extra = suggestion.steps.map(s => `- ${s}`).join('\n');
            descEl.value = descEl.value ? `${descEl.value}\n\nSuggested subtasks:\n${extra}` : `Suggested subtasks:\n${extra}`;
        }

        statusEl.textContent = '✨ Priority, due date & subtask ideas applied — tweak anything you like.';
    } catch (e) {
        statusEl.textContent = "Couldn't get AI suggestions: " + e.message;
    } finally {
        btn.disabled = false;
        btn.textContent = '🤖 AI Suggest';
        setTimeout(() => { statusEl.style.display = 'none'; }, 5000);
    }
}

async function saveTask(force) {
    const title = document.getElementById('t-title').value.trim();
    if (!currentTaskId && !title) { alert('Enter a task title'); return; }
    const btn = document.getElementById('t-save-btn');
    if (btn.disabled) return;
    btn.disabled = true;
    const orig = btn.textContent;
    btn.textContent = 'Saving…';
    const payload = {
        title,
        description: document.getElementById('t-desc').value.trim(),
        priority: document.getElementById('t-priority').value,
        due_date: document.getElementById('t-due').value || null,
        assignee_id: document.getElementById('t-assignee').value || null,
        status: document.getElementById('t-status').value,
    };
    if (force) payload.force = true;
    try {
        if (currentTaskId) {
            payload.id = currentTaskId;
            await Taskvel.request('/api/project_tasks.php?action=update', { method:'POST', body: payload });
            closeTaskModal();
        } else {
            payload.project_id = PROJECT_ID;
            const { task_id } = await Taskvel.request('/api/project_tasks.php?action=create', { method:'POST', body: payload });
            // Apply any labels picked before the task existed, then flip the
            // modal into edit mode so subtasks/attachments/dependencies can
            // be added right away without a second click.
            for (const labelId of newTaskLabelSelection) {
                await Taskvel.request('/api/project_tasks.php?action=task-label-toggle', { method:'POST', body: { task_id, label_id: labelId, on: true } });
            }
            toast('Task created — add subtasks, files, or links below');
            await loadTasks(); await loadSummary();
            await openEditTask(task_id);
            btn.disabled = false; btn.textContent = orig;
            return;
        }
        loadTasks(); loadSummary();
    } catch (e) {
        if (e.message && e.message.indexOf('blocked by') !== -1) {
            if (confirm(e.message + '\n\nMark it done anyway?')) { btn.disabled = false; btn.textContent = orig; return saveTask(true); }
        } else {
            alert(e.message || 'Could not save task');
        }
    } finally {
        btn.disabled = false;
        btn.textContent = orig;
    }
}

async function deleteTask() {
    if (!currentTaskId || !confirm('Delete this task?')) return;
    try {
        await Taskvel.request(`/api/project_tasks.php?action=delete&id=${currentTaskId}`, { method:'DELETE' });
        closeTaskModal();
        loadTasks(); loadSummary();
    } catch (e) { alert(e.message); }
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
    } catch (e) { alert(e.message); }
}

// ═══════════════════════ LABELS ═══════════════════════
function renderLabelPicker(activeLabels) {
    const activeIds = (activeLabels || []).map(l => l.id);
    const picker = document.getElementById('labels-picker');
    if (!projectLabels.length) {
        picker.innerHTML = `<span style="font-size:12px;color:var(--ink3)">No labels yet — click "🏷 Labels" above to create one.</span>`;
        return;
    }
    picker.innerHTML = projectLabels.map(l => {
        const on = currentTaskId ? activeIds.includes(l.id) : newTaskLabelSelection.includes(l.id);
        return `<span class="label-chip ${on ? 'on' : ''}" style="border-color:${l.color};${on ? `background:${l.color};color:#fff` : `color:${l.color}`}"
                    onclick="toggleTaskLabel(${l.id})"><span class="dot" style="background:${on ? '#fff' : l.color}"></span>${esc(l.name)}</span>`;
    }).join('');
}

async function toggleTaskLabel(labelId) {
    if (!currentTaskId) {
        // Not saved yet — just track the selection locally.
        const i = newTaskLabelSelection.indexOf(labelId);
        if (i === -1) newTaskLabelSelection.push(labelId); else newTaskLabelSelection.splice(i, 1);
        renderLabelPicker([]);
        return;
    }
    const t = tasksCache.find(x => x.id === currentTaskId);
    const isOn = t && (t.labels || []).some(l => l.id === labelId);
    try {
        await Taskvel.request('/api/project_tasks.php?action=task-label-toggle', { method:'POST', body: { task_id: currentTaskId, label_id: labelId, on: !isOn } });
        await loadTasks();
        const updated = tasksCache.find(x => x.id === currentTaskId);
        renderLabelPicker(updated ? updated.labels : []);
    } catch (e) { alert(e.message); }
}

function buildSwatches(containerId, selected) {
    const c = document.getElementById(containerId);
    c.innerHTML = LABEL_PALETTE.map(color =>
        `<span class="swatch ${color === selected ? 'on' : ''}" style="background:${color}" data-color="${color}" onclick="pickSwatch(this)"></span>`
    ).join('');
}
function pickSwatch(el) {
    el.parentElement.querySelectorAll('.swatch').forEach(s => s.classList.remove('on'));
    el.classList.add('on');
}
function selectedSwatch(containerId) {
    const on = document.querySelector(`#${containerId} .swatch.on`);
    return on ? on.dataset.color : LABEL_PALETTE[0];
}

async function openManageLabels() {
    await loadProjectLabels();
    renderManageLabelsList();
    buildSwatches('ml-swatches', LABEL_PALETTE[0]);
    document.getElementById('ml-name').value = '';
    document.getElementById('ml-overlay').classList.add('open');
}
function closeManageLabels() { document.getElementById('ml-overlay').classList.remove('open'); }

function renderManageLabelsList() {
    const list = document.getElementById('ml-list');
    list.innerHTML = projectLabels.length ? projectLabels.map(l => `
        <div class="manage-labels-row">
            <span class="dot" style="width:10px;height:10px;border-radius:50%;background:${l.color};display:inline-block"></span>
            <span class="lbl-name">${esc(l.name)}</span>
            ${IS_MANAGER ? `<button onclick="deleteLabel(${l.id})" title="Delete label">✕</button>` : ''}
        </div>`).join('') : '<div style="font-size:12.5px;color:var(--ink3)">No labels yet.</div>';
}

async function createLabelFromManager() {
    const name = document.getElementById('ml-name').value.trim();
    if (!name) return;
    try {
        await Taskvel.request('/api/project_tasks.php?action=label-create', { method:'POST', body:{ project_id: PROJECT_ID, name, color: selectedSwatch('ml-swatches') } });
        document.getElementById('ml-name').value = '';
        await loadProjectLabels();
        renderManageLabelsList();
        toast('Label created');
    } catch (e) { alert(e.message); }
}

async function deleteLabel(id) {
    if (!confirm('Delete this label? It will be removed from every task.')) return;
    try {
        await Taskvel.request(`/api/project_tasks.php?action=label-delete&id=${id}`, { method:'DELETE' });
        await loadProjectLabels();
        renderManageLabelsList();
        loadTasks();
    } catch (e) { alert(e.message); }
}

// ═══════════════════════ SUBTASKS ═══════════════════════
async function loadSubtasks(taskId) {
    const { subtasks } = await Taskvel.request(`/api/project_tasks.php?action=subtasks&task_id=${taskId}`);
    renderSubtasks(subtasks);
}
function renderSubtasks(subtasks) {
    const total = subtasks.length;
    const done = subtasks.filter(s => s.done).length;
    document.getElementById('subtask-count').textContent = total ? `${done}/${total}` : '';
    document.getElementById('subtask-bar').style.width = total ? Math.round(done / total * 100) + '%' : '0%';
    const list = document.getElementById('subtasks-list');
    list.innerHTML = subtasks.length ? subtasks.map(s => `
        <div class="subtask-row ${s.done ? 'done' : ''}">
            <input type="checkbox" ${s.done ? 'checked' : ''} onchange="toggleSubtask(${s.id})" />
            <span>${esc(s.title)}</span>
            <button onclick="deleteSubtask(${s.id})" title="Remove">✕</button>
        </div>`).join('') : '<div style="font-size:12.5px;color:var(--ink3);padding:6px 2px">No subtasks yet.</div>';
}
async function addSubtask() {
    const inp = document.getElementById('subtask-input');
    const title = inp.value.trim();
    if (!title || !currentTaskId) return;
    try {
        await Taskvel.request('/api/project_tasks.php?action=subtask-add', { method:'POST', body:{ task_id: currentTaskId, title } });
        inp.value = '';
        loadSubtasks(currentTaskId);
        loadTasks();
    } catch (e) { alert(e.message); }
}
async function toggleSubtask(id) {
    try { await Taskvel.request('/api/project_tasks.php?action=subtask-toggle', { method:'POST', body:{ id } }); loadSubtasks(currentTaskId); loadTasks(); }
    catch (e) { alert(e.message); }
}
async function deleteSubtask(id) {
    try { await Taskvel.request(`/api/project_tasks.php?action=subtask-delete&id=${id}`, { method:'DELETE' }); loadSubtasks(currentTaskId); loadTasks(); }
    catch (e) { alert(e.message); }
}

// ═══════════════════════ DEPENDENCIES ═══════════════════════
async function loadDependencies(taskId) {
    const { blocked_by, blocks } = await Taskvel.request(`/api/project_tasks.php?action=dependencies&task_id=${taskId}`);
    renderDependencies(blocked_by, blocks);
    const sel = document.getElementById('dep-select');
    const linkedIds = blocked_by.map(d => d.depends_on_id);
    const options = tasksCache.filter(t => t.id !== taskId && !linkedIds.includes(t.id));
    sel.innerHTML = options.length ? options.map(t => `<option value="${t.id}">${esc(t.title)}</option>`).join('') : '<option value="">No other tasks on this board</option>';
}
function renderDependencies(blockedBy, blocks) {
    const list = document.getElementById('deps-list');
    list.innerHTML = blockedBy.length ? blockedBy.map(d => `
        <div class="dep-chip ${d.status !== 'done' ? 'blocked-warn' : ''}">
            <span class="status-dot ${d.status}"></span>
            <span class="t">${esc(d.title)}</span>
            <button onclick="removeDependency(${d.id})" title="Unlink">✕</button>
        </div>`).join('') : '<div style="font-size:12.5px;color:var(--ink3);padding:4px 2px 8px">Not blocked by anything.</div>';
    document.getElementById('blocks-note').textContent = blocks.length
        ? `Blocking ${blocks.length} other task${blocks.length > 1 ? 's' : ''}: ${blocks.map(b => b.title).join(', ')}` : '';
}
async function addDependency() {
    const sel = document.getElementById('dep-select');
    const dependsOnId = parseInt(sel.value, 10);
    if (!dependsOnId || !currentTaskId) return;
    try {
        await Taskvel.request('/api/project_tasks.php?action=dependency-add', { method:'POST', body:{ task_id: currentTaskId, depends_on_id: dependsOnId } });
        loadDependencies(currentTaskId);
        loadTasks();
    } catch (e) { alert(e.message); }
}
async function removeDependency(id) {
    try { await Taskvel.request(`/api/project_tasks.php?action=dependency-delete&id=${id}`, { method:'DELETE' }); loadDependencies(currentTaskId); loadTasks(); }
    catch (e) { alert(e.message); }
}

// ═══════════════════════ ATTACHMENTS ═══════════════════════
function attachIcon(mime) {
    if (!mime) return '📄';
    if (mime.startsWith('image/')) return '🖼️';
    if (mime === 'application/pdf') return '📕';
    return '📄';
}
function fmtSize(bytes) {
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1024*1024) return Math.round(bytes/1024) + ' KB';
    return (bytes/1024/1024).toFixed(1) + ' MB';
}
async function loadAttachments(taskId) {
    const { attachments } = await Taskvel.attachments.listForProjectTask(taskId);
    renderAttachments(attachments);
}
function renderAttachments(attachments) {
    const list = document.getElementById('attach-list');
    list.innerHTML = attachments.length ? attachments.map(a => `
        <div class="attach-row">
            <span class="ic">${attachIcon(a.mime_type)}</span>
            <div class="info">
                <a class="fname" href="${esc(a.file_path)}" target="_blank" rel="noopener">${esc(a.file_name)}</a>
                <div class="fsize">${fmtSize(a.file_size)} · ${esc(a.uploaded_by_name || '')}</div>
            </div>
            <button onclick="deleteAttachment(${a.id})" title="Delete">✕</button>
        </div>`).join('') : '<div style="font-size:12.5px;color:var(--ink3);padding:4px 2px 8px">No files attached yet.</div>';
}
async function uploadAttachment(file) {
    if (!file || !currentTaskId) return;
    try {
        await Taskvel.attachments.upload(file, { projectTaskId: currentTaskId });
        document.getElementById('attach-input').value = '';
        loadAttachments(currentTaskId);
        loadTasks();
        toast('File uploaded');
    } catch (e) { alert(e.message || 'Upload failed'); }
}
async function deleteAttachment(id) {
    if (!confirm('Delete this file?')) return;
    try { await Taskvel.attachments.remove(id); loadAttachments(currentTaskId); loadTasks(); }
    catch (e) { alert(e.message); }
}

loadProject();
</script>
</body>
</html>
