<?php
// Public endpoint — Razorpay calls this directly, no session/CSRF available.
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/razorpay.php';
require_once __DIR__ . '/../includes/security.php'; // audit_log()
require_once __DIR__ . '/../includes/razorpay_client.php';

$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_RAZORPAY_SIGNATURE'] ?? '';

if (!razorpay_verify_webhook_signature($payload, $signature)) {
    http_response_code(400); exit('Invalid signature');
}

$event = json_decode($payload, true);
if (!is_array($event) || empty($event['id']) || empty($event['event'])) {
    http_response_code(400); exit('Malformed payload');
}
$pdo = db();

// Idempotency guard — Razorpay redelivers events on timeout/non-2xx
// response, mirrors stripe_processed_events for the Stripe webhook.
try {
    $pdo->prepare('INSERT INTO razorpay_processed_events (event_id) VALUES (?)')->execute([$event['id']]);
} catch (Throwable $e) {
    http_response_code(200); echo 'ok (duplicate, skipped)'; exit;
}

$type = $event['event'];

// Fires once a subscription's first payment is authorized/captured (UPI
// Autopay mandate confirmed, or first card charge) — the Razorpay
// equivalent of Stripe's checkout.session.completed.
if ($type === 'subscription.activated' || $type === 'subscription.authenticated') {
    $sub = $event['payload']['subscription']['entity'] ?? null;
    if ($sub) apply_razorpay_subscription_activated($pdo, $sub);
}

// Renewal charge succeeded — fires every billing cycle after the first.
if ($type === 'subscription.charged') {
    $sub = $event['payload']['subscription']['entity'] ?? null;
    $payment = $event['payload']['payment']['entity'] ?? null;
    if ($sub) apply_razorpay_subscription_charged($pdo, $sub, $payment);
}

// Cancelled, fully completed (hit total_count), or halted after repeated
// failed renewal attempts.
if (in_array($type, ['subscription.cancelled', 'subscription.completed', 'subscription.halted'], true)) {
    $sub = $event['payload']['subscription']['entity'] ?? null;
    if ($sub) apply_razorpay_subscription_ended($pdo, $sub, $type);
}

// A renewal charge failed (mandate limit exceeded, insufficient funds,
// etc). Same policy as Stripe's invoice.payment_failed — don't cut access
// here; Razorpay retries per its own schedule and only fires
// subscription.halted (handled above) once retries are exhausted.
if ($type === 'payment.failed') {
    $payment = $event['payload']['payment']['entity'] ?? null;
    $subId = $payment['subscription_id'] ?? null;
    if ($subId) apply_razorpay_payment_failed($pdo, $subId, $payment);
}

http_response_code(200);
echo 'ok';

// ---------------- helpers ----------------

function razorpay_ref_from_notes(array $sub): string
{
    return (string)($sub['notes']['ref'] ?? '');
}

