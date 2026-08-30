<?php
require_once __DIR__ . '/includes/teams.php';
require_once __DIR__ . '/includes/pro-shell.php';
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
<?php pro_head('Team'); ?>
<style>
    .member-row { display:flex; justify-content:space-between; align-items:center; gap:10px; }
    .member-id { display:flex; align-items:center; gap:11px; min-width:0; }
    .member-name { font-family:var(--font-display); font-weight:600; font-size:14px; }
    .member-email { font-size:11.5px; color:var(--ink3); overflow:hidden; text-overflow:ellipsis; }
    .member-actions { display:flex; gap:6px; align-items:center; flex-shrink:0; }
    select.role-select { padding:7px 9px; border-radius:9px; border:1px solid var(--line2); font-size:12px;
        background:var(--bg); color:var(--ink); font-family:var(--font-body); }

    .proj-row { display:flex; justify-content:space-between; align-items:center; gap:12px; }
    .proj-name { font-family:var(--font-display); font-weight:700; font-size:15px; }
    .proj-meta { font-size:11.5px; color:var(--ink3); margin-top:4px; }
    .proj-bar { height:5px; border-radius:4px; background:var(--bg-sunk); width:150px; overflow:hidden; margin-top:7px; }
    .proj-bar i { display:block; height:100%; background:var(--accent); border-radius:4px; }

    /* Events */
    .event-card { display:flex; gap:15px; align-items:flex-start; }
    .event-date-chip { flex-shrink:0; width:56px; text-align:center; background:var(--bg-sunk); border:1px solid var(--line);
        border-radius:12px; padding:8px 4px; }
    .event-date-chip .d { font-family:var(--font-display); font-size:19px; font-weight:700; line-height:1.1; }
    .event-date-chip .m { font-family:var(--font-display); font-size:9.5px; font-weight:700; text-transform:uppercase;
        letter-spacing:.8px; color:var(--accent); }
    .event-card.past { opacity:.55; }
    .event-title { font-family:var(--font-display); font-weight:700; font-size:15px; }
    .event-info { font-size:12px; color:var(--ink3); margin-top:3px; display:flex; gap:12px; flex-wrap:wrap; }
    .event-attendees { display:flex; align-items:center; gap:8px; margin-top:9px; flex-wrap:wrap; }
    .att-count { font-size:11px; color:var(--ink3); }
    .rsvp-pill { font-family:var(--font-display); font-size:10px; font-weight:700; padding:3px 9px; border-radius:999px; }
    .rsvp-going { background:var(--good-soft); color:var(--good); }
    .rsvp-invited { background:var(--warn-soft); color:var(--warn); }
    .rsvp-declined { background:var(--bad-soft); color:var(--bad); }
    .event-actions { display:flex; gap:6px; margin-top:10px; flex-wrap:wrap; }
    .event-main { min-width:0; flex:1; }
    .proj-tag { font-size:10px; font-family:var(--font-display); font-weight:700; padding:3px 9px; border-radius:999px;
        background:var(--accent-soft); color:var(--accent); }

    /* Team Tasks */
    .tt-card { display:flex; flex-direction:column; gap:8px; }
    .tt-top { display:flex; justify-content:space-between; align-items:flex-start; gap:10px; }
    .tt-title { font-family:var(--font-display); font-weight:700; font-size:14.5px; }
    .tt-meta { font-size:11.5px; color:var(--ink3); margin-top:3px; display:flex; gap:10px; flex-wrap:wrap; align-items:center; }
    .tt-pill { font-size:10px; font-family:var(--font-display); font-weight:700; padding:3px 9px; border-radius:999px; text-transform:capitalize; }
    .tt-pill.pri-low { background:var(--bg-sunk); color:var(--ink3); }
    .tt-pill.pri-medium { background:var(--accent-soft); color:var(--accent); }
    .tt-pill.pri-high { background:var(--warn-soft); color:var(--warn); }
    .tt-pill.pri-urgent { background:var(--bad-soft); color:var(--bad); }
    .tt-pill.status-todo { background:var(--bg-sunk); color:var(--ink3); }
    .tt-pill.status-in_progress { background:var(--warn-soft); color:var(--warn); }
    .tt-pill.status-done { background:var(--good-soft); color:var(--good); }
    .tt-bar { height:6px; border-radius:4px; background:var(--bg-sunk); overflow:hidden; }
    .tt-bar i { display:block; height:100%; background:var(--accent); border-radius:4px; }
    .tt-actions { display:flex; gap:6px; align-items:center; flex-wrap:wrap; }
    .tt-progress-input { width:70px; }    /* Team Chat */
    .chat-box { display:flex; flex-direction:column; background:var(--bg-sunk); border:1px solid var(--line);
        border-radius:16px; overflow:hidden; }
    .chat-messages { height:420px; overflow-y:auto; padding:16px; display:flex; flex-direction:column; gap:12px; scroll-behavior:smooth; }
    .chat-empty { margin:auto; font-size:13px; color:var(--ink3); text-align:center; }
    .chat-msg { display:flex; gap:9px; align-items:flex-end; max-width:78%; animation:chatIn .18s ease-out; }
    .chat-msg.me { align-self:flex-end; flex-direction:row-reverse; }
    .chat-msg .avatar { flex-shrink:0; width:26px; height:26px; }
    .chat-bubble-group { min-width:0; }
    .chat-msg.me .chat-bubble-group { text-align:right; }
    .chat-sender { font-size:10.5px; font-weight:700; color:var(--ink3); margin:0 4px 3px; font-family:var(--font-display); }
    .chat-bubble { display:inline-block; padding:9px 13px; border-radius:16px; font-size:13.5px; line-height:1.5;
        white-space:pre-wrap; word-break:break-word; text-align:left; background:var(--bg-elev); border:1px solid var(--line); }
    .chat-msg.me .chat-bubble { background:var(--accent); color:var(--on-accent); border-color:transparent; border-bottom-right-radius:5px; }
    .chat-msg:not(.me) .chat-bubble { border-bottom-left-radius:5px; }
    .chat-time { display:block; font-size:9.5px; color:var(--ink4); margin:3px 4px 0; opacity:.75; }
    .chat-day-divider { align-self:center; font-size:10.5px; color:var(--ink3); background:var(--bg-elev);
        padding:3px 12px; border-radius:999px; margin:6px 0; }
    .chat-input-row { display:flex; align-items:flex-end; gap:8px; padding:12px; border-top:1px solid var(--line);
        background:var(--bg-elev); }
    .chat-input { flex:1; resize:none; border:1px solid var(--line2); border-radius:14px; padding:10px 14px;
        font-family:var(--font-body); font-size:13.5px; background:var(--bg); color:var(--ink); max-height:110px; min-height:20px; }
    .chat-input:focus { outline:none; border-color:var(--accent); }
    .chat-send-btn { flex-shrink:0; width:38px; height:38px; border-radius:50%; border:none; background:var(--accent);
        color:var(--on-accent); font-size:15px; cursor:pointer; display:flex; align-items:center; justify-content:center;
        transition:transform .12s ease, opacity .12s ease; }
    .chat-send-btn:hover { transform:scale(1.07); }
    .chat-send-btn:disabled { opacity:.5; cursor:default; transform:none; }
    .chat-mention { background:var(--accent-soft); color:var(--accent); font-weight:700; padding:1px 5px; border-radius:6px; }
    .chat-msg.me .chat-mention { background:rgba(255,255,255,.22); color:inherit; }
    .chat-mention.me-mentioned { background:var(--warn-soft); color:var(--warn); }
    .chat-mention-menu { position:absolute; bottom:100%; left:12px; right:12px; margin-bottom:6px; background:var(--bg-elev);
        border:1px solid var(--line); border-radius:12px; box-shadow:0 8px 24px rgba(0,0,0,.15); overflow:hidden; z-index:5; }
    .chat-mention-opt { display:flex; align-items:center; gap:9px; padding:8px 12px; cursor:pointer; font-size:13px; }
    .chat-mention-opt.active, .chat-mention-opt:hover { background:var(--accent-soft); }
    .chat-mention-opt .avatar { width:22px; height:22px; font-size:9px; }
    @keyframes chatIn { from { opacity:0; transform:translateY(6px); } to { opacity:1; transform:translateY(0); } }
