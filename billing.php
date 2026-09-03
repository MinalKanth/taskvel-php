<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/pro-shell.php';
require_once __DIR__ . '/config/stripe.php';
if (!current_user_id()) { header('Location: login.php'); exit; }
$user = current_user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="csrf-token" content="<?= htmlspecialchars(csrf_token()) ?>">
<?php pro_head('Billing & Plan'); ?>
<style>
    .btn-spinner { display:inline-block; width:13px; height:13px; border:2px solid rgba(255,255,255,.5); border-top-color:#fff; border-radius:50%; animation:btnspin .7s linear infinite; margin-right:2px; vertical-align:-2px; }
    .btn.ghost .btn-spinner { border-color:var(--line2); border-top-color:var(--ink2); }
    @keyframes btnspin { to { transform:rotate(360deg); } }
    .plan-card { padding:22px 24px; border-radius:16px; background:var(--bg-elev); border:1px solid var(--line); margin-bottom:20px; }
    .plan-card.trial { border-color:var(--accent); }
    .plan-card.expired { border-color:var(--bad); background:var(--bad-soft); }
    .plan-badge { font-size:11px; font-family:var(--font-display); font-weight:700; padding:4px 11px; border-radius:999px; background:var(--accent-soft); color:var(--accent); text-transform:uppercase; letter-spacing:.03em; }
    .plan-days { font-family:var(--font-display); font-size:32px; font-weight:800; margin:10px 0 2px; }
    .seat-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(120px,1fr)); gap:14px; margin:16px 0; }
    .seat-stat { text-align:center; padding:14px; border-radius:12px; background:var(--bg-sunk); }
    .seat-stat b { display:block; font-family:var(--font-display); font-size:22px; }
    .member-row { display:flex; justify-content:space-between; align-items:center; gap:10px; padding:10px 0; border-bottom:1px solid var(--line); }
    .member-row:last-child { border-bottom:none; }
    .status-dot { width:8px; height:8px; border-radius:50%; display:inline-block; margin-right:6px; }
    .status-dot.active { background:var(--good); } .status-dot.suspended { background:var(--warn); }
</style>
</head>
<body>
<div class="wrap">
    <?php pro_header($user, 'billing'); ?>

    <h1 class="page-title">💳 Billing &amp; Plan</h1>
    <div class="sub">Your Taskvel Pro subscription, trial status, and organization licensing.</div>

    <div id="plan-section" style="margin-top:20px"></div>
    <div id="org-section" style="margin-top:20px"></div>
    <?php pro_footer($user); ?>
</div>

<!-- Create organization modal -->
<div class="modal-overlay" id="org-overlay" onclick="if(event.target===this && !orgActionInFlight)closeCreateOrg()">
    <div class="modal">
        <h2>Set up your organization</h2>
        <div class="fg"><label>Organization name</label><input type="text" id="org-name" maxlength="150" placeholder="e.g. Acme Inc." /></div>
        <div class="row2">
            <div class="fg"><label>Seats</label><input type="number" id="org-seats" min="1" value="5" /></div>
            <div class="fg"><label>Billing</label>
                <select id="org-cycle"><option value="monthly">Monthly</option><option value="yearly">Yearly</option></select>
            </div>
        </div>
        <div class="modal-actions">
            <button class="btn ghost" onclick="closeCreateOrg()">Cancel</button>
            <button class="btn" onclick="submitCreateOrg()">Create organization</button>
        </div>
    </div>
</div>

<!-- Business bundle modal -->
<div class="modal-overlay" id="biz-overlay" onclick="if(event.target===this)closeBusinessBundle()">
    <div class="modal">
        <h2>Taskvel Business</h2>
        <div class="sub" style="margin-bottom:12px">A flat-rate bundle of <?= BUSINESS_BUNDLE_SEATS ?> seats — simpler than buying seats one at a time if you're onboarding a full team at once.</div>
        <div class="fg"><label>Billing</label>
            <select id="biz-cycle">
                <option value="monthly">Monthly — ₹<?= number_format(STRIPE_PRICE_BUSINESS_MONTHLY, 0) ?></option>
                <option value="yearly">Yearly — ₹<?= number_format(STRIPE_PRICE_BUSINESS_YEARLY, 0) ?> (2 months free)</option>
            </select>
        </div>
        <div class="modal-actions">
            <button class="btn ghost" onclick="closeBusinessBundle()">Cancel</button>
            <button class="btn" id="biz-save-btn" onclick="submitBusinessBundle()">Continue to payment</button>
        </div>
    </div>
