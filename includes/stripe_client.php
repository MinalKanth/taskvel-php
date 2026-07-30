<?php
require_once __DIR__ . '/../config/stripe.php';

/**
 * Creates a Stripe Checkout Session via a direct REST call (no SDK
 * dependency, consistent with the rest of this codebase). Returns the
 * hosted checkout URL to redirect the browser to, or throws on failure.
 *
 * $lineItems: [['price' => 'price_...', 'quantity' => 1], ...]
 * $clientReferenceId: how api/stripe-webhook.php knows what to update when
 * payment completes — 'user:<id>' for a personal upgrade, 'org:<id>' for
 * an organization seat purchase (see stripe-webhook.php for parsing).
 */
function create_stripe_checkout_session(array $lineItems, string $mode, string $clientReferenceId, string $successUrl, string $cancelUrl, ?string $customerId = null): string
{
    if (STRIPE_SECRET_KEY === '') {
        throw new RuntimeException('Stripe is not configured on this server yet (STRIPE_SECRET_KEY is empty).');
    }

    $fields = [
        'mode' => $mode, // 'subscription'
        'success_url' => $successUrl,
        'cancel_url' => $cancelUrl,
        'client_reference_id' => $clientReferenceId,
    ];
    if ($customerId) $fields['customer'] = $customerId;
    foreach ($lineItems as $i => $item) {
        $fields["line_items[$i][price]"] = $item['price'];
        $fields["line_items[$i][quantity]"] = $item['quantity'];
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