</style>
</head>
<body>
<div class="wrap">
    <?php pro_header($user, 'teams', '<a href="teams.php">Teams</a> <span style="opacity:.5">/</span> <span id="crumb-team">…</span>'); ?>

    <h1 class="page-title"><span id="team-name">Loading…</span> <span class="role-badge role-<?= htmlspecialchars($role) ?>"><?= htmlspecialchars($role) ?></span></h1>
    <div class="sub" id="team-sub"></div>

    <section id="team-chat">
        <h2>💬 Team Chat <span style="font-size:11px;font-weight:400;color:var(--ink3);margin-left:6px">messages auto-delete after 7 days</span></h2>
        <div class="chat-box">
            <div class="chat-messages" id="chat-messages">
                <div class="chat-empty" id="chat-empty">Loading conversation…</div>
            </div>
            <div class="chat-input-row" style="position:relative">
                <div class="chat-mention-menu" id="chat-mention-menu" style="display:none"></div>
                <textarea id="chat-input" class="chat-input" rows="1" placeholder="Message the team… (@ to mention someone)"
                    onkeydown="handleChatInputKeydown(event)"
                    oninput="handleChatInputChange(this)"></textarea>
                <button class="chat-send-btn" id="chat-send-btn" onclick="sendChatMessage()" aria-label="Send">➤</button>
            </div>
        </div>
    </section>

    <section>
        <h2>📅 Events <button class="btn sm" onclick="openEventModal()">+ New event</button></h2>
        <div class="card-list" id="event-list"></div>
    </section>

    <section id="team-tasks">
        <h2>✅ Team Tasks <button class="btn sm" onclick="openAssignTask()">+ Assign task</button></h2>
        <div class="fg" style="display:flex;gap:8px;align-items:flex-start;margin:8px 0">
            <input type="text" id="ai-quickadd" placeholder="✨ Quick add with AI… e.g. Prepare client proposal by next Friday, high priority"
                style="flex:1" onkeydown="if(event.key==='Enter'){event.preventDefault();aiQuickAddTeamTask();}" />
            <button class="btn sm" id="ai-quickadd-btn" onclick="aiQuickAddTeamTask()">✨ Add</button>
        </div>
        <label style="display:flex;align-items:center;gap:7px;font-size:12px;color:var(--ink3);margin:6px 0 4px">
            <input type="checkbox" id="notify-email-toggle" onchange="toggleEmailNotifications(this.checked)" />
            Email me when someone updates a task I created or manage
        </label>
        <div class="card-list" id="team-task-list"></div>
    </section>

    <section>
        <h2>▦ Projects <button class="btn sm" onclick="openCreateProject()">+ New project</button></h2>
        <div class="card-list" id="project-list"></div>
    </section>

    <section>
        <h2>👥 Members <?php if ($role !== 'member'): ?><button class="btn sm" onclick="openInvite()">+ Invite</button><?php endif; ?></h2>
        <div class="card-list" id="member-list"></div>
    </section>

    <?php if ($role === 'owner'): ?>
    <section>
        <h2 style="color:var(--bad)">Danger zone</h2>
        <button class="btn danger sm" onclick="deleteTeam()">Delete this team</button>
    </section>
    <?php endif; ?>
    <?php pro_footer($user); ?>
</div>

<!-- Invite member modal -->
<div class="modal-overlay" id="inv-overlay" onclick="if(event.target===this)closeInvite()">
    <div class="modal">
        <h2>Invite a teammate</h2>
        <div class="fg"><label>Their Taskvel account email</label><input type="email" id="inv-email" placeholder="name@company.com" /></div>
        <div class="fg"><label>Role</label>
            <select id="inv-role">
                <option value="member">Member — can update their own assigned tasks</option>
                <option value="manager">Manager — can add/edit/assign/delete any task</option>
            </select>
        </div>
        <div class="modal-actions">
            <button class="btn ghost" onclick="closeInvite()">Cancel</button>
            <button class="btn" id="inv-save-btn" onclick="submitInvite()">Send invite</button>
        </div>
    </div>
</div>

<!-- Update Progress modal (Feature 3) -->
<div class="modal-overlay" id="pu-overlay" onclick="if(event.target===this)closeProgressUpdate()">
    <div class="modal">
        <h2>Update progress</h2>
        <div class="sub" id="pu-task-title" style="margin-bottom:14px"></div>
        <div class="fg"><label>Status</label>
            <select id="pu-status">
                <option value="todo">To do</option>
                <option value="in_progress">In progress</option>
                <option value="done">Done</option>
            </select>
        </div>
        <div class="fg">
            <label>Progress: <span id="pu-progress-label">0%</span></label>
            <input type="range" min="0" max="100" id="pu-progress" style="width:100%"
                oninput="document.getElementById('pu-progress-label').textContent = this.value + '%'" />
        </div>
        <div class="fg"><label>Notes</label><textarea id="pu-notes" rows="3" placeholder="What's the latest? Any blockers?"></textarea></div>
        <div class="fg">
            <label>Attachments (optional)</label>
            <input type="file" id="pu-file" onchange="uploadPendingAttachment(this)" />
            <div id="pu-attachments" style="margin-top:6px"></div>
        </div>
        <div class="modal-actions">
            <button class="btn ghost" onclick="closeProgressUpdate()">Cancel</button>
            <button class="btn" id="pu-save-btn" onclick="submitProgressUpdate()">Send update</button>
        </div>
    </div>
</div>

