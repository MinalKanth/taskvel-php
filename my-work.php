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
<?php pro_head('My Work'); ?>
<style>
    

    .mw-intro { margin-bottom:22px; }
    .mw-intro h1 { font-family:var(--font-display); font-size:24px; font-weight:700; letter-spacing:-.4px; margin:0 0 5px; }
    .mw-intro p { color:var(--ink3); font-size:13.5px; margin:0; line-height:1.5; }

    .mw-toolbar { display:flex; gap:10px; flex-wrap:wrap; align-items:center; margin-bottom:22px; }

    .mw-toolbar input[type="text"],
    .mw-toolbar select {
        height:42px;
        border:1px solid var(--line);
        border-radius:12px;
        background:var(--bg-elev);
        color:var(--ink);
        font-family:var(--font-body);
        font-size:13px;
        padding:0 14px;
        outline:none;
        box-shadow:var(--shadow-sm);
        transition:border-color .15s ease, box-shadow .15s ease;
    }
    .mw-toolbar input[type="text"]:hover,
    .mw-toolbar select:hover { border-color:var(--line2); }
    .mw-toolbar input[type="text"]:focus,
    .mw-toolbar select:focus { border-color:var(--accent); box-shadow:0 0 0 3px var(--ring); }
    .mw-toolbar input[type="text"]::placeholder { color:var(--ink3); }

    .mw-toolbar input[type="text"] { flex:1; min-width:160px; }

    .mw-toolbar select {
        min-width:150px;
        font-weight:600;
        appearance:none; -webkit-appearance:none; -moz-appearance:none;
        background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20' fill='none' stroke='%238a8f98' stroke-width='2'%3E%3Cpath d='M5 7.5l5 5 5-5' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E");
        background-repeat:no-repeat;
        background-position:right 12px center;
        background-size:16px;
        padding-right:34px;
    }

    .mw-section { margin-bottom:28px; }
    .mw-section-head { display:flex; align-items:center; gap:9px; margin-bottom:12px; }
    .mw-section-head h2 { font-size:12.5px; font-weight:800; text-transform:uppercase; letter-spacing:.6px; margin:0; }
    .mw-section-head .count { font-size:11px; font-weight:700; color:var(--ink3); font-family:var(--font-display); background:var(--bg-sunk); padding:3px 10px; border-radius:20px; }
    .mw-section-head::before { content:''; width:7px; height:7px; border-radius:50%; background:var(--ink4); }
    .mw-section-head.overdue h2 { color:var(--bad); }
    .mw-section-head.overdue::before { background:var(--bad); }
    .mw-section-head.overdue .count { color:var(--bad); background:var(--bad-soft); }
    .mw-section-head.today h2 { color:var(--accent); }
    .mw-section-head.today::before { background:var(--accent); }
    .mw-section-head.today .count { color:var(--accent); background:var(--accent-soft); }

    .mw-row { display:flex; align-items:center; gap:12px; padding:16px 17px; background:var(--bg-elev); border:1px solid var(--line);
        border-radius:var(--r-lg); margin-bottom:8px; cursor:pointer; position:relative; overflow:hidden;
        box-shadow:var(--shadow-sm);
        transition:border-color .2s ease, box-shadow .25s ease, transform .2s var(--ease); }
    .mw-row::before { content:''; position:absolute; left:0; top:0; bottom:0; width:3px; background:var(--ink4); opacity:.6; transition:width .2s ease, opacity .2s ease; }
    .mw-row[data-source="personal"]::before { background:var(--accent); }
    .mw-row[data-source="project"]::before { background:var(--good); }
    .mw-row[data-source="team"]::before { background:var(--warn); }
    .mw-row:hover { transform:translateY(-2px); box-shadow:var(--shadow); border-color:var(--line2); }
    .mw-row:hover::before { width:5px; opacity:1; }
    .mw-check { width:20px; height:20px; border-radius:50%; border:2px solid var(--line2); flex-shrink:0; cursor:pointer;
        display:flex; align-items:center; justify-content:center; transition:all .15s var(--ease); }
    .mw-check:hover { border-color:var(--good); transform:scale(1.08); }
    .mw-check.done { background:var(--good); border-color:var(--good); color:#fff; font-size:11px; }
    .mw-body { flex:1; min-width:0; }
    .mw-title { font-size:13.5px; font-weight:700; margin-bottom:3px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .mw-meta { display:flex; gap:8px; align-items:center; flex-wrap:wrap; font-size:10.5px; color:var(--ink3); font-family:var(--font-display); font-weight:600; }
    .mw-source { padding:3px 9px; border-radius:7px; font-size:9.5px; font-weight:700; text-transform:uppercase; letter-spacing:.4px; }
    .mw-source.personal { background:var(--accent-soft); color:var(--accent); }
    .mw-source.project { background:var(--good-soft); color:var(--good); }
    .mw-source.team { background:var(--warn-soft); color:var(--warn); }
    .mw-due.overdue { color:var(--bad); font-weight:700; }
    .mw-empty { text-align:center; color:var(--ink3); font-size:13px; padding:30px 20px; background:var(--bg-sunk); border:1px dashed var(--line2); border-radius:var(--r-lg); }
    .mw-loading { text-align:center; color:var(--ink3); font-size:13px; padding:40px 20px; }

    .pill { font-weight:700; padding:3px 9px; border-radius:7px; text-transform:uppercase; letter-spacing:.4px; font-family:var(--font-display); }
    .pri-critical { background:var(--ink); color:var(--bg); }
    :root[data-theme="dark"] .pri-critical { background:var(--accent); color:var(--on-accent); }
    .pri-urgent { background:var(--bad); color:#fff; }
    .pri-high { background:var(--bad-soft); color:var(--bad); }
    .pri-medium { background:var(--accent-soft); color:var(--accent); }
    .pri-low { background:var(--bg-sunk); color:var(--ink3); }
</style>
</head>
<body>
<div class="wrap">
    <?php pro_header($user, 'mywork'); ?>
    <div>
    <div class="mw-intro">
        <h1>My Work</h1>
        <p>Everything assigned to you — personal tasks, project tasks, and team tasks — in one place.</p>
    </div>

    <div class="mw-toolbar">
        <input type="text" id="mw-search" placeholder="Search your work…" oninput="renderWork()" />
        <select id="mw-source" onchange="renderWork()">
            <option value="">All sources</option>
            <option value="personal">Personal</option>
            <option value="project">Projects</option>
            <option value="team">Team tasks</option>
        </select>
        <select id="mw-hide-done" onchange="renderWork()">
            <option value="1">Hide completed</option>
            <option value="0">Show completed</option>
        </select>
    </div>

    <div id="mw-content"><div class="mw-loading">Loading your work…</div></div>
    </div>
    <?php pro_footer($user); ?>
</div>

<script src="js/api-client.js?v=3"></script>
<script>
function esc(s) { return (s || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;'); }

let allWork = [];

async function loadWork() {
    const [personal, project, team] = await Promise.all([
        Taskvel.request('/api/tasks.php?action=list').catch(() => ({ tasks: [] })),
        Taskvel.request('/api/project_tasks.php?action=my-tasks').catch(() => ({ tasks: [] })),
        Taskvel.request('/api/team_tasks.php?action=my-tasks').catch(() => ({ tasks: [] })),
    ]);

    const personalItems = (personal.tasks || []).map(t => ({
        id: t.id, source: 'personal', title: t.title, status: t.status, priority: t.priority,
        due_date: t.due_date, blocked: false,
        contextLabel: 'Personal', contextUrl: 'taskvel-pro.php',
        updatePayload: { status: t.status === 'done' ? 'todo' : 'done' },
        updateUrl: `/api/tasks.php?action=update&id=${t.id}`, updateMethod: 'PUT',
    }));

    const projectItems = (project.tasks || []).map(t => ({
        id: t.id, source: 'project', title: t.title, status: t.status, priority: t.priority,
        due_date: t.due_date, blocked: t.blocked_by_open_count > 0,
        contextLabel: `${t.project_name} · ${t.team_name}`, contextUrl: `project.php?id=${t.project_id}`,
        updatePayload: { id: t.id, status: t.status === 'done' ? 'todo' : 'done' },
        updateUrl: '/api/project_tasks.php?action=update', updateMethod: 'POST',
    }));

    const teamItems = (team.tasks || []).map(t => ({
        id: t.id, source: 'team', title: t.title, status: t.status, priority: t.priority,
        due_date: t.due_date, blocked: false,
        contextLabel: t.team_name, contextUrl: `team.php?id=${t.team_id}`,
        updatePayload: { id: t.id, status: t.status === 'done' ? 'todo' : 'done' },
        updateUrl: '/api/team_tasks.php?action=update', updateMethod: 'POST',
    }));

    allWork = [...personalItems, ...projectItems, ...teamItems];
    renderWork();
}

function bucketFor(item) {
    if (!item.due_date) return 'nodate';
    const today = new Date(); today.setHours(0,0,0,0);
    const due = new Date(item.due_date + 'T00:00:00');
    const diffDays = Math.round((due - today) / 86400000);
    if (diffDays < 0) return 'overdue';
    if (diffDays === 0) return 'today';
    if (diffDays <= 7) return 'upcoming';
    return 'later';
}

function renderWork() {
    const q = (document.getElementById('mw-search').value || '').trim().toLowerCase();
    const fSource = document.getElementById('mw-source').value;
    const hideDone = document.getElementById('mw-hide-done').value === '1';

    let items = allWork.filter(t => {
        if (q && !t.title.toLowerCase().includes(q)) return false;
        if (fSource && t.source !== fSource) return false;
        if (hideDone && t.status === 'done') return false;
        return true;
    });

    const buckets = { overdue: [], today: [], upcoming: [], later: [], nodate: [] };
    items.forEach(t => buckets[bucketFor(t)].push(t));

    const sections = [
        ['overdue', 'Overdue', 'overdue'],
        ['today', 'Due today', 'today'],
        ['upcoming', 'Upcoming (7 days)', ''],
        ['later', 'Later', ''],
        ['nodate', 'No due date', ''],
    ];

    const content = document.getElementById('mw-content');
    if (!items.length) {
        content.innerHTML = `<div class="mw-empty">${allWork.length ? 'Nothing matches your filters.' : "You're all caught up — nothing assigned to you right now."}</div>`;
        return;
    }

    content.innerHTML = sections.map(([key, label, cls]) => {
        const rows = buckets[key];
        if (!rows.length) return '';
        return `<div class="mw-section">
            <div class="mw-section-head ${cls}"><h2>${label}</h2><span class="count">${rows.length}</span></div>
            ${rows.map(rowHTML).join('')}
        </div>`;
    }).join('');
}

function rowHTML(t) {
    const overdue = bucketFor(t) === 'overdue' && t.status !== 'done';
    const sourceLabel = { personal: 'Personal', project: 'Project', team: 'Team' }[t.source];
    return `<div class="mw-row" data-source="${t.source}" onclick="location.href='${esc(t.contextUrl)}'">
        <div class="mw-check ${t.status === 'done' ? 'done' : ''}" onclick="event.stopPropagation(); toggleDone(this, '${t.source}', ${t.id})">${t.status === 'done' ? '✓' : ''}</div>
        <div class="mw-body">
            <div class="mw-title" style="${t.status === 'done' ? 'text-decoration:line-through;color:var(--ink3)' : ''}">${esc(t.title)}${t.blocked ? ' 🔒' : ''}</div>
            <div class="mw-meta">
                <span class="mw-source ${t.source}">${sourceLabel}</span>
                <span>${esc(t.contextLabel)}</span>
                <span class="pill pri-${t.priority}" style="font-size:9px">${t.priority}</span>
                ${t.due_date ? `<span class="mw-due ${overdue ? 'overdue' : ''}">${t.due_date}</span>` : ''}
            </div>
        </div>
    </div>`;
}

async function toggleDone(el, source, id) {
    const item = allWork.find(t => t.source === source && t.id === id);
    if (!item) return;
    const nextStatus = item.status === 'done' ? 'todo' : 'done';
    el.classList.toggle('done');
    el.textContent = nextStatus === 'done' ? '✓' : '';
    try {
        await Taskvel.request(item.updateUrl, { method: item.updateMethod, body: { ...item.updatePayload, status: nextStatus } });
        item.status = nextStatus;
        renderWork();
    } catch (e) {
        el.classList.toggle('done');
        el.textContent = item.status === 'done' ? '✓' : '';
        alert(e.message || 'Could not update task');
    }
}

loadWork();
</script>
</body>
</html>