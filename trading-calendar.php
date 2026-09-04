<?php
// File: trading-calendar.php  (app root, next to trading-journal.php)
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
<?php pro_head('Trading Calendar'); ?>
<style>
    .tc-topbar { display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap; margin-bottom:18px; }
    .tc-nav { display:flex; align-items:center; gap:8px; flex-wrap:wrap; }
    .tc-nav button { width:34px; height:34px; border-radius:9px; border:1px solid var(--line2); background:var(--bg-elev); color:var(--ink2); cursor:pointer; font-size:15px; }
    .tc-nav button:hover { background:var(--bg-sunk); }
    .tc-nav h2 { font-family:var(--font-display); font-size:16px; font-weight:800; margin:0; min-width:150px; text-align:center; }
    .tc-nav select { padding:8px 10px; border:1px solid var(--line2); border-radius:var(--r-sm); background:var(--bg-elev); color:var(--ink); font-family:var(--font-display); font-weight:700; font-size:13px; }
    .tc-total { font-family:var(--font-display); font-weight:800; font-size:14px; padding:9px 18px; border-radius:10px; background:var(--bg-elev); border:1px solid var(--line); white-space:nowrap; }
    .tc-total.pos { color:var(--good); }
    .tc-total.neg { color:var(--bad); }

    .tc-cal-card { background:var(--bg-elev); border:1px solid var(--line); border-radius:var(--r-lg); padding:18px; box-shadow:var(--shadow-sm); }
    .tc-grid { display:grid; grid-template-columns:repeat(7,1fr); gap:6px; }
    .tc-dow { text-align:center; font-family:var(--font-display); font-size:10.5px; color:var(--ink3); text-transform:uppercase; padding-bottom:6px; font-weight:700; }
    .tc-cell { position:relative; aspect-ratio:1; border-radius:10px; display:flex; flex-direction:column; align-items:center; justify-content:center; gap:2px;
        font-size:12px; font-weight:700; cursor:pointer; border:1px solid var(--line); background:var(--bg-sunk); color:var(--ink2);
        transition:transform .15s var(--ease), box-shadow .15s ease; }
    .tc-cell:hover { transform:scale(1.05); box-shadow:var(--shadow); border-color:var(--accent); }
    .tc-cell.empty { background:transparent; border:none; cursor:default; }
    .tc-cell.empty:hover { transform:none; box-shadow:none; }
    .tc-cell .amt { font-size:9.5px; font-weight:700; }
    .tc-cell .jflag { position:absolute; top:3px; right:4px; font-size:8px; }
    .tc-cell.today { border-color:var(--accent); box-shadow:0 0 0 2px var(--ring); }
    .tc-legend { display:flex; align-items:center; gap:6px; margin-top:14px; font-size:10.5px; color:var(--ink3); justify-content:flex-end; }
    .tc-legend .sw { width:12px; height:12px; border-radius:3px; }

    .fielderr { color:var(--bad); font-size:11.5px; margin-top:4px; display:none; }
    .fielderr.show { display:block; }
    .modal input.err, .modal textarea.err { border-color:var(--bad) !important; }
</style>
</head>
<body>
<div class="wrap">
    <?php pro_header($user, 'calendar'); ?>
    <div>

    <div id="tc-trial-note" style="display:none;font-size:12px;color:var(--ink3);margin-bottom:14px;"></div>

    <div class="tc-topbar">
        <div class="tc-nav">
            <button onclick="tcNav(-1)">‹</button>
            <h2 id="tc-title">—</h2>
            <button onclick="tcNav(1)">›</button>
            <select id="tc-month-select" onchange="tcJump()"></select>
            <select id="tc-year-select" onchange="tcJump()"></select>
        </div>
        <div class="tc-total" id="tc-total">₹0.00</div>
    </div>

    <div class="tc-cal-card">
        <div class="tc-grid" id="tc-dow"></div>
        <div class="tc-grid" id="tc-grid" style="margin-top:6px;"></div>
        <div class="tc-legend">
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
    <?php pro_footer($user); ?>
</div>

