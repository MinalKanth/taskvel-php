<?php
// Public endpoint — Stripe calls this directly, no session/CSRF available.
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/stripe.php';

$payload = file_get_contents('php://input');
$sigHeader = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

// Verify the signature so only Stripe can trigger plan changes.
function verify_stripe_signature(string $payload, string $sigHeader, string $secret, int $toleranceSeconds = 300): bool
{
    $parts = [];
    foreach (explode(',', $sigHeader) as $pair) { [$k, $v] = explode('=', $pair, 2) + [null, null]; $parts[$k] = $v; }
    if (empty($parts['t']) || empty($parts['v1'])) return false;
    if (abs(time() - (int)$parts['t']) > $toleranceSeconds) return false; // reject stale/replayed payloads
    $expected = hash_hmac('sha256', $parts['t'] . '.' . $payload, $secret);
    return hash_equals($expected, $parts['v1']);
}

if (!verify_stripe_signature($payload, $sigHeader, STRIPE_WEBHOOK_SECRET)) {
    http_response_code(400); exit('Invalid signature');
}

$event = json_decode($payload, true);
if (!is_array($event) || empty($event['id']) || empty($event['type'])) {
    http_response_code(400); exit('Malformed payload');
}
$pdo = db();

// Idempotency guard — Stripe redelivers events on timeout/network error/
// non-2xx response, so the same event id can arrive more than once. This
// insert fails (and we exit early, still with 200) if we've already
// processed it, preventing double seat grants / duplicate billing rows.
try {
    $pdo->prepare('INSERT INTO stripe_processed_events (event_id) VALUES (?)')->execute([$event['id']]);
} catch (Throwable $e) {
    http_response_code(200); echo 'ok (duplicate, skipped)'; exit;
}