</div>

<!-- Invite employee modal -->
<div class="modal-overlay" id="invite-overlay" onclick="if(event.target===this && !orgActionInFlight)closeInvite()">
    <div class="modal">
        <h2>Add an employee</h2>
        <div class="fg"><label>Email</label><input type="email" id="invite-email" placeholder="name@company.com" /></div>
        <div class="fg"><label>Role</label>
            <select id="invite-role"><option value="employee">Employee</option><option value="admin">Admin</option></select>
        </div>
        <div class="sub" style="margin-bottom:6px">If they don't have a Taskvel account yet, we'll create one and email them a temporary password.</div>
        <div class="modal-actions">
            <button class="btn ghost" onclick="closeInvite()">Cancel</button>
            <button class="btn" onclick="submitInvite()">Add employee</button>
        </div>

        <div style="margin:18px 0 4px;border-top:1px solid var(--line);padding-top:16px">
            <label style="font-size:12.5px;font-weight:700;display:block;margin-bottom:6px">Or bulk import from CSV</label>
            <div class="sub" style="margin-bottom:8px">One row per person: <code>email,role</code> — role is optional, defaults to employee. Max 50 rows per import.</div>
            <input type="file" id="bulk-csv-input" accept=".csv,text/csv" onchange="handleBulkCsv(event)" />
            <div id="bulk-csv-results" style="margin-top:10px;max-height:220px;overflow-y:auto"></div>
        </div>
    </div>
</div>

<script src="js/api-client.js?v=2"></script>
<script>
const MY_USER_ID = <?= (int)current_user_id() ?>;
let currentOrg = null;
let orgActionInFlight = false;

async function loadBillingPage() {
    await loadPlanStatus();
    await loadOrgSection();
}

async function loadPlanStatus() {
    const box = document.getElementById('plan-section');
    try {
        const s = await Taskvel.request('/api/billing.php?action=status');
        if (s.membership) {
            box.innerHTML = `<div class="plan-card"><span class="plan-badge">Pro · via ${esc(s.membership.org_name)}</span>
                <div style="margin-top:10px;font-size:13.5px;color:var(--ink2)">Your account is licensed by your organization. You have full Pro access as long as your seat stays active.</div></div>`;
            return;
        }
        if (s.plan === 'pro' && s.plan_source === 'trial') {
            box.innerHTML = `<div class="plan-card trial">
                <span class="plan-badge">Free trial</span>
                <div class="plan-days">${s.days_remaining} day${s.days_remaining===1?'':'s'} left</div>
                <div class="sub">Trial ends ${esc((s.trial_ends_at||'').slice(0,10))}. You have full Pro access until then.</div>
                <button class="btn" style="margin-top:12px" onclick="startCheckout()">Upgrade to Pro now</button>
            </div>`;
        } else if (s.plan === 'pro' && s.plan_source === 'stripe') {
            box.innerHTML = `<div class="plan-card"><span class="plan-badge">Pro</span>
                <div style="margin-top:10px;font-size:13.5px;color:var(--ink2)">You're subscribed to Taskvel Pro. Thanks for supporting Taskvel!</div></div>`;
        } else if (s.trial_expired) {
            box.innerHTML = `<div class="plan-card expired">
                <span class="plan-badge" style="background:var(--bad-soft);color:var(--bad)">Trial ended</span>
                <div style="margin:10px 0;font-size:14.5px;font-weight:600">Your 30-day Pro trial has ended.</div>
                <div class="sub">You're on the free plan now (2 teams, 5 members per team, 1 project per team). Upgrade to get unlimited teams, seats, and projects back.</div>
                <button class="btn" style="margin-top:12px" onclick="startCheckout()">Upgrade to Pro</button>
            </div>`;
        } else {
            box.innerHTML = `<div class="plan-card"><span class="plan-badge" style="background:var(--bg-sunk);color:var(--ink3)">Free plan</span>
                <div class="sub" style="margin-top:8px">2 teams, 5 members per team, 1 project per team.</div>
                <button class="btn" style="margin-top:12px" onclick="startCheckout()">Upgrade to Pro</button></div>`;
        }
    } catch (e) { box.innerHTML = `<div class="empty">Couldn't load your plan — ${esc(e.message)}.</div>`; }
}