function apply_razorpay_subscription_activated(PDO $pdo, array $sub): void
{
    $ref = razorpay_ref_from_notes($sub);
    $subId = $sub['id'];
    $customerId = $sub['customer_id'] ?? null;

    if (str_starts_with($ref, 'org:')) {
        [, $orgId, , $seatsPurchasedNow] = array_pad(explode(':', $ref), 4, null);
        $orgId = (int)$orgId;
        $seatsPurchasedNow = max(0, (int)$seatsPurchasedNow);

        // Guarded on razorpay_subscription_id IS NULL — Razorpay can fire
        // both subscription.authenticated and subscription.activated for
        // the same subscription (two different event ids), so without this
        // guard the seat count would be credited twice for one purchase.
        if ($seatsPurchasedNow > 0) {
            $pdo->prepare('UPDATE organizations SET seats_purchased = seats_purchased + ?, razorpay_customer_id = ?, razorpay_subscription_id = ?, plan_status = "active" WHERE id = ? AND razorpay_subscription_id IS NULL')
                ->execute([$seatsPurchasedNow, $customerId, $subId, $orgId]);
        } else {
            $pdo->prepare('UPDATE organizations SET razorpay_customer_id = ?, razorpay_subscription_id = ?, plan_status = "active" WHERE id = ? AND razorpay_subscription_id IS NULL')
                ->execute([$customerId, $subId, $orgId]);
        }
        $pdo->prepare('INSERT INTO organization_billing_history (organization_id, description, seats, billing_cycle, razorpay_payment_id)
                       SELECT id, "Payment confirmed (Razorpay)", ?, billing_cycle, ? FROM organizations WHERE id = ?')
            ->execute([$seatsPurchasedNow ?: null, $subId, $orgId]);
    } elseif (str_starts_with($ref, 'user:')) {
        $userId = (int)substr($ref, 5);
        $pdo->prepare("UPDATE users SET plan = 'pro', plan_source = 'razorpay', razorpay_customer_id = ?, razorpay_subscription_id = ? WHERE id = ?")
            ->execute([$customerId, $subId, $userId]);
    }
}

function apply_razorpay_subscription_charged(PDO $pdo, array $sub, ?array $payment): void
{
    $subId = $sub['id'];

    $stmt = $pdo->prepare('SELECT id FROM organizations WHERE razorpay_subscription_id = ?');
    $stmt->execute([$subId]);
    if ($orgId = $stmt->fetchColumn()) {
        require_once __DIR__ . '/../includes/licensing.php';
        reactivate_org_billing((int)$orgId);
        $pdo->prepare('INSERT INTO organization_billing_history (organization_id, description, amount_cents, currency, razorpay_payment_id)
                       VALUES (?, "Payment confirmed (Razorpay)", ?, ?, ?)')
            ->execute([$orgId, $payment['amount'] ?? null, $payment['currency'] ?? 'INR', $payment['id'] ?? null]);
        audit_log(null, 'org_payment_succeeded', ['organization_id' => (int)$orgId, 'razorpay_payment_id' => $payment['id'] ?? null]);
        return;
    }

    $stmt = $pdo->prepare('SELECT id FROM users WHERE razorpay_subscription_id = ?');
    $stmt->execute([$subId]);
    if ($userId = $stmt->fetchColumn()) {
        // Renewal succeeded for an individual subscriber — plan/plan_source
        // are already 'pro'/'razorpay' from activation; just log it.
        audit_log((int)$userId, 'user_payment_succeeded', ['razorpay_payment_id' => $payment['id'] ?? null]);
    }
}

function apply_razorpay_subscription_ended(PDO $pdo, array $sub, string $eventType): void
{
    $subId = $sub['id'];
    $pdo->prepare('UPDATE organizations SET plan_status = "canceled" WHERE razorpay_subscription_id = ?')
        ->execute([$subId]);

    $stmt = $pdo->prepare('SELECT id FROM users WHERE razorpay_subscription_id = ?');
    $stmt->execute([$subId]);
    if ($userId = $stmt->fetchColumn()) {
        require_once __DIR__ . '/../includes/licensing.php';
        $pdo->prepare("UPDATE users SET plan_source = 'none' WHERE id = ? AND plan_source = 'razorpay'")->execute([$userId]);
        recompute_user_plan((int)$userId);
        audit_log((int)$userId, 'user_subscription_updated', ['razorpay_status' => $eventType]);
    }
}

function apply_razorpay_payment_failed(PDO $pdo, string $subId, ?array $payment): void
{
    $pdo->prepare("UPDATE organizations SET plan_status = 'past_due', grace_ends_at = DATE_ADD(CURDATE(), INTERVAL 7 DAY) WHERE razorpay_subscription_id = ? AND plan_status = 'active'")
        ->execute([$subId]);

    $stmt = $pdo->prepare('SELECT id FROM organizations WHERE razorpay_subscription_id = ?');
    $stmt->execute([$subId]);
    if ($orgId = $stmt->fetchColumn()) {
        $pdo->prepare('INSERT INTO organization_billing_history (organization_id, description, amount_cents, currency, razorpay_payment_id)
                       VALUES (?, "Payment failed (Razorpay)", ?, ?, ?)')
            ->execute([$orgId, $payment['amount'] ?? null, $payment['currency'] ?? 'INR', $payment['id'] ?? null]);
        audit_log(null, 'org_payment_failed', ['organization_id' => (int)$orgId, 'razorpay_payment_id' => $payment['id'] ?? null]);
    }

    $stmt = $pdo->prepare('SELECT id FROM users WHERE razorpay_subscription_id = ?');
    $stmt->execute([$subId]);
    if ($userId = $stmt->fetchColumn()) {
        audit_log((int)$userId, 'user_payment_failed', ['razorpay_payment_id' => $payment['id'] ?? null]);
    }
}