<!-- Task history / timeline modal (Feature 3) -->
<div class="modal-overlay" id="hist-overlay" onclick="if(event.target===this)closeTaskHistory()">
    <div class="modal">
        <h2>Update history</h2>
        <div class="sub" id="hist-task-title" style="margin-bottom:14px"></div>
        <div class="card-list" id="hist-list"></div>
        <div class="modal-actions">
            <button class="btn ghost" onclick="closeTaskHistory()">Close</button>
        </div>
    </div>
</div>

<!-- Assign team task modal -->
<div class="modal-overlay" id="tt-overlay" onclick="if(event.target===this)closeAssignTask()">
    <div class="modal">
        <h2 id="tt-modal-title">Assign a task</h2>
        <input type="hidden" id="tt-id" />
        <div class="fg"><label>Title</label><input type="text" id="tt-title" maxlength="255" placeholder="e.g. Prepare client proposal" /></div>
        <div class="fg"><label>Description (optional)</label><textarea id="tt-desc" rows="3" placeholder="Any details the assignee needs…"></textarea>
            <button type="button" id="ai-suggest-btn" onclick="aiSuggestTeamTask()"
                style="margin-top:8px;font-size:12px;padding:7px 12px;border-radius:8px;border:1px solid var(--line,#ddd);background:var(--bg-elev);cursor:pointer">
                🤖 AI Suggest</button>
            <div id="ai-suggest-status" style="font-size:11px;color:var(--ink4,#888);margin-top:6px;display:none"></div>
        </div>
        <div class="row2">
            <div class="fg"><label>Assign to</label><select id="tt-assignee"></select></div>
            <div class="fg"><label>Priority</label>
                <select id="tt-priority">
                    <option value="low">Low</option>
                    <option value="medium" selected>Medium</option>
                    <option value="high">High</option>
                    <option value="urgent">Urgent</option>
                </select>
            </div>
        </div>
        <div class="fg"><label>Due date (optional)</label><input type="date" id="tt-due" /></div>
        <div class="modal-actions">
            <button class="btn ghost" onclick="closeAssignTask()">Cancel</button>
            <button class="btn" id="tt-save-btn" onclick="submitAssignTask()">Assign task</button>
        </div>
    </div>
</div>

<!-- Create project modal -->
<div class="modal-overlay" id="proj-overlay" onclick="if(event.target===this)closeCreateProject()">
    <div class="modal">
        <h2>New project</h2>
        <div class="fg"><label>Project name</label><input type="text" id="proj-name" maxlength="150" placeholder="e.g. Website revamp" /></div>
        <div class="fg"><label>Description (optional)</label><textarea id="proj-desc" rows="3" placeholder="What's this project about?"></textarea></div>
        <div class="modal-actions">
            <button class="btn ghost" onclick="closeCreateProject()">Cancel</button>
            <button class="btn" id="proj-save-btn" onclick="submitCreateProject()">Create</button>
        </div>
    </div>
</div>

<!-- Create / edit event modal -->
<div class="modal-overlay" id="ev-overlay" onclick="if(event.target===this)closeEventModal()">
    <div class="modal">
        <h2 id="ev-modal-title">New event</h2>
        <input type="hidden" id="ev-id" />
        <div class="fg"><label>Title</label><input type="text" id="ev-title" maxlength="190" placeholder="e.g. Sprint review, Client demo, Team lunch" /></div>
        <div class="row2">
            <div class="fg"><label>Date</label><input type="date" id="ev-date" min="" /></div>
            <div class="fg"><label>Project (optional)</label><select id="ev-project"><option value="">— None —</option></select></div>
        </div>
        <div class="row2">
            <div class="fg"><label>Starts</label><input type="time" id="ev-start" /></div>
            <div class="fg"><label>Ends</label><input type="time" id="ev-end" /></div>
        </div>
        <div class="fg"><label>Location / link (optional)</label><input type="text" id="ev-location" maxlength="190" placeholder="Meeting room, address, or a Meet/Zoom link" /></div>
        <div class="fg"><label>Details (optional)</label><textarea id="ev-desc" rows="2" placeholder="Agenda, notes…"></textarea></div>
        <div class="fg">
            <label>Attendees — tap team members to include them</label>
            <div class="chip-picker" id="ev-attendees"></div>
        </div>
        <div class="modal-actions">
            <button class="btn ghost" onclick="closeEventModal()">Cancel</button>
            <button class="btn" id="ev-save-btn" onclick="saveEvent()">Save event</button>
        </div>
    </div>
</div>

<script src="js/api-client.js?v=2"></script>
<script>
const TEAM_ID = <?= (int)$teamId ?>;
const MY_ROLE = '<?= htmlspecialchars($role) ?>';
const MY_USER_ID = <?= (int)current_user_id() ?>;
const IS_MANAGER = MY_ROLE === 'owner' || MY_ROLE === 'manager';
let members = [];
let projects = [];
let editingEventId = null;
let editingCreatorId = null;