async function startCheckout() {
    try {
        const { url } = await Taskvel.request('/api/billing.php?action=create-checkout-session', { method: 'POST' });
        window.location.href = url;
    } catch (e) { toast(e.message || 'Could not start checkout — is Stripe configured?'); }
}

async function loadOrgSection() {
    const box = document.getElementById('org-section');
    try {
        const { membership } = await Taskvel.request('/api/organizations.php?action=mine');
        if (!membership) {
            box.innerHTML = `<div class="plan-card">
                <h3 style="margin:0 0 6px;font-family:var(--font-display)">🏢 For teams &amp; companies</h3>
                <div class="sub" style="margin-bottom:12px">Purchase seats for your whole team and manage everyone's Pro access from one dashboard.</div>
                <button class="btn" onclick="openCreateOrg()">Set up an organization</button>
            </div>`;
            return;
        }
        currentOrg = membership;
        if (!['owner','admin'].includes(membership.role)) {
            box.innerHTML = `<div class="plan-card"><h3 style="margin:0 0 6px;font-family:var(--font-display)">🏢 ${esc(membership.org_name)}</h3>
                <div class="sub">You're licensed through this organization as ${esc(membership.role)}. Contact an admin to manage seats.</div></div>`;
            return;
        }
        const dash = await Taskvel.request(`/api/organizations.php?action=dashboard&org_id=${membership.organization_id}`);
        renderOrgDashboard(dash);
    } catch (e) { box.innerHTML = `<div class="empty">Couldn't load organization — ${esc(e.message)}.</div>`; }
}

