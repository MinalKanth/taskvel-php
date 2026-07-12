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
<title>Manager Dashboard · Taskvel</title>
<style>
    :root {
        --bg:#f6f6f4; --bg-elev:#fff; --bg-sunk:#ededea; --ink:#0a0a0a; --ink2:#3d3d3b; --ink3:#7c7c78;
        --line:#e6e5e0; --line2:#d4d3cd; --accent:#4f46e5; --accent-soft:rgba(79,70,229,.1); --on-accent:#fff;
        --good:#059669; --good-soft:rgba(5,150,105,.1); --warn:#d97706; --warn-soft:rgba(217,119,6,.1); --bad:#dc2626; --bad-soft:rgba(220,38,38,.1);
        --shadow:0 10px 34px rgba(10,10,10,.08); --r:14px; --ease:cubic-bezier(.22,1,.36,1);
    }
    * { box-sizing:border-box; }
    body { margin:0; font-family:-apple-system,'Segoe UI',Arial,sans-serif; background:var(--bg); color:var(--ink); }
    .wrap { max-width:1080px; margin:0 auto; padding:24px 18px 90px; }
    .topbar { display:flex; justify-content:space-between; align-items:center; margin-bottom:18px; }
    .topbar a.back { color:var(--ink3); text-decoration:none; font-size:13px; font-weight:600; }
    h1 { font-size:22px; font-weight:800; margin:0 0 4px; }
    .sub { color:var(--ink3); font-size:13.5px; margin-bottom:18px; }
    .btn { display:inline-flex; align-items:center; gap:6px; padding:9px 16px; border-radius:10px; border:none;
        background:var(--accent); color:var(--on-accent); font-weight:700; font-size:13px; cursor:pointer; }
    .btn.ghost { background:var(--bg-elev); color:var(--ink); border:1px solid var(--line2); }
    .btn.sm { padding:6px 11px; font-size:11.5px; }
    .btn.good { background:var(--good); } .btn.bad { background:var(--bad); }
    .range-bar { display:flex; gap:8px; margin-bottom:20px; flex-wrap:wrap; align-items:center; }
    .range-bar select, .range-bar input { padding:8px 10px; border:1px solid var(--line2); border-radius:9px; font-size:13px; }

    .grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(160px,1fr)); gap:12px; margin-bottom:24px; }
    .stat-box { background:var(--bg-elev); border:1px solid var(--line); border-radius:var(--r); padding:16px; }
    .stat-box .num { font-size:24px; font-weight:700; font-family:monospace; }
    .stat-box .lbl { font-size:11px; color:var(--ink3); text-transform:uppercase; letter-spacing:.5px; margin-top:4px; }

    section { margin-top:26px; }
    section h2 { font-size:14px; font-weight:700; margin-bottom:12px; display:flex; justify-content:space-between; align-items:center; }

    .emp-card { background:var(--bg-elev); border:1px solid var(--line); border-radius:12px; padding:14px 16px; margin-bottom:10px;
        display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap; }
    .emp-name { font-weight:700; font-size:14px; }
    .emp-stats { display:flex; gap:14px; font-size:12px; color:var(--ink3); }
    .emp-stats b { color:var(--ink); }

    table { width:100%; border-collapse:collapse; background:var(--bg-elev); border-radius:var(--r); overflow:hidden; border:1px solid var(--line); }
    th, td { padding:10px 12px; font-size:13px; text-align:left; border-bottom:1px solid var(--line); }
    th { background:var(--bg-sunk); font-size:10.5px; text-transform:uppercase; color:var(--ink3); letter-spacing:.4px; }
    .pill { font-size:9.5px; font-weight:700; padding:3px 9px; border-radius:20px; text-transform:uppercase; letter-spacing:.4px; }
    .pill-pending { background:var(--bg-sunk); color:var(--ink3); }
    .pill-in_progress { background:var(--warn-soft); color:var(--warn); }
    .pill-pending_approval { background:var(--accent-soft); color:var(--accent); }
    .pill-done { background:var(--good-soft); color:var(--good); }

    .flag-list { list-style:none; margin:0; padding:0; }
    .flag-list li { background:var(--bg-elev); border:1px solid var(--line); border-radius:10px; padding:9px 12px; margin-bottom:6px; font-size:12.5px; }
    .flag-list.warn li { border-left:3px solid var(--warn); }
    .flag-list.bad li { border-left:3px solid var(--bad); }

    .trend-chart { display:flex; align-items:flex-end; gap:8px; height:90px; background:var(--bg-elev); border:1px solid var(--line);
        border-radius:var(--r); padding:14px; }
    .trend-chart .col { flex:1; display:flex; flex-direction:column; align-items:center; gap:5px; height:100%; justify-content:flex-end; }
    .trend-chart .bar { width:100%; background:var(--accent); border-radius:4px 4px 0 0; min-height:2px; }
    .trend-chart .lbl { font-size:9px; color:var(--ink3); }
    .empty { text-align:center; color:var(--ink3); padding:30px; font-size:13px; }
