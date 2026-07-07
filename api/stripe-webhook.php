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
    $teamId = (int)$session['client_reference_id'];
    $pdo->prepare('UPDATE teams SET plan = "pro", stripe_customer_id = ?, stripe_subscription_id = ?, plan_status = "active" WHERE id = ?')
        ->execute([$session['customer'], $session['subscription'], $teamId]);
}

if ($event['type'] === 'customer.subscription.deleted') {
    $sub = $event['data']['object'];
    $pdo->prepare('UPDATE teams SET plan = "free", plan_status = "canceled" WHERE stripe_subscription_id = ?')
        ->execute([$sub['id']]);
}

http_response_code(200);
echo 'ok';