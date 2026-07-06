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
<title>Daily Check-in · Taskvel</title>
<style>
    :root {
        --bg:#f6f6f4; --bg-elev:#fff; --bg-sunk:#ededea; --ink:#0a0a0a; --ink2:#3d3d3b; --ink3:#7c7c78;
        --line:#e6e5e0; --line2:#d4d3cd; --accent:#4f46e5; --accent-soft:rgba(79,70,229,.1); --on-accent:#fff;
        --good:#059669; --good-soft:rgba(5,150,105,.1); --warn:#d97706; --warn-soft:rgba(217,119,6,.1); --bad:#dc2626;
        --shadow:0 10px 34px rgba(10,10,10,.08); --r:14px; --ease:cubic-bezier(.22,1,.36,1);
    }
    * { box-sizing:border-box; }
    body { margin:0; font-family:-apple-system,'Segoe UI',Arial,sans-serif; background:var(--bg); color:var(--ink); }
    .wrap { max-width:640px; margin:0 auto; padding:24px 18px 90px; }
    .topbar { display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; }
    .topbar-links { display:flex; gap:14px; align-items:center; }
    .topbar a.back { color:var(--ink3); text-decoration:none; font-size:13px; font-weight:600; }
    .topbar a.back:hover { color:var(--accent); }
    h1 { font-size:22px; font-weight:800; margin:0 0 4px; }
    .sub { color:var(--ink3); font-size:13.5px; margin-bottom:20px; }

    .status-card { background:var(--bg-elev); border:1px solid var(--line); border-radius:var(--r); padding:22px;
        text-align:center; margin-bottom:20px; box-shadow:var(--shadow); }
    .status-card .big-icon { font-size:40px; margin-bottom:10px; }
    .status-card h2 { margin:0 0 6px; font-size:18px; }
    .status-card .meta { color:var(--ink3); font-size:13px; }
    .status-card input { width:100%; max-width:320px; padding:11px 13px; border:1px solid var(--line2); border-radius:10px;
        font-size:14px; margin:14px auto 0; display:block; font-family:inherit; }
    .btn { display:inline-flex; align-items:center; gap:6px; padding:13px 24px; border-radius:12px; border:none;
        background:var(--accent); color:var(--on-accent); font-weight:700; font-size:14.5px; cursor:pointer;
        box-shadow:0 8px 20px -8px rgba(79,70,229,.4); transition:transform .2s var(--ease); margin-top:14px; }
    .btn:hover { transform:translateY(-2px); }
    .btn.ghost { background:var(--bg-elev); color:var(--ink); border:1px solid var(--line2); box-shadow:none; }
    .btn.danger { background:var(--bad); }
    .btn.warn { background:var(--warn); }
    .btn.sm { padding:8px 14px; font-size:12.5px; margin-top:0; }
    .btn:disabled { opacity:.5; cursor:not-allowed; transform:none; }

    .break-bar { display:flex; gap:8px; align-items:center; justify-content:center; margin-top:12px; flex-wrap:wrap; }
    .break-bar select { padding:8px 10px; border:1px solid var(--line2); border-radius:9px; font-size:13px; }
    .idle-badge { font-size:11px; color:var(--ink3); text-align:center; margin-top:8px; }

    section { margin-top:24px; }
    section h3 { font-size:13px; text-transform:uppercase; letter-spacing:.6px; color:var(--ink3); margin-bottom:12px;
        display:flex; justify-content:space-between; align-items:center; }

    .add-task-row { display:flex; flex-direction:column; gap:8px; background:var(--bg-elev); border:1px solid var(--line);
        border-radius:var(--r); padding:14px; margin-bottom:16px; }
    .add-task-row input { padding:11px 13px; border:1px solid var(--line2); border-radius:10px; font-size:14px; font-family:inherit; }
    .add-task-row .row2 { display:grid; grid-template-columns:1fr 1fr; gap:8px; }
    .add-task-row .hint { font-size:11px; color:var(--ink3); }

    .task-card { background:var(--bg-elev); border:1px solid var(--line); border-radius:12px; padding:13px 15px;
        margin-bottom:10px; }
    .task-top { display:flex; justify-content:space-between; align-items:flex-start; gap:10px; }
    .task-title { font-size:14.5px; font-weight:600; }
    .task-meta { font-size:11.5px; color:var(--ink3); margin-top:4px; }
    .pill { font-size:9.5px; font-weight:700; padding:3px 9px; border-radius:20px; text-transform:uppercase; letter-spacing:.4px; white-space:nowrap; }
    .pill-pending { background:var(--bg-sunk); color:var(--ink3); }
    .pill-in_progress { background:var(--warn-soft); color:var(--warn); }
    .pill-pending_approval { background:var(--accent-soft); color:var(--accent); }
    .pill-done { background:var(--good-soft); color:var(--good); }
    .task-actions { display:flex; gap:6px; margin-top:10px; flex-wrap:wrap; }
    .live-timer { font-family:monospace; font-size:12px; color:var(--warn); font-weight:700; }

    .empty { text-align:center; padding:30px 20px; color:var(--ink3); font-size:13px; }

    .checkout-notes { width:100%; padding:11px 13px; border:1px solid var(--line2); border-radius:10px; font-size:14px;
        font-family:inherit; resize:vertical; min-height:70px; margin-bottom:12px; }

    .summary-card { background:var(--bg-elev); border:1px solid var(--line); border-radius:var(--r); padding:22px; margin-top:16px; }
    .summary-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:10px; margin:16px 0; }
    .summary-stat { text-align:center; background:var(--bg-sunk); border-radius:10px; padding:12px 6px; }
    .summary-stat .num { font-size:20px; font-weight:700; font-family:monospace; }
    .summary-stat .lbl { font-size:9px; color:var(--ink3); text-transform:uppercase; letter-spacing:.5px; margin-top:3px; }
    .notified-line { font-size:12px; color:var(--ink3); margin-top:14px; padding-top:14px; border-top:1px solid var(--line); }