</style>
</head>
<body>
<div class="wrap">
    <div class="topbar">
        <a class="back" href="taskvel-pro.php">← Back to Taskvel</a>
        <span style="font-size:12.5px;color:var(--ink3)"><?= htmlspecialchars($user['email']) ?></span>
    </div>
    <h1>📊 Manager Dashboard</h1>
    <div class="sub">Everything reported to <strong><?= htmlspecialchars($user['email']) ?></strong> — from Daily Check-in task assignments.</div>

    <div class="range-bar">
        <select id="range-select" onchange="onRangeChange()">
            <option value="today">Today</option>
            <option value="week">This week</option>
            <option value="month">This month</option>
            <option value="custom">Custom range…</option>
        </select>
        <input type="date" id="from-input" style="display:none" onchange="load()" />
        <input type="date" id="to-input" style="display:none" onchange="load()" />
        <button class="btn ghost sm" onclick="exportCsv()">⬇ Export CSV</button>
    </div>

    <div class="grid" id="stat-grid"></div>

    <section>
        <h2>Per-person productivity</h2>
        <div id="employee-list"></div>
    </section>

    <section>
        <h2>7-day completion trend</h2>
        <div class="trend-chart" id="trend-chart"></div>
    </section>

    <section>
        <h2>All tasks <span id="task-count"></span></h2>
        <table>
            <thead><tr><th>Date</th><th>Employee</th><th>Task</th><th>Status</th><th>Time</th><th></th></tr></thead>
            <tbody id="task-table-body"></tbody>
        </table>
    </section>

    <section>
        <h2>⏰ Late check-ins</h2>
        <ul class="flag-list warn" id="late-list"></ul>
    </section>
    <section>
        <h2>🚪 Early checkouts</h2>
        <ul class="flag-list warn" id="early-list"></ul>
    </section>
    <section>
        <h2>⏱ Overtime</h2>
        <ul class="flag-list" id="overtime-list"></ul>
    </section>
    <section>
        <h2>⚠️ Overdue (past expected time)</h2>
        <ul class="flag-list bad" id="overdue-list"></ul>
    </section>
</div>

