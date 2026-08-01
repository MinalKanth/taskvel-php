<?php
define('STRIPE_SECRET_KEY', getenv('STRIPE_SECRET_KEY') ?: '');       // sk_live_... / sk_test_...
define('STRIPE_PUBLISHABLE_KEY', getenv('STRIPE_PUBLISHABLE_KEY') ?: ''); // pk_live_... / pk_test_... (only needed if you use Stripe.js client-side)
define('STRIPE_WEBHOOK_SECRET', getenv('STRIPE_WEBHOOK_SECRET') ?: ''); // whsec_...

// ------------------------------------------------------------
// Pricing — plain whole-currency-unit amounts (e.g. 99 = $99.00), NOT
// Stripe Price object IDs. includes/stripe_client.php builds the Checkout
// Session's pricing dynamically (price_data) from these numbers, so you
// never have to pre-create Price/Product objects in the Stripe Dashboard.
// Currency defaults to STRIPE_CURRENCY below (usd unless you change it).
// ------------------------------------------------------------
define('STRIPE_PRICE_PRO', (float)(getenv('STRIPE_PRICE_PRO') ?: 0));                       // individual "Upgrade to Pro", billed monthly
define('STRIPE_PRICE_BUSINESS', (float)(getenv('STRIPE_PRICE_BUSINESS') ?: 0));              // reserved for a future "business" team tier
define('STRIPE_PRICE_ORG_SEAT_MONTHLY', (float)(getenv('STRIPE_PRICE_ORG_SEAT_MONTHLY') ?: 0)); // per-seat price, billed monthly
define('STRIPE_PRICE_ORG_SEAT_YEARLY', (float)(getenv('STRIPE_PRICE_ORG_SEAT_YEARLY') ?: 0));   // per-seat price, billed yearly
define('STRIPE_CURRENCY', getenv('STRIPE_CURRENCY') ?: 'inr');                              // ISO currency code, e.g. 'usd', 'inr', 'eur'

define('APP_BASE_URL', getenv('APP_BASE_URL') ?: '');                  // e.g. https://app.taskvel.com — used for Stripe success/cancel URLs
