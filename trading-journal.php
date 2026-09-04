<?php
// File: trading-journal.php  (app root, next to my-work.php)
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
    /* ===================== HERO ===================== */
    .tj-hero { position:relative; border-radius:var(--r-lg); padding:26px 26px 22px; margin-bottom:22px; overflow:hidden;
        background:linear-gradient(135deg, var(--accent) 0%, var(--accent-2, var(--accent)) 100%); color:#fff; }
    .tj-hero::before { content:''; position:absolute; inset:0; background:
        radial-gradient(circle at 85% -10%, rgba(255,255,255,.22), transparent 55%),
        radial-gradient(circle at 5% 120%, rgba(255,255,255,.14), transparent 50%); pointer-events:none; }
    .tj-hero-row { position:relative; display:flex; justify-content:space-between; align-items:flex-end; gap:16px; flex-wrap:wrap; }
    .tj-hero h1 { font-family:var(--font-display); font-size:24px; font-weight:800; letter-spacing:-.4px; margin:0 0 6px; }
    .tj-hero p { opacity:.92; font-size:13px; margin:0; max-width:520px; line-height:1.5; }
    .tj-hero-badges { display:flex; gap:8px; flex-wrap:wrap; }
    .tj-hero-chip { background:rgba(255,255,255,.16); border:1px solid rgba(255,255,255,.3); backdrop-filter:blur(4px);
        padding:8px 14px; border-radius:12px; text-align:center; min-width:90px; }
    .tj-hero-chip .v { font-family:var(--font-display); font-weight:800; font-size:16px; }
    .tj-hero-chip .l { font-size:9.5px; opacity:.85; text-transform:uppercase; letter-spacing:.5px; margin-top:1px; }

    /* ===================== BADGES / ACHIEVEMENTS ===================== */
    .tj-badges-row { display:flex; gap:8px; flex-wrap:wrap; margin-bottom:22px; }
    .tj-badge { display:flex; align-items:center; gap:6px; background:var(--bg-elev); border:1px solid var(--line);
        border-radius:999px; padding:6px 13px 6px 8px; font-size:11.5px; font-weight:600; color:var(--ink2); box-shadow:var(--shadow-sm);
        animation:tj-pop .35s var(--ease) both; }
    .tj-badge .ic { font-size:14px; }
    @keyframes tj-pop { from { transform:scale(.85); opacity:0; } to { transform:scale(1); opacity:1; } }
    .tj-badge.locked { opacity:.4; filter:grayscale(1); }

    /* ===================== SUMMARY CARDS ===================== */
    .tj-cards { display:grid; grid-template-columns:repeat(4,1fr); gap:10px; margin-bottom:24px; }
    @media (max-width:900px) { .tj-cards { grid-template-columns:repeat(2,1fr); } }
    @media (max-width:480px) { .tj-cards { grid-template-columns:1fr 1fr; gap:8px; } }
    .tj-card { background:var(--bg-elev); border:1px solid var(--line); border-left:3px solid var(--line2); border-radius:var(--r-lg);
        padding:14px 15px; box-shadow:var(--shadow-sm); transition:transform .2s var(--ease), box-shadow .2s ease, border-color .2s ease; position:relative; overflow:hidden; }
    .tj-card:hover { transform:translateY(-3px); box-shadow:var(--shadow); }
    .tj-card .tj-card-label { font-family:var(--font-display); font-size:10px; font-weight:700; text-transform:uppercase;
        letter-spacing:.5px; color:var(--ink3); margin-bottom:6px; display:flex; align-items:center; gap:5px; }
    .tj-card .tj-card-value { font-family:var(--font-display); font-size:20px; font-weight:700; letter-spacing:-.3px; }
    .tj-card .tj-card-sub { font-size:10.5px; color:var(--ink3); margin-top:3px; }
    .tj-card canvas.tj-spark { width:100%; height:26px; margin-top:6px; display:block; }
    .tj-card.pos { border-left-color:var(--good); }
    .tj-card.pos .tj-card-value { color:var(--good); }
    .tj-card.neg { border-left-color:var(--bad); }
    .tj-card.neg .tj-card-value { color:var(--bad); }
    .tj-card.accent { border-left-color:var(--accent); }
    .tj-card.accent .tj-card-value { color:var(--accent); }
    .tj-card-compare { font-size:10.5px; font-weight:700; margin-top:4px; display:inline-flex; align-items:center; gap:3px; padding:1px 7px; border-radius:8px; }
    .tj-card-compare.up { color:var(--good); background:var(--good-soft); }
    .tj-card-compare.down { color:var(--bad); background:var(--bad-soft); }
    .tj-skel { display:inline-block; width:70%; height:18px; border-radius:6px; background:linear-gradient(90deg,var(--bg-sunk) 25%,var(--line) 37%,var(--bg-sunk) 63%);
        background-size:400% 100%; animation:tj-shimmer 1.4s ease infinite; }
    @keyframes tj-shimmer { 0% { background-position:100% 50%; } 100% { background-position:0 50%; } }

    /* ===================== GOAL CALCULATOR ===================== */
    .tj-goal-card { background:var(--bg-elev); border:1px solid var(--line); border-radius:var(--r-lg); padding:20px; margin-bottom:24px; box-shadow:var(--shadow-sm); }
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

    .tj-progress-bar { width:100%; height:12px; border-radius:999px; background:var(--bg-sunk); overflow:hidden; margin-top:14px; border:1px solid var(--line); position:relative; }
    .tj-progress-fill { height:100%; border-radius:999px; background:linear-gradient(90deg,var(--accent),var(--accent-2,var(--accent))); transition:width .8s cubic-bezier(.22,1,.36,1); position:relative; }
    .tj-progress-fill.full { background:linear-gradient(90deg,var(--good),#22c55e); }
    .tj-progress-fill::after { content:''; position:absolute; inset:0; background:linear-gradient(90deg,transparent,rgba(255,255,255,.35),transparent);
        animation:tj-shine 2.2s ease-in-out infinite; }
    @keyframes tj-shine { 0% { transform:translateX(-100%); } 100% { transform:translateX(100%); } }

    /* ===================== TOOLBAR / FILTERS ===================== */
    .tj-toolbar { display:flex; gap:10px; flex-wrap:wrap; align-items:center; margin-bottom:18px; }
    .tj-filter-pills { display:flex; gap:6px; flex-wrap:wrap; background:var(--bg-sunk); border:1px solid var(--line); border-radius:14px; padding:4px; }
    .tj-filter-pills button { font-family:var(--font-display); font-size:12px; font-weight:600; padding:7px 12px; border-radius:10px;
        border:1px solid transparent; background:transparent; color:var(--ink2); cursor:pointer; transition:all .15s var(--ease); white-space:nowrap; }
    .tj-filter-pills button:hover { background:var(--bg-elev); color:var(--ink); }
    .tj-filter-pills button.active { background:var(--bg-elev); color:var(--accent); border-color:var(--line); box-shadow:var(--shadow-sm); }
    .tj-custom-range { display:none; gap:8px; align-items:center; }
    .tj-custom-range.show { display:flex; }
    .tj-custom-range input { padding:8px 10px; border:1px solid var(--line2); border-radius:var(--r-sm); font-size:12.5px; background:var(--bg-elev); color:var(--ink); }
    .tj-view-toggle { display:flex; gap:4px; }
    .tj-view-toggle button { font-family:var(--font-display); font-size:11.5px; font-weight:700; padding:7px 12px; border-radius:9px;
        border:1px solid var(--line2); background:var(--bg-elev); color:var(--ink2); cursor:pointer; }
    .tj-view-toggle button.active { background:var(--accent); color:var(--on-accent,#fff); border-color:var(--accent); }
    .tj-toolbar-actions { display:flex; gap:8px; margin-left:auto; }
    .tj-icon-btn { display:inline-flex; align-items:center; gap:6px; font-family:var(--font-display); font-size:12px; font-weight:700;
        padding:8px 13px; border-radius:10px; border:1px solid var(--line2); background:var(--bg-elev); color:var(--ink2); cursor:pointer; transition:all .15s var(--ease); }
    .tj-icon-btn:hover { background:var(--bg-sunk); color:var(--ink); transform:translateY(-1px); }

    /* ===================== TABS ===================== */
    .tj-tabs { display:flex; gap:3px; margin:0 0 18px; flex-wrap:wrap; padding:4px; background:var(--bg-sunk); border:1px solid var(--line); border-radius:14px; width:fit-content; }
    .tj-tabs button { font-family:var(--font-display); font-size:12.5px; font-weight:600; padding:8px 14px; border-radius:10px; border:1px solid transparent;
        background:transparent; color:var(--ink2); cursor:pointer; transition:all .18s var(--ease); }
    .tj-tabs button:hover { background:var(--bg-elev); color:var(--ink); }
    .tj-tabs button.active { background:var(--bg-elev); color:var(--accent); border-color:var(--line); box-shadow:var(--shadow-sm); }
    .tj-panel { display:none; animation:tj-fadein .25s var(--ease); }
    .tj-panel.active { display:block; }
    @keyframes tj-fadein { from { opacity:0; transform:translateY(6px); } to { opacity:1; transform:translateY(0); } }

    /* ===================== CHARTS ===================== */
    .tj-charts-grid { display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:10px; }
    @media (max-width:900px) { .tj-charts-grid { grid-template-columns:1fr; } }
    .tj-chart-card { background:var(--bg-elev); border:1px solid var(--line); border-radius:var(--r-lg); padding:16px; box-shadow:var(--shadow-sm); }
    .tj-chart-card h3 { font-family:var(--font-display); font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:.5px; color:var(--ink3); margin:0 0 12px; display:flex; align-items:center; justify-content:space-between; }
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

    /* ===================== ANALYTICS TAB ===================== */
    .tj-metrics-row { display:grid; grid-template-columns:repeat(6,1fr); gap:10px; margin-bottom:16px; }
    @media (max-width:1000px) { .tj-metrics-row { grid-template-columns:repeat(3,1fr); } }
    @media (max-width:560px) { .tj-metrics-row { grid-template-columns:1fr 1fr; } }
    .tj-metric { background:var(--bg-elev); border:1px solid var(--line); border-radius:var(--r-sm); padding:12px; text-align:center; box-shadow:var(--shadow-sm); }
    .tj-metric .l { font-size:9.5px; text-transform:uppercase; letter-spacing:.4px; color:var(--ink3); font-family:var(--font-display); font-weight:700; }
    .tj-metric .v { font-family:var(--font-display); font-size:17px; font-weight:800; margin-top:5px; }
    .tj-metric .v.pos { color:var(--good); } .tj-metric .v.neg { color:var(--bad); }

    .tj-compare-grid { display:grid; grid-template-columns:1fr 1fr; gap:14px; margin-bottom:16px; }
    @media (max-width:700px) { .tj-compare-grid { grid-template-columns:1fr; } }
    .tj-compare-card { background:var(--bg-elev); border:1px solid var(--line); border-radius:var(--r-lg); padding:16px; box-shadow:var(--shadow-sm); }
    .tj-compare-card h4 { font-family:var(--font-display); font-size:11px; text-transform:uppercase; letter-spacing:.5px; color:var(--ink3); margin:0 0 10px; }
    .tj-compare-row { display:flex; justify-content:space-between; align-items:center; padding:6px 0; border-bottom:1px dashed var(--line); font-size:12.5px; }
    .tj-compare-row:last-child { border-bottom:none; }
    .tj-compare-row .lbl { color:var(--ink3); }
    .tj-compare-row .val { font-family:var(--font-display); font-weight:700; }

    .tj-table { width:100%; border-collapse:collapse; font-size:12.5px; }
    .tj-table th { text-align:left; font-family:var(--font-display); font-size:10px; text-transform:uppercase; letter-spacing:.4px; color:var(--ink3); padding:8px 10px; border-bottom:1px solid var(--line); }
    .tj-table td { padding:9px 10px; border-bottom:1px solid var(--line); }
    .tj-table tr:last-child td { border-bottom:none; }
    .tj-table td.amt.pos { color:var(--good); font-weight:700; }
    .tj-table td.amt.neg { color:var(--bad); font-weight:700; }

    /* ===================== CALENDAR HEATMAP ===================== */
    .tj-cal-head { display:flex; justify-content:space-between; align-items:center; margin-bottom:14px; }
    .tj-cal-head h3 { font-family:var(--font-display); font-size:15px; font-weight:700; margin:0; }
    .tj-cal-nav { display:flex; gap:6px; }
    .tj-cal-nav button { width:32px; height:32px; border-radius:9px; border:1px solid var(--line2); background:var(--bg-elev); color:var(--ink2); cursor:pointer; font-size:14px; }
    .tj-cal-nav button:hover { background:var(--bg-sunk); }
    .tj-cal-grid { display:grid; grid-template-columns:repeat(7,1fr); gap:6px; }
    .tj-cal-dow { text-align:center; font-family:var(--font-display); font-size:10px; color:var(--ink3); text-transform:uppercase; padding-bottom:4px; }
    .tj-cal-cell { aspect-ratio:1; border-radius:8px; display:flex; flex-direction:column; align-items:center; justify-content:center;
        font-size:11px; font-weight:700; cursor:default; border:1px solid var(--line); background:var(--bg-sunk); color:var(--ink3); transition:transform .15s var(--ease); position:relative; }
    .tj-cal-cell.has:hover { transform:scale(1.08); z-index:2; box-shadow:var(--shadow); }
    .tj-cal-cell .amt { font-size:9px; margin-top:2px; }
    .tj-cal-cell.empty { background:transparent; border:none; }
    .tj-cal-legend { display:flex; align-items:center; gap:6px; margin-top:14px; font-size:10.5px; color:var(--ink3); justify-content:flex-end; }
    .tj-cal-legend .sw { width:12px; height:12px; border-radius:3px; }

    /* ===================== RISK CALCULATOR ===================== */
    .tj-calc-grid { display:grid; grid-template-columns:1fr 1fr; gap:20px; }
    @media (max-width:800px) { .tj-calc-grid { grid-template-columns:1fr; } }
    .tj-calc-card { background:var(--bg-elev); border:1px solid var(--line); border-radius:var(--r-lg); padding:22px; box-shadow:var(--shadow-sm); }
    .tj-calc-card .fg label { font-family:var(--font-display); font-size:10.5px; color:var(--ink3); text-transform:uppercase; letter-spacing:.6px; display:block; margin-bottom:6px; font-weight:600; }
    .tj-calc-card input { width:100%; padding:10px 12px; border:1px solid var(--line2); border-radius:var(--r-sm); font-size:14px; background:var(--bg); color:var(--ink); }
    .tj-calc-result { background:linear-gradient(135deg, var(--accent), var(--accent-2,var(--accent))); color:#fff; border-radius:var(--r-lg); padding:22px; }
    .tj-calc-result .row { display:flex; justify-content:space-between; align-items:center; padding:10px 0; border-bottom:1px solid rgba(255,255,255,.2); }
    .tj-calc-result .row:last-child { border-bottom:none; }
    .tj-calc-result .lbl { font-size:12px; opacity:.9; }
    .tj-calc-result .val { font-family:var(--font-display); font-weight:800; font-size:18px; }

    /* ===================== ENTRIES ===================== */
    .tj-entries-toolbar { display:flex; gap:10px; flex-wrap:wrap; align-items:center; margin-bottom:14px; }
    .tj-entries-toolbar input[type=text] { flex:1; min-width:180px; padding:9px 12px; border:1px solid var(--line2); border-radius:var(--r-sm); background:var(--bg-elev); color:var(--ink); font-size:13px; }
    .tj-entries-toolbar select { padding:9px 12px; border:1px solid var(--line2); border-radius:var(--r-sm); background:var(--bg-elev); color:var(--ink); font-size:13px; }
    .tj-entry-row { display:flex; align-items:center; gap:12px; padding:14px 16px; background:var(--bg-elev); border:1px solid var(--line);
        border-radius:var(--r-lg); margin-bottom:8px; box-shadow:var(--shadow-sm); transition:border-color .2s ease, box-shadow .2s ease, transform .2s var(--ease); }
    .tj-entry-row:hover { transform:translateY(-1px); box-shadow:var(--shadow); border-color:var(--line2); }
    .tj-entry-dot { width:10px; height:10px; border-radius:50%; flex-shrink:0; }
    .tj-entry-dot.profit { background:var(--good); }
    .tj-entry-dot.loss { background:var(--bad); }
    .tj-entry-dot.breakeven { background:var(--ink4); }
    .tj-entry-body { flex:1; min-width:0; }
    .tj-entry-date { font-family:var(--font-display); font-size:12.5px; font-weight:700; }
    .tj-entry-tag { display:inline-block; font-size:9.5px; font-weight:700; padding:1px 7px; border-radius:6px; background:var(--accent-soft, var(--bg-sunk)); color:var(--accent); margin-left:6px; text-transform:uppercase; letter-spacing:.3px; }
    .tj-entry-notes { font-size:12px; color:var(--ink3); margin-top:2px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .tj-entry-amt { font-family:var(--font-display); font-weight:800; font-size:15px; white-space:nowrap; }
    .tj-entry-amt.profit { color:var(--good); }
    .tj-entry-amt.loss { color:var(--bad); }
    .tj-entry-amt.breakeven { color:var(--ink3); }
    .tj-entry-actions { display:flex; gap:4px; flex-shrink:0; }
    .tj-entry-actions button { width:30px; height:30px; border-radius:8px; border:1px solid var(--line2); background:var(--bg); color:var(--ink2); cursor:pointer; font-size:13px; }
    .tj-entry-actions button:hover { background:var(--bg-sunk); color:var(--ink); }
    .tj-tag-chips { display:flex; gap:6px; flex-wrap:wrap; margin-top:8px; }
    .tj-tag-chip { font-size:11px; font-weight:600; padding:5px 11px; border-radius:999px; border:1px solid var(--line2); background:var(--bg); color:var(--ink2); cursor:pointer; transition:all .15s; }
    .tj-tag-chip:hover, .tj-tag-chip.active { background:var(--accent); color:#fff; border-color:var(--accent); }

    /* ===================== JOURNAL ===================== */
    .tj-journal-card { background:var(--bg-elev); border:1px solid var(--line); border-radius:var(--r-lg); padding:18px; margin-bottom:10px; box-shadow:var(--shadow-sm); }
    .tj-journal-head { display:flex; justify-content:space-between; align-items:center; gap:10px; margin-bottom:10px; flex-wrap:wrap; }
    .tj-journal-head .dt { font-family:var(--font-display); font-weight:700; font-size:13.5px; }
    .tj-mood-picker { display:flex; gap:6px; margin-bottom:12px; flex-wrap:wrap; }
    .tj-mood-btn { font-size:19px; width:38px; height:38px; border-radius:10px; border:1px solid var(--line2); background:var(--bg); cursor:pointer; transition:all .15s var(--ease); }
    .tj-mood-btn:hover { background:var(--bg-sunk); transform:scale(1.08); }
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

    .fielderr { color:var(--bad); font-size:11.5px; margin-top:4px; display:none; }
    .fielderr.show { display:block; }
    .modal input.err, .modal textarea.err { border-color:var(--bad) !important; }

    #tj-confetti-canvas { position:fixed; inset:0; pointer-events:none; z-index:9999; }

    @media print {
        .main-nav, .tj-toolbar, .tj-tabs, .tj-goal-form button, .tj-entry-actions, #tj-add-entry-btn,
        .tj-entries-toolbar, .tj-filter-pills, .tj-view-toggle, .tj-toolbar-actions { display:none !important; }
        .tj-panel { display:block !important; }
    }
</style>
</head>
<body>
<div class="wrap">
    <?php pro_header($user, 'journal'); ?>
    <div>

    <!-- ===================== HERO ===================== -->
    <div class="tj-hero">
        <div class="tj-hero-row">
            <div>
                <h1>📈 Trading Journal &amp; P/L Dashboard</h1>
                <p>Track your monthly goal, daily profit &amp; loss, and trading psychology — all in one premium view.</p>
            </div>
            <div class="tj-hero-badges">
                <div class="tj-hero-chip"><div class="v" id="hero-streak">0</div><div class="l">Current Streak</div></div>
                <div class="tj-hero-chip"><div class="v" id="hero-winrate">0%</div><div class="l">All-Time Win Rate</div></div>
                <div class="tj-hero-chip"><div class="v" id="hero-total">₹0</div><div class="l">All-Time P/L</div></div>
            </div>
        </div>
    </div>

    <!-- ===================== ACHIEVEMENT BADGES ===================== -->
    <div class="tj-badges-row" id="tj-badges-row"></div>

    <!-- ===================== SUMMARY CARDS ===================== -->
    <div class="tj-cards" id="tj-cards">
        <div class="tj-card accent"><div class="tj-card-label">🎯 Monthly Goal</div><div class="tj-card-value"><span class="tj-skel"></span></div><canvas class="tj-spark" id="spark-0"></canvas></div>
        <div class="tj-card"><div class="tj-card-label">💰 Current P/L</div><div class="tj-card-value"><span class="tj-skel"></span></div><div id="compare-0"></div></div>
        <div class="tj-card"><div class="tj-card-label">⏳ Remaining Target</div><div class="tj-card-value"><span class="tj-skel"></span></div></div>
        <div class="tj-card accent"><div class="tj-card-label">🏆 Goal Achievement</div><div class="tj-card-value"><span class="tj-skel"></span></div><canvas class="tj-spark" id="spark-1"></canvas></div>
        <div class="tj-card"><div class="tj-card-label">📅 Trading Days</div><div class="tj-card-value"><span class="tj-skel"></span></div></div>
        <div class="tj-card pos"><div class="tj-card-label">✅ Profitable Days</div><div class="tj-card-value"><span class="tj-skel"></span></div></div>
        <div class="tj-card neg"><div class="tj-card-label">❌ Loss Days</div><div class="tj-card-value"><span class="tj-skel"></span></div></div>
        <div class="tj-card accent"><div class="tj-card-label">🎲 Win Rate</div><div class="tj-card-value"><span class="tj-skel"></span></div><canvas class="tj-spark" id="spark-2"></canvas></div>
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
        <div class="tj-toolbar-actions">
            <button class="tj-icon-btn" onclick="exportCSV()">⬇ Export CSV</button>
            <button class="tj-icon-btn" onclick="window.print()">🖨 Print Report</button>
            <button class="btn" id="tj-add-entry-btn" onclick="openEntryModal()">+ Add Daily Entry</button>
        </div>
    </div>

    <!-- ===================== TABS ===================== -->
    <div class="tj-tabs" id="tj-tabs">
        <button data-tab="overview" class="active">📊 Overview</button>
        <button data-tab="analytics">📈 Analytics</button>
        <button data-tab="calendar">🗓️ Calendar</button>
        <button data-tab="entries">🧾 Entries</button>
        <button data-tab="journal">📔 Journal</button>
        <button data-tab="calculator">🧮 Risk Calculator</button>
    </div>

    <!-- ---------- OVERVIEW PANEL ---------- -->
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

    <!-- ---------- ANALYTICS PANEL ---------- -->
    <div class="tj-panel" id="panel-analytics">
        <div class="tj-metrics-row" id="tj-metrics-row">
            <div class="tj-metric"><div class="l">Profit Factor</div><div class="v" id="m-profitfactor">—</div></div>
            <div class="tj-metric"><div class="l">Expectancy</div><div class="v" id="m-expectancy">—</div></div>
            <div class="tj-metric"><div class="l">Avg Win</div><div class="v pos" id="m-avgwin">—</div></div>
            <div class="tj-metric"><div class="l">Avg Loss</div><div class="v neg" id="m-avgloss">—</div></div>
            <div class="tj-metric"><div class="l">Best Streak</div><div class="v pos" id="m-beststreak">—</div></div>
            <div class="tj-metric"><div class="l">Max Drawdown</div><div class="v neg" id="m-drawdown">—</div></div>
        </div>
        <div class="tj-compare-grid">
            <div class="tj-compare-card">
                <h4>This Month vs Last Month</h4>
                <div class="tj-compare-row"><span class="lbl">Net P/L</span><span class="val" id="cmp-pl">—</span></div>
                <div class="tj-compare-row"><span class="lbl">Trading Days</span><span class="val" id="cmp-days">—</span></div>
                <div class="tj-compare-row"><span class="lbl">Win Rate</span><span class="val" id="cmp-winrate">—</span></div>
                <div class="tj-compare-row"><span class="lbl">Goal Achievement</span><span class="val" id="cmp-goal">—</span></div>
            </div>
            <div class="tj-chart-card" style="box-shadow:none;border:1px solid var(--line);">
                <h3>Top Setups / Tags</h3>
                <canvas id="chart-tags"></canvas>
            </div>
        </div>
        <div class="tj-charts-grid">
            <div class="tj-chart-card"><h3>Equity Curve (All-Time)</h3><canvas id="chart-equity"></canvas></div>
            <div class="tj-chart-card"><h3>Drawdown</h3><canvas id="chart-drawdown"></canvas></div>
            <div class="tj-chart-card wide">
                <h3>Weekly Performance</h3>
                <div style="overflow-x:auto;">
                <table class="tj-table" id="tj-weekly-table">
                    <thead><tr><th>Week</th><th>Trades</th><th>Win Rate</th><th>Net P/L</th></tr></thead>
                    <tbody></tbody>
                </table>
                </div>
            </div>
        </div>
    </div>

    <!-- ---------- CALENDAR PANEL ---------- -->
    <div class="tj-panel" id="panel-calendar">
        <div class="tj-chart-card">
            <div class="tj-cal-head">
                <h3 id="tj-cal-title">September 2026</h3>
                <div class="tj-cal-nav">
                    <button onclick="calNav(-1)">‹</button>
                    <button onclick="calNav(1)">›</button>
                </div>
            </div>
            <div class="tj-cal-grid" id="tj-cal-dow"></div>
            <div class="tj-cal-grid" id="tj-cal-grid" style="margin-top:6px;"></div>
            <div class="tj-cal-legend">
                <span>Loss</span>
                <span class="sw" style="background:var(--bad);"></span>
                <span class="sw" style="background:var(--bad-soft);"></span>
                <span class="sw" style="background:var(--bg-sunk);"></span>
                <span class="sw" style="background:var(--good-soft);"></span>
                <span class="sw" style="background:var(--good);"></span>
                <span>Profit</span>
            </div>
        </div>
    </div>

    <!-- ---------- ENTRIES PANEL ---------- -->
    <div class="tj-panel" id="panel-entries">
        <div class="tj-entries-toolbar">
            <input type="text" id="tj-entry-search" placeholder="🔍 Search notes or tags…" oninput="renderAll()">
            <select id="tj-entry-sort" onchange="renderAll()">
                <option value="date-desc">Newest first</option>
                <option value="date-asc">Oldest first</option>
                <option value="amt-desc">Highest P/L</option>
                <option value="amt-asc">Lowest P/L</option>
            </select>
        </div>
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
            <div class="tj-mood-picker" id="tj-mood-picker">
                <button type="button" class="tj-mood-btn" data-mood="😄 Confident" title="Confident">😄</button>
                <button type="button" class="tj-mood-btn" data-mood="😌 Calm" title="Calm">😌</button>
                <button type="button" class="tj-mood-btn" data-mood="😐 Neutral" title="Neutral">😐</button>
                <button type="button" class="tj-mood-btn" data-mood="😤 Frustrated" title="Frustrated">😤</button>
                <button type="button" class="tj-mood-btn" data-mood="😰 Anxious" title="Anxious">😰</button>
                <button type="button" class="tj-mood-btn" data-mood="😴 Tired" title="Tired">😴</button>
            </div>
            <textarea id="tj-journal-text" maxlength="4000" placeholder="How the day went...&#10;Trading experience...&#10;Emotions / mindset...&#10;Mistakes made...&#10;Lessons learned...&#10;Important observations..." oninput="onJournalInput()"></textarea>
            <div class="tj-journal-foot">
                <div class="tj-journal-count" id="tj-journal-count">0 / 15 lines</div>
                <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                    <button type="button" class="tj-icon-btn" id="tj-journal-ai-btn" onclick="aiFixJournal()">✨ AI Fix &amp; Rephrase</button>
                    <button class="btn" id="tj-journal-save-btn" onclick="saveJournal()">Save Journal Entry</button>
                </div>
            </div>
            <div id="tj-journal-ai-status" style="font-size:11.5px;color:var(--ink3);margin-top:6px;display:none;"></div>
        </div>
        <section>
            <h2>Recent Journal Entries</h2>
            <div id="tj-journal-list"><div class="tj-loading">Loading journal…</div></div>
        </section>
    </div>

    <!-- ---------- RISK CALCULATOR PANEL ---------- -->
    <div class="tj-panel" id="panel-calculator">
        <div class="tj-calc-grid">
            <div class="tj-calc-card">
                <h3 style="margin-top:0;font-family:var(--font-display);">Position Size &amp; Risk Calculator</h3>
                <p style="font-size:12.5px;color:var(--ink3);margin-top:-4px;">Work out how big a position to take so a single trade never risks more than you're comfortable with.</p>
                <div class="fg"><label>Account Balance (₹)</label><input type="number" id="calc-balance" value="10000" min="0" step="0.01" oninput="runCalc()"></div>
                <div class="fg"><label>Risk per Trade (%)</label><input type="number" id="calc-risk-pct" value="1" min="0" max="100" step="0.1" oninput="runCalc()"></div>
                <div class="fg"><label>Stop Loss Distance (₹ per unit)</label><input type="number" id="calc-sl-distance" value="2.50" min="0.0001" step="0.0001" oninput="runCalc()"></div>
                <div class="fg"><label>Reward : Risk Ratio</label><input type="number" id="calc-rr" value="2" min="0" step="0.1" oninput="runCalc()"></div>
            </div>
            <div class="tj-calc-result">
                <div class="row"><span class="lbl">Amount at Risk</span><span class="val" id="calc-risk-amt">₹0.00</span></div>
                <div class="row"><span class="lbl">Position Size (units)</span><span class="val" id="calc-position-size">0</span></div>
                <div class="row"><span class="lbl">Potential Reward</span><span class="val" id="calc-reward">₹0.00</span></div>
                <div class="row"><span class="lbl">Risk-Adjusted Balance After Loss</span><span class="val" id="calc-after-loss">₹0.00</span></div>
                <div class="row"><span class="lbl">Balance After Target Win</span><span class="val" id="calc-after-win">₹0.00</span></div>
            </div>
        </div>
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
            <label>Setup / Tag (optional)</label>
            <div class="tj-tag-chips" id="entry-tag-chips">
                <button type="button" class="tj-tag-chip" data-tag="Breakout">Breakout</button>
                <button type="button" class="tj-tag-chip" data-tag="Trend">Trend</button>
                <button type="button" class="tj-tag-chip" data-tag="Reversal">Reversal</button>
                <button type="button" class="tj-tag-chip" data-tag="Scalp">Scalp</button>
                <button type="button" class="tj-tag-chip" data-tag="News">News</button>
                <button type="button" class="tj-tag-chip" data-tag="Swing">Swing</button>
            </div>
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

<canvas id="tj-confetti-canvas"></canvas>

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
let calMonthCursor = new Date(); calMonthCursor.setDate(1);
let selectedTag = null;

function esc(s) { return (s || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;'); }
function fmtMoney(n) {
    const v = Number(n) || 0;
    const sign = v < 0 ? '-' : '';
    return sign + '₹' + Math.abs(v).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}
function todayStr() { const d = new Date(); return d.toISOString().slice(0,10); }
function monthStr(d) { return d.toISOString().slice(0,7); }
function toast(msg) { if (window.Taskvel && Taskvel.toast) Taskvel.toast(msg); else console.log(msg); }
function extractTag(notes) { const m = (notes || '').match(/^\[(.+?)\]/); return m ? m[1] : null; }
function animateNumber(el, to, fmt) {
    const from = 0; const dur = 700; const start = performance.now();
    function step(now) {
        const p = Math.min(1, (now - start) / dur);
        const eased = 1 - Math.pow(1 - p, 3);
        el.textContent = fmt(from + (to - from) * eased);
        if (p < 1) requestAnimationFrame(step);
        else el.textContent = fmt(to);
    }
    requestAnimationFrame(step);
}

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
    renderHero();
    renderBadges();
    renderCalendar();
    renderAnalytics();
    runCalc();
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
        const day = now.getDay() === 0 ? 7 : now.getDay();
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
function entriesInRange(from, to) { return allEntries.filter(e => e.entry_date >= from && e.entry_date <= to); }

// ═══════════════════════════════════════════════════════════════
// FILTER PILLS / VIEW TOGGLE / TABS
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
document.getElementById('tj-tabs').addEventListener('click', (e) => {
    const btn = e.target.closest('button[data-tab]');
    if (!btn) return;
    document.querySelectorAll('#tj-tabs button').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    document.querySelectorAll('.tj-panel').forEach(p => p.classList.remove('active'));
    document.getElementById('panel-' + btn.dataset.tab).classList.add('active');
    if (btn.dataset.tab === 'analytics') renderAnalytics();
    if (btn.dataset.tab === 'calendar') renderCalendar();
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
// HERO
// ═══════════════════════════════════════════════════════════════
function renderHero() {
    const total = allEntries.reduce((s,e) => s + Number(e.pnl_amount), 0);
    const wins = allEntries.filter(e => e.status === 'profit').length;
    const winRate = allEntries.length ? (wins / allEntries.length) * 100 : 0;
    const sorted = [...allEntries].sort((a,b) => a.entry_date.localeCompare(b.entry_date));
    let streak = 0;
    for (let i = sorted.length - 1; i >= 0; i--) {
        if (sorted[i].status === 'profit') streak++;
        else break;
    }
    document.getElementById('hero-streak').textContent = streak + (streak === 1 ? ' day' : ' days');
    document.getElementById('hero-winrate').textContent = winRate.toFixed(1) + '%';
    document.getElementById('hero-total').textContent = fmtMoney(total);
}

// ═══════════════════════════════════════════════════════════════
// BADGES / ACHIEVEMENTS
// ═══════════════════════════════════════════════════════════════
function computeStreaks(entries) {
    const sorted = [...entries].sort((a,b) => a.entry_date.localeCompare(b.entry_date));
    let cur = 0, curType = null, bestWin = 0, bestLoss = 0, run = 0, runType = null;
    sorted.forEach(e => {
        if (e.status === runType) run++;
        else { runType = e.status; run = 1; }
        if (runType === 'profit') bestWin = Math.max(bestWin, run);
        if (runType === 'loss') bestLoss = Math.max(bestLoss, run);
    });
    for (let i = sorted.length - 1; i >= 0; i--) {
        if (curType === null) { curType = sorted[i].status; cur = 1; }
        else if (sorted[i].status === curType) cur++;
        else break;
    }
    return { current: cur, currentType: curType, bestWin, bestLoss };
}
function renderBadges() {
    const month = currentGoalMonth();
    const goal = allGoals.find(g => g.month === month);
    const target = goal ? goal.target_amount : 0;
    const monthEntries = allEntries.filter(e => e.entry_date.slice(0,7) === month);
    const achieved = monthEntries.reduce((s,e) => s + Number(e.pnl_amount), 0);
    const pct = target > 0 ? (achieved / target) * 100 : 0;
    const streaks = computeStreaks(allEntries);
    const wins = allEntries.filter(e => e.status === 'profit').length;
    const winRate = allEntries.length ? (wins / allEntries.length) * 100 : 0;
    const totalWin = allEntries.filter(e => e.status==='profit').reduce((s,e)=>s+Number(e.pnl_amount),0);
    const totalLoss = Math.abs(allEntries.filter(e => e.status==='loss').reduce((s,e)=>s+Number(e.pnl_amount),0));
    const profitFactor = totalLoss > 0 ? totalWin / totalLoss : (totalWin > 0 ? Infinity : 0);

    const journalDatesLast5 = [];
    for (let i=0;i<5;i++){ const d=new Date(); d.setDate(d.getDate()-i); journalDatesLast5.push(d.toISOString().slice(0,10)); }
    const journalStreak = journalDatesLast5.every(d => allJournal.some(j => j.entry_date === d));

    const badges = [
        { ic:'🎯', label:'Goal Crusher', earned: pct >= 100 },
        { ic:'🔥', label:'On Fire (3+ win streak)', earned: streaks.currentType === 'profit' && streaks.current >= 3 },
        { ic:'💪', label:'Consistent Trader', earned: winRate >= 60 && allEntries.length >= 10 },
        { ic:'📊', label:'Profit Factor Pro', earned: profitFactor >= 2 },
        { ic:'✍️', label:'5-Day Journal Streak', earned: journalStreak },
        { ic:'🏅', label:'50+ Trades Logged', earned: allEntries.length >= 50 },
    ];
    document.getElementById('tj-badges-row').innerHTML = badges.map(b => `
        <div class="tj-badge ${b.earned ? '' : 'locked'}"><span class="ic">${b.ic}</span> ${esc(b.label)}</div>
    `).join('');
}

// ═══════════════════════════════════════════════════════════════
// GOAL SECTION
// ═══════════════════════════════════════════════════════════════
function currentGoalMonth() { return document.getElementById('tj-goal-month').value || monthStr(new Date()); }
let celebratedMonths = new Set(JSON.parse(localStorage.getItem('tj_celebrated') || '[]'));

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

    if (pct >= 100 && !celebratedMonths.has(month)) {
        celebratedMonths.add(month);
        localStorage.setItem('tj_celebrated', JSON.stringify([...celebratedMonths]));
        fireConfetti();
        toast('🎉 Monthly goal reached! Great trading discipline.');
    }
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
        data: { datasets: [{ data: [pct, 100 - pct], backgroundColor: [pct >= 100 ? good : accent, track], borderWidth: 0 }] },
        options: { circumference: 180, rotation: 270, cutout: '75%',
            plugins: { legend: { display: false }, tooltip: { enabled: false } }, animation: { duration: 500 } }
    });
}

async function saveGoal() {
    const month = currentGoalMonth();
    const amtInput = document.getElementById('tj-goal-amount');
    const amount = parseFloat(amtInput.value);
    const err = document.getElementById('tj-goal-err');
    if (isNaN(amount) || amount < 0) { amtInput.classList.add('err'); err.classList.add('show'); return; }
    amtInput.classList.remove('err'); err.classList.remove('show');
    try {
        await Taskvel.request('/api/trading_journal.php?action=save-goal', { method: 'POST', body: { month, target_amount: amount } });
        const existing = allGoals.find(g => g.month === month);
        if (existing) existing.target_amount = amount;
        else allGoals.push({ month, target_amount: amount });
        renderGoalSection(); renderAll(); renderBadges(); renderAnalytics();
        toast('Monthly goal saved');
    } catch (e) { toast(e.message || 'Could not save goal'); }
}
document.getElementById('tj-goal-month').addEventListener('change', () => { renderGoalSection(); renderAll(); renderBadges(); renderAnalytics(); });

// ═══════════════════════════════════════════════════════════════
// SUMMARY CARDS (+ sparklines + MoM compare)
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
    animateNumber(cards[0], target, v => fmtMoney(v));
    animateNumber(cards[1], currentPL, v => fmtMoney(v));
    cards[1].parentElement.classList.toggle('pos', currentPL > 0);
    cards[1].parentElement.classList.toggle('neg', currentPL < 0);
    animateNumber(cards[2], remaining, v => fmtMoney(v));
    animateNumber(cards[3], pct, v => v.toFixed(1) + '%');
    cards[4].textContent = totalDays;
    cards[5].textContent = profitDays;
    cards[6].textContent = lossDays;
    animateNumber(cards[7], winRate, v => v.toFixed(1) + '%');

    // Month-over-month compare chip under Current P/L card
    const prevMonthDate = new Date(month + '-01'); prevMonthDate.setMonth(prevMonthDate.getMonth() - 1);
    const prevMonth = monthStr(prevMonthDate);
    const prevEntries = allEntries.filter(e => e.entry_date.slice(0,7) === prevMonth);
    const prevPL = prevEntries.reduce((s,e) => s + Number(e.pnl_amount), 0);
    const diff = achieved - prevPL;
    const cmpEl = document.getElementById('compare-0');
    if (prevEntries.length) {
        cmpEl.innerHTML = `<span class="tj-card-compare ${diff >= 0 ? 'up' : 'down'}">${diff >= 0 ? '▲' : '▼'} ${fmtMoney(Math.abs(diff))} vs last month</span>`;
    } else cmpEl.innerHTML = '';

    // Sparklines: last 14 days trend
    drawSparkline('spark-0', allGoals.slice(-6).map(g => { const a = allEntries.filter(e=>e.entry_date.slice(0,7)===g.month).reduce((s,e)=>s+Number(e.pnl_amount),0); return g.target_amount>0 ? (a/g.target_amount)*100 : 0; }));
    drawSparkline('spark-1', allGoals.slice(-6).map(g => { const a = allEntries.filter(e=>e.entry_date.slice(0,7)===g.month).reduce((s,e)=>s+Number(e.pnl_amount),0); return g.target_amount>0 ? (a/g.target_amount)*100 : 0; }));
    const last14 = [...allEntries].sort((a,b)=>a.entry_date.localeCompare(b.entry_date)).slice(-14);
    let running = 0; const rollingWin = [];
    last14.forEach((e,i) => { running += e.status==='profit'?1:0; rollingWin.push((running/(i+1))*100); });
    drawSparkline('spark-2', rollingWin);
}
function drawSparkline(canvasId, data) {
    const el = document.getElementById(canvasId);
    if (!el || !data.length) { if (el && charts[canvasId]) { charts[canvasId].destroy(); charts[canvasId]=null; } return; }
    const c = chartColors();
    if (charts[canvasId]) charts[canvasId].destroy();
    charts[canvasId] = new Chart(el, {
        type: 'line',
        data: { labels: data.map((_,i)=>i), datasets: [{ data, borderColor: c.accent, borderWidth: 2, pointRadius: 0, tension: .35, fill:false }] },
        options: { responsive:true, maintainAspectRatio:false, animation:{duration:400},
            plugins:{legend:{display:false},tooltip:{enabled:false}},
            scales:{ x:{display:false}, y:{display:false} } }
    });
}

// ═══════════════════════════════════════════════════════════════
// CHARTS (Overview tab)
// ═══════════════════════════════════════════════════════════════
function chartColors() {
    const s = getComputedStyle(document.documentElement);
    return {
        accent: s.getPropertyValue('--accent').trim(), good: s.getPropertyValue('--good').trim(),
        bad: s.getPropertyValue('--bad').trim(), ink3: s.getPropertyValue('--ink3').trim(),
        line: s.getPropertyValue('--line').trim(), ink: s.getPropertyValue('--ink').trim(),
    };
}
function destroyChart(key) { if (charts[key]) { charts[key].destroy(); charts[key] = null; } }
function chartBaseOptions(gridOpts) {
    return { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { x: gridOpts, y: gridOpts } };
}

function renderCharts(scoped) {
    const c = chartColors();
    const gridOpts = { grid: { color: c.line }, ticks: { color: c.ink3, font: { size: 10 } } };

    const sortedDaily = [...scoped].sort((a,b) => a.entry_date.localeCompare(b.entry_date));
    const dailyTotals = {};
    sortedDaily.forEach(e => { dailyTotals[e.entry_date] = (dailyTotals[e.entry_date] || 0) + Number(e.pnl_amount); });
    const dailyLabels = Object.keys(dailyTotals);
    const dailyValues = Object.values(dailyTotals);
    destroyChart('daily');
    charts.daily = new Chart(document.getElementById('chart-daily'), {
        type: 'line',
        data: { labels: dailyLabels, datasets: [{ label: 'P/L', data: dailyValues, borderColor: c.accent, backgroundColor: c.accent + '22', fill: true, tension: .3, pointRadius: 3 }] },
        options: chartBaseOptions(gridOpts)
    });

    const monthly = {};
    allEntries.forEach(e => { const m = e.entry_date.slice(0,7); monthly[m] = (monthly[m] || 0) + Number(e.pnl_amount); });
    document.getElementById('chart-bar-title').textContent = perfView === 'yearly' ? 'Yearly Profit / Loss' : 'Monthly Profit / Loss';
    let barLabels, barValues;
    if (perfView === 'yearly') {
        const yearly = {};
        allEntries.forEach(e => { const y = e.entry_date.slice(0,4); yearly[y] = (yearly[y] || 0) + Number(e.pnl_amount); });
        barLabels = Object.keys(yearly).sort(); barValues = barLabels.map(y => yearly[y]);
    } else {
        barLabels = Object.keys(monthly).sort().slice(-12); barValues = barLabels.map(m => monthly[m]);
    }
    destroyChart('bar');
    charts.bar = new Chart(document.getElementById('chart-bar'), {
        type: 'bar',
        data: { labels: barLabels, datasets: [{ label: 'P/L', data: barValues, backgroundColor: barValues.map(v => v >= 0 ? c.good : c.bad) }] },
        options: chartBaseOptions(gridOpts)
    });

    const goalLabels = allGoals.map(g => g.month).sort();
    const targetVals = goalLabels.map(m => allGoals.find(g => g.month === m).target_amount);
    const achievedVals = goalLabels.map(m => allEntries.filter(e => e.entry_date.slice(0,7) === m).reduce((s,e) => s + Number(e.pnl_amount), 0));
    destroyChart('goal');
    charts.goal = new Chart(document.getElementById('chart-goal'), {
        type: 'bar',
        data: { labels: goalLabels, datasets: [
            { label: 'Target', data: targetVals, backgroundColor: c.ink3 + '55' },
            { label: 'Achieved', data: achievedVals, backgroundColor: c.accent } ] },
        options: chartBaseOptions(gridOpts)
    });

    const totalProfit = scoped.filter(e => e.status === 'profit').reduce((s,e) => s + Number(e.pnl_amount), 0);
    const totalLoss = Math.abs(scoped.filter(e => e.status === 'loss').reduce((s,e) => s + Number(e.pnl_amount), 0));
    destroyChart('dist');
    charts.dist = new Chart(document.getElementById('chart-dist'), {
        type: 'pie',
        data: { labels: ['Profit', 'Loss'], datasets: [{ data: [totalProfit, totalLoss], backgroundColor: [c.good, c.bad] }] },
        options: { plugins: { legend: { position: 'bottom', labels: { color: c.ink3, font: { size: 11 } } } }, responsive: true, maintainAspectRatio: false }
    });

    const wins = scoped.filter(e => e.status === 'profit').length;
    const losses = scoped.filter(e => e.status === 'loss').length;
    const breakeven = scoped.filter(e => e.status === 'breakeven').length;
    destroyChart('winloss');
    charts.winloss = new Chart(document.getElementById('chart-winloss'), {
        type: 'doughnut',
        data: { labels: ['Wins', 'Losses', 'Break-even'], datasets: [{ data: [wins, losses, breakeven], backgroundColor: [c.good, c.bad, c.ink3] }] },
        options: { plugins: { legend: { position: 'bottom', labels: { color: c.ink3, font: { size: 11 } } } }, responsive: true, maintainAspectRatio: false }
    });

    if (scoped.length) {
        const best = scoped.reduce((a,b) => Number(a.pnl_amount) >= Number(b.pnl_amount) ? a : b);
        const worst = scoped.reduce((a,b) => Number(a.pnl_amount) <= Number(b.pnl_amount) ? a : b);
        document.getElementById('bw-best-amt').textContent = fmtMoney(best.pnl_amount);
        document.getElementById('bw-best-date').textContent = best.entry_date;
        document.getElementById('bw-worst-amt').textContent = fmtMoney(worst.pnl_amount);
        document.getElementById('bw-worst-date').textContent = worst.entry_date;
    } else {
        document.getElementById('bw-best-amt').textContent = '—'; document.getElementById('bw-best-date').textContent = 'No data yet';
        document.getElementById('bw-worst-amt').textContent = '—'; document.getElementById('bw-worst-date').textContent = 'No data yet';
    }

    document.getElementById('chart-summary-title').textContent = perfView === 'yearly' ? 'Yearly Performance Summary' : 'Monthly Performance Summary';
    destroyChart('summary');
    charts.summary = new Chart(document.getElementById('chart-summary'), {
        type: 'bar',
        data: { labels: barLabels, datasets: [{ label: 'Net P/L', data: barValues, backgroundColor: barValues.map(v => v >= 0 ? c.good : c.bad) }] },
        options: chartBaseOptions(gridOpts)
    });
}

// ═══════════════════════════════════════════════════════════════
// ANALYTICS TAB
// ═══════════════════════════════════════════════════════════════
function renderAnalytics() {
    const c = chartColors();
    const gridOpts = { grid: { color: c.line }, ticks: { color: c.ink3, font: { size: 10 } } };
    const wins = allEntries.filter(e => e.status === 'profit');
    const losses = allEntries.filter(e => e.status === 'loss');
    const totalWin = wins.reduce((s,e)=>s+Number(e.pnl_amount),0);
    const totalLoss = Math.abs(losses.reduce((s,e)=>s+Number(e.pnl_amount),0));
    const avgWin = wins.length ? totalWin/wins.length : 0;
    const avgLoss = losses.length ? totalLoss/losses.length : 0;
    const profitFactor = totalLoss > 0 ? totalWin/totalLoss : (totalWin>0?Infinity:0);
    const winRateAll = allEntries.length ? wins.length/allEntries.length : 0;
    const lossRateAll = allEntries.length ? losses.length/allEntries.length : 0;
    const expectancy = (winRateAll*avgWin) - (lossRateAll*avgLoss);
    const streaks = computeStreaks(allEntries);

    document.getElementById('m-profitfactor').textContent = profitFactor === Infinity ? '∞' : profitFactor.toFixed(2);
    document.getElementById('m-expectancy').textContent = fmtMoney(expectancy);
    document.getElementById('m-expectancy').className = 'v ' + (expectancy>=0?'pos':'neg');
    document.getElementById('m-avgwin').textContent = fmtMoney(avgWin);
    document.getElementById('m-avgloss').textContent = fmtMoney(-avgLoss);
    document.getElementById('m-beststreak').textContent = streaks.bestWin + ' days';

    // Equity curve + drawdown (all-time, sorted)
    const sorted = [...allEntries].sort((a,b) => a.entry_date.localeCompare(b.entry_date));
    let running = 0, peak = -Infinity, maxDD = 0;
    const eqLabels = [], eqValues = [], ddValues = [];
    sorted.forEach(e => {
        running += Number(e.pnl_amount);
        eqLabels.push(e.entry_date); eqValues.push(running);
        peak = Math.max(peak, running);
        const dd = peak - running;
        maxDD = Math.max(maxDD, dd);
        ddValues.push(-dd);
    });
    document.getElementById('m-drawdown').textContent = fmtMoney(-maxDD);

    destroyChart('equity');
    charts.equity = new Chart(document.getElementById('chart-equity'), {
        type: 'line',
        data: { labels: eqLabels, datasets: [{ label: 'Equity', data: eqValues, borderColor: c.accent, backgroundColor: c.accent+'1a', fill:true, tension:.25, pointRadius:0 }] },
        options: chartBaseOptions(gridOpts)
    });
    destroyChart('drawdown');
    charts.drawdown = new Chart(document.getElementById('chart-drawdown'), {
        type: 'line',
        data: { labels: eqLabels, datasets: [{ label: 'Drawdown', data: ddValues, borderColor: c.bad, backgroundColor: c.bad+'22', fill:true, tension:.25, pointRadius:0 }] },
        options: chartBaseOptions(gridOpts)
    });

    // Top setups/tags
    const tagTotals = {};
    allEntries.forEach(e => { const tag = extractTag(e.notes); if (tag) tagTotals[tag] = (tagTotals[tag]||0) + Number(e.pnl_amount); });
    const tagLabels = Object.keys(tagTotals);
    destroyChart('tags');
    charts.tags = new Chart(document.getElementById('chart-tags'), {
        type: 'bar',
        data: { labels: tagLabels.length ? tagLabels : ['No tags yet'], datasets: [{ data: tagLabels.length ? tagLabels.map(t=>tagTotals[t]) : [0], backgroundColor: c.accent }] },
        options: { indexAxis: 'y', responsive:true, maintainAspectRatio:false, plugins:{legend:{display:false}}, scales:{x:gridOpts,y:gridOpts} }
    });

    // Month-over-month comparison
    const month = currentGoalMonth();
    const prevMonthDate = new Date(month + '-01'); prevMonthDate.setMonth(prevMonthDate.getMonth() - 1);
    const prevMonth = monthStr(prevMonthDate);
    const curEntries = allEntries.filter(e => e.entry_date.slice(0,7) === month);
    const prevEntries = allEntries.filter(e => e.entry_date.slice(0,7) === prevMonth);
    const curPL = curEntries.reduce((s,e)=>s+Number(e.pnl_amount),0);
    const prevPL = prevEntries.reduce((s,e)=>s+Number(e.pnl_amount),0);
    const curWinRate = curEntries.length ? (curEntries.filter(e=>e.status==='profit').length/curEntries.length)*100 : 0;
    const prevWinRate = prevEntries.length ? (prevEntries.filter(e=>e.status==='profit').length/prevEntries.length)*100 : 0;
    const curGoal = allGoals.find(g=>g.month===month); const prevGoal = allGoals.find(g=>g.month===prevMonth);
    const curGoalPct = curGoal && curGoal.target_amount>0 ? (curPL/curGoal.target_amount)*100 : 0;
    const prevGoalPct = prevGoal && prevGoal.target_amount>0 ? (prevPL/prevGoal.target_amount)*100 : 0;

    document.getElementById('cmp-pl').innerHTML = `${fmtMoney(curPL)} <span style="color:var(--ink3);font-weight:400;">vs ${fmtMoney(prevPL)}</span>`;
    document.getElementById('cmp-days').innerHTML = `${curEntries.length} <span style="color:var(--ink3);font-weight:400;">vs ${prevEntries.length}</span>`;
    document.getElementById('cmp-winrate').innerHTML = `${curWinRate.toFixed(1)}% <span style="color:var(--ink3);font-weight:400;">vs ${prevWinRate.toFixed(1)}%</span>`;
    document.getElementById('cmp-goal').innerHTML = `${curGoalPct.toFixed(1)}% <span style="color:var(--ink3);font-weight:400;">vs ${prevGoalPct.toFixed(1)}%</span>`;

    // Weekly performance table (current filter range's underlying month, last 8 weeks of data)
    const weekMap = {};
    sorted.slice(-200).forEach(e => {
        const d = new Date(e.entry_date + 'T00:00:00');
        const onejan = new Date(d.getFullYear(),0,1);
        const week = Math.ceil((((d - onejan) / 86400000) + onejan.getDay()+1)/7);
        const key = `${d.getFullYear()}-W${week}`;
        if (!weekMap[key]) weekMap[key] = { trades:0, wins:0, pl:0 };
        weekMap[key].trades++; weekMap[key].pl += Number(e.pnl_amount);
        if (e.status === 'profit') weekMap[key].wins++;
    });
    const weekKeys = Object.keys(weekMap).slice(-8);
    const tbody = document.querySelector('#tj-weekly-table tbody');
    tbody.innerHTML = weekKeys.length ? weekKeys.map(k => {
        const w = weekMap[k]; const wr = w.trades ? (w.wins/w.trades*100).toFixed(0) : 0;
        return `<tr><td>${k}</td><td>${w.trades}</td><td>${wr}%</td><td class="amt ${w.pl>=0?'pos':'neg'}">${fmtMoney(w.pl)}</td></tr>`;
    }).join('') : `<tr><td colspan="4" style="text-align:center;color:var(--ink3);">No data yet</td></tr>`;
}

// ═══════════════════════════════════════════════════════════════
// CALENDAR HEATMAP
// ═══════════════════════════════════════════════════════════════
function calNav(dir) { calMonthCursor.setMonth(calMonthCursor.getMonth() + dir); renderCalendar(); }
function renderCalendar() {
    const y = calMonthCursor.getFullYear(), m = calMonthCursor.getMonth();
    document.getElementById('tj-cal-title').textContent = calMonthCursor.toLocaleDateString('en-US', { month:'long', year:'numeric' });
    const dowEl = document.getElementById('tj-cal-dow');
    dowEl.innerHTML = ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'].map(d => `<div class="tj-cal-dow">${d}</div>`).join('');

    const firstDay = new Date(y, m, 1);
    const startOffset = (firstDay.getDay() + 6) % 7; // Monday=0
    const daysInMonth = new Date(y, m+1, 0).getDate();

    const monthEntries = {};
    allEntries.filter(e => e.entry_date.slice(0,7) === `${y}-${String(m+1).padStart(2,'0')}`).forEach(e => {
        const d = e.entry_date; monthEntries[d] = (monthEntries[d] || 0) + Number(e.pnl_amount);
    });
    const maxAbs = Math.max(1, ...Object.values(monthEntries).map(v => Math.abs(v)));

    let html = '';
    for (let i=0;i<startOffset;i++) html += '<div class="tj-cal-cell empty"></div>';
    for (let day=1; day<=daysInMonth; day++) {
        const dateStr = `${y}-${String(m+1).padStart(2,'0')}-${String(day).padStart(2,'0')}`;
        const val = monthEntries[dateStr];
        let bg = 'var(--bg-sunk)', color='var(--ink3)';
        if (val !== undefined) {
            const intensity = Math.min(1, Math.abs(val) / maxAbs);
            if (val > 0) { bg = `color-mix(in srgb, var(--good) ${20+intensity*60}%, var(--bg-elev))`; color='var(--ink)'; }
            else if (val < 0) { bg = `color-mix(in srgb, var(--bad) ${20+intensity*60}%, var(--bg-elev))`; color='var(--ink)'; }
            else { bg = 'var(--line)'; }
        }
        html += `<div class="tj-cal-cell ${val!==undefined?'has':''}" style="background:${bg};color:${color};" title="${dateStr}">
            ${day}${val!==undefined ? `<span class="amt">${val>=0?'+':''}${Math.round(val)}</span>` : ''}
        </div>`;
    }
    document.getElementById('tj-cal-grid').innerHTML = html;
}

// ═══════════════════════════════════════════════════════════════
// RISK CALCULATOR
// ═══════════════════════════════════════════════════════════════
function runCalc() {
    const balance = parseFloat(document.getElementById('calc-balance').value) || 0;
    const riskPct = parseFloat(document.getElementById('calc-risk-pct').value) || 0;
    const slDist = parseFloat(document.getElementById('calc-sl-distance').value) || 0;
    const rr = parseFloat(document.getElementById('calc-rr').value) || 0;

    const riskAmt = balance * (riskPct/100);
    const posSize = slDist > 0 ? riskAmt / slDist : 0;
    const reward = riskAmt * rr;

    document.getElementById('calc-risk-amt').textContent = fmtMoney(riskAmt);
    document.getElementById('calc-position-size').textContent = posSize.toLocaleString('en-IN', {maximumFractionDigits:2});
    document.getElementById('calc-reward').textContent = fmtMoney(reward);
    document.getElementById('calc-after-loss').textContent = fmtMoney(balance - riskAmt);
    document.getElementById('calc-after-win').textContent = fmtMoney(balance + reward);
}

// ═══════════════════════════════════════════════════════════════
// ENTRIES LIST + CRUD (search, sort, tags, export)
// ═══════════════════════════════════════════════════════════════
function renderEntriesList(scoped) {
    const list = document.getElementById('tj-entries-list');
    const search = (document.getElementById('tj-entry-search')?.value || '').toLowerCase().trim();
    const sortMode = document.getElementById('tj-entry-sort')?.value || 'date-desc';

    let filtered = scoped.filter(e => !search || (e.notes || '').toLowerCase().includes(search) || e.entry_date.includes(search));
    filtered = [...filtered].sort((a,b) => {
        if (sortMode === 'date-asc') return a.entry_date.localeCompare(b.entry_date) || a.id - b.id;
        if (sortMode === 'amt-desc') return Number(b.pnl_amount) - Number(a.pnl_amount);
        if (sortMode === 'amt-asc') return Number(a.pnl_amount) - Number(b.pnl_amount);
        return b.entry_date.localeCompare(a.entry_date) || b.id - a.id;
    });

    if (!filtered.length) {
        list.innerHTML = `<div class="tj-empty"><span class="ic">📭</span>No trading entries match this view.<br>Click "+ Add Daily Entry" to log a trade.</div>`;
        return;
    }
    list.innerHTML = filtered.map(e => {
        const tag = extractTag(e.notes);
        const cleanNotes = tag ? (e.notes || '').replace(/^\[.+?\]\s*/, '') : e.notes;
        return `
        <div class="tj-entry-row">
            <div class="tj-entry-dot ${e.status}"></div>
            <div class="tj-entry-body">
                <div class="tj-entry-date">${esc(e.entry_date)}${tag ? `<span class="tj-entry-tag">${esc(tag)}</span>` : ''}</div>
                ${cleanNotes ? `<div class="tj-entry-notes">${esc(cleanNotes)}</div>` : ''}
            </div>
            <div class="tj-entry-amt ${e.status}">${fmtMoney(e.pnl_amount)}</div>
            <div class="tj-entry-actions">
                <button title="Edit" onclick="editEntry(${e.id})">✎</button>
                <button title="Delete" onclick="deleteEntry(${e.id})">🗑</button>
            </div>
        </div>`;
    }).join('');
}

function openEntryModal(entry) {
    document.getElementById('entry-modal-title').textContent = entry ? 'Edit Daily Entry' : 'Add Daily Entry';
    document.getElementById('entry-id').value = entry ? entry.id : '';
    document.getElementById('entry-date').value = entry ? entry.entry_date : todayStr();
    document.getElementById('entry-status').value = entry ? entry.status : 'profit';
    document.getElementById('entry-amount').value = entry ? entry.pnl_amount : '';
    const tag = entry ? extractTag(entry.notes) : null;
    selectedTag = tag;
    document.querySelectorAll('#entry-tag-chips .tj-tag-chip').forEach(chip => chip.classList.toggle('active', chip.dataset.tag === tag));
    document.getElementById('entry-notes').value = entry ? (entry.notes || '').replace(/^\[.+?\]\s*/, '') : '';
    clearEntryErrors();
    document.getElementById('entry-modal-overlay').classList.add('open');
}
document.getElementById('entry-tag-chips').addEventListener('click', (e) => {
    const chip = e.target.closest('.tj-tag-chip');
    if (!chip) return;
    const already = chip.classList.contains('active');
    document.querySelectorAll('#entry-tag-chips .tj-tag-chip').forEach(c => c.classList.remove('active'));
    if (!already) { chip.classList.add('active'); selectedTag = chip.dataset.tag; }
    else selectedTag = null;
});
function closeEntryModal() { document.getElementById('entry-modal-overlay').classList.remove('open'); }
function clearEntryErrors() {
    ['entry-date','entry-amount'].forEach(id => document.getElementById(id).classList.remove('err'));
    ['entry-date-err','entry-amount-err'].forEach(id => document.getElementById(id).classList.remove('show'));
}
function onEntryStatusChange() { if (document.getElementById('entry-status').value === 'breakeven') document.getElementById('entry-amount').value = 0; }
function editEntry(id) { const entry = allEntries.find(e => e.id === id); if (entry) openEntryModal(entry); }
async function deleteEntry(id) {
    if (!confirm('Delete this trading entry? This cannot be undone.')) return;
    try {
        await Taskvel.request(`/api/trading_journal.php?action=delete-entry&id=${id}`, { method: 'DELETE' });
        allEntries = allEntries.filter(e => e.id !== id);
        renderGoalSection(); renderAll(); renderHero(); renderBadges(); renderCalendar(); renderAnalytics();
        toast('Entry deleted');
    } catch (e) { toast(e.message || 'Could not delete entry'); }
}
async function submitEntry() {
    clearEntryErrors();
    const id = document.getElementById('entry-id').value;
    const date = document.getElementById('entry-date').value;
    const status = document.getElementById('entry-status').value;
    const amountRaw = document.getElementById('entry-amount').value;
    let notes = document.getElementById('entry-notes').value.trim();
    if (selectedTag) notes = `[${selectedTag}] ${notes}`.trim();

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
        renderGoalSection(); renderAll(); renderHero(); renderBadges(); renderCalendar(); renderAnalytics();
        toast(id ? 'Entry updated' : 'Entry added');
    } catch (e) { toast(e.message || 'Could not save entry'); }
}

function exportCSV() {
    const { from, to } = dateRangeForFilter(currentFilter);
    const scoped = entriesInRange(from, to);
    if (!scoped.length) { toast('No entries to export in this range'); return; }
    const rows = [['Date','Amount','Status','Notes']];
    [...scoped].sort((a,b)=>a.entry_date.localeCompare(b.entry_date)).forEach(e => {
        rows.push([e.entry_date, e.pnl_amount, e.status, (e.notes || '').replace(/"/g,'""')]);
    });
    const csv = rows.map(r => r.map(v => `"${v}"`).join(',')).join('\n');
    const blob = new Blob([csv], { type: 'text/csv' });
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = `trading-journal-${from}-to-${to}.csv`;
    a.click();
    URL.revokeObjectURL(a.href);
}

// ═══════════════════════════════════════════════════════════════
// JOURNAL (+ mood picker)
// ═══════════════════════════════════════════════════════════════
document.getElementById('tj-mood-picker').addEventListener('click', (e) => {
    const btn = e.target.closest('.tj-mood-btn');
    if (!btn) return;
    const ta = document.getElementById('tj-journal-text');
    const lines = ta.value.split('\n');
    if (/^(😄|😌|😐|😤|😰|😴)/.test(lines[0] || '')) lines.shift();
    lines.unshift(btn.dataset.mood);
    ta.value = lines.slice(0,15).join('\n');
    onJournalInput();
});
function onJournalInput() {
    const ta = document.getElementById('tj-journal-text');
    let lines = ta.value.split('\n');
    if (lines.length > 15) { lines = lines.slice(0, 15); ta.value = lines.join('\n'); }
    const countEl = document.getElementById('tj-journal-count');
    countEl.textContent = `${lines.length} / 15 lines`;
    countEl.classList.toggle('over', lines.length >= 15);
}

async function aiFixJournal() {
    const ta = document.getElementById('tj-journal-text');
    const content = ta.value.trim();
    const statusEl = document.getElementById('tj-journal-ai-status');
    const btn = document.getElementById('tj-journal-ai-btn');
    if (!content) { toast('Write something first, then hit AI Fix & Rephrase'); return; }
    btn.disabled = true;
    btn.textContent = '✨ Fixing…';
    statusEl.style.display = 'block';
    statusEl.textContent = 'Correcting spelling, grammar and rephrasing…';
    try {
        const { corrected } = await Taskvel.request('/api/trading_journal.php?action=ai-fix-journal', {
            method: 'POST',
            body: { content }
        });
        if (corrected) {
            ta.value = corrected.split('\n').slice(0, 15).join('\n');
            onJournalInput();
            statusEl.textContent = '✨ Journal corrected — review and save.';
        } else {
            statusEl.textContent = "AI didn't return a correction.";
        }
    } catch (e) {
        statusEl.textContent = "Couldn't fix journal: " + e.message;
    } finally {
        btn.disabled = false;
        btn.textContent = '✨ AI Fix & Rephrase';
        setTimeout(() => { statusEl.style.display = 'none'; }, 4000);
    }
}

async function loadJournalForDate(date) {
    document.getElementById('tj-journal-date-label').textContent = date === todayStr() ? 'today' : date;
    try {
        const res = await Taskvel.request(`/api/trading_journal.php?action=journal&date=${date}`);
        document.getElementById('tj-journal-text').value = res.journal ? res.journal.content : '';
    } catch (e) { document.getElementById('tj-journal-text').value = ''; }
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
        if (existing) existing.content = content; else allJournal.push({ entry_date: date, content });
        renderJournalList(); renderBadges();
        toast('Journal entry saved');
    } catch (e) { toast(e.message || 'Could not save journal entry'); }
    finally { btn.disabled = false; btn.textContent = 'Save Journal Entry'; }
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

// ═══════════════════════════════════════════════════════════════
// CONFETTI (vanilla canvas, no dependencies)
// ═══════════════════════════════════════════════════════════════
function fireConfetti() {
    const canvas = document.getElementById('tj-confetti-canvas');
    canvas.width = window.innerWidth; canvas.height = window.innerHeight;
    const ctx = canvas.getContext('2d');
    const colors = ['#f43f5e','#facc15','#22c55e','#3b82f6','#a855f7'];
    const pieces = Array.from({length:140}, () => ({
        x: Math.random()*canvas.width, y: -20 - Math.random()*canvas.height*0.3,
        w: 6+Math.random()*6, h: 8+Math.random()*8, color: colors[Math.floor(Math.random()*colors.length)],
        vy: 2+Math.random()*3, vx: -1.5+Math.random()*3, rot: Math.random()*360, vr: -6+Math.random()*12,
    }));
    let frame = 0;
    function tick() {
        ctx.clearRect(0,0,canvas.width,canvas.height);
        pieces.forEach(p => {
            p.x += p.vx; p.y += p.vy; p.rot += p.vr;
            ctx.save(); ctx.translate(p.x,p.y); ctx.rotate(p.rot*Math.PI/180);
            ctx.fillStyle = p.color; ctx.fillRect(-p.w/2,-p.h/2,p.w,p.h); ctx.restore();
        });
        frame++;
        if (frame < 130) requestAnimationFrame(tick);
        else ctx.clearRect(0,0,canvas.width,canvas.height);
    }
    tick();
}

loadAll();
</script>
</body>
</html>