</style>
</head>
<body>
<div class="wrap">
    <div class="topbar">
        <a class="back" href="index.php">← Back to Taskvel</a>
        <div class="topbar-links">
            <a class="back" href="manager.php">📊 Manager dashboard</a>
            <span style="font-size:12.5px;color:var(--ink3)"><?= htmlspecialchars($user['email']) ?></span>
        </div>
    </div>
    <h1>📍 Daily Check-in</h1>
    <div class="sub">Optional office mode — check in, log what you work on, report finished tasks to whoever needs to know, and check out with a full summary.</div>

    <div id="status-area"></div>
    <div id="task-area" style="display:none">
        <section>
            <h3>Add a task</h3>
            <div class="add-task-row">
                <input type="text" id="new-task-title" placeholder="What are you working on?" />
                <div class="row2">
                    <input type="email" id="new-task-email" placeholder="Report to (optional email)" />
                    <input type="number" id="new-task-expected" placeholder="Expected minutes (optional)" min="1" />
                </div>
                <div class="hint">Leave "Report to" blank if this task doesn't need anyone notified. Expected minutes flags it as overdue on the manager dashboard if it runs long.</div>
                <button class="btn sm" onclick="addTask()" style="align-self:flex-start">+ Add task</button>
            </div>
        </section>
        <section>
            <h3>Today's tasks <span id="task-count"></span></h3>
            <div id="task-list"></div>
        </section>
        <section>
            <h3>End of day notes (optional)</h3>
            <textarea class="checkout-notes" id="checkout-notes" placeholder="Anything worth mentioning — accomplishments, blockers, plans for tomorrow…"></textarea>
        </section>
        <button class="btn danger" id="checkout-btn" onclick="checkOut()">🚪 Check out for the day</button>
    </div>
    <div id="summary-area"></div>
</div>

<script src="js/api-client.js"></script>
<script>
function esc(s) { return (s || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
function fmtTime(iso) { return new Date(iso.replace(' ', 'T')).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }); }
function fmtDuration(seconds) {
    const h = Math.floor(seconds / 3600), m = Math.floor((seconds % 3600) / 60);
    return h > 0 ? `${h}h ${m}m` : `${m}m`;
}

let currentWorkday = null;
let currentTasks = [];
let currentBreaks = [];
let liveTimerHandle = null;

async function load() {
    const { workday, tasks, breaks } = await Taskvel.request('/api/workday.php?action=today');
    currentWorkday = workday;
    currentTasks = tasks;
    currentBreaks = breaks || [];
    renderStatus();
    if (workday && !workday.checkout_at) {
        document.getElementById('task-area').style.display = 'block';
        renderTasks();
        startLiveTimer();
        startIdleWatch();
    } else if (workday && workday.checkout_at) {
        document.getElementById('task-area').style.display = 'none';
        renderCheckedOutSummary();
    }
}