function renderOrgDashboard(dash) {
    const box = document.getElementById('org-section');
    const org = dash.organization, seats = dash.seats;
    const isLocked = org.plan_status === 'locked';
    const isGrace = org.plan_status === 'past_due';
    let graceDaysLeft = null;
    if (isGrace && org.grace_ends_at) {
        graceDaysLeft = Math.max(0, Math.ceil((new Date(org.grace_ends_at + 'T00:00:00') - new Date(new Date().toDateString())) / 86400000));
    }
    const banner = isLocked
        ? `<div style="margin-bottom:16px;padding:14px 16px;border-radius:12px;background:var(--bad-soft);border:1px solid var(--bad);color:var(--bad);font-size:13.5px;font-weight:600">
             🔒 This organization is locked — payment wasn't received during the grace period. Every member has lost Pro access. Renew now to restore it instantly.
           </div>`
        : isGrace
            ? `<div style="margin-bottom:16px;padding:14px 16px;border-radius:12px;background:var(--warn-soft);border:1px solid var(--warn);color:var(--warn);font-size:13.5px;font-weight:600">
                 ⚠️ Payment is overdue. Your account will be locked in <strong>${graceDaysLeft} day${graceDaysLeft === 1 ? '' : 's'}</strong> — kindly renew to avoid interruption.
               </div>`
            : '';
    box.innerHTML = `
    ${banner}
    <div class="plan-card">
        <div class="row2" style="align-items:flex-start">
            <div>
                <h3 style="margin:0 0 4px;font-family:var(--font-display)">🏢 ${esc(org.name)}</h3>
                <div class="sub">${org.billing_cycle} billing · renews ${esc(org.renewal_date || '—')} · status: ${esc(org.plan_status)}</div>
            </div>
            <button class="btn sm" onclick="openInvite()" ${isLocked ? 'disabled title="Renew to add employees"' : ''}>+ Add employee</button>
        </div>
        <div class="seat-grid">
            <div class="seat-stat"><b>${seats.purchased}</b>Seats purchased</div>
            <div class="seat-stat"><b>${seats.assigned}</b>Assigned</div>
            <div class="seat-stat"><b>${seats.available}</b>Available</div>
        </div>
        <button class="btn ghost sm" id="add-seats-btn" onclick="addSeats()" ${isLocked ? 'disabled' : ''}>+ Add more seats</button>
        <button class="btn ghost sm" onclick="openBusinessBundle()" style="${isLocked ? 'border-color:var(--bad);color:var(--bad)' : ''}">${isLocked ? '🔒 Renew — Business, ' : '🏢 Business — '}<?= BUSINESS_BUNDLE_SEATS ?> seats bundled</button>
        <h4 style="margin:20px 0 6px;font-family:var(--font-display)">Members</h4>
        <div id="org-members"></div>
        <h4 style="margin:20px 0 6px;font-family:var(--font-display)">Team task progress</h4>
        <div id="org-progress"></div>
        <h4 style="margin:20px 0 6px;font-family:var(--font-display)">Activity log</h4>
        <div id="org-activity"></div>

        <h4 style="margin:20px 0 6px;font-family:var(--font-display)">Branding</h4>
        <div class="sub" style="margin-bottom:10px">Replace Taskvel's default logo and accent color with your own, for every member of this org.</div>
        <div style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap;margin-bottom:10px">
            <div style="flex:1;min-width:220px">
                <label style="font-size:11.5px;font-weight:700;display:block;margin-bottom:4px">Logo image URL</label>
                <input type="url" id="org-logo-url" placeholder="https://yourcompany.com/logo.png" value="${esc(org.logo_url || '')}" style="width:100%;padding:9px 12px;border-radius:9px;border:1px solid var(--line);background:var(--bg-elev)">
            </div>
            <div>
                <label style="font-size:11.5px;font-weight:700;display:block;margin-bottom:4px">Accent color</label>
                <input type="color" id="org-brand-color" value="${org.brand_color || '#4f46e5'}" style="width:52px;height:38px;padding:2px;border-radius:9px;border:1px solid var(--line);background:var(--bg-elev)">
            </div>
            <button class="btn sm" onclick="saveOrgBranding(${org.id})">Save branding</button>
        </div>

        <div style="margin:20px 0 6px;display:flex;justify-content:space-between;align-items:center">
            <h4 style="margin:0;font-family:var(--font-display)">Reports</h4>
            <a class="btn ghost sm" href="/api/organizations.php?action=export-report&org_id=${org.id}">⬇ Download full report (CSV)</a>
        </div>
    </div>`;
    loadOrgMembers(org.id);
    loadOrgProgress(org.id);
    loadOrgActivity(org.id);
}

async function saveOrgBranding(orgId) {
    const logoUrl = document.getElementById('org-logo-url').value.trim();
    const brandColor = document.getElementById('org-brand-color').value;
    try {
        await Taskvel.request('/api/organizations.php?action=update-branding', {
            method: 'POST', body: { org_id: orgId, logo_url: logoUrl, brand_color: brandColor },
        });
        toast('Branding updated ✓ — members will see it on their next visit');
    } catch (e) { toast(e.message || 'Could not save branding'); }
}

const ACTIVITY_LABELS = {
    org_created: 'Organization created',
    org_seats_added: 'Seats purchased',
    org_seat_assigned: 'Employee added',
    org_seat_released: 'Employee removed',
    org_member_suspended: 'Member suspended',
    org_member_reactivated: 'Member reactivated',
    org_seat_transferred: 'Seat transferred',
    org_payment_failed: 'Payment failed',
    org_payment_succeeded: 'Payment confirmed',
    org_subscription_updated: 'Subscription updated',
};

async function loadOrgActivity(orgId) {
    const box = document.getElementById('org-activity');
    if (!box) return;
    box.innerHTML = '<div class="empty">Loading…</div>';
    try {
        const { activity } = await Taskvel.request(`/api/organizations.php?action=activity&org_id=${orgId}`);
        if (!activity.length) { box.innerHTML = '<div class="empty">No activity yet.</div>'; return; }
        box.innerHTML = `<div style="display:flex;flex-direction:column;gap:6px;max-height:340px;overflow-y:auto">
            ${activity.map(a => {
                const meta = (() => { try { return JSON.parse(a.meta || '{}'); } catch (e) { return {}; } })();
                const who = a.actor_name ? esc(a.actor_name) : 'System';
                return `<div style="padding:10px 12px;border:1px solid var(--line);border-radius:10px;background:var(--bg-elev);font-size:12.5px">
                    <div style="display:flex;justify-content:space-between;gap:10px">
                        <b>${esc(ACTIVITY_LABELS[a.event] || a.event)}</b>
                        <span style="color:var(--ink3);font-size:11px;white-space:nowrap">${new Date(a.created_at).toLocaleString()}</span>
                    </div>
                    <div style="color:var(--ink3);margin-top:3px">by ${who}${meta.email ? ' · ' + esc(meta.email) : ''}${meta.seats ? ' · ' + meta.seats + ' seat(s)' : ''}</div>
                </div>`;
            }).join('')}
        </div>`;
    } catch (e) { box.innerHTML = `<div class="empty">${esc(e.message)}</div>`; }
}

