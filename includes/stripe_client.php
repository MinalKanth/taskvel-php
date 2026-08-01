<?php
require_once __DIR__ . '/../config/stripe.php';

/**
 * Creates a Stripe Checkout Session via a direct REST call (no SDK
 * dependency, consistent with the rest of this codebase). Returns the
 * hosted checkout URL to redirect the browser to, or throws on failure.
 *
 * Each item in $lineItems is EITHER:
 *   - ['price' => 'price_...', 'quantity' => n]  — an existing Stripe
 *     Price object ID (managed in the Stripe Dashboard), OR
 *   - ['amount' => 99, 'currency' => 'usd', 'product_name' => 'Taskvel Pro',
 *     'interval' => 'month', 'quantity' => n]  — fully dynamic/inline
 *     pricing with NO pre-created Price object required. 'amount' is in
 *     whole currency units (e.g. 99 = $99.00); this function converts it
 *     to the minor-unit integer Stripe's API expects. Omit 'interval' for
 *     a one-time payment; include it ('month'/'year') for a subscription.
 *
 * This dual support means you can start with plain amounts (fastest to
 * configure) and move to Dashboard-managed Price objects later — e.g. for
 * proration rules, multiple currencies, or tiered pricing — without
 * touching this function again.
 *
 * $clientReferenceId: how api/stripe-webhook.php knows what to update when
 * payment completes — 'user:<id>' for a personal upgrade, 'org:<id>:seats:<n>'
 * for an organization seat purchase (see stripe-webhook.php for parsing).
 */
function create_stripe_checkout_session(array $lineItems, string $mode, string $clientReferenceId, string $successUrl, string $cancelUrl, ?string $customerId = null): string
{
    if (STRIPE_SECRET_KEY === '') {
        throw new RuntimeException('Stripe is not configured on this server yet (STRIPE_SECRET_KEY is empty).');
    }

    $fields = [
        'mode' => $mode, // 'subscription' or 'payment'
        'success_url' => $successUrl,
        'cancel_url' => $cancelUrl,
        'client_reference_id' => $clientReferenceId,
    ];
    if ($customerId) $fields['customer'] = $customerId;

    foreach ($lineItems as $i => $item) {
        $fields["line_items[$i][quantity]"] = $item['quantity'];

        if (!empty($item['price'])) {
            // Dashboard-managed Price object.
            $fields["line_items[$i][price]"] = $item['price'];
            continue;
        }

        // Dynamic/inline pricing — no Price object needed.
        if (empty($item['amount']) || (float)$item['amount'] <= 0) {
            throw new RuntimeException("Line item $i has neither a valid 'price' ID nor a positive 'amount' configured.");
        }
        $fields["line_items[$i][price_data][currency]"] = $item['currency'] ?? (defined('STRIPE_CURRENCY') ? STRIPE_CURRENCY : 'usd');
        $fields["line_items[$i][price_data][product_data][name]"] = $item['product_name'] ?? 'Taskvel Pro';
        $fields["line_items[$i][price_data][unit_amount]"] = (int)round(((float)$item['amount']) * 100); // dollars -> cents
        if (!empty($item['interval'])) {
            $fields["line_items[$i][price_data][recurring][interval]"] = $item['interval']; // 'month' | 'year'
        }
    }

    $ch = curl_init('https://api.stripe.com/v1/checkout/sessions');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($fields),
        CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . STRIPE_SECRET_KEY],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
    ]);
    $raw = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($raw === false) throw new RuntimeException("Stripe request failed: $curlError");
    $data = json_decode($raw, true);
    if ($httpCode >= 400 || empty($data['url'])) {
        throw new RuntimeException('Stripe error: ' . ($data['error']['message'] ?? "HTTP $httpCode"));
    }
    return $data['url'];
}