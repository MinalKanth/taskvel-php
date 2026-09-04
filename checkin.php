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
<?php pro_head('Daily Check-in'); ?>
<style>
    

    .checkin-intro { margin-bottom:22px; }
    .checkin-intro h1.page-title { margin-bottom:5px; }
    .checkin-intro .sub { margin-bottom:0; }

    .status-card { background:var(--bg-elev); border:1px solid var(--line); border-radius:var(--r-lg); padding:28px 22px;
        text-align:center; margin-bottom:22px; box-shadow:var(--shadow); }
    .status-card .big-icon { font-size:40px; margin-bottom:10px; }
    .status-card h2 { margin:0 0 6px; font-family:var(--font-display); font-size:18px; font-weight:700; }
    .status-card .meta { color:var(--ink3); font-size:13px; line-height:1.5; }
    .status-card input { width:100%; max-width:340px; padding:12px 14px; border:1px solid var(--line2); border-radius:var(--r-sm);
        font-size:14px; margin:18px auto 14px; display:block; font-family:var(--font-body); background:var(--bg); color:var(--ink);
        transition:border-color .15s, box-shadow .15s; }
    .status-card input:focus { outline:none; border-color:var(--accent); box-shadow:0 0 0 3px var(--ring); }
    .status-card .btn { margin-top:4px; }

    .break-bar { display:flex; gap:10px; align-items:center; justify-content:center; margin-top:16px; flex-wrap:wrap; }
    .break-bar select { padding:9px 12px; border:1px solid var(--line2); border-radius:var(--r-sm); font-size:13px;
        background:var(--bg); color:var(--ink); font-family:var(--font-body); }
    .idle-badge { font-size:11px; color:var(--ink3); text-align:center; margin-top:10px; }

    section { margin-top:26px; }
    section > h3 { font-family:var(--font-display); font-size:12.5px; font-weight:700; text-transform:uppercase;
        letter-spacing:.5px; color:var(--ink3); margin-bottom:12px; }

    .add-task-row { display:flex; flex-direction:column; gap:10px; background:var(--bg-elev); border:1px solid var(--line);
        border-radius:var(--r-lg); padding:16px; margin-bottom:16px; box-shadow:var(--shadow-sm); }
    .add-task-row input { padding:12px 14px; border:1px solid var(--line2); border-radius:var(--r-sm); font-size:14px;
        font-family:var(--font-body); background:var(--bg); color:var(--ink); transition:border-color .15s, box-shadow .15s; }
    .add-task-row input:focus { outline:none; border-color:var(--accent); box-shadow:0 0 0 3px var(--ring); }
    .add-task-row .row2 { display:grid; grid-template-columns:1fr 1fr; gap:10px; }
    .add-task-row .hint { font-size:11px; color:var(--ink3); line-height:1.5; }

    .task-card { background:var(--bg-elev); border:1px solid var(--line); border-radius:var(--r); padding:15px 16px;
        margin-bottom:10px; box-shadow:var(--shadow-sm); transition:border-color .15s ease, box-shadow .2s ease; }
    .task-card:hover { border-color:var(--line2); box-shadow:var(--shadow); }
    .task-top { display:flex; justify-content:space-between; align-items:flex-start; gap:10px; }
    .task-title { font-family:var(--font-display); font-size:14.5px; font-weight:600; }
    .task-meta { font-size:11.5px; color:var(--ink3); margin-top:4px; }
    .pill { font-family:'JetBrains Mono',monospace; font-size:9.5px; font-weight:700; padding:3px 9px; border-radius:20px;
        text-transform:uppercase; letter-spacing:.4px; white-space:nowrap; }
    .pill-pending { background:var(--bg-sunk); color:var(--ink3); }
    .pill-in_progress { background:var(--warn-soft); color:var(--warn); }
    .pill-pending_approval { background:var(--accent-soft); color:var(--accent); }
    .pill-done { background:var(--good-soft); color:var(--good); }
    .task-actions { display:flex; gap:7px; margin-top:12px; flex-wrap:wrap; }
    .live-timer { font-family:'JetBrains Mono',monospace; font-size:12px; color:var(--warn); font-weight:700; }

    .checkout-notes { width:100%; padding:12px 14px; border:1px solid var(--line2); border-radius:var(--r-sm); font-size:14px;
        font-family:var(--font-body); background:var(--bg); color:var(--ink); resize:vertical; min-height:80px; margin-bottom:0;
        transition:border-color .15s, box-shadow .15s; }
    .checkout-notes:focus { outline:none; border-color:var(--accent); box-shadow:0 0 0 3px var(--ring); }

    .summary-card { background:var(--bg-elev); border:1px solid var(--line); border-radius:var(--r-lg); padding:24px; margin-top:18px;
        box-shadow:var(--shadow); }
    .summary-card h3 { font-family:var(--font-display); }
    .summary-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:10px; margin:18px 0; }
    .summary-stat { text-align:center; background:var(--bg-sunk); border-radius:var(--r-sm); padding:14px 6px; }
    .summary-stat .num { font-size:20px; font-weight:700; font-family:'JetBrains Mono',monospace; color:var(--ink); }
    .summary-stat .lbl { font-size:9px; color:var(--ink3); text-transform:uppercase; letter-spacing:.5px; margin-top:4px; }
    .notified-line { font-size:12px; color:var(--ink3); margin-top:16px; padding-top:16px; border-top:1px solid var(--line); }

    /* Send end-of-day report to */
    .report-card { background:var(--bg-elev); border:1px solid var(--line); border-radius:var(--r-lg); padding:18px; margin-bottom:16px;
        box-shadow:var(--shadow-sm); }
    .report-card label { font-family:var(--font-display); font-size:11px; font-weight:700; color:var(--ink3);
        text-transform:uppercase; letter-spacing:.5px; display:block; margin:16px 0 8px; }
    .report-card label:first-child { margin-top:0; }
    .report-card select { width:100%; padding:11px 13px; border:1px solid var(--line2); border-radius:var(--r-sm); font-size:13.5px;
        font-family:var(--font-body); background:var(--bg); color:var(--ink); transition:border-color .15s, box-shadow .15s; }
    .report-card select:focus { outline:none; border-color:var(--accent); box-shadow:0 0 0 3px var(--ring); }
    .team-member-list { display:flex; flex-wrap:wrap; gap:8px; }
    .team-member-chip { display:flex; align-items:center; gap:6px; padding:8px 13px; border:1px solid var(--line2); border-radius:999px;
        font-size:12.5px; font-family:var(--font-display); font-weight:500; color:var(--ink2); background:var(--bg); cursor:pointer;
        user-select:none; transition:background .15s, border-color .15s, color .15s; }
    .team-member-chip:hover { border-color:var(--line2); background:var(--bg-sunk); }
    .team-member-chip.checked { background:var(--accent-soft); border-color:var(--accent); color:var(--accent); }
    .team-member-chip input { margin:0; }
    .custom-email-row { display:flex; gap:10px; }
    .custom-email-row input { flex:1; padding:11px 13px; border:1px solid var(--line2); border-radius:var(--r-sm); font-size:13.5px;
        font-family:var(--font-body); background:var(--bg); color:var(--ink); transition:border-color .15s, box-shadow .15s; }
    .custom-email-row input:focus { outline:none; border-color:var(--accent); box-shadow:0 0 0 3px var(--ring); }
    .custom-email-chips { display:flex; flex-wrap:wrap; gap:7px; margin-top:10px; }
    .custom-email-chip { display:flex; align-items:center; gap:7px; background:var(--bg-sunk); border:1px solid var(--line);
        border-radius:999px; padding:7px 9px 7px 13px; font-size:12.5px; font-family:var(--font-display); color:var(--ink2); }
    .custom-email-chip button { border:none; background:var(--line2); color:var(--ink2); width:18px; height:18px; border-radius:50%;
        cursor:pointer; font-size:11px; line-height:1; display:flex; align-items:center; justify-content:center; transition:background .15s; }
    .custom-email-chip button:hover { background:var(--bad-soft); color:var(--bad); }
    .report-hint { font-size:11px; color:var(--ink3); margin-top:10px; line-height:1.5; }