<script src="js/api-client.js"></script>
<script>
function esc(s) { return (s || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
function fmtDuration(seconds) {
    if (!seconds) return '—';
    const h = Math.floor(seconds / 3600), m = Math.floor((seconds % 3600) / 60);
    return h > 0 ? `${h}h ${m}m` : `${m}m`;
}

function onRangeChange() {
    const range = document.getElementById('range-select').value;
    const isCustom = range === 'custom';
    document.getElementById('from-input').style.display = isCustom ? 'inline-block' : 'none';
    document.getElementById('to-input').style.display = isCustom ? 'inline-block' : 'none';
    if (!isCustom) load();
}

function buildQuery() {
    const range = document.getElementById('range-select').value;
    let q = `range=${range}`;
    if (range === 'custom') {
        q += `&from=${document.getElementById('from-input').value}&to=${document.getElementById('to-input').value}`;
    }
    return q;
}

async function load() {
    const data = await Taskvel.request(`/api/manager.php?action=overview&${buildQuery()}`);
    renderStats(data);
    renderEmployees(data.employees);
    renderTrend(data.trend);
    renderTasks(data.tasks);
    renderFlags(data.flags, data.overdue);
}

function renderStats(data) {
    const done = data.tasks.filter(t => t.status === 'done').length;
    const pendingApproval = data.tasks.filter(t => t.status === 'pending_approval').length;
    const inProgress = data.tasks.filter(t => t.status === 'in_progress').length;
    const totalSeconds = data.tasks.reduce((s, t) => s + (parseInt(t.duration_seconds) || 0), 0);
    document.getElementById('stat-grid').innerHTML = `
        <div class="stat-box"><div class="num">${data.employees.length}</div><div class="lbl">People reporting to you</div></div>
        <div class="stat-box"><div class="num" style="color:var(--good)">${done}</div><div class="lbl">Tasks done</div></div>
        <div class="stat-box"><div class="num" style="color:var(--accent)">${pendingApproval}</div><div class="lbl">Awaiting your approval</div></div>
        <div class="stat-box"><div class="num" style="color:var(--warn)">${inProgress}</div><div class="lbl">In progress now</div></div>
        <div class="stat-box"><div class="num">${fmtDuration(totalSeconds)}</div><div class="lbl">Total time logged</div></div>
    `;
}

function renderEmployees(employees) {
    const el = document.getElementById('employee-list');
    if (!employees.length) { el.innerHTML = `<div class="empty">Nobody has reported tasks to you yet in this range.</div>`; return; }
    el.innerHTML = employees.map(e => `
        <div class="emp-card">
            <div class="emp-name">${esc(e.name)}</div>
            <div class="emp-stats">
                <span><b>${e.done}</b> done</span>
                <span><b>${e.pending_approval}</b> awaiting approval</span>
                <span><b>${e.in_progress}</b> in progress</span>
                <span><b>${e.pending}</b> pending</span>
                <span>avg <b>${fmtDuration(e.avg_seconds)}</b>/task</span>
                <span>total <b>${fmtDuration(e.total_seconds)}</b></span>
            </div>
        </div>
    `).join('');
}

function renderTrend(trend) {
    const max = Math.max(1, ...trend.map(d => d.count));
    document.getElementById('trend-chart').innerHTML = trend.map(d => {
        const h = Math.max(2, Math.round((d.count / max) * 62));
        const label = new Date(d.date + 'T00:00:00').toLocaleDateString([], { weekday: 'short' });
        return `<div class="col"><div class="lbl">${d.count}</div><div class="bar" style="height:${h}px"></div><div class="lbl">${label}</div></div>`;
    }).join('');
}

function renderTasks(tasks) {
    document.getElementById('task-count').textContent = `(${tasks.length})`;
    const body = document.getElementById('task-table-body');
    if (!tasks.length) { body.innerHTML = `<tr><td colspan="6" class="empty">No tasks in this range.</td></tr>`; return; }
    const statusLabel = { pending: 'Pending', in_progress: 'In progress', pending_approval: 'Awaiting approval', done: 'Done' };
    body.innerHTML = tasks.map(t => `
        <tr>
            <td>${t.work_date}</td>
            <td>${esc(t.employee_name)}</td>
            <td>${esc(t.title)}</td>
            <td><span class="pill pill-${t.status}">${statusLabel[t.status]}</span></td>
            <td>${fmtDuration(t.duration_seconds)}</td>
            <td>${t.status === 'pending_approval' ? `<button class="btn good sm" onclick="decide(${t.id},'approve')">✓</button> <button class="btn bad sm" onclick="decide(${t.id},'reject')">✕</button>` : ''}</td>
        </tr>
    `).join('');
}

async function decide(taskId, action) {
    try {
        await Taskvel.request(`/api/manager.php?action=${action}`, { method: 'POST', body: { task_id: taskId } });
        load();
    } catch (e) { alert(e.message || 'Could not update'); }
}

function renderFlags(flags, overdue) {
    const render = (id, items, fmt) => {
        const el = document.getElementById(id);
        el.innerHTML = items.length ? items.map(fmt).join('') : `<li style="color:var(--ink3)">None 🎉</li>`;
    };
    render('late-list', flags.late_checkin, f => `<li>${esc(f.name)} — ${f.date} at ${f.time}</li>`);
    render('early-list', flags.early_checkout, f => `<li>${esc(f.name)} — ${f.date} at ${f.time}</li>`);
    render('overtime-list', flags.overtime, f => `<li>${esc(f.name)} — ${f.date}, +${f.minutes}m over standard hours</li>`);
    render('overdue-list', overdue, t => `<li>${esc(t.employee_name)} — "${esc(t.title)}" is past its expected ${t.expected_minutes}m</li>`);
}

function exportCsv() {
    window.open(`/api/manager.php?action=export-csv&${buildQuery()}`, '_blank');
}

load();
</script>
</body>
</html>