<!-- ===================== ENTRY MODAL ===================== -->
<div class="modal-overlay" id="tc-entry-modal-overlay" onclick="if(event.target===this)tcCloseModal()">
    <div class="modal">
        <h2 id="tc-modal-title">Entry</h2>
        <input type="hidden" id="tc-entry-id">
        <input type="hidden" id="tc-date">
        <div class="fg">
            <label for="tc-status">Trading Status</label>
            <select id="tc-status" onchange="tcOnStatusChange()">
                <option value="profit">Profit</option>
                <option value="loss">Loss</option>
                <option value="breakeven">Break-even</option>
            </select>
        </div>
        <div class="fg">
            <label for="tc-amount">Profit / Loss amount (₹)</label>
            <input type="number" id="tc-amount" step="0.01" placeholder="0.00">
            <div class="fielderr" id="tc-amount-err">Please enter a valid amount.</div>
        </div>
        <div class="fg">
            <label for="tc-notes">Notes (optional)</label>
            <textarea id="tc-notes" maxlength="500" placeholder="What happened, setup, symbol, etc."></textarea>
        </div>
        <div class="fg">
            <label for="tc-journal">Journal entry (optional, max 15 lines)</label>
            <textarea id="tc-journal" rows="6" maxlength="4000" placeholder="How the day went, mindset, lessons learned..."></textarea>
        </div>
        <div class="modal-actions">
            <button class="btn ghost" id="tc-delete-btn" onclick="tcDeleteEntry()" style="display:none;">Delete</button>
            <button class="btn ghost" onclick="tcCloseModal()">Cancel</button>
            <button class="btn" onclick="tcSaveEntry()">Save</button>
        </div>
    </div>
</div>

<div class="modal-overlay" id="tc-paywall-overlay" onclick="if(event.target===this)tcClosePaywall()">
    <div class="modal">
        <h2>🔒 Trading Journal trial ended</h2>
        <p style="font-size:13px;color:var(--ink3);margin:10px 0 16px;line-height:1.6;">
            Your 10-day free trial has ended. All your past entries are still visible — subscribe to the ₹49 plan, Pro, or Enterprise to add or edit new ones.
        </p>
        <div class="modal-actions">
            <button class="btn ghost" onclick="tcClosePaywall()">Not now</button>
            <a class="btn" href="billing.php">View plans</a>
        </div>
    </div>
</div>

<script src="js/api-client.js?v=3"></script>
<script>
let tcEntries = [];
let tcJournalByDate = {};
let tcAccess = { can_write: true, is_paid: true };
let tcCursor = new Date(); tcCursor.setDate(1);

