<?php
// Public endpoint — Stripe calls this directly, no session/CSRF available.
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/stripe.php';

$payload = file_get_contents('php://input');
$sigHeader = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

// Verify the signature so only Stripe can trigger plan changes.
function verify_stripe_signature(string $payload, string $sigHeader, string $secret): bool
{
    $parts = [];
    foreach (explode(',', $sigHeader) as $pair) { [$k, $v] = explode('=', $pair, 2) + [null, null]; $parts[$k] = $v; }
    if (empty($parts['t']) || empty($parts['v1'])) return false;
    $expected = hash_hmac('sha256', $parts['t'] . '.' . $payload, $secret);
    return hash_equals($expected, $parts['v1']);
}

if (!verify_stripe_signature($payload, $sigHeader, STRIPE_WEBHOOK_SECRET)) {
    http_response_code(400); exit('Invalid signature');
}

$event = json_decode($payload, true);
$pdo = db();

if ($event['type'] === 'checkout.session.completed') {
    $session = $event['data']['object'];
    $ref = (string)($session['client_reference_id'] ?? '');

    if (str_starts_with($ref, 'org:')) {
        // Feature 4, Part B — organization seat purchase (either the
        // initial purchase at org-creation time, or "add seats").
        $orgId = (int)substr($ref, 4);
        $pdo->prepare('UPDATE organizations SET stripe_customer_id = ?, stripe_subscription_id = ?, plan_status = "active" WHERE id = ?')
            ->execute([$session['customer'], $session['subscription'], $orgId]);
        $pdo->prepare('INSERT INTO organization_billing_history (organization_id, description, seats, billing_cycle, amount_cents, currency, stripe_invoice_id)
                       SELECT id, "Payment confirmed", seats_purchased, billing_cycle, ?, ?, ? FROM organizations WHERE id = ?')
            ->execute([$session['amount_total'] ?? null, $session['currency'] ?? 'usd', $session['id'] ?? null, $orgId]);
    } elseif (str_starts_with($ref, 'user:')) {
        // Feature 4, Part A — individual "Upgrade to Pro" (bypasses/ends
        // any trial; plan_source='stripe' so recompute_user_plan() never
        // downgrades a real paying subscriber).
        $userId = (int)substr($ref, 5);
        $pdo->prepare("UPDATE users SET plan = 'pro', plan_source = 'stripe', stripe_customer_id = ?, stripe_subscription_id = ? WHERE id = ?")
            ->execute([$session['customer'], $session['subscription'], $userId]);
    } else {
        // Legacy path — team-level plan upgrade (pre-dates Feature 4).
        $teamId = (int)$ref;
        $pdo->prepare('UPDATE teams SET plan = "pro", stripe_customer_id = ?, stripe_subscription_id = ?, plan_status = "active" WHERE id = ?')
            ->execute([$session['customer'], $session['subscription'], $teamId]);
    }
}

if ($event['type'] === 'customer.subscription.deleted') {
    $sub = $event['data']['object'];
    $pdo->prepare('UPDATE teams SET plan = "free", plan_status = "canceled" WHERE stripe_subscription_id = ?')
        ->execute([$sub['id']]);
    $pdo->prepare('UPDATE organizations SET plan_status = "canceled" WHERE stripe_subscription_id = ?')
        ->execute([$sub['id']]);
    // A personal Stripe subscriber whose subscription just ended goes back
    // through the normal plan recomputation (falls to 'free' unless they
    // also happen to still be within a trial window, which is virtually
    // never the case for someone who was paying, but keeps the one
    // recompute function as the single source of truth either way).
    $stmt = $pdo->prepare("SELECT id FROM users WHERE stripe_subscription_id = ?");
    $stmt->execute([$sub['id']]);
    if ($userId = $stmt->fetchColumn()) {
        require_once __DIR__ . '/../includes/licensing.php';
        $pdo->prepare("UPDATE users SET plan_source = 'none' WHERE id = ? AND plan_source = 'stripe'")->execute([$userId]);
        recompute_user_plan((int)$userId);
    }
}

http_response_code(200);
echo 'ok';