if ($event['type'] === 'checkout.session.completed') {
    $session = $event['data']['object'];
    $ref = (string)($session['client_reference_id'] ?? '');

    if (str_starts_with($ref, 'org:')) {
        // Feature 4, Part B — organization seat purchase (either the
        // initial purchase at org-creation time, or "add seats").
        // client_reference_id is "org:<id>:seats:<n>" — parsed here rather
        // than re-fetching the session's line items from Stripe's API.
        [, $orgId, , $seatsPurchasedNow] = array_pad(explode(':', $ref), 4, null);
        $orgId = (int)$orgId;
        $seatsPurchasedNow = max(0, (int)$seatsPurchasedNow);

        if ($seatsPurchasedNow > 0) {
            $pdo->prepare('UPDATE organizations SET seats_purchased = seats_purchased + ?, stripe_customer_id = ?, stripe_subscription_id = ?, plan_status = "active" WHERE id = ?')
                ->execute([$seatsPurchasedNow, $session['customer'], $session['subscription'], $orgId]);
        } else {
            // Fallback for older links without an encoded seat count — at
            // least keep the subscription/billing status correct.
            $pdo->prepare('UPDATE organizations SET stripe_customer_id = ?, stripe_subscription_id = ?, plan_status = "active" WHERE id = ?')
                ->execute([$session['customer'], $session['subscription'], $orgId]);
        }
        $pdo->prepare('INSERT INTO organization_billing_history (organization_id, description, seats, billing_cycle, amount_cents, currency, stripe_invoice_id)
                       SELECT id, "Payment confirmed", ?, billing_cycle, ?, ?, ? FROM organizations WHERE id = ?')
            ->execute([$seatsPurchasedNow ?: null, $session['amount_total'] ?? null, $session['currency'] ?? 'usd', $session['id'] ?? null, $orgId]);
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

// A renewal charge failed (expired/declined card, insufficient funds,
// etc). We do NOT revoke access here — Stripe will retry per its retry
// schedule and only fires customer.subscription.deleted (handled above)
// once it gives up entirely. This just records the problem so it's
// visible on the billing dashboard and via audit_log, so an admin can
// update their card before access actually lapses.
if ($event['type'] === 'invoice.payment_failed') {
    $invoice = $event['data']['object'];
    $subId = $invoice['subscription'] ?? null;
    if ($subId) {
        $pdo->prepare("UPDATE organizations SET plan_status = 'past_due', grace_ends_at = DATE_ADD(CURDATE(), INTERVAL 7 DAY) WHERE stripe_subscription_id = ? AND plan_status = 'active'")
            ->execute([$subId]);
        $pdo->prepare('UPDATE teams SET plan_status = "past_due" WHERE stripe_subscription_id = ? AND plan_status = "active"')
            ->execute([$subId]);

        $stmt = $pdo->prepare('SELECT id FROM organizations WHERE stripe_subscription_id = ?');
        $stmt->execute([$subId]);
        if ($orgId = $stmt->fetchColumn()) {
            $pdo->prepare('INSERT INTO organization_billing_history (organization_id, description, amount_cents, currency, stripe_invoice_id)
                           VALUES (?, "Payment failed", ?, ?, ?)')
                ->execute([$orgId, $invoice['amount_due'] ?? null, $invoice['currency'] ?? 'usd', $invoice['id'] ?? null]);
            audit_log(null, 'org_payment_failed', ['organization_id' => (int)$orgId, 'stripe_invoice_id' => $invoice['id'] ?? null]);
        }

        $stmt = $pdo->prepare('SELECT id FROM users WHERE stripe_subscription_id = ?');
        $stmt->execute([$subId]);
        if ($userId = $stmt->fetchColumn()) {
            audit_log((int)$userId, 'user_payment_failed', ['stripe_invoice_id' => $invoice['id'] ?? null]);
        }
    }
}

// A renewal charge succeeded — fires every month for an existing
// subscription (the first payment is checkout.session.completed above;
// every renewal after that comes through here). Revives an org from
// past_due/locked back to active and realigns billing to the calendar month.
if ($event['type'] === 'invoice.payment_succeeded') {
    $invoice = $event['data']['object'];
    $subId = $invoice['subscription'] ?? null;
    if ($subId) {
        $stmt = $pdo->prepare('SELECT id FROM organizations WHERE stripe_subscription_id = ?');
        $stmt->execute([$subId]);
        if ($orgId = $stmt->fetchColumn()) {
            require_once __DIR__ . '/../includes/licensing.php';
            reactivate_org_billing((int)$orgId);
            $pdo->prepare('INSERT INTO organization_billing_history (organization_id, description, amount_cents, currency, stripe_invoice_id)
                           VALUES (?, "Payment confirmed", ?, ?, ?)')
                ->execute([$orgId, $invoice['amount_paid'] ?? null, $invoice['currency'] ?? 'usd', $invoice['id'] ?? null]);
            audit_log(null, 'org_payment_succeeded', ['organization_id' => (int)$orgId, 'stripe_invoice_id' => $invoice['id'] ?? null]);
        }
    }
}

// Covers subscription state changes that aren't a fresh checkout or a
// full cancellation — e.g. Stripe moving a subscription to "past_due"/
// "unpaid" after retries are exhausted-but-not-yet-canceled, a manual
// quantity change made directly in the Stripe Dashboard (outside our
// add-seats flow), or recovery back to "active" after a late payment
// succeeds. Keeps plan_status and seats_purchased from drifting out of
// sync with what Stripe actually thinks is true.
if ($event['type'] === 'customer.subscription.updated') {
    $sub = $event['data']['object'];
    $status = $sub['status'] ?? '';
    $quantity = $sub['items']['data'][0]['quantity'] ?? null;

    // Map Stripe's subscription statuses onto our plan_status values.
    // 'active' and 'trialing' both count as active for our purposes;
    // 'past_due'/'unpaid' are surfaced but access isn't cut here (same
    // policy as invoice.payment_failed above); 'canceled'/'incomplete_expired'
    // are treated like the explicit deletion handler.
    $planStatus = match ($status) {
        'active', 'trialing' => 'active',
        'past_due', 'unpaid' => 'past_due',
        'canceled', 'incomplete_expired' => 'canceled',
        default => null, // unrecognized/irrelevant status — don't touch plan_status
    };

    $stmt = $pdo->prepare('SELECT id, seats_purchased FROM organizations WHERE stripe_subscription_id = ?');
    $stmt->execute([$sub['id']]);
    if ($org = $stmt->fetch()) {
        if ($planStatus !== null) {
            $pdo->prepare('UPDATE organizations SET plan_status = ? WHERE id = ?')->execute([$planStatus, $org['id']]);
        }
        // Only sync seat count if Stripe's quantity genuinely differs from
        // ours — avoids an unnecessary write (and a confusing billing
        // history row) on every unrelated subscription update.
        if ($quantity !== null && (int)$quantity !== (int)$org['seats_purchased']) {
            $pdo->prepare('UPDATE organizations SET seats_purchased = ? WHERE id = ?')->execute([(int)$quantity, $org['id']]);
            $pdo->prepare('INSERT INTO organization_billing_history (organization_id, description, seats) VALUES (?, "Seat count synced from Stripe", ?)')
                ->execute([$org['id'], (int)$quantity]);
        }
        audit_log(null, 'org_subscription_updated', ['organization_id' => (int)$org['id'], 'stripe_status' => $status, 'quantity' => $quantity]);
    }

    if ($planStatus !== null) {
        $pdo->prepare('UPDATE teams SET plan_status = ? WHERE stripe_subscription_id = ?')->execute([$planStatus, $sub['id']]);
    }

    $stmt = $pdo->prepare('SELECT id FROM users WHERE stripe_subscription_id = ?');
    $stmt->execute([$sub['id']]);
    if ($userId = $stmt->fetchColumn()) {
        if ($planStatus === 'canceled') {
            require_once __DIR__ . '/../includes/licensing.php';
            $pdo->prepare("UPDATE users SET plan_source = 'none' WHERE id = ? AND plan_source = 'stripe'")->execute([$userId]);
            recompute_user_plan((int)$userId);
        }
        audit_log((int)$userId, 'user_subscription_updated', ['stripe_status' => $status]);
    }
}

http_response_code(200);
echo 'ok';