function esc(s) { return (s || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;'); }
function fmtMoney(n) {
    const v = Number(n) || 0;
    const sign = v < 0 ? '-' : '';
    return sign + '₹' + Math.abs(v).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}
function todayStr() { return new Date().toISOString().slice(0,10); }
function toast(msg) { if (window.Taskvel && Taskvel.toast) Taskvel.toast(msg); else console.log(msg); }

function checkWriteAccess() {
    if (tcAccess.can_write) return true;
    document.getElementById('tc-paywall-overlay').classList.add('open');
    return false;
}
function tcClosePaywall() { document.getElementById('tc-paywall-overlay').classList.remove('open'); }

async function tcLoadAll() {
    try {
        const [entriesRes, journalRes, accessRes] = await Promise.all([
            Taskvel.request('/api/trading_journal.php?action=list-entries'),
            Taskvel.request('/api/trading_journal.php?action=list-journal'),
            Taskvel.request('/api/trading_journal.php?action=access-status'),
        ]);
        tcEntries = entriesRes.entries || [];
        tcJournalByDate = {};
        (journalRes.journal || []).forEach(j => { tcJournalByDate[j.entry_date] = j; });
        tcAccess = accessRes.access;
    } catch (e) {
        toast(e.message || 'Could not load calendar data');
    }
    const note = document.getElementById('tc-trial-note');
    if (!tcAccess.is_paid) {
        note.style.display = 'block';
        note.textContent = tcAccess.trial_active
            ? `⏳ ${tcAccess.days_left} day(s) left in your Trading Journal trial.`
            : (tcAccess.trial_started ? '🔒 Your Trading Journal trial has ended — existing entries are visible, upgrade to add new ones.' : '✨ Logging your first trade starts a 10-day free trial.');
    } else { note.style.display = 'none'; }
    tcPopulateSelects();
    tcRenderCalendar();
}

function tcPopulateSelects() {
    const ms = document.getElementById('tc-month-select');
    const ys = document.getElementById('tc-year-select');
    if (!ms.options.length) {
        for (let m = 0; m < 12; m++) {
            const o = document.createElement('option');
            o.value = m;
            o.textContent = new Date(2000, m, 1).toLocaleDateString('en-US', { month: 'long' });
            ms.appendChild(o);
        }
    }
    if (!ys.options.length) {
        const curY = new Date().getFullYear();
        for (let y = curY - 6; y <= curY + 2; y++) {
            const o = document.createElement('option');
            o.value = y; o.textContent = y;
            ys.appendChild(o);
        }
    }
    ms.value = tcCursor.getMonth();
    ys.value = tcCursor.getFullYear();
}
function tcJump() {
    const m = parseInt(document.getElementById('tc-month-select').value);
    const y = parseInt(document.getElementById('tc-year-select').value);
    tcCursor = new Date(y, m, 1);
    tcRenderCalendar();
}
function tcNav(dir) {
    tcCursor.setMonth(tcCursor.getMonth() + dir);
    document.getElementById('tc-month-select').value = tcCursor.getMonth();
    document.getElementById('tc-year-select').value = tcCursor.getFullYear();
    tcRenderCalendar();
}

function tcRenderCalendar() {
    const y = tcCursor.getFullYear(), m = tcCursor.getMonth();
    document.getElementById('tc-title').textContent = tcCursor.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });

    const dowEl = document.getElementById('tc-dow');
    dowEl.innerHTML = ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'].map(d => `<div class="tc-dow">${d}</div>`).join('');

    const firstDay = new Date(y, m, 1);
    const startOffset = (firstDay.getDay() + 6) % 7;
    const daysInMonth = new Date(y, m + 1, 0).getDate();
    const monthKey = `${y}-${String(m+1).padStart(2,'0')}`;

    const monthEntries = {};
    let monthTotal = 0;
    tcEntries.filter(e => e.entry_date.slice(0,7) === monthKey).forEach(e => {
        monthEntries[e.entry_date] = (monthEntries[e.entry_date] || 0) + Number(e.pnl_amount);
        monthTotal += Number(e.pnl_amount);
    });
    const maxAbs = Math.max(1, ...Object.values(monthEntries).map(v => Math.abs(v)));

    const totalEl = document.getElementById('tc-total');
    totalEl.textContent = 'Month total: ' + fmtMoney(monthTotal);
    totalEl.className = 'tc-total ' + (monthTotal > 0 ? 'pos' : monthTotal < 0 ? 'neg' : '');

    let html = '';
    for (let i = 0; i < startOffset; i++) html += '<div class="tc-cell empty"></div>';
    const today = todayStr();
    for (let day = 1; day <= daysInMonth; day++) {
        const dateStr = `${y}-${String(m+1).padStart(2,'0')}-${String(day).padStart(2,'0')}`;
        const val = monthEntries[dateStr];
        const hasJournal = !!tcJournalByDate[dateStr];
        let bg = 'var(--bg-sunk)', color = 'var(--ink2)';
        if (val !== undefined) {
            const intensity = Math.min(1, Math.abs(val) / maxAbs);
            if (val > 0) { bg = `color-mix(in srgb, var(--good) ${20+intensity*60}%, var(--bg-elev))`; color = 'var(--ink)'; }
            else if (val < 0) { bg = `color-mix(in srgb, var(--bad) ${20+intensity*60}%, var(--bg-elev))`; color = 'var(--ink)'; }
            else { bg = 'var(--line)'; color = 'var(--ink)'; }
        }
        html += `<div class="tc-cell ${dateStr===today?'today':''}" style="background:${bg};color:${color};" onclick="tcOpenModal('${dateStr}')">
            ${hasJournal ? '<span class="jflag">📔</span>' : ''}
            ${day}${val!==undefined ? `<span class="amt">${val>=0?'+':''}${fmtMoney(val)}</span>` : ''}
        </div>`;
    }
    document.getElementById('tc-grid').innerHTML = html;
}

