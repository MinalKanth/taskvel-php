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
    @media (min-width:1040px) {
        .wrap { max-width:1040px; }
    }

    .billing-intro { margin-bottom:22px; }
    .billing-intro h1.page-title { margin-bottom:5px; }
    .billing-intro .sub { margin-bottom:0; }

    .billing-section {
        margin-top:22px;
    }

    .plan-card {
        position:relative;
        padding:22px 24px;
        border-radius:var(--r-lg);
        background:var(--bg-elev);
        border:1px solid var(--line);
        margin-bottom:18px;
        box-shadow:var(--shadow-sm);
        overflow:hidden;
        transition:border-color .2s ease, box-shadow .25s ease, transform .2s var(--ease);
    }

    .plan-card:hover {
        border-color:var(--line2);
        box-shadow:var(--shadow);
    }

    .plan-card::before {
        content:'';
        position:absolute;
        left:0;
        top:0;
        bottom:0;
        width:3px;
        background:var(--accent);
        opacity:.65;
    }

    .plan-card.trial {
        border-color:var(--accent);
    }

    .plan-card.trial::before {
        opacity:1;
    }

    .plan-card.expired {
        border-color:var(--bad);
        background:var(--bad-soft);
    }

    .plan-card.expired::before {
        background:var(--bad);
        opacity:1;
    }

    .plan-badge {
        display:inline-flex;
        align-items:center;
        font-family:'JetBrains Mono',monospace;
        font-size:10px;
        font-weight:700;
        padding:5px 11px;
        border-radius:999px;
        background:var(--accent-soft);
        color:var(--accent);
        text-transform:uppercase;
        letter-spacing:.45px;
    }

    .plan-days {
        font-family:var(--font-display);
        font-size:34px;
        line-height:1.05;
        font-weight:800;
        color:var(--ink);
        margin:12px 0 5px;
    }

    .billing-actions {
        display:flex;
        align-items:center;
        gap:8px;
        flex-wrap:wrap;
        margin-top:14px;
    }

    .org-heading {
        display:flex;
        justify-content:space-between;
        align-items:flex-start;
        gap:16px;
        margin-bottom:18px;
    }

    .org-heading h3 {
        margin:0 0 5px;
        font-family:var(--font-display);
        font-size:18px;
        font-weight:700;
    }

    .org-status {
        display:inline-flex;
        align-items:center;
        gap:6px;
        font-family:'JetBrains Mono',monospace;
        font-size:10px;
        font-weight:700;
        padding:5px 10px;
        border-radius:999px;
        background:var(--bg-sunk);
        color:var(--ink3);
        text-transform:uppercase;
        letter-spacing:.4px;
        margin-top:3px;
    }

    .seat-grid {
        display:grid;
        grid-template-columns:repeat(3,1fr);
        gap:10px;
        margin:18px 0;
    }

    .seat-stat {
        text-align:center;
        padding:16px 10px;
        border-radius:var(--r-sm);
        background:var(--bg-sunk);
        border:1px solid var(--line);
        transition:border-color .2s ease, transform .2s ease;
    }

    .seat-stat:hover {
        border-color:var(--line2);
        transform:translateY(-1px);
    }

    .seat-stat b {
        display:block;
        font-family:'JetBrains Mono',monospace;
        font-size:22px;
        line-height:1.1;
        color:var(--ink);
        margin-bottom:5px;
    }

    .seat-stat span {
        font-size:10px;
        color:var(--ink3);
        text-transform:uppercase;
        letter-spacing:.45px;
    }

    .org-actions {
        display:flex;
        gap:8px;
        flex-wrap:wrap;
        margin-bottom:22px;
    }

    .dashboard-section {
        margin-top:24px;
        padding-top:22px;
        border-top:1px solid var(--line);
    }

    .dashboard-section:first-of-type {
        margin-top:0;
        padding-top:0;
        border-top:0;
    }

    .dashboard-section-title {
        display:flex;
        justify-content:space-between;
        align-items:center;
        gap:12px;
        margin-bottom:10px;
    }

    .dashboard-section-title h4 {
        margin:0;
        font-family:var(--font-display);
        font-size:12.5px;
        font-weight:700;
        text-transform:uppercase;
        letter-spacing:.5px;
        color:var(--ink3);
    }

    .section-sub {
        font-size:11.5px;
        color:var(--ink3);
        line-height:1.5;
        margin-bottom:12px;
    }

    .member-row {
        display:flex;
        justify-content:space-between;
        align-items:center;
        gap:14px;
        padding:12px 0;
        border-bottom:1px solid var(--line);
    }

    .member-row:last-child {
        border-bottom:none;
    }

    .member-info {
        min-width:0;
    }

    .member-name {
        font-family:var(--font-display);
        font-size:13px;
        font-weight:600;
        color:var(--ink);
    }

    .member-email {
        display:block;
        margin-top:3px;
        font-size:11px;
        color:var(--ink3);
        word-break:break-word;
    }

    .member-role {
        display:inline-flex;
        align-items:center;
        padding:3px 8px;
        margin-left:6px;
        border-radius:999px;
        background:var(--bg-sunk);
        color:var(--ink3);
        font-family:'JetBrains Mono',monospace;
        font-size:9px;
        text-transform:uppercase;
        letter-spacing:.35px;
    }

    .status-dot {
        width:7px;
        height:7px;
        border-radius:50%;
        display:inline-block;
        margin-right:7px;
        vertical-align:1px;
    }

    .status-dot.active { background:var(--good); box-shadow:0 0 0 3px var(--good-soft); }
    .status-dot.suspended { background:var(--warn); box-shadow:0 0 0 3px var(--warn-soft); }

    .member-actions {
        display:flex;
        gap:6px;
        flex-wrap:wrap;
        justify-content:flex-end;
        flex-shrink:0;
    }

    .progress-wrap {
        width:100%;
        overflow-x:auto;
        border:1px solid var(--line);
        border-radius:var(--r);
        background:var(--bg-elev);
    }

    .progress-table {
        width:100%;
        min-width:620px;
        border-collapse:collapse;
    }

    .progress-table th {
        text-align:left;
        padding:10px 12px;
        background:var(--bg-sunk);
        border-bottom:1px solid var(--line);
        font-family:'JetBrains Mono',monospace;
        font-size:9.5px;
        font-weight:700;
        text-transform:uppercase;
        letter-spacing:.45px;
        color:var(--ink3);
    }

    .progress-table th:not(:first-child),
    .progress-table td:not(:first-child) {
        text-align:center;
    }

    .progress-table td {
        padding:11px 12px;
        border-top:1px solid var(--line);
        font-size:12.5px;
        color:var(--ink2);
    }

    .progress-table tbody tr {
        transition:background .15s ease;
    }

    .progress-table tbody tr:hover {
        background:var(--bg-sunk);
    }

    .progress-table .employee-cell {
        text-align:left !important;
    }

    .progress-table .employee-name {
        font-family:var(--font-display);
        font-weight:600;
        color:var(--ink);
    }

    .progress-table .employee-email {
        display:block;
        margin-top:2px;
        font-size:10.5px;
        color:var(--ink3);
    }

    .progress-number {
        font-family:'JetBrains Mono',monospace;
        font-weight:700;
    }

    .activity-list {
        display:flex;
        flex-direction:column;
        gap:7px;
        max-height:340px;
        overflow-y:auto;
        padding-right:2px;
    }

    .activity-item {
        padding:11px 13px;
        border:1px solid var(--line);
        border-radius:var(--r-sm);
        background:var(--bg-elev);
        transition:border-color .15s ease, background .15s ease;
    }

    .activity-item:hover {
        border-color:var(--line2);
        background:var(--bg-sunk);
    }

    .activity-top {
        display:flex;
        justify-content:space-between;
        align-items:center;
        gap:10px;
    }

    .activity-event {
        font-family:var(--font-display);
        font-size:12px;
        font-weight:600;
        color:var(--ink);
    }

    .activity-date {
        color:var(--ink3);
        font-size:10px;
        white-space:nowrap;
    }

    .activity-meta {
        color:var(--ink3);
        font-size:10.5px;
        margin-top:4px;
    }

    .branding-box {
        display:grid;
        grid-template-columns:minmax(0,1fr) auto auto;
        align-items:end;
        gap:10px;
        margin-top:12px;
    }

    .field-label {
        display:block;
        margin-bottom:5px;
        font-family:var(--font-display);
        font-size:10.5px;
        font-weight:700;
        color:var(--ink3);
        text-transform:uppercase;
        letter-spacing:.45px;
    }

    .branding-box input[type="url"] {
        width:100%;
        padding:10px 12px;
        border:1px solid var(--line2);
        border-radius:var(--r-sm);
        background:var(--bg);
        color:var(--ink);
        font-family:var(--font-body);
        font-size:13px;
        transition:border-color .15s, box-shadow .15s;
    }

    .branding-box input[type="url"]:focus {
        outline:none;
        border-color:var(--accent);
        box-shadow:0 0 0 3px var(--ring);
    }

    .branding-color-wrap {
        min-width:56px;
    }

    .branding-box input[type="color"] {
        width:52px;
        height:38px;
        padding:2px;
        border-radius:var(--r-sm);
        border:1px solid var(--line);
        background:var(--bg-elev);
        cursor:pointer;
    }

    .report-row {
        display:flex;
        justify-content:space-between;
        align-items:center;
        gap:12px;
    }

    .alert-banner {
        margin-bottom:16px;
        padding:13px 15px;
        border-radius:var(--r);
        font-size:12.5px;
        line-height:1.5;
        font-weight:600;
    }

    .alert-banner.locked {
        background:var(--bad-soft);
        border:1px solid var(--bad);
        color:var(--bad);
    }

    .alert-banner.warning {
        background:var(--warn-soft);
        border:1px solid var(--warn);
        color:var(--warn);
    }

    @media (max-width:700px) {
        .seat-grid {
            grid-template-columns:1fr;
        }

        .org-heading {
            flex-direction:column;
        }

        .branding-box {
            grid-template-columns:1fr;
        }

        .branding-color-wrap {
            min-width:0;
        }

        .member-row {
            align-items:flex-start;
            flex-direction:column;
        }

        .member-actions {
            justify-content:flex-start;
        }

        .report-row {
            align-items:flex-start;
            flex-direction:column;
        }
    }

    @media (max-width:520px) {
        .plan-card {
            padding:19px 17px;
        }

        .billing-actions,
        .org-actions {
            flex-direction:column;
            align-items:stretch;
        }

        .billing-actions .btn,
        .org-actions .btn {
            width:100%;
        }
    }

    .btn-spinner {
        display:inline-block;
        width:13px;
        height:13px;
        border:2px solid rgba(255,255,255,.5);
        border-top-color:#fff;
        border-radius:50%;
        animation:btnspin .7s linear infinite;
        margin-right:4px;
        vertical-align:-2px;
    }

    .btn.ghost .btn-spinner {
        border-color:var(--line2);
        border-top-color:var(--ink2);
    }

    @keyframes btnspin {
        to { transform:rotate(360deg); }
    }