async function loadOrgProgress(orgId) {
    const box = document.getElementById('org-progress');
    if (!box) return;
    box.innerHTML = '<div class="empty">Loading…</div>';
    try {
        const { progress } = await Taskvel.request(`/api/organizations.php?action=progress&org_id=${orgId}`);
        if (!progress.length) { box.innerHTML = '<div class="empty">No members yet.</div>'; return; }
        box.innerHTML = `
        <table style="width:100%;border-collapse:collapse;background:var(--bg-elev);border:1px solid var(--line);border-radius:12px;overflow:hidden">
            <thead><tr style="background:var(--bg-sunk)">
                <th style="text-align:left;padding:9px 12px;font-size:10.5px;text-transform:uppercase;color:var(--ink3)">Employee</th>
                <th style="text-align:center;padding:9px 12px;font-size:10.5px;text-transform:uppercase;color:var(--ink3)">Done</th>
                <th style="text-align:center;padding:9px 12px;font-size:10.5px;text-transform:uppercase;color:var(--ink3)">Open</th>
                <th style="text-align:center;padding:9px 12px;font-size:10.5px;text-transform:uppercase;color:var(--ink3)">Overdue</th>
                <th style="text-align:left;padding:9px 12px;font-size:10.5px;text-transform:uppercase;color:var(--ink3)">Last active</th>
            </tr></thead>
            <tbody>
            ${progress.map(p => `
                <tr style="border-top:1px solid var(--line)">
                    <td style="padding:9px 12px;font-size:13px"><b>${esc(p.name)}</b><div style="font-size:11px;color:var(--ink3)">${esc(p.email)}</div></td>
                    <td style="padding:9px 12px;text-align:center;color:var(--good);font-weight:700">${p.done_tasks || 0}</td>
                    <td style="padding:9px 12px;text-align:center">${p.open_tasks || 0}</td>
                    <td style="padding:9px 12px;text-align:center;color:${(p.overdue_tasks||0) > 0 ? 'var(--bad)' : 'var(--ink3)'};font-weight:${(p.overdue_tasks||0) > 0 ? '700' : '400'}">${p.overdue_tasks || 0}</td>
                    <td style="padding:9px 12px;font-size:12px;color:var(--ink3)">${p.last_activity ? new Date(p.last_activity).toLocaleDateString() : '—'}</td>
                </tr>`).join('')}
            </tbody>
        </table>`;
    } catch (e) { box.innerHTML = `<div class="empty">${esc(e.message)}</div>`; }
}

async function loadOrgMembers(orgId) {
    const box = document.getElementById('org-members');
    try {
        const { members } = await Taskvel.request(`/api/organizations.php?action=members&org_id=${orgId}`);
        box.innerHTML = members.map(m => `
            <div class="member-row">
                <div>
                    <span class="status-dot ${m.status}"></span><b>${esc(m.name)}</b> <span style="color:var(--ink3);font-size:12px">${esc(m.email)} · ${esc(m.role)}</span>
                </div>
                <div style="display:flex;gap:6px;flex-wrap:wrap">
                    ${m.role !== 'owner' ? (m.status === 'active'
                        ? `<button class="btn ghost sm" onclick="suspendMember(${orgId}, ${m.user_id})">Suspend</button>`
                        : `<button class="btn ghost sm" onclick="reactivateMember(${orgId}, ${m.user_id})">Reactivate</button>`) : ''}
                    ${m.role !== 'owner' ? `<button class="btn ghost sm" style="color:var(--bad)" onclick="removeMember(${orgId}, ${m.user_id})">Remove</button>` : ''}
                </div>
            </div>`).join('') || '<div class="empty">No members yet.</div>';
    } catch (e) { box.innerHTML = `<div class="empty">${esc(e.message)}</div>`; }
}

