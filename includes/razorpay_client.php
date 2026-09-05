<?php
require_once __DIR__ . '/../config/razorpay.php';
require_once __DIR__ . '/../config/db.php';

/**
 * Thin REST wrapper over the Razorpay API (HTTP Basic Auth: key_id as the
 * username, key_secret as the password) — no SDK dependency, same approach
 * as includes/stripe_client.php.
 */
function razorpay_api(string $method, string $path, array $fields = []): array
{
    if (RAZORPAY_KEY_ID === '' || RAZORPAY_KEY_SECRET === '') {
        throw new RuntimeException('Razorpay is not configured on this server yet (RAZORPAY_KEY_ID/RAZORPAY_KEY_SECRET empty).');
    }

    $opts = [
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_USERPWD => RAZORPAY_KEY_ID . ':' . RAZORPAY_KEY_SECRET,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
    ];
    if ($method !== 'GET') {
        $opts[CURLOPT_HTTPHEADER] = ['Content-Type: application/json'];
        $opts[CURLOPT_POSTFIELDS] = json_encode($fields);
    }

    $ch = curl_init('https://api.razorpay.com/v1' . $path);
    curl_setopt_array($ch, $opts);
    $raw = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($raw === false) throw new RuntimeException("Razorpay request failed: $curlError");
    $data = json_decode($raw, true);
    if ($httpCode >= 400) {
        throw new RuntimeException('Razorpay error: ' . ($data['error']['description'] ?? "HTTP $httpCode"));
    }
    return is_array($data) ? $data : [];
}

/**
 * Returns a Razorpay Plan id for the given (cache_key, amount, period),
 * creating it via the API the first time and reusing it on every later
 * call. Razorpay has no "get or create by amount" endpoint — every
 * Subscription must point at a real, pre-created Plan object.
 */
function razorpay_get_or_create_plan(string $cacheKey, float $amountRupees, string $period, string $name): string
{
    $amountPaise = (int)round($amountRupees * 100);

    $stmt = db()->prepare('SELECT plan_id, amount FROM razorpay_plans WHERE cache_key = ?');
    $stmt->execute([$cacheKey]);
    $cached = $stmt->fetch();
    if ($cached && (int)$cached['amount'] === $amountPaise) {
        return $cached['plan_id'];
    }

    $plan = razorpay_api('POST', '/plans', [
        'period' => $period, // 'monthly' | 'yearly'
        'interval' => 1,
        'item' => [
            'name' => $name,
            'amount' => $amountPaise,
            'currency' => RAZORPAY_CURRENCY,
        ],
    ]);
    if (empty($plan['id'])) {
        throw new RuntimeException('Razorpay did not return a plan id.');
    }

    db()->prepare(
        'INSERT INTO razorpay_plans (cache_key, plan_id, amount) VALUES (?, ?, ?)
         ON DUPLICATE KEY UPDATE plan_id = VALUES(plan_id), amount = VALUES(amount)'
    )->execute([$cacheKey, $plan['id'], $amountPaise]);

    return $plan['id'];
}

/**
 * Creates a Razorpay Subscription for the given plan. $notes carries the
 * same kind of routing information Stripe's client_reference_id does —
 * api/razorpay-webhook.php reads notes['ref'] the same way stripe-webhook.php
 * reads client_reference_id ('user:<id>' / 'org:<id>:seats:<n>').
 */
function razorpay_create_subscription(string $planId, int $totalCount, int $quantity, array $notes): array
{
    $sub = razorpay_api('POST', '/subscriptions', [
        'plan_id' => $planId,
        'total_count' => $totalCount,
        'quantity' => $quantity,
        'customer_notify' => 1,
        'notes' => $notes,
    ]);
    if (empty($sub['id'])) {
        throw new RuntimeException('Razorpay did not return a subscription id.');
    }
    return $sub;
}

// Verifies the signature Razorpay Checkout hands back to the browser right
// after a subscription is authorized (razorpay_payment_id +
// razorpay_subscription_id + razorpay_signature). Per Razorpay's documented
// formula for subscription checkouts: HMAC-SHA256('<payment_id>|<subscription_id>', key_secret).
function razorpay_verify_subscription_signature(string $paymentId, string $subscriptionId, string $signature): bool
{
    $expected = hash_hmac('sha256', $paymentId . '|' . $subscriptionId, RAZORPAY_KEY_SECRET);
    return hash_equals($expected, $signature);
}

// Verifies an incoming webhook call — HMAC-SHA256 of the raw JSON body,
// hex-encoded, using the separate Webhook Secret configured in the
// Razorpay Dashboard (NOT the API key_secret).
function razorpay_verify_webhook_signature(string $payload, string $signature): bool
{
    if (RAZORPAY_WEBHOOK_SECRET === '') return false;
    $expected = hash_hmac('sha256', $payload, RAZORPAY_WEBHOOK_SECRET);
    return hash_equals($expected, $signature);
}