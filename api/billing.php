<?php
require_once __DIR__ . '/../includes/licensing.php';
require_once __DIR__ . '/../includes/stripe_client.php';
require_once __DIR__ . '/../config/stripe.php';
require_once __DIR__ . '/../includes/razorpay_client.php';
require_once __DIR__ . '/../config/razorpay.php';
require_login();

$uid = current_user_id();
$pdo = db();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$in = body();

switch ("$method:$action") {

    // Personal trial/plan status — powers the billing.php banner/upgrade wall.
    case 'GET:status':
        // Always recompute before reading — this is the page most likely
        // to be stared at right after a trial/grace period should have
        // ended, so it must never show a number that's stale by hours or
        // days just because the person hasn't logged out and back in.
        recompute_user_plan($uid);
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
                // NEW:
[['amount' => STRIPE_PRICE_PRO, 'product_name' => 'Taskvel Pro', 'interval' => 'month', 'quantity' => 1]],
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

        $price = $cycle === 'yearly' ? STRIPE_PRICE_ORG_SEAT_YEARLY : STRIPE_PRICE_ORG_SEAT_MONTHLY;
        $base = APP_BASE_URL ?: ((!empty($_SERVER['HTTPS']) ? 'https://' : 'http://') . ($_SERVER['HTTP_HOST'] ?? ''));
        try {
            $url = create_stripe_checkout_session(
                // NEW:
[['amount' => $price, 'product_name' => 'Taskvel Pro — Organization seat', 'interval' => $cycle === 'yearly' ? 'year' : 'month', 'quantity' => $seats]],
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

    // Business tier — a flat-rate bundle of BUSINESS_BUNDLE_SEATS seats,
    // instead of the per-seat pricing above. Seat count is fixed at
    // BUSINESS_BUNDLE_SEATS regardless of Stripe's line-item "quantity"
    // (which stays 1 — this is one flat-rate bundle, not N of something),
    // so it's encoded directly into client_reference_id the same way the
    // per-seat flow already does.
    case 'POST:create-business-checkout-session':
        $orgId = (int)($in['org_id'] ?? 0);
        require_org_admin($orgId);
        $cycle = one_of($in['billing_cycle'] ?? 'monthly', ['monthly', 'yearly'], 'monthly');

        $stmt = $pdo->prepare('SELECT id FROM organizations WHERE id = ?');
        $stmt->execute([$orgId]);
        if (!$stmt->fetchColumn()) json_response(['error' => 'Organization not found'], 404);

        $amount = $cycle === 'yearly' ? STRIPE_PRICE_BUSINESS_YEARLY : STRIPE_PRICE_BUSINESS_MONTHLY;
        $interval = $cycle === 'yearly' ? 'year' : 'month';
        $base = APP_BASE_URL ?: ((!empty($_SERVER['HTTPS']) ? 'https://' : 'http://') . ($_SERVER['HTTP_HOST'] ?? ''));
        try {
            $url = create_stripe_checkout_session(
                [['amount' => $amount, 'product_name' => 'Taskvel Business — ' . BUSINESS_BUNDLE_SEATS . ' seats (' . ucfirst($cycle) . ')', 'interval' => $interval, 'quantity' => 1]],
                'subscription',
                'org:' . $orgId . ':seats:' . BUSINESS_BUNDLE_SEATS,
                "$base/billing.php?org_checkout=success",
                "$base/billing.php?org_checkout=cancelled"
            );
            json_response(['url' => $url]);
        } catch (Throwable $e) {
            json_response(['error' => $e->getMessage()], 502);
        }
        break;

//     default:
//         json_response(['error' => 'Unknown route'], 404);
// }
    // ================= RAZORPAY (UPI/QR-capable alternative to Stripe) =================

    // Individual "Upgrade to Pro" via Razorpay — sibling of
    // create-checkout-session above, same eligibility rule. Returns a
    // subscription_id for the browser to open with Razorpay's Checkout.js.
    case 'POST:create-razorpay-subscription':
        if (user_organization_membership($uid)) {
            json_response(['error' => 'Your account is already licensed through an organization.'], 422);
        }
        try {
            $planId = razorpay_get_or_create_plan('user_pro_month', RAZORPAY_PRICE_PRO, 'monthly', 'Taskvel Pro');
            $sub = razorpay_create_subscription($planId, RAZORPAY_TOTAL_CYCLES_MONTHLY, 1, ['ref' => "user:$uid"]);
            json_response(['subscription_id' => $sub['id'], 'key_id' => RAZORPAY_KEY_ID]);
        } catch (Throwable $e) {
            json_response(['error' => $e->getMessage()], 502);
        }
        break;

    // Organization seat purchase via Razorpay — sibling of
    // create-org-checkout-session above.
    case 'POST:create-razorpay-org-subscription':
        $orgId = (int)($in['org_id'] ?? 0);
        require_org_admin($orgId);
        $seats = max(1, (int)($in['seats'] ?? 5));

        $stmt = $pdo->prepare('SELECT billing_cycle FROM organizations WHERE id = ?');
        $stmt->execute([$orgId]);
        $cycle = $stmt->fetchColumn();
        if (!$cycle) json_response(['error' => 'Organization not found'], 404);

        $period = $cycle === 'yearly' ? 'yearly' : 'monthly';
        $price = $cycle === 'yearly' ? RAZORPAY_PRICE_ORG_SEAT_YEARLY : RAZORPAY_PRICE_ORG_SEAT_MONTHLY;
        $totalCount = $cycle === 'yearly' ? RAZORPAY_TOTAL_CYCLES_YEARLY : RAZORPAY_TOTAL_CYCLES_MONTHLY;
        try {
            $planId = razorpay_get_or_create_plan("org_seat_$period", $price, $period, 'Taskvel Pro — Organization seat');
            $sub = razorpay_create_subscription($planId, $totalCount, $seats, ['ref' => "org:$orgId:seats:$seats"]);
            json_response(['subscription_id' => $sub['id'], 'key_id' => RAZORPAY_KEY_ID]);
        } catch (Throwable $e) {
            json_response(['error' => $e->getMessage()], 502);
        }
        break;

    // Business tier via Razorpay — sibling of create-business-checkout-session above.
    case 'POST:create-razorpay-business-subscription':
        $orgId = (int)($in['org_id'] ?? 0);
        require_org_admin($orgId);
        $cycle = one_of($in['billing_cycle'] ?? 'monthly', ['monthly', 'yearly'], 'monthly');

        $stmt = $pdo->prepare('SELECT id FROM organizations WHERE id = ?');
        $stmt->execute([$orgId]);
        if (!$stmt->fetchColumn()) json_response(['error' => 'Organization not found'], 404);

        $amount = $cycle === 'yearly' ? RAZORPAY_PRICE_BUSINESS_YEARLY : RAZORPAY_PRICE_BUSINESS_MONTHLY;
        $totalCount = $cycle === 'yearly' ? RAZORPAY_TOTAL_CYCLES_YEARLY : RAZORPAY_TOTAL_CYCLES_MONTHLY;
        try {
            $planId = razorpay_get_or_create_plan("business_$cycle", $amount, $cycle, 'Taskvel Business — ' . BUSINESS_BUNDLE_SEATS . ' seats (' . ucfirst($cycle) . ')');
            $sub = razorpay_create_subscription($planId, $totalCount, 1, ['ref' => 'org:' . $orgId . ':seats:' . BUSINESS_BUNDLE_SEATS]);
            json_response(['subscription_id' => $sub['id'], 'key_id' => RAZORPAY_KEY_ID]);
        } catch (Throwable $e) {
            json_response(['error' => $e->getMessage()], 502);
        }
        break;

    // Client-side confirmation right after Razorpay Checkout succeeds for
    // an INDIVIDUAL subscription — verifies the signature Razorpay handed
    // the browser and grants Pro immediately, purely so the UI doesn't have
    // to wait on webhook delivery lag. api/razorpay-webhook.php still
    // applies the same update (idempotently) and is the authoritative path
    // for org seats and all renewals.
    case 'POST:confirm-razorpay-subscription':
        $paymentId = clean_str($in['razorpay_payment_id'] ?? '', 100);
        $subscriptionId = clean_str($in['razorpay_subscription_id'] ?? '', 100);
        $signature = clean_str($in['razorpay_signature'] ?? '', 200);
        if ($paymentId === '' || $subscriptionId === '' || $signature === '') {
            json_response(['error' => 'Missing Razorpay confirmation fields'], 422);
        }
        if (!razorpay_verify_subscription_signature($paymentId, $subscriptionId, $signature)) {
            json_response(['error' => 'Could not verify payment signature'], 400);
        }
        if (!user_organization_membership($uid)) {
            $pdo->prepare("UPDATE users SET plan = 'pro', plan_source = 'razorpay', razorpay_subscription_id = ? WHERE id = ?")
                ->execute([$subscriptionId, $uid]);
        }
        json_response(['ok' => true]);
        break;

    default:
        json_response(['error' => 'Unknown route'], 404);
}