function tcClearErrors() {
    document.getElementById('tc-amount').classList.remove('err');
    document.getElementById('tc-amount-err').classList.remove('show');
}
function tcOnStatusChange() { if (document.getElementById('tc-status').value === 'breakeven') document.getElementById('tc-amount').value = 0; }

function tcOpenModal(dateStr) {
    if (!checkWriteAccess()) return;
    const entry = tcEntries.find(e => e.entry_date === dateStr);
    const journal = tcJournalByDate[dateStr];

    document.getElementById('tc-modal-title').textContent = dateStr;
    document.getElementById('tc-date').value = dateStr;
    document.getElementById('tc-entry-id').value = entry ? entry.id : '';
    document.getElementById('tc-status').value = entry ? entry.status : 'profit';
    document.getElementById('tc-amount').value = entry ? entry.pnl_amount : '';
    document.getElementById('tc-notes').value = entry ? (entry.notes || '') : '';
    document.getElementById('tc-journal').value = journal ? journal.content : '';
    document.getElementById('tc-delete-btn').style.display = entry ? 'inline-flex' : 'none';
    tcClearErrors();
    document.getElementById('tc-entry-modal-overlay').classList.add('open');
}
function tcCloseModal() { document.getElementById('tc-entry-modal-overlay').classList.remove('open'); }

async function tcSaveEntry() {
    tcClearErrors();
    const date = document.getElementById('tc-date').value;
    const id = document.getElementById('tc-entry-id').value;
    const status = document.getElementById('tc-status').value;
    const amountRaw = document.getElementById('tc-amount').value;
    const notes = document.getElementById('tc-notes').value.trim();
    const journalText = document.getElementById('tc-journal').value.trim();

    if (amountRaw === '' || isNaN(parseFloat(amountRaw))) {
        document.getElementById('tc-amount').classList.add('err');
        document.getElementById('tc-amount-err').classList.add('show');
        return;
    }
    let amount = Math.abs(parseFloat(amountRaw));
    if (status === 'loss') amount = -amount;
    else if (status === 'breakeven') amount = 0;

    try {
        const res = await Taskvel.request('/api/trading_journal.php?action=save-entry', {
            method: 'POST',
            body: { id: id ? parseInt(id) : undefined, entry_date: date, status, pnl_amount: amount, notes }
        });
        if (id) {
            const existing = tcEntries.find(e => e.id === parseInt(id));
            Object.assign(existing, { entry_date: date, status, pnl_amount: amount, notes });
        } else {
            tcEntries.push({ id: res.id, entry_date: date, status, pnl_amount: amount, notes });
        }

        if (journalText) {
            await Taskvel.request('/api/trading_journal.php?action=save-journal', { method: 'POST', body: { date, content: journalText } });
            tcJournalByDate[date] = { entry_date: date, content: journalText };
        }

        tcCloseModal();
        toast('Entry saved');
        if (!id) {
            try {
                const accessRes = await Taskvel.request('/api/trading_journal.php?action=access-status');
                tcAccess = accessRes.access;
            } catch (e) {}
        }
        tcRenderCalendar();
    } catch (e) {
        toast(e.message || 'Could not save entry');
    }
}

async function tcDeleteEntry() {
    if (!checkWriteAccess()) return;
    const id = document.getElementById('tc-entry-id').value;
    const date = document.getElementById('tc-date').value;
    if (!id) return;
    if (!confirm('Delete this trading entry? This cannot be undone.')) return;
    try {
        await Taskvel.request(`/api/trading_journal.php?action=delete-entry&id=${id}`, { method: 'DELETE' });
        tcEntries = tcEntries.filter(e => e.id !== parseInt(id));
        tcCloseModal();
        toast('Entry deleted');
        tcRenderCalendar();
    } catch (e) {
        toast(e.message || 'Could not delete entry');
    }
}

tcLoadAll();
</script>
</body>
</html>