</style>
</head>
<body>
<div class="wrap">
    <?php pro_header($user, 'checkin'); ?>

    <div class="checkin-intro">
        <h1 class="page-title">📍 Daily Check-in</h1>
        <div class="sub">Optional office mode — check in, log what you work on, report finished tasks to whoever needs to know, and check out with a full summary.</div>
    </div>

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
                <button class="btn sm" id="add-task-btn" onclick="addTask()" style="align-self:flex-start">+ Add task</button>
            </div>
        </section>
        <section>
            <h3>Today's tasks <span id="task-count"></span></h3>
            <div id="task-list"></div>
        </section>
        <section>
            <h3>End of day notes (optional)</h3>
            <textarea class="checkout-notes" id="checkout-notes" placeholder="Anything worth mentioning — accomplishments, blockers, plans for tomorrow…" style="margin-bottom:0"></textarea>
        </section>
        <section>
            <h3>📤 Send end-of-day report to</h3>
            <div class="report-card">
                <label for="report-manager">Manager</label>
                <select id="report-manager"><option value="">— None —</option></select>

                <label>Teammates</label>
                <div class="team-member-list" id="report-team-members"><span class="report-hint">No teams found — join a team to loop in teammates here.</span></div>

                <label>Custom email addresses</label>
                <div class="custom-email-row">
                    <input type="email" id="report-custom-email-input" placeholder="anyone@company.com" onkeydown="if(event.key==='Enter'||event.key===','){event.preventDefault();addCustomEmailChip();}" />
                    <button type="button" class="btn sm ghost" onclick="addCustomEmailChip()">Add</button>
                </div>
                <div class="custom-email-chips" id="custom-email-chips"></div>
                <div class="report-hint">Pick a manager, any number of teammates, and/or type in any other email — everyone selected gets the same report when you check out.</div>
            </div>
        </section>
        <button class="btn danger" id="checkout-btn" onclick="checkOut()" style="margin-top:4px;width:100%;justify-content:center;padding:13px">🚪 Check out for the day</button>
    </div>
    <div id="summary-area"></div>
    <?php pro_footer($user); ?>
