<?php
require_once __DIR__ . '/../includes/licensing.php';
require_once __DIR__ . '/../includes/stripe_client.php';
require_once __DIR__ . '/../config/stripe.php';
require_login();

$uid = current_user_id();
$pdo = db();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$in = body();

switch ("$method:$action") {

    // Personal trial/plan status — powers the billing.php banner/upgrade wall.
    case 'GET:status':
        $stmt = $pdo->prepare('SELECT plan, plan_source, trial_ends_at FROM users WHERE id = ?');
        $stmt->execute([$uid]);
        $user = $stmt->fetch();

        $daysRemaining = null;
        if ($user['plan_source'] === 'trial' && $user['trial_ends_at']) {
            $daysRemaining = max(0, (int)ceil((strtotime($user['trial_ends_at']) - time()) / 86400));
        }
        $trialExpired = $user['plan_source'] !== 'trial' && $user['plan'] === 'free' && $user['trial_ends_at'] !== null;

        json_response([
            'plan' => $user['plan'],
            'plan_source' => $user['plan_source'],
            'trial_ends_at' => $user['trial_ends_at'],
            'days_remaining' => $daysRemaining,
            'trial_expired' => $trialExpired,
            'membership' => user_organization_membership($uid),
        ]);
        break;

    // Individual "Upgrade to Pro" — one seat, billed to this user directly
    // (separate from an organization's seat-based billing in Part B).
    case 'POST:create-checkout-session':
        if (user_organization_membership($uid)) {
            json_response(['error' => 'Your account is already licensed through an organization.'], 422);
        }
        $base = APP_BASE_URL ?: ((!empty($_SERVER['HTTPS']) ? 'https://' : 'http://') . ($_SERVER['HTTP_HOST'] ?? ''));
        try {
            $url = create_stripe_checkout_session(
                [['amount' => STRIPE_PRICE_PRO, 'product_name' => 'Taskvel Pro (Monthly)', 'interval' => 'month', 'quantity' => 1]],
                'subscription',
                "user:$uid",
                "$base/billing.php?checkout=success",
                "$base/billing.php?checkout=cancelled"
            );
            json_response(['url' => $url]);
        } catch (Throwable $e) {
            json_response(['error' => $e->getMessage()], 502);
        }
        break;

    // Organization seat purchase — used both at org creation time (Part B)
    // and for "add additional seats at any time".
    case 'POST:create-org-checkout-session':
        $orgId = (int)($in['org_id'] ?? 0);
        require_org_admin($orgId);
        $seats = max(1, (int)($in['seats'] ?? 5));

        $stmt = $pdo->prepare('SELECT billing_cycle FROM organizations WHERE id = ?');
        $stmt->execute([$orgId]);
        $cycle = $stmt->fetchColumn();
        if (!$cycle) json_response(['error' => 'Organization not found'], 404);

        $amount = $cycle === 'yearly' ? STRIPE_PRICE_ORG_SEAT_YEARLY : STRIPE_PRICE_ORG_SEAT_MONTHLY;
        $interval = $cycle === 'yearly' ? 'year' : 'month';
        $base = APP_BASE_URL ?: ((!empty($_SERVER['HTTPS']) ? 'https://' : 'http://') . ($_SERVER['HTTP_HOST'] ?? ''));
        try {
            $url = create_stripe_checkout_session(
                [['amount' => $amount, 'product_name' => 'Taskvel Pro — Organization Seat (' . ucfirst($cycle) . ')', 'interval' => $interval, 'quantity' => $seats]],
                'subscription',
                // Seat count is encoded here (not just "org:$orgId") so the
                // webhook can apply the exact quantity purchased without a
                // second round-trip to Stripe's API to fetch line items.
                "org:$orgId:seats:$seats",
                "$base/billing.php?org_checkout=success",
                "$base/billing.php?org_checkout=cancelled"
            );
            json_response(['url' => $url]);
        } catch (Throwable $e) {
            json_response(['error' => $e->getMessage()], 502);
        }
        break;

    default:
        json_response(['error' => 'Unknown route'], 404);
}