function renderStatus() {
    const el = document.getElementById('status-area');
    if (!currentWorkday) {
        el.innerHTML = `
            <div class="status-card">
                <div class="big-icon">☀️</div>
                <h2>Ready to start your day?</h2>
                <div class="meta">Check in to start logging and tracking today's tasks.</div>
                <input type="email" id="checkin-report-to" placeholder="Report to (optional — manager/lead email)" />
                <button class="btn" onclick="checkIn()">✅ Check in</button>
            </div>`;
        return;
    }
    if (currentWorkday.checkout_at) {
        el.innerHTML = `
            <div class="status-card">
                <div class="big-icon">🌙</div>
                <h2>Day complete</h2>
                <div class="meta">Checked in ${fmtTime(currentWorkday.checkin_at)} · Checked out ${fmtTime(currentWorkday.checkout_at)}</div>
            </div>`;
        return;
    }
    const activeBreak = currentBreaks.find(b => !b.ended_at);
    el.innerHTML = `
        <div class="status-card">
            <div class="big-icon">${activeBreak ? '☕' : '🟢'}</div>
            <h2>${activeBreak ? 'On a break' : 'Checked in'}</h2>
            <div class="meta">Since ${fmtTime(currentWorkday.checkin_at)}${activeBreak ? ' · on ' + activeBreak.break_type + ' break since ' + fmtTime(activeBreak.started_at) : ' — have a great day.'}</div>
            <div class="break-bar">
                ${activeBreak
                    ? `<button class="btn sm warn" onclick="endBreak()">▶ End break</button>`
                    : `<select id="break-type"><option value="lunch">Lunch</option><option value="tea">Tea</option><option value="personal">Personal</option><option value="other">Other</option></select>
                       <button class="btn sm ghost" onclick="startBreak()">⏸ Start break</button>`}
            </div>
            <div class="idle-badge" id="idle-badge"></div>
        </div>`;
}

async function checkIn() {
    const reportTo = document.getElementById('checkin-report-to').value.trim();
    try {
        await Taskvel.request('/api/workday.php?action=checkin', { method: 'POST', body: { report_to_email: reportTo || null } });
        load();
    } catch (e) { alert(e.message || 'Could not check in'); }
}
async function startBreak() {
    const type = document.getElementById('break-type').value;
    await Taskvel.request('/api/workday.php?action=break-start', { method: 'POST', body: { break_type: type } });
    load();
}
async function endBreak() {
    await Taskvel.request('/api/workday.php?action=break-end', { method: 'POST' });
    load();
}

function renderTasks() {
    const list = document.getElementById('task-list');
    document.getElementById('task-count').textContent = `(${currentTasks.length})`;
    if (!currentTasks.length) { list.innerHTML = `<div class="empty">No tasks logged yet today.</div>`; return; }
    const statusLabel = { pending: 'Pending', in_progress: 'In progress', pending_approval: 'Awaiting approval', done: 'Done' };
    list.innerHTML = currentTasks.map(t => {
        let actions = '';
        if (t.status === 'pending') actions = `<button class="btn sm" onclick="startTask(${t.id})">▶ Start</button><button class="btn sm ghost" onclick="completeTask(${t.id})">✓ Mark done</button>`;
        else if (t.status === 'in_progress') actions = `<button class="btn sm" onclick="completeTask(${t.id})">✓ Mark done</button>`;
        else if (t.status === 'pending_approval') actions = `<span style="font-size:12px;color:var(--ink3)">Waiting on ${esc(t.report_to_email)} to approve…</span>`;
        let timerHtml = '';
        if (t.status === 'in_progress') timerHtml = `<span class="live-timer" data-started="${t.started_at}" id="timer-${t.id}"></span>`;
        else if (t.status === 'done' || t.status === 'pending_approval') timerHtml = `<span class="task-meta">⏱ ${fmtDuration(t.duration_seconds || 0)}</span>`;
        return `<div class="task-card">
            <div class="task-top">
                <div>
                    <div class="task-title">${esc(t.title)}</div>
                    <div class="task-meta">${t.report_to_email ? '📧 reports to ' + esc(t.report_to_email) : 'No report-to set'}${t.expected_minutes ? ' · ⏳ expected ' + t.expected_minutes + 'm' : ''}</div>
                </div>
                <span class="pill pill-${t.status}">${statusLabel[t.status]}</span>
            </div>
            <div class="task-actions">${actions}${timerHtml ? `<span style="margin-left:auto;align-self:center">${timerHtml}</span>` : ''}
                ${t.status === 'pending' || t.status === 'in_progress' ? `<button class="btn sm ghost" onclick="deleteTask(${t.id})" title="Remove">🗑</button>` : ''}
            </div>
        </div>`;
    }).join('');
    tickLiveTimers();
}

function startLiveTimer() {
    clearInterval(liveTimerHandle);
    liveTimerHandle = setInterval(tickLiveTimers, 1000);
}
function tickLiveTimers() {
    document.querySelectorAll('.live-timer').forEach(el => {
        const started = new Date(el.dataset.started.replace(' ', 'T'));
        const secs = Math.max(0, Math.floor((Date.now() - started.getTime()) / 1000));
        el.textContent = '⏱ ' + fmtDuration(secs) + ' so far';
    });
}

