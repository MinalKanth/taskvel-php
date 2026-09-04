<?php
// New file: trading-journal.php  (place in the app root, next to my-work.php)
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
<?php pro_head('Trading Journal'); ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.4/chart.umd.min.js"></script>
<style>
    .tj-intro { margin-bottom:22px; }
    .tj-intro h1 { font-family:var(--font-display); font-size:24px; font-weight:700; letter-spacing:-.4px; margin:0 0 5px; }
    .tj-intro p { color:var(--ink3); font-size:13.5px; margin:0; line-height:1.5; }

    /* ---------- Summary cards ---------- */
    .tj-cards { display:grid; grid-template-columns:repeat(4,1fr); gap:10px; margin-bottom:24px; }
    @media (max-width:900px) { .tj-cards { grid-template-columns:repeat(2,1fr); } }
    @media (max-width:480px) { .tj-cards { grid-template-columns:1fr 1fr; gap:8px; } }
    .tj-card { background:var(--bg-elev); border:1px solid var(--line); border-radius:var(--r-lg); padding:14px 15px;
        box-shadow:var(--shadow-sm); transition:transform .2s var(--ease), box-shadow .2s ease; position:relative; overflow:hidden; }
    .tj-card:hover { transform:translateY(-2px); box-shadow:var(--shadow); }
    .tj-card .tj-card-label { font-family:var(--font-display); font-size:10px; font-weight:700; text-transform:uppercase;
        letter-spacing:.5px; color:var(--ink3); margin-bottom:6px; }
    .tj-card .tj-card-value { font-family:var(--font-display); font-size:20px; font-weight:700; letter-spacing:-.3px; }
    .tj-card .tj-card-sub { font-size:10.5px; color:var(--ink3); margin-top:3px; }
    .tj-card.pos .tj-card-value { color:var(--good); }
    .tj-card.neg .tj-card-value { color:var(--bad); }
    .tj-card.accent .tj-card-value { color:var(--accent); }
    .tj-skel { display:inline-block; width:70%; height:18px; border-radius:6px; background:linear-gradient(90deg,var(--bg-sunk) 25%,var(--line) 37%,var(--bg-sunk) 63%);
        background-size:400% 100%; animation:tj-shimmer 1.4s ease infinite; }
    @keyframes tj-shimmer { 0% { background-position:100% 50%; } 100% { background-position:0 50%; } }

    /* ---------- Goal calculator ---------- */
    .tj-goal-card { background:var(--bg-elev); border:1px solid var(--line); border-radius:var(--r-lg); padding:20px; margin-bottom:28px; box-shadow:var(--shadow-sm); }
    .tj-goal-top { display:flex; gap:20px; flex-wrap:wrap; align-items:flex-start; justify-content:space-between; }
    .tj-goal-form { display:flex; gap:10px; align-items:flex-end; flex-wrap:wrap; }
    .tj-goal-form .fg { margin:0; min-width:160px; }
    .tj-goal-form label { font-family:var(--font-display); font-size:10.5px; color:var(--ink3); text-transform:uppercase; letter-spacing:.6px; display:block; margin-bottom:6px; font-weight:600; }
    .tj-goal-form input { width:100%; padding:10px 12px; border:1px solid var(--line2); border-radius:var(--r-sm); font-size:14px; font-family:var(--font-body); background:var(--bg); color:var(--ink); }
    .tj-goal-form input:focus { outline:none; border-color:var(--accent); box-shadow:0 0 0 3px var(--ring); }

    .tj-gauge-wrap { display:flex; flex-direction:column; align-items:center; gap:6px; min-width:160px; }
    .tj-gauge-pct { font-family:var(--font-display); font-size:22px; font-weight:800; margin-top:-62px; }
    .tj-gauge-label { font-size:10.5px; color:var(--ink3); }

    .tj-goal-stats { display:grid; grid-template-columns:repeat(4,1fr); gap:10px; margin-top:18px; }
    @media (max-width:700px) { .tj-goal-stats { grid-template-columns:1fr 1fr; } }
    .tj-goal-stat { background:var(--bg-sunk); border-radius:var(--r-sm); padding:10px 12px; }
    .tj-goal-stat .l { font-size:10px; color:var(--ink3); font-family:var(--font-display); text-transform:uppercase; letter-spacing:.4px; }
    .tj-goal-stat .v { font-family:var(--font-display); font-weight:700; font-size:15px; margin-top:2px; }

    .tj-progress-bar { width:100%; height:12px; border-radius:999px; background:var(--bg-sunk); overflow:hidden; margin-top:14px; border:1px solid var(--line); }
    .tj-progress-fill { height:100%; border-radius:999px; background:linear-gradient(90deg,var(--accent),var(--accent-2)); transition:width .6s var(--ease); }
    .tj-progress-fill.full { background:linear-gradient(90deg,var(--good),#22c55e); }

    /* ---------- Toolbar / filters ---------- */
    .tj-toolbar { display:flex; gap:10px; flex-wrap:wrap; align-items:center; margin-bottom:18px; }
    .tj-filter-pills { display:flex; gap:6px; flex-wrap:wrap; background:var(--bg-sunk); border:1px solid var(--line); border-radius:14px; padding:4px; }
    .tj-filter-pills button { font-family:var(--font-display); font-size:12px; font-weight:600; padding:7px 12px; border-radius:10px;
        border:1px solid transparent; background:transparent; color:var(--ink2); cursor:pointer; transition:all .15s var(--ease); white-space:nowrap; }
    .tj-filter-pills button:hover { background:var(--bg-elev); color:var(--ink); }
    .tj-filter-pills button.active { background:var(--bg-elev); color:var(--accent); border-color:var(--line); box-shadow:var(--shadow-sm); }
    .tj-custom-range { display:none; gap:8px; align-items:center; }
    .tj-custom-range.show { display:flex; }
    .tj-custom-range input { padding:8px 10px; border:1px solid var(--line2); border-radius:var(--r-sm); font-size:12.5px; background:var(--bg-elev); color:var(--ink); }
    .tj-view-toggle { display:flex; gap:4px; margin-left:auto; }
    .tj-view-toggle button { font-family:var(--font-display); font-size:11.5px; font-weight:700; padding:7px 12px; border-radius:9px;
        border:1px solid var(--line2); background:var(--bg-elev); color:var(--ink2); cursor:pointer; }
    .tj-view-toggle button.active { background:var(--accent); color:var(--on-accent); border-color:var(--accent); }

    /* ---------- Charts ---------- */
    .tj-charts-grid { display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:28px; }
    @media (max-width:900px) { .tj-charts-grid { grid-template-columns:1fr; } }
    .tj-chart-card { background:var(--bg-elev); border:1px solid var(--line); border-radius:var(--r-lg); padding:16px; box-shadow:var(--shadow-sm); }
    .tj-chart-card h3 { font-family:var(--font-display); font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:.5px; color:var(--ink3); margin:0 0 12px; }
    .tj-chart-card canvas { max-height:230px; }
    .tj-chart-card.wide { grid-column:1 / -1; }

    .tj-best-worst { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
    @media (max-width:480px) { .tj-best-worst { grid-template-columns:1fr; } }
    .tj-bw-item { border-radius:var(--r-sm); padding:12px 14px; }
    .tj-bw-item.best { background:var(--good-soft); }
    .tj-bw-item.worst { background:var(--bad-soft); }
    .tj-bw-item .l { font-family:var(--font-display); font-size:10px; text-transform:uppercase; letter-spacing:.5px; font-weight:700; }
    .tj-bw-item.best .l { color:var(--good); }
    .tj-bw-item.worst .l { color:var(--bad); }
    .tj-bw-item .amt { font-family:var(--font-display); font-size:19px; font-weight:800; margin-top:4px; }
    .tj-bw-item .dt { font-size:11px; color:var(--ink3); margin-top:2px; }

    /* ---------- Entries table ---------- */
    .tj-entry-row { display:flex; align-items:center; gap:12px; padding:14px 16px; background:var(--bg-elev); border:1px solid var(--line);
        border-radius:var(--r-lg); margin-bottom:8px; box-shadow:var(--shadow-sm); transition:border-color .2s ease, box-shadow .2s ease, transform .2s var(--ease); }
    .tj-entry-row:hover { transform:translateY(-1px); box-shadow:var(--shadow); border-color:var(--line2); }
    .tj-entry-dot { width:10px; height:10px; border-radius:50%; flex-shrink:0; }
    .tj-entry-dot.profit { background:var(--good); }
    .tj-entry-dot.loss { background:var(--bad); }
    .tj-entry-dot.breakeven { background:var(--ink4); }
    .tj-entry-body { flex:1; min-width:0; }
    .tj-entry-date { font-family:var(--font-display); font-size:12.5px; font-weight:700; }
    .tj-entry-notes { font-size:12px; color:var(--ink3); margin-top:2px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .tj-entry-amt { font-family:var(--font-display); font-weight:800; font-size:15px; white-space:nowrap; }
    .tj-entry-amt.profit { color:var(--good); }
    .tj-entry-amt.loss { color:var(--bad); }
    .tj-entry-amt.breakeven { color:var(--ink3); }
    .tj-entry-actions { display:flex; gap:4px; flex-shrink:0; }
    .tj-entry-actions button { width:30px; height:30px; border-radius:8px; border:1px solid var(--line2); background:var(--bg); color:var(--ink2); cursor:pointer; font-size:13px; }
    .tj-entry-actions button:hover { background:var(--bg-sunk); color:var(--ink); }

    /* ---------- Journal ---------- */
    .tj-journal-card { background:var(--bg-elev); border:1px solid var(--line); border-radius:var(--r-lg); padding:18px; margin-bottom:10px; box-shadow:var(--shadow-sm); }
    .tj-journal-head { display:flex; justify-content:space-between; align-items:center; gap:10px; margin-bottom:10px; flex-wrap:wrap; }
    .tj-journal-head .dt { font-family:var(--font-display); font-weight:700; font-size:13.5px; }
    #tj-journal-text { width:100%; min-height:200px; padding:12px 14px; border:1px solid var(--line2); border-radius:var(--r-sm);
        font-size:13.5px; font-family:var(--font-body); background:var(--bg); color:var(--ink); resize:vertical; line-height:1.6; }
    #tj-journal-text:focus { outline:none; border-color:var(--accent); box-shadow:0 0 0 3px var(--ring); }
    .tj-journal-foot { display:flex; justify-content:space-between; align-items:center; margin-top:10px; gap:10px; flex-wrap:wrap; }
    .tj-journal-count { font-size:11.5px; color:var(--ink3); font-family:var(--font-display); }
    .tj-journal-count.over { color:var(--bad); font-weight:700; }
    .tj-journal-hint { font-size:11.5px; color:var(--ink3); margin-bottom:10px; line-height:1.5; }
    .tj-journal-list-item { padding:10px 12px; border-radius:var(--r-sm); background:var(--bg-sunk); margin-bottom:6px; cursor:pointer; transition:background .15s; }
    .tj-journal-list-item:hover { background:var(--line); }
    .tj-journal-list-item .d { font-family:var(--font-display); font-weight:700; font-size:12px; }
    .tj-journal-list-item .p { font-size:11.5px; color:var(--ink3); overflow:hidden; text-overflow:ellipsis; white-space:nowrap; margin-top:2px; }

    .tj-empty { text-align:center; padding:36px 20px; color:var(--ink3); font-size:13px; background:var(--bg-sunk); border:1px dashed var(--line2); border-radius:var(--r-lg); }
    .tj-empty .ic { font-size:28px; margin-bottom:8px; display:block; opacity:.5; }
    .tj-loading { text-align:center; color:var(--ink3); font-size:13px; padding:34px 20px; }

    .tj-tabs { display:flex; gap:3px; margin:0 0 18px; flex-wrap:wrap; padding:4px; background:var(--bg-sunk); border:1px solid var(--line); border-radius:14px; width:fit-content; }
    .tj-tabs button { font-family:var(--font-display); font-size:12.5px; font-weight:600; padding:8px 14px; border-radius:10px; border:1px solid transparent;
        background:transparent; color:var(--ink2); cursor:pointer; transition:all .18s var(--ease); }
    .tj-tabs button:hover { background:var(--bg-elev); color:var(--ink); }
    .tj-tabs button.active { background:var(--bg-elev); color:var(--accent); border-color:var(--line); box-shadow:var(--shadow-sm); }
    .tj-panel { display:none; }
    .tj-panel.active { display:block; }

    .fielderr { color:var(--bad); font-size:11.5px; margin-top:4px; display:none; }
    .fielderr.show { display:block; }
    .modal input.err, .modal textarea.err { border-color:var(--bad) !important; }
</style>
</head>
<body>
<div class="wrap">
    <?php pro_header($user, 'journal'); ?>
    <div>
    <div class="tj-intro">
        <h1>Trading Journal &amp; P/L Dashboard</h1>
        <p>Track your monthly goal, daily profit &amp; loss, and trading journal — all in one place.</p>
    </div>

    <!-- ===================== SUMMARY CARDS ===================== -->
    <div class="tj-cards" id="tj-cards">
        <div class="tj-card accent"><div class="tj-card-label">Monthly Goal</div><div class="tj-card-value"><span class="tj-skel"></span></div></div>
        <div class="tj-card"><div class="tj-card-label">Current P/L</div><div class="tj-card-value"><span class="tj-skel"></span></div></div>
        <div class="tj-card"><div class="tj-card-label">Remaining Target</div><div class="tj-card-value"><span class="tj-skel"></span></div></div>
        <div class="tj-card accent"><div class="tj-card-label">Goal Achievement</div><div class="tj-card-value"><span class="tj-skel"></span></div></div>
        <div class="tj-card"><div class="tj-card-label">Trading Days</div><div class="tj-card-value"><span class="tj-skel"></span></div></div>
        <div class="tj-card pos"><div class="tj-card-label">Profitable Days</div><div class="tj-card-value"><span class="tj-skel"></span></div></div>
        <div class="tj-card neg"><div class="tj-card-label">Loss Days</div><div class="tj-card-value"><span class="tj-skel"></span></div></div>
        <div class="tj-card accent"><div class="tj-card-label">Win Rate</div><div class="tj-card-value"><span class="tj-skel"></span></div></div>
    </div>

    <!-- ===================== GOAL CALCULATOR ===================== -->
    <div class="tj-goal-card">
        <div class="tj-goal-top">
            <div class="tj-goal-form">
                <div class="fg">
                    <label for="tj-goal-month">Month</label>
                    <input type="month" id="tj-goal-month">
                </div>
                <div class="fg">
                    <label for="tj-goal-amount">Monthly target (₹)</label>
                    <input type="number" id="tj-goal-amount" placeholder="e.g. 2000" min="0" step="0.01">
                    <div class="fielderr" id="tj-goal-err">Please enter a valid target amount.</div>
                </div>
                <button class="btn" onclick="saveGoal()">Save Goal</button>
            </div>
            <div class="tj-gauge-wrap">
                <canvas id="tj-gauge" width="180" height="110"></canvas>
                <div class="tj-gauge-pct" id="tj-gauge-pct">0%</div>
                <div class="tj-gauge-label">of monthly goal</div>
            </div>
        </div>
        <div class="tj-progress-bar"><div class="tj-progress-fill" id="tj-progress-fill" style="width:0%"></div></div>
        <div class="tj-goal-stats">
            <div class="tj-goal-stat"><div class="l">Target</div><div class="v" id="tj-stat-target">—</div></div>
            <div class="tj-goal-stat"><div class="l">Achieved</div><div class="v" id="tj-stat-achieved">—</div></div>
            <div class="tj-goal-stat"><div class="l">Remaining</div><div class="v" id="tj-stat-remaining">—</div></div>
            <div class="tj-goal-stat"><div class="l">Achievement</div><div class="v" id="tj-stat-pct">—</div></div>
        </div>
    </div>

    <!-- ===================== TOOLBAR / FILTERS ===================== -->
    <div class="tj-toolbar">
        <div class="tj-filter-pills" id="tj-filter-pills">
            <button data-f="today" class="active">Today</button>
            <button data-f="week">This Week</button>
            <button data-f="month">This Month</button>
            <button data-f="prevmonth">Previous Month</button>
            <button data-f="year">This Year</button>
            <button data-f="custom">Custom Range</button>
        </div>
        <div class="tj-custom-range" id="tj-custom-range">
            <input type="date" id="tj-range-from"> <span style="color:var(--ink3)">to</span> <input type="date" id="tj-range-to">
            <button class="btn sm" onclick="applyCustomRange()">Apply</button>
        </div>
        <div class="tj-view-toggle">
            <button data-v="monthly" class="active" onclick="setPerfView('monthly')">Monthly</button>
            <button data-v="yearly" onclick="setPerfView('yearly')">Yearly</button>
        </div>
    </div>

    <div style="display:flex; justify-content:flex-end; margin-bottom:16px;">
        <button class="btn" onclick="openEntryModal()">+ Add Daily Entry</button>
    </div>

    <!-- ===================== TABS: Overview / Charts / Entries / Journal ===================== -->
    <div class="tj-tabs" id="tj-tabs">
        <button data-tab="overview" class="active">📊 Overview</button>
        <button data-tab="entries">🧾 Entries</button>
        <button data-tab="journal">📔 Journal</button>
    </div>

    <!-- ---------- OVERVIEW / CHARTS PANEL ---------- -->
    <div class="tj-panel active" id="panel-overview">
        <div class="tj-charts-grid">
            <div class="tj-chart-card"><h3>Daily Profit / Loss</h3><canvas id="chart-daily"></canvas></div>
            <div class="tj-chart-card"><h3 id="chart-bar-title">Monthly Profit / Loss</h3><canvas id="chart-bar"></canvas></div>
            <div class="tj-chart-card"><h3>Goal vs Achieved</h3><canvas id="chart-goal"></canvas></div>
            <div class="tj-chart-card"><h3>Profit vs Loss Distribution</h3><canvas id="chart-dist"></canvas></div>
            <div class="tj-chart-card"><h3>Win / Loss Percentage</h3><canvas id="chart-winloss"></canvas></div>
            <div class="tj-chart-card">
                <h3>Best &amp; Worst Trading Days</h3>
                <div class="tj-best-worst">
                    <div class="tj-bw-item best"><div class="l">Best Day</div><div class="amt" id="bw-best-amt">—</div><div class="dt" id="bw-best-date">No data yet</div></div>
                    <div class="tj-bw-item worst"><div class="l">Worst Day</div><div class="amt" id="bw-worst-amt">—</div><div class="dt" id="bw-worst-date">No data yet</div></div>
                </div>
            </div>
            <div class="tj-chart-card wide">
                <h3 id="chart-summary-title">Monthly &amp; Yearly Performance Summary</h3>
                <canvas id="chart-summary"></canvas>
            </div>
        </div>
    </div>

    <!-- ---------- ENTRIES PANEL ---------- -->
    <div class="tj-panel" id="panel-entries">
        <div id="tj-entries-list"><div class="tj-loading">Loading entries…</div></div>
    </div>

    <!-- ---------- JOURNAL PANEL ---------- -->
    <div class="tj-panel" id="panel-journal">
        <div class="tj-journal-card">
            <div class="tj-journal-head">
                <div class="dt">Journal for <span id="tj-journal-date-label">today</span></div>
                <input type="date" id="tj-journal-date-picker">
            </div>
            <div class="tj-journal-hint">Cover how the day went, your trading experience, emotions/mindset, mistakes made, lessons learned, and important observations — max 15 lines.</div>
            <textarea id="tj-journal-text" maxlength="4000" placeholder="How the day went...&#10;Trading experience...&#10;Emotions / mindset...&#10;Mistakes made...&#10;Lessons learned...&#10;Important observations..." oninput="onJournalInput()"></textarea>
            <div class="tj-journal-foot">
                <div class="tj-journal-count" id="tj-journal-count">0 / 15 lines</div>
                <button class="btn" id="tj-journal-save-btn" onclick="saveJournal()">Save Journal Entry</button>
            </div>
        </div>
        <section>
            <h2>Recent Journal Entries</h2>
            <div id="tj-journal-list"><div class="tj-loading">Loading journal…</div></div>
        </section>
    </div>
    </div>
    <?php pro_footer($user); ?>
</div>

<!-- ===================== ENTRY MODAL ===================== -->
<div class="modal-overlay" id="entry-modal-overlay" onclick="if(event.target===this)closeEntryModal()">
    <div class="modal">
        <h2 id="entry-modal-title">Add Daily Entry</h2>
        <input type="hidden" id="entry-id">
        <div class="fg">
            <label for="entry-date">Date</label>
            <input type="date" id="entry-date">
            <div class="fielderr" id="entry-date-err">Please choose a date.</div>
        </div>
        <div class="fg">
            <label for="entry-status">Trading Status</label>
            <select id="entry-status" onchange="onEntryStatusChange()">
                <option value="profit">Profit</option>
                <option value="loss">Loss</option>
                <option value="breakeven">Break-even</option>
            </select>
        </div>
        <div class="fg">
            <label for="entry-amount">Profit / Loss amount (₹)</label>
            <input type="number" id="entry-amount" step="0.01" placeholder="0.00">
            <div class="fielderr" id="entry-amount-err">Please enter a valid amount.</div>
        </div>
        <div class="fg">
            <label for="entry-notes">Notes (optional)</label>
            <textarea id="entry-notes" maxlength="500" placeholder="What happened, setup, symbol, etc."></textarea>
        </div>
        <div class="modal-actions">
            <button class="btn ghost" onclick="closeEntryModal()">Cancel</button>
            <button class="btn" onclick="submitEntry()">Save Entry</button>
        </div>
    </div>
</div>

<script src="js/api-client.js?v=3"></script>
<script>
// ═══════════════════════════════════════════════════════════════
// STATE
// ═══════════════════════════════════════════════════════════════
let allEntries = [];      // every trading_entries row for this user
let allGoals = [];        // every trading_goals row for this user
let allJournal = [];      // every trading_journal row for this user
let currentFilter = 'today';
let customFrom = null, customTo = null;
let perfView = 'monthly'; // 'monthly' | 'yearly'
let charts = {};

function esc(s) { return (s || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;'); }
function fmtMoney(n) {
    const v = Number(n) || 0;
    const sign = v < 0 ? '-' : '';
    return sign + '₹' + Math.abs(v).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}
function todayStr() { const d = new Date(); return d.toISOString().slice(0,10); }
function monthStr(d) { return d.toISOString().slice(0,7); }

// ═══════════════════════════════════════════════════════════════
// LOAD
// ═══════════════════════════════════════════════════════════════
async function loadAll() {
    document.getElementById('tj-goal-month').value = monthStr(new Date());
    document.getElementById('tj-journal-date-picker').value = todayStr();

    try {
        const [entriesRes, goalsRes, journalRes] = await Promise.all([
            Taskvel.request('/api/trading_journal.php?action=list-entries'),
            Taskvel.request('/api/trading_journal.php?action=list-goals'),
            Taskvel.request('/api/trading_journal.php?action=list-journal'),
        ]);
        allEntries = entriesRes.entries || [];
        allGoals = goalsRes.goals || [];
        allJournal = journalRes.journal || [];
    } catch (e) {
        toast(e.message || 'Could not load trading journal data');
        allEntries = []; allGoals = []; allJournal = [];
    }

    renderGoalSection();
    renderAll();
    await loadJournalForDate(todayStr());
    renderJournalList();
}

// ═══════════════════════════════════════════════════════════════
// FILTER HELPERS
// ═══════════════════════════════════════════════════════════════
function dateRangeForFilter(filter) {
    const now = new Date(); now.setHours(0,0,0,0);
    const iso = d => d.toISOString().slice(0,10);
    let from, to;
    if (filter === 'today') { from = to = now; }
    else if (filter === 'week') {
        const day = now.getDay() === 0 ? 7 : now.getDay(); // Mon=1..Sun=7
        from = new Date(now); from.setDate(now.getDate() - (day - 1));
        to = now;
    } else if (filter === 'month') {
        from = new Date(now.getFullYear(), now.getMonth(), 1);
        to = now;
    } else if (filter === 'prevmonth') {
        from = new Date(now.getFullYear(), now.getMonth() - 1, 1);
        to = new Date(now.getFullYear(), now.getMonth(), 0);
    } else if (filter === 'year') {
        from = new Date(now.getFullYear(), 0, 1);
        to = now;
    } else if (filter === 'custom' && customFrom && customTo) {
        return { from: customFrom, to: customTo };
    } else {
        from = new Date(now.getFullYear(), now.getMonth(), 1);
        to = now;
    }
    return { from: iso(from), to: iso(to) };
}

function entriesInRange(from, to) {
    return allEntries.filter(e => e.entry_date >= from && e.entry_date <= to);
}

// ═══════════════════════════════════════════════════════════════
// FILTER PILLS
// ═══════════════════════════════════════════════════════════════
document.getElementById('tj-filter-pills').addEventListener('click', (e) => {
    const btn = e.target.closest('button[data-f]');
    if (!btn) return;
    document.querySelectorAll('#tj-filter-pills button').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    currentFilter = btn.dataset.f;
    document.getElementById('tj-custom-range').classList.toggle('show', currentFilter === 'custom');
    if (currentFilter !== 'custom') renderAll();
});
function applyCustomRange() {
    const from = document.getElementById('tj-range-from').value;
    const to = document.getElementById('tj-range-to').value;
    if (!from || !to) { toast('Please pick both a start and end date'); return; }
    if (from > to) { toast('Start date must be before end date'); return; }
    customFrom = from; customTo = to;
    renderAll();
}
function setPerfView(view) {
    perfView = view;
    document.querySelectorAll('.tj-view-toggle button').forEach(b => b.classList.toggle('active', b.dataset.v === view));
    renderAll();
}

// ═══════════════════════════════════════════════════════════════
// TABS
// ═══════════════════════════════════════════════════════════════
document.getElementById('tj-tabs').addEventListener('click', (e) => {
    const btn = e.target.closest('button[data-tab]');
    if (!btn) return;
    document.querySelectorAll('#tj-tabs button').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    document.querySelectorAll('.tj-panel').forEach(p => p.classList.remove('active'));
    document.getElementById('panel-' + btn.dataset.tab).classList.add('active');
});

// ═══════════════════════════════════════════════════════════════
// MASTER RENDER
// ═══════════════════════════════════════════════════════════════
function renderAll() {
    const { from, to } = dateRangeForFilter(currentFilter);
    const scoped = entriesInRange(from, to);
    renderSummaryCards(scoped);
    renderEntriesList(scoped);
    renderCharts(scoped);
}

// ═══════════════════════════════════════════════════════════════
// GOAL SECTION
// ═══════════════════════════════════════════════════════════════
function currentGoalMonth() { return document.getElementById('tj-goal-month').value || monthStr(new Date()); }

function renderGoalSection() {
    const month = currentGoalMonth();
    const goal = allGoals.find(g => g.month === month);
    const target = goal ? goal.target_amount : 0;
    document.getElementById('tj-goal-amount').value = target || '';

    const monthEntries = allEntries.filter(e => e.entry_date.slice(0,7) === month);
    const achieved = monthEntries.reduce((s, e) => s + Number(e.pnl_amount), 0);
    const remaining = Math.max(target - achieved, 0);
    const pct = target > 0 ? Math.max(0, Math.min(100, (achieved / target) * 100)) : 0;

    document.getElementById('tj-stat-target').textContent = fmtMoney(target);
    document.getElementById('tj-stat-achieved').textContent = fmtMoney(achieved);
    document.getElementById('tj-stat-remaining').textContent = fmtMoney(remaining);
    document.getElementById('tj-stat-pct').textContent = pct.toFixed(1) + '%';
    document.getElementById('tj-gauge-pct').textContent = pct.toFixed(0) + '%';

    const fill = document.getElementById('tj-progress-fill');
    fill.style.width = pct + '%';
    fill.classList.toggle('full', pct >= 100);

    drawGauge(pct);
}

function drawGauge(pct) {
    const ctx = document.getElementById('tj-gauge');
    const styles = getComputedStyle(document.documentElement);
    const accent = styles.getPropertyValue('--accent').trim() || '#4f46e5';
    const good = styles.getPropertyValue('--good').trim() || '#16a34a';
    const track = styles.getPropertyValue('--bg-sunk').trim() || '#eef0f3';
    if (charts.gauge) charts.gauge.destroy();
    charts.gauge = new Chart(ctx, {
        type: 'doughnut',
        data: {
            datasets: [{
                data: [pct, 100 - pct],
                backgroundColor: [pct >= 100 ? good : accent, track],
                borderWidth: 0,
            }]
        },
        options: {
            circumference: 180, rotation: 270, cutout: '75%',
            plugins: { legend: { display: false }, tooltip: { enabled: false } },
            animation: { duration: 500 },
        }
    });
}

async function saveGoal() {
    const month = currentGoalMonth();
    const amtInput = document.getElementById('tj-goal-amount');
    const amount = parseFloat(amtInput.value);
    const err = document.getElementById('tj-goal-err');
    if (isNaN(amount) || amount < 0) {
        amtInput.classList.add('err'); err.classList.add('show');
        return;
    }
    amtInput.classList.remove('err'); err.classList.remove('show');
    try {
        await Taskvel.request('/api/trading_journal.php?action=save-goal', { method: 'POST', body: { month, target_amount: amount } });
        const existing = allGoals.find(g => g.month === month);
        if (existing) existing.target_amount = amount;
        else allGoals.push({ month, target_amount: amount });
        renderGoalSection();
        renderAll();
        toast('Monthly goal saved');
    } catch (e) {
        toast(e.message || 'Could not save goal');
    }
}
document.getElementById('tj-goal-month').addEventListener('change', () => { renderGoalSection(); renderAll(); });

// ═══════════════════════════════════════════════════════════════
// SUMMARY CARDS
// ═══════════════════════════════════════════════════════════════
function renderSummaryCards(scoped) {
    const month = currentGoalMonth();
    const goal = allGoals.find(g => g.month === month);
    const target = goal ? goal.target_amount : 0;
    const monthEntries = allEntries.filter(e => e.entry_date.slice(0,7) === month);
    const achieved = monthEntries.reduce((s, e) => s + Number(e.pnl_amount), 0);
    const remaining = Math.max(target - achieved, 0);
    const pct = target > 0 ? Math.max(0, Math.min(999, (achieved / target) * 100)) : 0;

    const totalDays = scoped.length;
    const profitDays = scoped.filter(e => e.status === 'profit').length;
    const lossDays = scoped.filter(e => e.status === 'loss').length;
    const winRate = totalDays > 0 ? (profitDays / totalDays) * 100 : 0;
    const currentPL = scoped.reduce((s, e) => s + Number(e.pnl_amount), 0);

    const cards = document.querySelectorAll('#tj-cards .tj-card .tj-card-value');
    cards[0].textContent = fmtMoney(target);
    cards[1].textContent = fmtMoney(currentPL);
    cards[1].parentElement.classList.toggle('pos', currentPL > 0);
    cards[1].parentElement.classList.toggle('neg', currentPL < 0);
    cards[2].textContent = fmtMoney(remaining);
    cards[3].textContent = pct.toFixed(1) + '%';
    cards[4].textContent = totalDays;
    cards[5].textContent = profitDays;
    cards[6].textContent = lossDays;
    cards[7].textContent = winRate.toFixed(1) + '%';
}

// ═══════════════════════════════════════════════════════════════
// CHARTS
// ═══════════════════════════════════════════════════════════════
function chartColors() {
    const s = getComputedStyle(document.documentElement);
    return {
        accent: s.getPropertyValue('--accent').trim(),
        good: s.getPropertyValue('--good').trim(),
        bad: s.getPropertyValue('--bad').trim(),
        ink3: s.getPropertyValue('--ink3').trim(),
        line: s.getPropertyValue('--line').trim(),
        ink: s.getPropertyValue('--ink').trim(),
    };
}
function destroyChart(key) { if (charts[key]) { charts[key].destroy(); charts[key] = null; } }

function renderCharts(scoped) {
    const c = chartColors();
    const gridOpts = { grid: { color: c.line }, ticks: { color: c.ink3, font: { size: 10 } } };

    // ---- Daily P/L line chart ----
    const sortedDaily = [...scoped].sort((a,b) => a.entry_date.localeCompare(b.entry_date));
    const dailyTotals = {};
    sortedDaily.forEach(e => { dailyTotals[e.entry_date] = (dailyTotals[e.entry_date] || 0) + Number(e.pnl_amount); });
    const dailyLabels = Object.keys(dailyTotals);
    const dailyValues = Object.values(dailyTotals);
    destroyChart('daily');
    charts.daily = new Chart(document.getElementById('chart-daily'), {
        type: 'line',
        data: { labels: dailyLabels, datasets: [{ label: 'P/L', data: dailyValues, borderColor: c.accent,
            backgroundColor: c.accent + '22', fill: true, tension: .3, pointRadius: 3 }] },
        options: chartBaseOptions(gridOpts, dailyLabels.length === 0)
    });

    // ---- Monthly bar chart (last 12 months of ALL entries, not just scoped) ----
    const monthly = {};
    allEntries.forEach(e => { const m = e.entry_date.slice(0,7); monthly[m] = (monthly[m] || 0) + Number(e.pnl_amount); });
    const monthKeys = Object.keys(monthly).sort().slice(-12);
    document.getElementById('chart-bar-title').textContent = perfView === 'yearly' ? 'Yearly Profit / Loss' : 'Monthly Profit / Loss';
    let barLabels, barValues;
    if (perfView === 'yearly') {
        const yearly = {};
        allEntries.forEach(e => { const y = e.entry_date.slice(0,4); yearly[y] = (yearly[y] || 0) + Number(e.pnl_amount); });
        barLabels = Object.keys(yearly).sort();
        barValues = barLabels.map(y => yearly[y]);
    } else {
        barLabels = monthKeys;
        barValues = monthKeys.map(m => monthly[m]);
    }
    destroyChart('bar');
    charts.bar = new Chart(document.getElementById('chart-bar'), {
        type: 'bar',
        data: { labels: barLabels, datasets: [{ label: 'P/L', data: barValues,
            backgroundColor: barValues.map(v => v >= 0 ? c.good : c.bad) }] },
        options: chartBaseOptions(gridOpts, barLabels.length === 0)
    });

    // ---- Goal vs Achieved ----
    const goalLabels = allGoals.map(g => g.month).sort();
    const targetVals = goalLabels.map(m => allGoals.find(g => g.month === m).target_amount);
    const achievedVals = goalLabels.map(m => allEntries.filter(e => e.entry_date.slice(0,7) === m).reduce((s,e) => s + Number(e.pnl_amount), 0));
    destroyChart('goal');
    charts.goal = new Chart(document.getElementById('chart-goal'), {
        type: 'bar',
        data: { labels: goalLabels, datasets: [
            { label: 'Target', data: targetVals, backgroundColor: c.ink3 + '55' },
            { label: 'Achieved', data: achievedVals, backgroundColor: c.accent },
        ] },
        options: chartBaseOptions(gridOpts, goalLabels.length === 0)
    });

    // ---- Profit vs Loss distribution (pie) ----
    const totalProfit = scoped.filter(e => e.status === 'profit').reduce((s,e) => s + Number(e.pnl_amount), 0);
    const totalLoss = Math.abs(scoped.filter(e => e.status === 'loss').reduce((s,e) => s + Number(e.pnl_amount), 0));
    destroyChart('dist');
    charts.dist = new Chart(document.getElementById('chart-dist'), {
        type: 'pie',
        data: { labels: ['Profit', 'Loss'], datasets: [{ data: [totalProfit, totalLoss], backgroundColor: [c.good, c.bad] }] },
        options: { plugins: { legend: { position: 'bottom', labels: { color: c.ink3, font: { size: 11 } } } },
            responsive: true, maintainAspectRatio: false }
    });

    // ---- Win/Loss percentage (doughnut) ----
    const wins = scoped.filter(e => e.status === 'profit').length;
    const losses = scoped.filter(e => e.status === 'loss').length;
    const breakeven = scoped.filter(e => e.status === 'breakeven').length;
    destroyChart('winloss');
    charts.winloss = new Chart(document.getElementById('chart-winloss'), {
        type: 'doughnut',
        data: { labels: ['Wins', 'Losses', 'Break-even'], datasets: [{ data: [wins, losses, breakeven], backgroundColor: [c.good, c.bad, c.ink3] }] },
        options: { plugins: { legend: { position: 'bottom', labels: { color: c.ink3, font: { size: 11 } } } },
            responsive: true, maintainAspectRatio: false }
    });

    // ---- Best / Worst days ----
    if (scoped.length) {
        const best = scoped.reduce((a,b) => Number(a.pnl_amount) >= Number(b.pnl_amount) ? a : b);
        const worst = scoped.reduce((a,b) => Number(a.pnl_amount) <= Number(b.pnl_amount) ? a : b);
        document.getElementById('bw-best-amt').textContent = fmtMoney(best.pnl_amount);
        document.getElementById('bw-best-date').textContent = best.entry_date;
        document.getElementById('bw-worst-amt').textContent = fmtMoney(worst.pnl_amount);
        document.getElementById('bw-worst-date').textContent = worst.entry_date;
    } else {
        document.getElementById('bw-best-amt').textContent = '—';
        document.getElementById('bw-best-date').textContent = 'No data yet';
        document.getElementById('bw-worst-amt').textContent = '—';
        document.getElementById('bw-worst-date').textContent = 'No data yet';
    }

    // ---- Monthly & Yearly performance summary (combo, last 12 periods) ----
    document.getElementById('chart-summary-title').textContent = perfView === 'yearly' ? 'Yearly Performance Summary' : 'Monthly Performance Summary';
    destroyChart('summary');
    charts.summary = new Chart(document.getElementById('chart-summary'), {
        type: 'bar',
        data: { labels: barLabels, datasets: [{ label: 'Net P/L', data: barValues, backgroundColor: barValues.map(v => v >= 0 ? c.good : c.bad) }] },
        options: chartBaseOptions(gridOpts, barLabels.length === 0)
    });
}

function chartBaseOptions(gridOpts, isEmpty) {
    return {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: { x: gridOpts, y: gridOpts },
    };
}

// ═══════════════════════════════════════════════════════════════
// ENTRIES LIST + CRUD
// ═══════════════════════════════════════════════════════════════
function renderEntriesList(scoped) {
    const list = document.getElementById('tj-entries-list');
    if (!scoped.length) {
        list.innerHTML = `<div class="tj-empty"><span class="ic">📭</span>No trading entries in this range yet.<br>Click "+ Add Daily Entry" to log your first trade.</div>`;
        return;
    }
    const sorted = [...scoped].sort((a,b) => b.entry_date.localeCompare(a.entry_date) || b.id - a.id);
    list.innerHTML = sorted.map(e => `
        <div class="tj-entry-row">
            <div class="tj-entry-dot ${e.status}"></div>
            <div class="tj-entry-body">
                <div class="tj-entry-date">${esc(e.entry_date)}</div>
                ${e.notes ? `<div class="tj-entry-notes">${esc(e.notes)}</div>` : ''}
            </div>
            <div class="tj-entry-amt ${e.status}">${fmtMoney(e.pnl_amount)}</div>
            <div class="tj-entry-actions">
                <button title="Edit" onclick="editEntry(${e.id})">✎</button>
                <button title="Delete" onclick="deleteEntry(${e.id})">🗑</button>
            </div>
        </div>
    `).join('');
}

function openEntryModal(entry) {
    document.getElementById('entry-modal-title').textContent = entry ? 'Edit Daily Entry' : 'Add Daily Entry';
    document.getElementById('entry-id').value = entry ? entry.id : '';
    document.getElementById('entry-date').value = entry ? entry.entry_date : todayStr();
    document.getElementById('entry-status').value = entry ? entry.status : 'profit';
    document.getElementById('entry-amount').value = entry ? entry.pnl_amount : '';
    document.getElementById('entry-notes').value = entry ? (entry.notes || '') : '';
    clearEntryErrors();
    document.getElementById('entry-modal-overlay').classList.add('open');
}
function closeEntryModal() { document.getElementById('entry-modal-overlay').classList.remove('open'); }
function clearEntryErrors() {
    ['entry-date','entry-amount'].forEach(id => document.getElementById(id).classList.remove('err'));
    ['entry-date-err','entry-amount-err'].forEach(id => document.getElementById(id).classList.remove('show'));
}
function onEntryStatusChange() {
    if (document.getElementById('entry-status').value === 'breakeven') document.getElementById('entry-amount').value = 0;
}
function editEntry(id) {
    const entry = allEntries.find(e => e.id === id);
    if (entry) openEntryModal(entry);
}
async function deleteEntry(id) {
    if (!confirm('Delete this trading entry? This cannot be undone.')) return;
    try {
        await Taskvel.request(`/api/trading_journal.php?action=delete-entry&id=${id}`, { method: 'DELETE' });
        allEntries = allEntries.filter(e => e.id !== id);
        renderGoalSection();
        renderAll();
        toast('Entry deleted');
    } catch (e) {
        toast(e.message || 'Could not delete entry');
    }
}
async function submitEntry() {
    clearEntryErrors();
    const id = document.getElementById('entry-id').value;
    const date = document.getElementById('entry-date').value;
    const status = document.getElementById('entry-status').value;
    const amountRaw = document.getElementById('entry-amount').value;
    const notes = document.getElementById('entry-notes').value;

    let ok = true;
    if (!date) { document.getElementById('entry-date').classList.add('err'); document.getElementById('entry-date-err').classList.add('show'); ok = false; }
    if (amountRaw === '' || isNaN(parseFloat(amountRaw))) { document.getElementById('entry-amount').classList.add('err'); document.getElementById('entry-amount-err').classList.add('show'); ok = false; }
    if (!ok) return;

    const amount = parseFloat(amountRaw);
    const payload = { id: id ? parseInt(id) : undefined, entry_date: date, status, pnl_amount: amount, notes };
    try {
        const res = await Taskvel.request('/api/trading_journal.php?action=save-entry', { method: 'POST', body: payload });
        if (id) {
            const existing = allEntries.find(e => e.id === parseInt(id));
            Object.assign(existing, { entry_date: date, status, pnl_amount: amount, notes });
        } else {
            allEntries.push({ id: res.id, entry_date: date, status, pnl_amount: amount, notes });
        }
        closeEntryModal();
        renderGoalSection();
        renderAll();
        toast(id ? 'Entry updated' : 'Entry added');
    } catch (e) {
        toast(e.message || 'Could not save entry');
    }
}

// ═══════════════════════════════════════════════════════════════
// JOURNAL
// ═══════════════════════════════════════════════════════════════
function onJournalInput() {
    const ta = document.getElementById('tj-journal-text');
    let lines = ta.value.split('\n');
    if (lines.length > 15) {
        lines = lines.slice(0, 15);
        ta.value = lines.join('\n');
    }
    const countEl = document.getElementById('tj-journal-count');
    countEl.textContent = `${lines.length} / 15 lines`;
    countEl.classList.toggle('over', lines.length >= 15);
}
async function loadJournalForDate(date) {
    document.getElementById('tj-journal-date-label').textContent = date === todayStr() ? 'today' : date;
    try {
        const res = await Taskvel.request(`/api/trading_journal.php?action=journal&date=${date}`);
        document.getElementById('tj-journal-text').value = res.journal ? res.journal.content : '';
    } catch (e) {
        document.getElementById('tj-journal-text').value = '';
    }
    onJournalInput();
}
document.getElementById('tj-journal-date-picker').addEventListener('change', (e) => loadJournalForDate(e.target.value));

async function saveJournal() {
    const date = document.getElementById('tj-journal-date-picker').value || todayStr();
    const content = document.getElementById('tj-journal-text').value.trim();
    if (!content) { toast('Write something before saving'); return; }
    const btn = document.getElementById('tj-journal-save-btn');
    btn.disabled = true; btn.textContent = 'Saving…';
    try {
        await Taskvel.request('/api/trading_journal.php?action=save-journal', { method: 'POST', body: { date, content } });
        const existing = allJournal.find(j => j.entry_date === date);
        if (existing) existing.content = content;
        else allJournal.push({ entry_date: date, content });
        renderJournalList();
        toast('Journal entry saved');
    } catch (e) {
        toast(e.message || 'Could not save journal entry');
    } finally {
        btn.disabled = false; btn.textContent = 'Save Journal Entry';
    }
}
function renderJournalList() {
    const list = document.getElementById('tj-journal-list');
    if (!allJournal.length) {
        list.innerHTML = `<div class="tj-empty"><span class="ic">📔</span>No journal entries yet — write about today's trading session above.</div>`;
        return;
    }
    const sorted = [...allJournal].sort((a,b) => b.entry_date.localeCompare(a.entry_date));
    list.innerHTML = sorted.slice(0, 30).map(j => `
        <div class="tj-journal-list-item" onclick="jumpToJournal('${j.entry_date}')">
            <div class="d">${esc(j.entry_date)}</div>
            <div class="p">${esc((j.content || '').split('\\n')[0])}</div>
        </div>
    `).join('');
}
function jumpToJournal(date) {
    document.getElementById('tj-journal-date-picker').value = date;
    loadJournalForDate(date);
    document.querySelectorAll('#tj-tabs button').forEach(b => b.classList.toggle('active', b.dataset.tab === 'journal'));
    document.querySelectorAll('.tj-panel').forEach(p => p.classList.toggle('active', p.id === 'panel-journal'));
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

loadAll();
</script>
</body>
</html>