</style>
</head>
<body>
<div class="wrap">
    <?php pro_header($user, 'billing'); ?>

    <div class="billing-intro">
        <h1 class="page-title">💳 Billing &amp; Plan</h1>
        <div class="sub">Manage your Taskvel Pro subscription, organization seats, and team licensing.</div>
    </div>

    <div class="billing-section" id="plan-section"></div>
    <div class="billing-section" id="org-section"></div>
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
            box.innerHTML = `<div class="plan-card">
                <span class="plan-badge">Pro · via ${esc(s.membership.org_name)}</span>
                        <div class="sub" style="margin-top:10px">Your account is licensed by your organization. You have full Pro access as long as your seat stays active.</div>
                    </div>`;
            return;
        }
        if (s.plan === 'pro' && s.plan_source === 'trial') {
            box.innerHTML = `<div class="plan-card trial">
                <span class="plan-badge">Free trial</span>
                <div class="plan-days">${s.days_remaining} day${s.days_remaining===1?'':'s'} left</div>
                <div class="sub">Trial ends ${esc((s.trial_ends_at||'').slice(0,10))}. You have full Pro access until then.</div>
                <div class="billing-actions">
                    <button class="btn" onclick="startCheckout()">Upgrade to Pro now</button>
                </div>
            </div>`;
        } else if (s.plan === 'pro' && s.plan_source === 'stripe') {
            box.innerHTML = `<div class="plan-card">
                <span class="plan-badge">Pro</span>
                <div class="sub" style="margin-top:10px">You're subscribed to Taskvel Pro. Thanks for supporting Taskvel!</div>
            </div>`;
        } else if (s.trial_expired) {
            box.innerHTML = `<div class="plan-card expired">
                <span class="plan-badge" style="background:var(--bad-soft);color:var(--bad)">Trial ended</span>
                <div style="margin:10px 0 5px;font-family:var(--font-display);font-size:15px;font-weight:700">Your 30-day Pro trial has ended.</div>
                <div class="sub">You're on the free plan now (2 teams, 5 members per team, 1 project per team). Upgrade to get unlimited teams, seats, and projects back.</div>
                <div class="billing-actions">
                    <button class="btn" onclick="startCheckout()">Upgrade to Pro</button>
                </div>
            </div>`;
        } else {
            box.innerHTML = `<div class="plan-card">
                <span class="plan-badge" style="background:var(--bg-sunk);color:var(--ink3)">Free plan</span>
                <div class="sub" style="margin-top:8px">2 teams, 5 members per team, 1 project per team.</div>
                <div class="billing-actions">
                    <button class="btn" onclick="startCheckout()">Upgrade to Pro</button>
                </div>
            </div>`;
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
            <div class="org-heading">
                <div>
                    <h3>🏢 For teams &amp; companies</h3>
                    <div class="sub">Purchase seats for your whole team and manage everyone's Pro access from one dashboard.</div>
                </div>
            </div>
            <div class="billing-actions">
                <button class="btn" onclick="openCreateOrg()">Set up an organization</button>
            </div>
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
    ? `<div class="alert-banner locked">
         🔒 This organization is locked — payment wasn't received during the grace period. Every member has lost Pro access. Renew now to restore it instantly.
       </div>`
    : isGrace
        ? `<div class="alert-banner warning">
             ⚠️ Payment is overdue. Your account will be locked in <strong>${graceDaysLeft} day${graceDaysLeft === 1 ? '' : 's'}</strong> — kindly renew to avoid interruption.
           </div>`
        : '';
    box.innerHTML = `
        ${banner}

        <div class="plan-card">

            <div class="org-heading">
                <div>
                    <h3>🏢 ${esc(org.name)}</h3>
                    <div class="sub">
                        ${org.billing_cycle} billing · renews ${esc(org.renewal_date || '—')}
                    </div>
                    <span class="org-status">● ${esc(org.plan_status)}</span>
                </div>

                <button class="btn sm" onclick="openInvite()" ${isLocked ? 'disabled title="Renew to add employees"' : ''}>
                    + Add employee
                </button>
            </div>

            <div class="seat-grid">
                <div class="seat-stat">
                    <b>${seats.purchased}</b>
                    <span>Seats purchased</span>
                </div>
                <div class="seat-stat">
                    <b>${seats.assigned}</b>
                    <span>Assigned</span>
                </div>
                <div class="seat-stat">
                    <b>${seats.available}</b>
                    <span>Available</span>
                </div>
            </div>

            <div class="org-actions">
                <button class="btn ghost sm" id="add-seats-btn" onclick="addSeats()" ${isLocked ? 'disabled' : ''}>
                    + Add more seats
                </button>

                <button class="btn ghost sm"
                    onclick="openBusinessBundle()"
                    style="${isLocked ? 'border-color:var(--bad);color:var(--bad)' : ''}">
                    ${isLocked ? '🔒 Renew — Business, ' : '🏢 Business — '}<?= BUSINESS_BUNDLE_SEATS ?> seats bundled
                </button>
            </div>

            <div class="dashboard-section">
                <div class="dashboard-section-title">
                    <h4>Members</h4>
                </div>
                <div class="section-sub">Manage your organization members, access, and assigned seats.</div>
                <div id="org-members"></div>
            </div>

            <div class="dashboard-section">
                <div class="dashboard-section-title">
                    <h4>Projects &amp; teams</h4>
                </div>
                <div class="section-sub">Every Team/Project board your organization's employees work in — the actual collaborative Kanban work, separate from anyone's private to-do list.</div>
                <div id="org-projects"></div>
            </div>

            <div class="dashboard-section">
                <div class="dashboard-section-title">
                    <h4>Employee task progress</h4>
                </div>
                <div class="section-sub">Personal to-do list completion and assigned Team/Project work, side by side, per employee.</div>
                <div id="org-progress"></div>
            </div>

            <div class="dashboard-section">
                <div class="dashboard-section-title">
                    <h4>Activity log</h4>
                </div>
                <div class="section-sub">Recent organization and billing activity.</div>
                <div id="org-activity"></div>
            </div>

            <div class="dashboard-section">
                <div class="dashboard-section-title">
                    <h4>Branding</h4>
                </div>

                <div class="section-sub">
                    Replace Taskvel's default logo and accent color with your own, for every member of this organization.
                </div>

                <div class="branding-box">
                    <div>
                        <label class="field-label">Logo image URL</label>
                        <input type="url"
                            id="org-logo-url"
                            placeholder="https://yourcompany.com/logo.png"
                            value="${esc(org.logo_url || '')}">
                    </div>

                    <div class="branding-color-wrap">
                        <label class="field-label">Accent</label>
                        <input type="color"
                            id="org-brand-color"
                            value="${org.brand_color || '#4f46e5'}">
                    </div>

                    <button class="btn sm" onclick="saveOrgBranding(${org.id})">
                        Save branding
                    </button>
                </div>
            </div>

            <div class="dashboard-section">
                <div class="report-row">
                    <div>
                        <div class="dashboard-section-title" style="margin-bottom:3px">
                            <h4>Reports</h4>
                        </div>
                        <div class="section-sub" style="margin-bottom:0">
                            Export your organization's complete activity report.
                        </div>
                    </div>

                    <a class="btn ghost sm"
                        href="/api/organizations.php?action=export-report&org_id=${org.id}">
                        ⬇ Download CSV
                    </a>
                </div>
            </div>

        </div>`;
    loadOrgMembers(org.id);
    loadOrgProjects(org.id);
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
        box.innerHTML = `<div class="activity-list">
            ${activity.map(a => {
                const meta = (() => { try { return JSON.parse(a.meta || '{}'); } catch (e) { return {}; } })();
                const who = a.actor_name ? esc(a.actor_name) : 'System';
                return `<div class="activity-item">
                    <div class="activity-top">
                        <span class="activity-event">${esc(ACTIVITY_LABELS[a.event] || a.event)}</span>
                        <span class="activity-date">${new Date(a.created_at).toLocaleString()}</span>
                    </div>
                    <div class="activity-meta">
                        by ${who}${meta.email ? ' · ' + esc(meta.email) : ''}${meta.seats ? ' · ' + meta.seats + ' seat(s)' : ''}
                    </div>
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
            <div class="progress-wrap">
                <table class="progress-table">
                    <thead>
                        <tr>
                            <th rowspan="2" class="employee-cell">Employee</th>
                            <th colspan="3">Personal to-dos</th>
                            <th colspan="3">Team/project work</th>
                        </tr>
                        <tr>
                            <th>Done</th>
                            <th>Open</th>
                            <th>Overdue</th>
                            <th>Done</th>
                            <th>Open</th>
                            <th>Overdue</th>
                        </tr>
                    </thead>

                    <tbody>
                    ${progress.map(p => `
                        <tr>
                            <td class="employee-cell">
                                <span class="employee-name">${esc(p.name)}</span>
                                <span class="employee-email">${esc(p.email)}</span>
                            </td>

                            <td><span class="progress-number" style="color:var(--good)">${p.done_tasks || 0}</span></td>
                            <td><span class="progress-number">${p.open_tasks || 0}</span></td>
                            <td><span class="progress-number" style="color:${(p.overdue_tasks||0) > 0 ? 'var(--bad)' : 'var(--ink3)'}">${p.overdue_tasks || 0}</span></td>

                            <td><span class="progress-number" style="color:var(--good)">${p.team_done_tasks || 0}</span></td>
                            <td><span class="progress-number">${p.team_open_tasks || 0}</span></td>
                            <td><span class="progress-number" style="color:${(p.team_overdue_tasks||0) > 0 ? 'var(--bad)' : 'var(--ink3)'}">${p.team_overdue_tasks || 0}</span></td>
                        </tr>
                    `).join('')}
                    </tbody>
                </table>
            </div>`;
    } catch (e) { box.innerHTML = `<div class="empty">${esc(e.message)}</div>`; }
}

async function loadOrgProjects(orgId) {
    const box = document.getElementById('org-projects');
    if (!box) return;
    box.innerHTML = '<div class="empty">Loading…</div>';
    try {
        const { projects } = await Taskvel.request(`/api/organizations.php?action=org-projects&org_id=${orgId}`);
        if (!projects.length) {
            box.innerHTML = '<div class="empty">No employees are working in a Team/Project board yet — Teams are created separately from this organization, on the Teams page.</div>';
            return;
        }
        box.innerHTML = `
            <div class="progress-wrap">
                <table class="progress-table">
                    <thead>
                        <tr>
                            <th class="employee-cell">Project</th>
                            <th>Members</th>
                            <th>Done</th>
                            <th>Open</th>
                            <th>Overdue</th>
                            <th>Last activity</th>
                        </tr>
                    </thead>
                    <tbody>
                    ${projects.map(p => {
                        const total = p.total_tasks || 0;
                        const pct = total ? Math.round((p.done_tasks / total) * 100) : 0;
                        return `
                        <tr>
                            <td class="employee-cell">
                                <span class="employee-name">${esc(p.project_name)}${p.archived == 1 ? ' <span class="member-role">archived</span>' : ''}</span>
                                <span class="employee-email">${esc(p.team_name)} · ${total} task${total === 1 ? '' : 's'} · ${pct}% done</span>
                            </td>
                            <td><span class="progress-number">${p.org_members_in_team}</span></td>
                            <td><span class="progress-number" style="color:var(--good)">${p.done_tasks || 0}</span></td>
                            <td><span class="progress-number">${p.open_tasks || 0}</span></td>
                            <td><span class="progress-number" style="color:${(p.overdue_tasks||0) > 0 ? 'var(--bad)' : 'var(--ink3)'}">${p.overdue_tasks || 0}</span></td>
                            <td>${p.last_activity ? new Date(p.last_activity).toLocaleDateString() : '—'}</td>
                        </tr>`;
                    }).join('')}
                    </tbody>
                </table>
            </div>`;
    } catch (e) { box.innerHTML = `<div class="empty">${esc(e.message)}</div>`; }
}

async function loadOrgMembers(orgId) {
    const box = document.getElementById('org-members');
    try {
        const { members } = await Taskvel.request(`/api/organizations.php?action=members&org_id=${orgId}`);
        box.innerHTML = members.map(m => `
            <div class="member-row">
                <div class="member-info">
                    <div class="member-name">
                        <span class="status-dot ${m.status}"></span>${esc(m.name)}
                        <span class="member-role">${esc(m.role)}</span>
                    </div>
                    <span class="member-email">${esc(m.email)}</span>
                </div>

                <div class="member-actions">
                    ${m.role !== 'owner' ? (m.status === 'active'
                        ? `<button class="btn ghost sm" onclick="suspendMember(${orgId}, ${m.user_id})">Suspend</button>`
                        : `<button class="btn ghost sm" onclick="reactivateMember(${orgId}, ${m.user_id})">Reactivate</button>`) : ''}

                    ${m.role !== 'owner'
                        ? `<button class="btn ghost sm" style="color:var(--bad)" onclick="removeMember(${orgId}, ${m.user_id})">Remove</button>`
                        : ''}
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