function openCreateOrg() { document.getElementById('org-overlay').classList.add('open'); }
function closeCreateOrg() { document.getElementById('org-overlay').classList.remove('open'); }
async function submitCreateOrg() {
    if (orgActionInFlight) return;
    const name = document.getElementById('org-name').value.trim();
    if (!name) { toast('Give your organization a name'); return; }
    const btn = document.querySelector('#org-overlay .modal-actions .btn:not(.ghost)');
    const cancelBtn = document.querySelector('#org-overlay .modal-actions .btn.ghost');
    const originalHTML = btn.innerHTML;
    orgActionInFlight = true;
    btn.disabled = true;
    cancelBtn.disabled = true;
    btn.innerHTML = '<span class="btn-spinner"></span> Creating organization…';
    btn.style.cursor = 'wait';
    try {
        const created = await Taskvel.request('/api/organizations.php?action=create', { method:'POST', body:{
            name, seats: parseInt(document.getElementById('org-seats').value, 10) || 5,
            billing_cycle: document.getElementById('org-cycle').value,
        }});
        // Org is created in 'pending' state with 0 seats and grants no Pro
        // access yet — send the owner straight to Stripe to pay for the
        // seats they just requested. plan_status flips to 'active' (and
        // seats_purchased is set) by the webhook once payment completes.
        const { url } = await Taskvel.request('/api/billing.php?action=create-org-checkout-session', {
            method: 'POST', body: { org_id: created.organization_id, seats: created.seats },
        });
        window.location.href = url;
        return; // leaving the page — skip the finally's loadOrgSection()
    } catch (e) {
        toast(e.message || 'Could not create organization');
    } finally {
        orgActionInFlight = false;
        btn.disabled = false;
        cancelBtn.disabled = false;
        btn.innerHTML = originalHTML;
        btn.style.cursor = '';
        await loadOrgSection();
    }
}

function openInvite() { document.getElementById('invite-overlay').classList.add('open'); }
function closeInvite() { document.getElementById('invite-overlay').classList.remove('open'); }
async function submitInvite() {
    if (orgActionInFlight) return;
    const email = document.getElementById('invite-email').value.trim();
    if (!email) { toast('Enter an email address'); return; }
    const btn = document.querySelector('#invite-overlay .modal-actions .btn:not(.ghost)');
    const cancelBtn = document.querySelector('#invite-overlay .modal-actions .btn.ghost');
    const originalHTML = btn.innerHTML;
    orgActionInFlight = true;
    btn.disabled = true;
    cancelBtn.disabled = true;
    btn.innerHTML = '<span class="btn-spinner"></span> Adding employee…';
    btn.style.cursor = 'wait';
    try {
        const res = await Taskvel.request('/api/organizations.php?action=invite', { method:'POST', body:{
            org_id: currentOrg.organization_id, email, role: document.getElementById('invite-role').value,
        }});
        if (res.email_sent) {
            toast(res.is_new_user ? 'Account created and invited ✓' : 'Added to your organization ✓');
        } else {
            toast(`Seat assigned, but the ${res.is_new_user ? 'onboarding email with their temporary password' : 'notification email'} could not be sent — check your SMTP settings in config/db.php.`);
        }
        closeInvite();
    } catch (e) {
    toast(e.message || 'Could not add employee');
        if ((e.message || '').toLowerCase().includes('already a member')) closeInvite();
    } finally {
        orgActionInFlight = false;
        btn.disabled = false;
        cancelBtn.disabled = false;
        btn.innerHTML = originalHTML;
        btn.style.cursor = '';
        await loadOrgSection();
    }
}

function parseBulkCsv(text) {
    return text.split(/\r?\n/).map(l => l.trim()).filter(Boolean).map(line => {
        const [email, role] = line.split(',').map(s => (s || '').trim());
        return { email, role: role || 'employee' };
    });
}

