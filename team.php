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
</style>
</head>
<body>
<div class="wrap">
    <?php pro_header($user, 'teams', '<a href="teams.php">Teams</a> <span style="opacity:.5">/</span> <span id="crumb-team">…</span>'); ?>

    <h1 class="page-title"><span id="team-name">Loading…</span> <span class="role-badge role-<?= htmlspecialchars($role) ?>"><?= htmlspecialchars($role) ?></span></h1>
    <div class="sub" id="team-sub"></div>

    <section>
        <h2>📅 Events <button class="btn sm" onclick="openEventModal()">+ New event</button></h2>
        <div class="card-list" id="event-list"></div>
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
            <button class="btn" onclick="submitInvite()">Send invite</button>
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
            <button class="btn" onclick="submitCreateProject()">Create</button>
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
            <div class="fg"><label>Date</label><input type="date" id="ev-date" /></div>
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

<script src="js/api-client.js"></script>
<script>
const TEAM_ID = <?= (int)$teamId ?>;
const MY_ROLE = '<?= htmlspecialchars($role) ?>';
const MY_USER_ID = <?= (int)current_user_id() ?>;
const IS_MANAGER = MY_ROLE === 'owner' || MY_ROLE === 'manager';
let members = [];
let projects = [];
let editingEventId = null;
let editingCreatorId = null;

function esc(s) { return (s || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
function initials(name) { return (name || '?').split(' ').map(w => w[0]).join('').slice(0,2).toUpperCase(); }
const MONTHS = ['JAN','FEB','MAR','APR','MAY','JUN','JUL','AUG','SEP','OCT','NOV','DEC'];
function fmtTime(t) { if (!t) return ''; const [h,m] = t.split(':').map(Number);
    const ap = h >= 12 ? 'PM' : 'AM'; const hh = h % 12 || 12; return `${hh}:${String(m).padStart(2,'0')} ${ap}`; }

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

    // Events (after members, so attendees always resolve to real people)
    loadEvents();
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
    } catch (e) { toast(e.message || 'Could not save event'); }
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

// ─────────── Members & projects actions ───────────
function openInvite() { document.getElementById('inv-overlay').classList.add('open'); document.getElementById('inv-email').focus(); }
function closeInvite() { document.getElementById('inv-overlay').classList.remove('open'); document.getElementById('inv-email').value=''; }
async function submitInvite() {
    const email = document.getElementById('inv-email').value.trim();
    const role = document.getElementById('inv-role').value;
    if (!email) return;
    try {
        const res = await Taskvel.request('/api/teams.php?action=invite', { method:'POST', body:{ team_id: TEAM_ID, email, role } });
        closeInvite();
        toast(`${res.name} added to the team ✓`);
        loadAll();
    } catch (e) {
        if ((e.message || '').includes('Upgrade')) {
            if (confirm(e.message + '\n\nGo to billing now?')) window.location.href = 'billing.php?team_id=' + TEAM_ID;
        } else { toast(e.message || 'Could not invite'); }
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
    try {
        const res = await Taskvel.request('/api/projects.php?action=create', { method:'POST', body:{ team_id: TEAM_ID, name, description } });
        window.location.href = 'project.php?id=' + res.project_id;
    } catch (e) { toast(e.message || 'Could not create project'); }
}
loadAll();
</script>
</body>
</html>