</div>

<script src="js/api-client.js?v=2"></script>
<script>
function esc(s) { return (s || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;'); }
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
        loadReportContacts();
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
                <input type="email" id="checkin-report-to" placeholder="Report to (optional — manager/lead email)" style="margin-bottom:16px" />
                <button class="btn" id="checkin-btn" onclick="checkIn()">✅ Check in</button>
            </div>`;
        return;
    }
    if (currentWorkday.checkout_at) {
        el.innerHTML = `
            <div class="status-card">
                <div class="big-icon">🌙</div>
                <h2>Day complete</h2>
                <div class="meta">Checked in ${fmtTime(currentWorkday.checkin_at)} · Checked out ${fmtTime(currentWorkday.checkout_at)}</div>
                <button class="btn sm ghost" style="margin-top:12px" onclick="reopenDay()">🔁 Check in again</button>
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
    const btn = document.getElementById('checkin-btn');
    if (btn.disabled) return;
    btn.disabled = true;
    const orig = btn.textContent;
    btn.textContent = 'Checking in…';
    try {
        await Taskvel.request('/api/workday.php?action=checkin', { method: 'POST', body: { report_to_email: reportTo || null } });
        load();
    } catch (e) {
        alert(e.message || 'Could not check in');
        btn.disabled = false;
        btn.textContent = orig;
    }
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
async function reopenDay() {
    try {
        await Taskvel.request('/api/workday.php?action=reopen', { method: 'POST' });
        load();
    } catch (e) { alert(e.message || 'Could not check in again'); }
}

function renderTasks() {
    const list = document.getElementById('task-list');
    document.getElementById('task-count').textContent = `(${currentTasks.length})`;
    if (!currentTasks.length) { list.innerHTML = `<div class="empty"><span class="ic">📝</span>No tasks logged yet today.</div>`; return; }
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
    const btn = document.getElementById('add-task-btn');
    if (btn.disabled) return;
    btn.disabled = true;
    try {
        await Taskvel.request('/api/workday.php?action=add-task', { method: 'POST', body: { title, report_to_email: email || null, expected_minutes: expected || null } });
        document.getElementById('new-task-title').value = '';
        document.getElementById('new-task-email').value = '';
        document.getElementById('new-task-expected').value = '';
        load();
    } catch (e) { alert(e.message || 'Could not add task'); } finally {
        btn.disabled = false;
    }
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

// ─────────── Send end-of-day report to (manager / teammates / custom emails) ───────────
let customEmailChips = [];

async function loadReportContacts() {
    try {
        const { managers, team_members } = await Taskvel.request('/api/workday.php?action=report-contacts');
        const managerSelect = document.getElementById('report-manager');
        managerSelect.innerHTML = '<option value="">— None —</option>' +
            managers.map(m => `<option value="${m.id}">${esc(m.name)} (${esc(m.team_name)})</option>`).join('');

        const list = document.getElementById('report-team-members');
        list.innerHTML = team_members.length ? team_members.map(m => `
            <label class="team-member-chip">
                <input type="checkbox" value="${m.id}" onchange="this.closest('.team-member-chip').classList.toggle('checked', this.checked)" />
                ${esc(m.name)} <span style="color:var(--ink3);font-size:11px">· ${esc(m.team_name)}</span>
            </label>`).join('') : '<span class="report-hint">No teams found — join a team to loop in teammates here.</span>';
    } catch (e) { /* Daily Check-in still works standalone without Teams */ }
}

function addCustomEmailChip() {
    const input = document.getElementById('report-custom-email-input');
    const email = input.value.trim().replace(/,$/, '');
    if (!email) return;
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) { alert('That doesn\'t look like a valid email address'); return; }
    if (!customEmailChips.includes(email)) customEmailChips.push(email);
    input.value = '';
    renderCustomEmailChips();
}
function removeCustomEmailChip(email) {
    customEmailChips = customEmailChips.filter(e => e !== email);
    renderCustomEmailChips();
}
function renderCustomEmailChips() {
    document.getElementById('custom-email-chips').innerHTML = customEmailChips.map(email => `
        <span class="custom-email-chip">${esc(email)} <button type="button" onclick="removeCustomEmailChip('${email.replace(/'/g,"\\'")}')">×</button></span>`).join('');
}

async function checkOut() {
    const stillOpen = currentTasks.filter(t => t.status !== 'done').length;
    if (stillOpen && !confirm(`${stillOpen} task(s) aren't fully done yet. Check out anyway?`)) return;
    const btn = document.getElementById('checkout-btn');
    if (btn.disabled) return;
    btn.disabled = true;
    const orig = btn.textContent;
    btn.textContent = 'Checking out…';
    const notes = document.getElementById('checkout-notes').value.trim();
    const managerUserId = document.getElementById('report-manager').value || null;
    const teamMemberUserIds = Array.from(document.querySelectorAll('#report-team-members input:checked')).map(el => parseInt(el.value, 10));
    try {
        const res = await Taskvel.request('/api/workday.php?action=checkout', {
            method: 'POST',
            body: { notes, manager_user_id: managerUserId, team_member_user_ids: teamMemberUserIds, custom_emails: customEmailChips },
        });
        clearInterval(liveTimerHandle);
        await load();
        renderSummary(res.summary, res.notified, res.ai_summary);
    } catch (e) {
        alert(e.message || 'Could not check out');
        btn.disabled = false;
        btn.textContent = orig;
    }
}

function renderSummary(summary, notified, aiSummary) {
    const el = document.getElementById('summary-area');
    el.innerHTML = `
        <div class="summary-card">
            <h3 style="margin-top:0">📋 Today's summary</h3>
            ${aiSummary ? `<div style="margin-bottom:14px;padding:12px 14px;background:var(--accent-soft,rgba(99,102,241,.08));border-radius:10px;font-size:13.5px;line-height:1.6">🤖 ${esc(aiSummary)}</div>` : ''}
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