async function handleBulkCsv(event) {
    const file = event.target.files[0];
    if (!file || orgActionInFlight) { event.target.value = ''; return; }
    const resultsBox = document.getElementById('bulk-csv-results');
    const text = await file.text();
    const rows = parseBulkCsv(text).filter(r => r.email && r.email.toLowerCase() !== 'email');
    event.target.value = '';

    if (!rows.length) { toast('No valid rows found in that file'); return; }
    if (rows.length > 50) { toast('Import at most 50 rows at a time'); return; }

    orgActionInFlight = true;
    resultsBox.innerHTML = `<div class="empty">Importing ${rows.length} people…</div>`;
    try {
        const { results } = await Taskvel.request('/api/organizations.php?action=bulk-invite', {
            method: 'POST', body: { org_id: currentOrg.organization_id, rows },
        });
        const ok = results.filter(r => r.ok).length;
        toast(`Imported ${ok}/${results.length} ✓`);
        resultsBox.innerHTML = results.map(r => `
            <div style="padding:7px 10px;border-radius:8px;margin-bottom:5px;font-size:12px;
                background:${r.ok ? 'var(--good-soft)' : 'var(--bad-soft)'};color:${r.ok ? 'var(--good)' : 'var(--bad)'}">
                ${r.ok ? '✓' : '✕'} ${esc(r.email)} — ${r.ok ? (r.is_new_user ? 'account created & invited' : 'added') : esc(r.error)}
            </div>`).join('');
    } catch (e) {
        resultsBox.innerHTML = '';
        toast(e.message || 'Bulk import failed');
    } finally {
        orgActionInFlight = false;
        await loadOrgSection();
    }
}

async function addSeats() {
    const n = prompt('How many additional seats would you like to purchase?', '5');
    if (!n || isNaN(n) || parseInt(n, 10) < 1) return;
    const btn = document.getElementById('add-seats-btn');
    if (btn.disabled) return;
    btn.disabled = true;
    const orig = btn.textContent;
    btn.textContent = 'Starting checkout…';
    try {
        const { url } = await Taskvel.request('/api/billing.php?action=create-org-checkout-session', {
            method: 'POST', body: { org_id: currentOrg.organization_id, seats: parseInt(n, 10) },
        });
        window.location.href = url; // Stripe Checkout — seats are added by the webhook once payment completes
    } catch (e) {
        toast(e.message || 'Could not start checkout — is Stripe configured?');
        btn.disabled = false;
        btn.textContent = orig;
    }
}

function openBusinessBundle() { document.getElementById('biz-overlay').classList.add('open'); }
function closeBusinessBundle() { document.getElementById('biz-overlay').classList.remove('open'); }
async function submitBusinessBundle() {
    const btn = document.getElementById('biz-save-btn');
    if (btn.disabled) return;
    btn.disabled = true;
    const orig = btn.textContent;
    btn.textContent = 'Starting checkout…';
    try {
        const { url } = await Taskvel.request('/api/billing.php?action=create-business-checkout-session', {
            method: 'POST', body: { org_id: currentOrg.organization_id, billing_cycle: document.getElementById('biz-cycle').value },
        });
        window.location.href = url;
    } catch (e) {
        toast(e.message || 'Could not start checkout — is Stripe configured?');
        btn.disabled = false;
        btn.textContent = orig;
    }
}
async function suspendMember(orgId, userId) {
    try { await Taskvel.request('/api/organizations.php?action=suspend-member', { method:'POST', body:{ org_id: orgId, user_id: userId } }); loadOrgMembers(orgId); }
    catch (e) { toast(e.message); }
}
async function reactivateMember(orgId, userId) {
    try { await Taskvel.request('/api/organizations.php?action=reactivate-member', { method:'POST', body:{ org_id: orgId, user_id: userId } }); loadOrgMembers(orgId); }
    catch (e) { toast(e.message); }
}
async function removeMember(orgId, userId) {
    if (!confirm('Remove this person and free their seat?')) return;
    try { await Taskvel.request('/api/organizations.php?action=remove-member', { method:'POST', body:{ org_id: orgId, user_id: userId } }); loadOrgSection(); }
    catch (e) { toast(e.message); }
}

function esc(s) { const d = document.createElement('div'); d.textContent = s ?? ''; return d.innerHTML; }

loadBillingPage();
</script>
</body>
</html>