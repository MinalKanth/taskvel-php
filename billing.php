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
    box.innerHTML = `
    <div class="plan-card">
        <div class="row2" style="align-items:flex-start">
            <div>
                <h3 style="margin:0 0 4px;font-family:var(--font-display)">🏢 ${esc(org.name)}</h3>
                <div class="sub">${org.billing_cycle} billing · renews ${esc(org.renewal_date || '—')} · status: ${esc(org.plan_status)}</div>
            </div>
            <button class="btn sm" onclick="openInvite()">+ Add employee</button>
        </div>
        <div class="seat-grid">
            <div class="seat-stat"><b>${seats.purchased}</b>Seats purchased</div>
            <div class="seat-stat"><b>${seats.assigned}</b>Assigned</div>
            <div class="seat-stat"><b>${seats.available}</b>Available</div>
        </div>
        <button class="btn ghost sm" id="add-seats-btn" onclick="addSeats()">+ Add more seats</button>
        <button class="btn ghost sm" onclick="openBusinessBundle()">🏢 Business — <?= BUSINESS_BUNDLE_SEATS ?> seats bundled</button>
        <h4 style="margin:20px 0 6px;font-family:var(--font-display)">Members</h4>
        <div id="org-members"></div>
    </div>`;
    loadOrgMembers(org.id);
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