function esc(s) { return (s || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;'); }
function initials(name) { return (name || '?').split(' ').map(w => w[0]).join('').slice(0,2).toUpperCase(); }
const MONTHS = ['JAN','FEB','MAR','APR','MAY','JUN','JUL','AUG','SEP','OCT','NOV','DEC'];
function fmtTime(t) { if (!t) return ''; const [h,m] = t.split(':').map(Number);
    const ap = h >= 12 ? 'PM' : 'AM'; const hh = h % 12 || 12; return `${hh}:${String(m).padStart(2,'0')} ${ap}`; }

// ════════════════════════════════════════════
// TEAM CHAT — polls for new messages every few seconds. Not a true
// websocket-pushed "live" chat (this app runs on plain PHP hosting with
// no persistent socket server), but new messages typically show up within
// ~3 seconds for everyone in the team, which reads as live in practice.
// Messages older than 7 days are purged automatically by the backend on
// every send/list call — nothing to schedule or maintain.
// ════════════════════════════════════════════
let chatLastId = 0;
let chatPollHandle = null;
let chatLastDayKey = null;

function initChat() {
    loadChatInitial();
    if (chatPollHandle) clearInterval(chatPollHandle);
    chatPollHandle = setInterval(pollChat, 3000);
}

function chatIsScrolledToBottom(el) {
    return el.scrollHeight - el.scrollTop - el.clientHeight < 60;
}

function fmtChatTime(iso) {
    const d = new Date(iso.replace(' ', 'T'));
    return d.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' });
}

function fmtChatDay(iso) {
    const d = new Date(iso.replace(' ', 'T'));
    const today = new Date();
    const yest = new Date(); yest.setDate(today.getDate() - 1);
    const key = d.toDateString();
    if (key === today.toDateString()) return 'Today';
    if (key === yest.toDateString()) return 'Yesterday';
    return d.toLocaleDateString([], { weekday: 'long', month: 'short', day: 'numeric' });
}

// ── @mentions ──
// Typed as the unambiguous token @[Full Name] (inserted only via the
// autocomplete below, never hand-typed), so the backend can reliably tell
// "@[Jane Smith]" apart from an ordinary sentence containing an @ sign —
// multi-word names make a plain regex like /@(\w+)/ unsafe to rely on.
let chatMentionActive = false;
let chatMentionMatches = [];
let chatMentionIndex = 0;
let chatMentionStart = -1;

function renderMessageText(text, isMe) {
    return esc(text).replace(/@\[([^\]]+)\]/g, (full, name) => {
        const member = members.find(m => m.name.toLowerCase() === name.toLowerCase());
        const mine = member && member.id === MY_USER_ID;
        return `<span class="chat-mention${mine ? ' me-mentioned' : ''}">@${esc(name)}</span>`;
    });
}

function handleChatInputChange(el) {
    el.style.height = 'auto';
    el.style.height = Math.min(el.scrollHeight, 110) + 'px';

    const upToCursor = el.value.slice(0, el.selectionStart);
    const match = upToCursor.match(/@([a-zA-Z0-9 ]{0,30})$/);
    if (!match) { closeChatMentionMenu(); return; }

    const fragment = match[1].trim().toLowerCase();
    chatMentionMatches = members
        .filter(m => m.name.toLowerCase().includes(fragment))
        .slice(0, 6);

    if (!chatMentionMatches.length) { closeChatMentionMenu(); return; }

    chatMentionStart = upToCursor.length - match[0].length;
    chatMentionIndex = 0;
    chatMentionActive = true;
    renderChatMentionMenu();
}

function renderChatMentionMenu() {
    const menu = document.getElementById('chat-mention-menu');
    if (!chatMentionActive || !chatMentionMatches.length) { menu.style.display = 'none'; return; }
    menu.innerHTML = chatMentionMatches.map((m, i) => `
        <div class="chat-mention-opt${i === chatMentionIndex ? ' active' : ''}" onmousedown="event.preventDefault();selectChatMention(${i})">
            <span class="avatar">${initials(m.name)}</span>${esc(m.name)}
        </div>`).join('');
    menu.style.display = 'block';
}

function closeChatMentionMenu() {
    chatMentionActive = false;
    chatMentionMatches = [];
    document.getElementById('chat-mention-menu').style.display = 'none';
}

function selectChatMention(idx) {
    const member = chatMentionMatches[idx];
    if (!member) return;
    const input = document.getElementById('chat-input');
    const before = input.value.slice(0, chatMentionStart);
    const after = input.value.slice(input.selectionStart);
    const insert = `@[${member.name}] `;
    input.value = before + insert + after;
    const caret = before.length + insert.length;
    input.setSelectionRange(caret, caret);
    closeChatMentionMenu();
    input.focus();
}

function handleChatInputKeydown(event) {
    if (chatMentionActive) {
        if (event.key === 'ArrowDown') { event.preventDefault(); chatMentionIndex = (chatMentionIndex + 1) % chatMentionMatches.length; renderChatMentionMenu(); return; }
        if (event.key === 'ArrowUp') { event.preventDefault(); chatMentionIndex = (chatMentionIndex - 1 + chatMentionMatches.length) % chatMentionMatches.length; renderChatMentionMenu(); return; }
        if (event.key === 'Enter' || event.key === 'Tab') { event.preventDefault(); selectChatMention(chatMentionIndex); return; }
        if (event.key === 'Escape') { closeChatMentionMenu(); return; }
    }
    if (event.key === 'Enter' && !event.shiftKey) {
        event.preventDefault();
        sendChatMessage();
    }
}

function appendChatMessage(m) {
    const container = document.getElementById('chat-messages');
    const emptyEl = document.getElementById('chat-empty');
    if (emptyEl) emptyEl.remove();

    const dayKey = new Date(m.created_at.replace(' ', 'T')).toDateString();
    if (dayKey !== chatLastDayKey) {
        chatLastDayKey = dayKey;
        const div = document.createElement('div');
        div.className = 'chat-day-divider';
        div.textContent = fmtChatDay(m.created_at);
        container.appendChild(div);
    }

    const isMe = m.user_id === MY_USER_ID;
    const wrap = document.createElement('div');
    wrap.className = 'chat-msg' + (isMe ? ' me' : '');
    wrap.innerHTML = `
        <span class="avatar" title="${esc(m.name)}">${initials(m.name)}</span>
        <div class="chat-bubble-group">
            ${isMe ? '' : `<div class="chat-sender">${esc(m.name)}</div>`}
            <div class="chat-bubble">${renderMessageText(m.message, isMe)}</div>
            <span class="chat-time">${fmtChatTime(m.created_at)}</span>
        </div>`;
    container.appendChild(wrap);
}

async function loadChatInitial() {
    try {
        const { messages } = await Taskvel.request(`/api/team_chat.php?action=list&team_id=${TEAM_ID}`);
        const container = document.getElementById('chat-messages');
        container.innerHTML = messages.length
            ? ''
            : '<div class="chat-empty" id="chat-empty">No messages yet — say hello 👋</div>';
        chatLastDayKey = null;
        messages.forEach(appendChatMessage);
        if (messages.length) chatLastId = messages[messages.length - 1].id;
        container.scrollTop = container.scrollHeight;
    } catch (e) {
        document.getElementById('chat-messages').innerHTML = `<div class="chat-empty">Couldn't load chat.</div>`;
    }
}

async function pollChat() {
    try {
        const { messages } = await Taskvel.request(`/api/team_chat.php?action=list&team_id=${TEAM_ID}&since_id=${chatLastId}`);
        if (!messages.length) return;
        const container = document.getElementById('chat-messages');
        const wasAtBottom = chatIsScrolledToBottom(container);
        messages.forEach(appendChatMessage);
        chatLastId = messages[messages.length - 1].id;
        if (wasAtBottom) container.scrollTop = container.scrollHeight;
    } catch (e) { /* silent — next poll will retry */ }
}

async function sendChatMessage() {
    const input = document.getElementById('chat-input');
    const btn = document.getElementById('chat-send-btn');
    const text = input.value.trim();
    if (!text) return;
    closeChatMentionMenu();
    btn.disabled = true;
    try {
        const { message: m } = await Taskvel.request('/api/team_chat.php?action=send', {
            method: 'POST',
            body: { team_id: TEAM_ID, message: text }
        });
        input.value = '';
        input.style.height = 'auto';
        chatLastId = Math.max(chatLastId, m.id);
        appendChatMessage(m);
        const container = document.getElementById('chat-messages');
        container.scrollTop = container.scrollHeight;
    } catch (e) {
        toast(e.message || 'Could not send message');
    } finally {
        btn.disabled = false;
        input.focus();
    }
}

async function loadAll() {
    // Team name + stats
    try {
        const { team } = await Taskvel.request(`/api/teams.php?action=get&team_id=${TEAM_ID}`);
        document.getElementById('team-name').textContent = team.name;
        document.getElementById('crumb-team').textContent = team.name;
        document.title = team.name + ' · Taskvel Pro';
        document.getElementById('team-sub').textContent =
            `${team.member_count} member${team.member_count == 1 ? '' : 's'} · ${team.project_count} project${team.project_count == 1 ? '' : 's'} · ${team.upcoming_event_count} upcoming event${team.upcoming_event_count == 1 ? '' : 's'}`;
    } catch (e) { document.getElementById('team-name').textContent = 'Team'; }

    // Members (needed BEFORE events so attendee chips can render)
    try {
        const { members: m } = await Taskvel.request(`/api/teams.php?action=members&team_id=${TEAM_ID}`);
        members = m;
        renderMembers();
    } catch (e) { document.getElementById('member-list').innerHTML = `<div class="empty">Couldn't load members.</div>`; }

    // Projects
    try {
        const { projects: p } = await Taskvel.request(`/api/projects.php?action=list&team_id=${TEAM_ID}`);
        projects = p;
        renderProjects();
    } catch (e) { document.getElementById('project-list').innerHTML = `<div class="empty">Couldn't load projects.</div>`; }

    // Team tasks (after members, so assignee names/pickers always resolve to real people)
    loadTeamTasks();
    loadNotificationPref();

    // Events (after members, so attendees always resolve to real people)
    loadEvents();

    // Chat — starts polling once we know who we are on this team
    initChat();
}

async function loadNotificationPref() {
    try {
        const { notify_task_updates_email } = await Taskvel.settings.getNotificationPrefs();
        document.getElementById('notify-email-toggle').checked = notify_task_updates_email;
    } catch (e) { /* non-critical */ }
}
async function toggleEmailNotifications(checked) {
    try { await Taskvel.settings.setNotificationPrefs({ notify_task_updates_email: checked }); toast(checked ? 'Email updates on ✓' : 'Email updates off'); }
    catch (e) { toast(e.message || 'Could not save preference'); }
}

function renderMembers() {
    const list = document.getElementById('member-list');
    list.innerHTML = members.map(m => `
        <div class="card">
            <div class="member-row">
                <div class="member-id">
                    <span class="avatar" title="${esc(m.name)}">${initials(m.name)}</span>
                    <div style="min-width:0">
                        <div class="member-name">${esc(m.name)} ${m.role === 'owner' ? '👑' : ''}${m.id == MY_USER_ID ? ' <span style="color:var(--ink3);font-weight:400;font-size:11px">(you)</span>' : ''}</div>
                        <div class="member-email">${esc(m.email)}</div>
                    </div>
                </div>
                <div class="member-actions">
                    ${(MY_ROLE === 'owner' && m.role !== 'owner') ? `
                        <select class="role-select" onchange="changeRole(${m.id}, this.value)">
                            <option value="member" ${m.role === 'member' ? 'selected' : ''}>Member</option>
                            <option value="manager" ${m.role === 'manager' ? 'selected' : ''}>Manager</option>
                        </select>
                        <button class="btn ghost sm" onclick="removeMember(${m.id})">Remove</button>
                    ` : `<span class="role-badge role-${m.role}">${m.role}</span>`}
                </div>
            </div>
        </div>
    `).join('');
}

function renderProjects() {
    const list = document.getElementById('project-list');
    const sel = document.getElementById('ev-project');
    sel.innerHTML = '<option value="">— None —</option>' + projects.map(p => `<option value="${p.id}">${esc(p.name)}</option>`).join('');
    if (!projects.length) { list.innerHTML = `<div class="empty"><span class="ic">▦</span>No projects yet. Create one to start assigning tasks.</div>`; return; }
    list.innerHTML = projects.map(p => {
        const pct = p.task_count ? Math.round((p.done_count / p.task_count) * 100) : 0;
        return `
        <a class="card" href="project.php?id=${p.id}">
            <div class="proj-row">
                <div>
                    <div class="proj-name">${esc(p.name)}</div>
                    <div class="proj-meta">${p.done_count}/${p.task_count} tasks done · ${pct}%</div>
                    <div class="proj-bar"><i style="width:${pct}%"></i></div>
                </div>
                <span style="font-size:18px;color:var(--ink3)">→</span>
            </div>
        </a>`;
    }).join('');
}

// ─────────── EVENTS — the fix: every event carries + displays its team members ───────────
async function loadEvents() {
    const list = document.getElementById('event-list');
    try {
        const { events } = await Taskvel.request(`/api/team_events.php?action=list&team_id=${TEAM_ID}`);
        if (!events.length) {
            list.innerHTML = `<div class="empty"><span class="ic">📅</span>No events yet — plan a meeting, review, or deadline and pick which team members are attending.</div>`;
            return;
        }
        const todayStr = new Date().toISOString().slice(0,10);
        list.innerHTML = events.map(ev => {
            const [y, mo, d] = ev.event_date.split('-');
            const isPast = ev.event_date < todayStr;
            const me = (ev.attendees || []).find(a => a.id == MY_USER_ID);
            const canEdit = IS_MANAGER || ev.created_by == MY_USER_ID;
            const timeStr = ev.start_time ? fmtTime(ev.start_time) + (ev.end_time ? ' – ' + fmtTime(ev.end_time) : '') : '';
            return `
            <div class="card event-card ${isPast ? 'past' : ''}">
                <div class="event-date-chip"><div class="m">${MONTHS[parseInt(mo,10)-1]}</div><div class="d">${parseInt(d,10)}</div></div>
                <div class="event-main">
                    <div class="event-title">${esc(ev.title)} ${ev.project_name ? `<span class="proj-tag">${esc(ev.project_name)}</span>` : ''}</div>
                    <div class="event-info">
                        ${timeStr ? `<span>🕐 ${timeStr}</span>` : ''}
                        ${ev.location ? `<span>📍 ${esc(ev.location)}</span>` : ''}
                        <span>by ${esc(ev.creator_name || '—')}</span>
                    </div>
                    ${ev.description ? `<div style="font-size:12.5px;color:var(--ink2);margin-top:7px;line-height:1.5">${esc(ev.description)}</div>` : ''}
                    <div class="event-attendees">
                        <span class="avatar-stack">${(ev.attendees || []).slice(0,7).map(a =>
                            `<span class="avatar" title="${esc(a.name)} · ${a.rsvp}">${initials(a.name)}</span>`).join('')}</span>
                        <span class="att-count">${ev.attendee_count} attendee${ev.attendee_count == 1 ? '' : 's'}:
                            ${(ev.attendees || []).map(a => esc(a.name.split(' ')[0])).join(', ')}</span>
                        ${me ? `<span class="rsvp-pill rsvp-${me.rsvp}">${me.rsvp === 'going' ? '✓ going' : me.rsvp}</span>` : ''}
                    </div>
                    <div class="event-actions">
                        ${me && me.rsvp !== 'going' ? `<button class="btn sm" onclick="rsvp(${ev.id},'going')">✓ I'm going</button>` : ''}
                        ${me && me.rsvp === 'going' ? `<button class="btn ghost sm" onclick="rsvp(${ev.id},'declined')">Can't make it</button>` : ''}
                        ${!me ? `<button class="btn ghost sm" onclick="rsvp(${ev.id},'going')">Join event</button>` : ''}
                        ${canEdit ? `<button class="btn ghost sm" onclick='openEventModal(${JSON.stringify(ev).replace(/'/g,"&#39;")})'>Edit</button>
                                     <button class="btn ghost sm" style="color:var(--bad)" onclick="deleteEvent(${ev.id})">Delete</button>` : ''}
                    </div>
                </div>
            </div>`;
        }).join('');
    } catch (e) {
        list.innerHTML = `<div class="empty">Couldn't load events — ${esc(e.message)}.<br><small>If this is a fresh setup, run <b>sql/migration_11_team_events.sql</b>.</small></div>`;
    }
}

function renderAttendeePicker(selectedIds, creatorId) {
    const box = document.getElementById('ev-attendees');
    if (!members.length) { box.innerHTML = '<span style="font-size:12px;color:var(--ink3)">No members loaded yet…</span>'; return; }
    box.innerHTML = members.map(m => {
        const locked = m.id == creatorId;
        const on = locked || selectedIds.includes(m.id);
        return `<span class="chip ${on ? 'on' : ''} ${locked ? 'locked' : ''}" data-id="${m.id}"
            onclick="${locked ? '' : 'this.classList.toggle(\'on\')'}"
            title="${locked ? 'Event creator is always included' : esc(m.email)}">${esc(m.name)}${locked ? ' ★' : ''}</span>`;
    }).join('');
}

function openEventModal(ev = null) {
    editingEventId = ev ? ev.id : null;
    editingCreatorId = ev ? ev.created_by : MY_USER_ID;
    document.getElementById('ev-modal-title').textContent = ev ? 'Edit event' : 'New event';
    document.getElementById('ev-save-btn').textContent = ev ? 'Update event' : 'Save event';
    document.getElementById('ev-title').value = ev ? ev.title : '';
    document.getElementById('ev-date').value = ev ? ev.event_date : new Date().toISOString().slice(0,10);
    document.getElementById('ev-date').min = new Date().toISOString().slice(0,10);
    document.getElementById('ev-start').value = ev && ev.start_time ? ev.start_time.slice(0,5) : '';
    document.getElementById('ev-end').value = ev && ev.end_time ? ev.end_time.slice(0,5) : '';
    document.getElementById('ev-location').value = ev ? (ev.location || '') : '';
    document.getElementById('ev-desc').value = ev ? (ev.description || '') : '';
    document.getElementById('ev-project').value = ev && ev.project_id ? ev.project_id : '';
    renderAttendeePicker(ev ? (ev.attendees || []).map(a => a.id) : members.map(m => m.id), editingCreatorId);
    document.getElementById('ev-overlay').classList.add('open');
    document.getElementById('ev-title').focus();
}
function closeEventModal() { document.getElementById('ev-overlay').classList.remove('open'); }

async function saveEvent() {
    const title = document.getElementById('ev-title').value.trim();
    const event_date = document.getElementById('ev-date').value;
    if (!title) { toast('Give the event a title'); return; }
    if (!event_date) { toast('Pick a date'); return; }
    const btn = document.getElementById('ev-save-btn');
    if (btn.disabled) return;
    btn.disabled = true;
    const orig = btn.textContent;
    btn.textContent = editingEventId ? 'Updating…' : 'Saving…';
    const attendee_ids = [...document.querySelectorAll('#ev-attendees .chip.on')].map(c => parseInt(c.dataset.id, 10));
    const payload = {
        team_id: TEAM_ID, title, event_date,
        start_time: document.getElementById('ev-start').value || null,
        end_time: document.getElementById('ev-end').value || null,
        location: document.getElementById('ev-location').value.trim(),
        description: document.getElementById('ev-desc').value.trim(),
        project_id: document.getElementById('ev-project').value || null,
        attendee_ids,
    };
    try {
        if (editingEventId) {
            payload.id = editingEventId;
            await Taskvel.request('/api/team_events.php?action=update', { method:'POST', body: payload });
            toast('Event updated ✓');
        } else {
            await Taskvel.request('/api/team_events.php?action=create', { method:'POST', body: payload });
            toast('Event created with ' + Math.max(attendee_ids.length,1) + ' attendee(s) ✓');
        }
        closeEventModal();
        loadEvents();
    } catch (e) { toast(e.message || 'Could not save event'); } finally {
        btn.disabled = false;
        btn.textContent = orig;
    }
}
async function rsvp(eventId, status) {
    try { await Taskvel.request('/api/team_events.php?action=rsvp', { method:'POST', body:{ event_id: eventId, status } }); loadEvents(); }
    catch (e) { toast(e.message); }
}
async function deleteEvent(eventId) {
    if (!confirm('Delete this event?')) return;
    try { await Taskvel.request(`/api/team_events.php?action=delete&id=${eventId}`, { method:'DELETE' }); loadEvents(); }
    catch (e) { toast(e.message); }
}

// ─────────── Team Tasks (Feature 1: assign tasks directly to team members) ───────────
let teamTasks = [];
let editingTeamTaskId = null;

async function loadTeamTasks() {
    const list = document.getElementById('team-task-list');
    try {
        const { tasks } = await Taskvel.request(`/api/team_tasks.php?action=list&team_id=${TEAM_ID}`);
        teamTasks = tasks;
        renderTeamTasks();
    } catch (e) {
        list.innerHTML = `<div class="empty">Couldn't load team tasks — ${esc(e.message)}.<br><small>If this is a fresh setup, run <b>sql/migration_09_team_tasks.sql</b>.</small></div>`;
    }
}

function renderTeamTasks() {
    const list = document.getElementById('team-task-list');
    if (!teamTasks.length) {
        list.innerHTML = `<div class="empty"><span class="ic">✅</span>No team tasks yet. Assign one to get a teammate moving on it.</div>`;
        return;
    }
    list.innerHTML = teamTasks.map(t => {
        const isAssignee = t.assignee_id == MY_USER_ID;
        const canEdit = IS_MANAGER;
        const canDelete = IS_MANAGER || t.created_by == MY_USER_ID;
        const overdue = t.due_date && t.due_date < new Date().toISOString().slice(0,10) && t.status !== 'done';
        return `
        <div class="card tt-card" data-id="${t.id}">
            <div class="tt-top">
                <div>
                    <div class="tt-title">${esc(t.title)}</div>
                    <div class="tt-meta">
                        <span class="tt-pill pri-${t.priority}">${t.priority}</span>
                        <span class="tt-pill status-${t.status}">${t.status.replace('_',' ')}</span>
                        ${t.assignee_name ? `<span>👤 ${esc(t.assignee_name)}</span>` : `<span>Unassigned</span>`}
                        ${t.due_date ? `<span style="${overdue ? 'color:var(--bad)' : ''}">📅 ${esc(t.due_date)}</span>` : ''}
                    </div>
                    ${t.description ? `<div style="font-size:12.5px;color:var(--ink2);margin-top:6px;line-height:1.5">${esc(t.description)}</div>` : ''}
                </div>
            </div>
            <div class="tt-bar"><i style="width:${t.progress}%"></i></div>
            <div class="tt-actions">
                <span style="font-size:11px;color:var(--ink3)">${t.progress}% complete</span>
                ${isAssignee || canEdit ? `<button class="btn sm" onclick='openProgressUpdate(${JSON.stringify(t).replace(/'/g,"&#39;")})'>Update progress</button>` : ''}
                <button class="btn ghost sm" onclick="openTaskHistory(${t.id}, ${JSON.stringify(esc(t.title))})">History</button>
                ${canEdit ? `<button class="btn ghost sm" onclick='openAssignTask(${JSON.stringify(t).replace(/'/g,"&#39;")})'>Reassign / edit</button>` : ''}
                ${canDelete ? `<button class="btn ghost sm" style="color:var(--bad)" onclick="deleteTeamTask(${t.id})">Delete</button>` : ''}
            </div>
        </div>`;
    }).join('');
}

function populateAssigneeSelect(selectedId = null) {
    const sel = document.getElementById('tt-assignee');
    const options = IS_MANAGER ? members : members.filter(m => m.id == MY_USER_ID);
    sel.innerHTML = options.map(m => `<option value="${m.id}" ${m.id == (selectedId ?? MY_USER_ID) ? 'selected' : ''}>${esc(m.name)}${m.id == MY_USER_ID ? ' (you)' : ''}</option>`).join('');
}

function openAssignTask(task = null) {
    editingTeamTaskId = task ? task.id : null;
    document.getElementById('tt-modal-title').textContent = task ? 'Edit task' : 'Assign a task';
    document.getElementById('tt-save-btn').textContent = task ? 'Save changes' : 'Assign task';
    document.getElementById('tt-title').value = task ? task.title : '';
    document.getElementById('tt-desc').value = task ? (task.description || '') : '';
    document.getElementById('tt-priority').value = task ? task.priority : 'medium';
    document.getElementById('tt-due').value = task ? (task.due_date || '') : '';
    populateAssigneeSelect(task ? task.assignee_id : MY_USER_ID);
    document.getElementById('tt-overlay').classList.add('open');
    document.getElementById('tt-title').focus();
}
function closeAssignTask() { document.getElementById('tt-overlay').classList.remove('open'); editingTeamTaskId = null; }

async function aiSuggestTeamTask() {
    const title = document.getElementById('tt-title').value.trim();
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
            body: { name: title, note: document.getElementById('tt-desc').value.trim() }
        });

        // This modal's priority options are low/medium/high/urgent, not
        // .../critical like the personal & project task forms, so map it.
        if (suggestion.urgency) {
            document.getElementById('tt-priority').value = suggestion.urgency === 'critical' ? 'urgent' : suggestion.urgency;
        }
        if (suggestion.deadline) document.getElementById('tt-due').value = suggestion.deadline;

        if ((suggestion.steps || []).length) {
            const descEl = document.getElementById('tt-desc');
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

async function aiQuickAddTeamTask() {
    const input = document.getElementById('ai-quickadd');
    const btn = document.getElementById('ai-quickadd-btn');
    const text = input.value.trim();
    if (!text) { toast('Type a task first, e.g. "Prepare client proposal by next Friday, high priority"'); return; }
    btn.disabled = true;
    btn.textContent = '…';
    try {
        const { task: t } = await Taskvel.request('/api/ai_parse_task.php?action=parse', {
            method: 'POST',
            body: { text }
        });
        let description = '';
        if ((t.steps || []).length) {
            description = 'Suggested subtasks:\n' + t.steps.map(s => `- ${s}`).join('\n');
        }
        await Taskvel.request('/api/team_tasks.php?action=create', {
            method: 'POST',
            body: {
                team_id: TEAM_ID,
                title: t.title,
                description,
                priority: t.urgency === 'critical' ? 'urgent' : t.urgency,
                assignee_id: null,
                due_date: t.deadline || null,
            }
        });
        input.value = '';
        toast(`✨ Added: "${t.title}"`);
        loadTeamTasks();
    } catch (e) {
        toast("Couldn't parse that: " + (e.message || 'error'));
    } finally {
        btn.disabled = false;
        btn.textContent = '✨ Add';
    }
}

async function submitAssignTask() {
    const title = document.getElementById('tt-title').value.trim();
    if (!title) { toast('Give the task a title'); return; }
    const btn = document.getElementById('tt-save-btn');
    if (btn.disabled) return;
    btn.disabled = true;
    const orig = btn.textContent;
    btn.textContent = editingTeamTaskId ? 'Saving…' : 'Assigning…';
    const payload = {
        team_id: TEAM_ID, title,
        description: document.getElementById('tt-desc').value.trim(),
        priority: document.getElementById('tt-priority').value,
        assignee_id: document.getElementById('tt-assignee').value || null,
        due_date: document.getElementById('tt-due').value || null,
    };
    try {
        if (editingTeamTaskId) {
            payload.id = editingTeamTaskId;
            await Taskvel.request('/api/team_tasks.php?action=update', { method:'POST', body: payload });
            toast('Task updated ✓');
        } else {
            await Taskvel.request('/api/team_tasks.php?action=create', { method:'POST', body: payload });
            toast('Task assigned ✓');
        }
        closeAssignTask();
        loadTeamTasks();
    } catch (e) { toast(e.message || 'Could not save task'); } finally {
        btn.disabled = false;
        btn.textContent = orig;
    }
}

// ─────────── Update Progress (Feature 3) ───────────
let pendingAttachments = []; // [{id, file_name}] staged for the task currently being updated
let progressTaskId = null;

function openProgressUpdate(task) {
    progressTaskId = task.id;
    pendingAttachments = [];
    document.getElementById('pu-task-title').textContent = task.title;
    document.getElementById('pu-status').value = task.status;
    document.getElementById('pu-progress').value = task.progress;
    document.getElementById('pu-progress-label').textContent = task.progress + '%';
    document.getElementById('pu-notes').value = '';
    document.getElementById('pu-file').value = '';
    renderPendingAttachments();
    document.getElementById('pu-overlay').classList.add('open');
}
function closeProgressUpdate() { document.getElementById('pu-overlay').classList.remove('open'); progressTaskId = null; }

function renderPendingAttachments() {
    document.getElementById('pu-attachments').innerHTML = pendingAttachments.length
        ? pendingAttachments.map(a => `<span class="tt-pill pri-low">📎 ${esc(a.file_name)}</span>`).join(' ')
        : '<span style="font-size:11.5px;color:var(--ink3)">No files attached yet.</span>';
}

async function uploadPendingAttachment(input) {
    const file = input.files[0];
    if (!file || !progressTaskId) return;
    try {
        const { attachment } = await Taskvel.attachments.upload(file, { teamTaskId: progressTaskId });
        pendingAttachments.push(attachment);
        renderPendingAttachments();
    } catch (e) { toast(e.message || 'Could not attach file'); }
    input.value = '';
}

async function submitProgressUpdate() {
    if (!progressTaskId) return;
    const btn = document.getElementById('pu-save-btn');
    if (btn.disabled) return;
    btn.disabled = true;
    const orig = btn.textContent;
    btn.textContent = 'Sending…';
    const payload = {
        id: progressTaskId,
        status: document.getElementById('pu-status').value,
        progress: parseInt(document.getElementById('pu-progress').value, 10),
        notes: document.getElementById('pu-notes').value.trim(),
        attachment_ids: pendingAttachments.map(a => a.id),
    };
    try {
        await Taskvel.request('/api/team_tasks.php?action=progress-update', { method: 'POST', body: payload });
        toast('Progress update sent ✓');
        closeProgressUpdate();
        loadTeamTasks();
    } catch (e) { toast(e.message || 'Could not send update'); } finally {
        btn.disabled = false;
        btn.textContent = orig;
    }
}

async function openTaskHistory(taskId, title) {
    document.getElementById('hist-task-title').textContent = title;
    document.getElementById('hist-overlay').classList.add('open');
    const box = document.getElementById('hist-list');
    box.innerHTML = '<div class="empty">Loading…</div>';
    try {
        const { updates } = await Taskvel.request(`/api/team_tasks.php?action=updates&task_id=${taskId}`);
        if (!updates.length) { box.innerHTML = '<div class="empty">No updates yet.</div>'; return; }
        box.innerHTML = (await Promise.all(updates.map(async u => {
            let attachments = [];
            try { ({ attachments } = await Taskvel.attachments.listForUpdate(u.id)); } catch (e) {}
            const when = new Date(u.created_at.replace(' ', 'T')).toLocaleString();
            return `
            <div class="card" style="padding:12px 14px">
                <div style="font-size:12.5px;font-weight:700;font-family:var(--font-display)">${esc(u.user_name)}</div>
                <div style="font-size:11.5px;color:var(--ink3);margin:2px 0 6px">${when} · ${(u.status_to||'').replace('_',' ')} · ${u.progress_to}%</div>
                ${u.notes ? `<div style="font-size:12.5px;color:var(--ink2);line-height:1.5">${esc(u.notes)}</div>` : ''}
                ${attachments.length ? `<div style="margin-top:8px">${attachments.map(a => `<a href="${esc(a.file_path)}" target="_blank" class="tt-pill pri-low" style="text-decoration:none">📎 ${esc(a.file_name)}</a>`).join(' ')}</div>` : ''}
            </div>`;
        }))).join('');
    } catch (e) { box.innerHTML = `<div class="empty">Couldn't load history — ${esc(e.message)}.</div>`; }
}
function closeTaskHistory() { document.getElementById('hist-overlay').classList.remove('open'); }

async function deleteTeamTask(id) {
    if (!confirm('Delete this task?')) return;
    try { await Taskvel.request(`/api/team_tasks.php?action=delete&id=${id}`, { method:'DELETE' }); loadTeamTasks(); }
    catch (e) { toast(e.message); }
}

// ─────────── Members & projects actions ───────────
function openInvite() { document.getElementById('inv-overlay').classList.add('open'); document.getElementById('inv-email').focus(); }
function closeInvite() { document.getElementById('inv-overlay').classList.remove('open'); document.getElementById('inv-email').value=''; }
async function submitInvite() {
    const email = document.getElementById('inv-email').value.trim();
    const role = document.getElementById('inv-role').value;
    if (!email) return;
    const btn = document.getElementById('inv-save-btn');
    if (btn.disabled) return;
    btn.disabled = true;
    const orig = btn.textContent;
    btn.textContent = 'Sending…';
    try {
        const res = await Taskvel.request('/api/teams.php?action=invite', { method:'POST', body:{ team_id: TEAM_ID, email, role } });
        closeInvite();
        toast(`${res.name} added to the team ✓`);
        loadAll();
    } catch (e) {
        if ((e.message || '').includes('Upgrade')) {
            if (confirm(e.message + '\n\nGo to billing now?')) window.location.href = 'billing.php?team_id=' + TEAM_ID;
        } else { toast(e.message || 'Could not invite'); }
    } finally {
        btn.disabled = false;
        btn.textContent = orig;
    }
}
async function changeRole(userId, role) {
    try { await Taskvel.request('/api/teams.php?action=update-role', { method:'POST', body:{ team_id: TEAM_ID, user_id: userId, role } }); loadAll(); }
    catch (e) { toast(e.message); loadAll(); }
}
async function removeMember(userId) {
    if (!confirm('Remove this person from the team? Their assigned tasks will become unassigned.')) return;
    try { await Taskvel.request('/api/teams.php?action=remove-member', { method:'POST', body:{ team_id: TEAM_ID, user_id: userId } }); loadAll(); }
    catch (e) { toast(e.message); }
}
async function deleteTeam() {
    if (!confirm('Delete this entire team, including all its projects, tasks, and events? This cannot be undone.')) return;
    try { await Taskvel.request(`/api/teams.php?action=delete&team_id=${TEAM_ID}`, { method:'DELETE' }); window.location.href = 'teams.php'; }
    catch (e) { toast(e.message); }
}
function openCreateProject() { document.getElementById('proj-overlay').classList.add('open'); document.getElementById('proj-name').focus(); }
function closeCreateProject() { document.getElementById('proj-overlay').classList.remove('open'); document.getElementById('proj-name').value=''; document.getElementById('proj-desc').value=''; }
async function submitCreateProject() {
    const name = document.getElementById('proj-name').value.trim();
    const description = document.getElementById('proj-desc').value.trim();
    if (!name) return;
    const btn = document.getElementById('proj-save-btn');
    if (btn.disabled) return;
    btn.disabled = true;
    const orig = btn.textContent;
    btn.textContent = 'Creating…';
    try {
        const res = await Taskvel.request('/api/projects.php?action=create', { method:'POST', body:{ team_id: TEAM_ID, name, description } });
        window.location.href = 'project.php?id=' + res.project_id;
    } catch (e) {
        toast(e.message || 'Could not create project');
        btn.disabled = false;
        btn.textContent = orig;
    }
}
loadAll();
</script>
</body>
</html>