// ── Idle detection: tab-level only. Tracks the gap since the last mouse/
// keyboard/touch event; if the user comes back after 5+ minutes idle, the
// gap is reported once. This is NOT screen or activity capture — just a
// soft "were they actually at the keyboard" signal for the manager view.
let lastActivity = Date.now();
let idleWatchStarted = false;
const IDLE_THRESHOLD_MS = 5 * 60 * 1000;
function markActivity() {
    const now = Date.now();
    const gap = now - lastActivity;
    if (gap > IDLE_THRESHOLD_MS) {
        Taskvel.request('/api/workday.php?action=log-idle', { method: 'POST', body: { idle_seconds: Math.floor(gap / 1000) } }).catch(() => {});
        const badge = document.getElementById('idle-badge');
        if (badge) badge.textContent = `Welcome back — ${fmtDuration(Math.floor(gap / 1000))} idle before that.`;
    }
    lastActivity = now;
}
function startIdleWatch() {
    if (idleWatchStarted) return;
    idleWatchStarted = true;
    ['mousemove', 'keydown', 'touchstart', 'scroll'].forEach(evt => document.addEventListener(evt, markActivity, { passive: true }));
}

async function addTask() {
    const title = document.getElementById('new-task-title').value.trim();
    const email = document.getElementById('new-task-email').value.trim();
    const expected = document.getElementById('new-task-expected').value.trim();
    if (!title) { alert('Enter a task title'); return; }
    try {
        await Taskvel.request('/api/workday.php?action=add-task', { method: 'POST', body: { title, report_to_email: email || null, expected_minutes: expected || null } });
        document.getElementById('new-task-title').value = '';
        document.getElementById('new-task-email').value = '';
        document.getElementById('new-task-expected').value = '';
        load();
    } catch (e) { alert(e.message || 'Could not add task'); }
}
async function startTask(id) {
    await Taskvel.request('/api/workday.php?action=start-task', { method: 'POST', body: { id } });
    load();
}
async function completeTask(id) {
    await Taskvel.request('/api/workday.php?action=complete-task', { method: 'POST', body: { id } });
    load();
}
async function deleteTask(id) {
    if (!confirm('Remove this task?')) return;
    await Taskvel.request(`/api/workday.php?action=delete-task&id=${id}`, { method: 'DELETE' });
    load();
}

async function checkOut() {
    const stillOpen = currentTasks.filter(t => t.status !== 'done').length;
    if (stillOpen && !confirm(`${stillOpen} task(s) aren't fully done yet. Check out anyway?`)) return;
    const notes = document.getElementById('checkout-notes').value.trim();
    try {
        const res = await Taskvel.request('/api/workday.php?action=checkout', { method: 'POST', body: { notes } });
        clearInterval(liveTimerHandle);
        load();
        renderSummary(res.summary, res.notified);
    } catch (e) { alert(e.message || 'Could not check out'); }
}

function renderSummary(summary, notified) {
    const el = document.getElementById('summary-area');
    el.innerHTML = `
        <div class="summary-card">
            <h3 style="margin-top:0">📋 Today's summary</h3>
            <div class="summary-grid">
                <div class="summary-stat"><div class="num">${summary.total}</div><div class="lbl">Total</div></div>
                <div class="summary-stat"><div class="num" style="color:var(--good)">${summary.done}</div><div class="lbl">Done</div></div>
                <div class="summary-stat"><div class="num" style="color:var(--warn)">${summary.in_progress}</div><div class="lbl">In progress</div></div>
                <div class="summary-stat"><div class="num">${summary.pending}</div><div class="lbl">Pending</div></div>
            </div>
            <div style="text-align:center;font-size:14px">
                Worked <strong>${summary.worked_text || ''}</strong>${summary.break_text ? ` · Break time <strong>${summary.break_text}</strong>` : ''}${summary.overtime_text ? ` · Overtime <strong>${summary.overtime_text}</strong>` : ''}
            </div>
            ${notified && notified.length ? `<div class="notified-line">📧 Summary emailed to: ${notified.map(esc).join(', ')}</div>` : `<div class="notified-line">No report-to emails were set today, so no summary email was sent.</div>`}
        </div>`;
}
function renderCheckedOutSummary() {
    const done = currentTasks.filter(t => t.status === 'done');
    const pending = currentTasks.filter(t => t.status === 'pending');
    const inProgress = currentTasks.filter(t => t.status === 'in_progress');
    const totalSeconds = done.reduce((s, t) => s + (t.duration_seconds || 0), 0);
    renderSummary({
        total: currentTasks.length, done: done.length, pending: pending.length, in_progress: inProgress.length,
        worked_text: null,
    }, currentWorkday.summary_sent ? Array.from(new Set(currentTasks.map(t => t.report_to_email).filter(Boolean))) : null);
}

load();
</script>
</body>
</html>
