<?php
define('STRIPE_SECRET_KEY', getenv('STRIPE_SECRET_KEY') ?: '');       // sk_live_... / sk_test_...
define('STRIPE_PUBLISHABLE_KEY', getenv('STRIPE_PUBLISHABLE_KEY') ?: ''); // pk_live_... / pk_test_... (only needed if you use Stripe.js client-side)
define('STRIPE_WEBHOOK_SECRET', getenv('STRIPE_WEBHOOK_SECRET') ?: ''); // whsec_...

// ------------------------------------------------------------
// Pricing — plain whole-currency-unit amounts (e.g. 99 = ₹99.00), NOT
// Stripe Price object IDs. includes/stripe_client.php builds the Checkout
// Session's pricing dynamically (price_data) from these numbers, so you
// never have to pre-create Price/Product objects in the Stripe Dashboard.
//
// Hardcoded fallback defaults below (99 / 99 / 1099) so pricing still
// works even if .env fails to load for any reason (e.g. only .env.zip
// exists on the server instead of the extracted .env file) — getenv()
// still takes priority whenever it succeeds.
// ------------------------------------------------------------
define('STRIPE_PRICE_PRO', (float)(getenv('STRIPE_PRICE_PRO') ?: 99));                          // individual "Upgrade to Pro", billed monthly
define('STRIPE_PRICE_ORG_SEAT_MONTHLY', (float)(getenv('STRIPE_PRICE_ORG_SEAT_MONTHLY') ?: 99)); // per-seat price, billed monthly
define('STRIPE_PRICE_ORG_SEAT_YEARLY', (float)(getenv('STRIPE_PRICE_ORG_SEAT_YEARLY') ?: 1099));  // per-seat price, billed yearly

// Business tier — a flat-rate bundle of BUSINESS_BUNDLE_SEATS seats, instead
// of paying per-seat. Yearly is discounted relative to 12x the monthly rate
// (499 * 12 = 5988; 4990 works out to ~2 months free) — a standard SaaS
// yearly-commitment incentive.
define('BUSINESS_BUNDLE_SEATS', (int)(getenv('BUSINESS_BUNDLE_SEATS') ?: 5));
define('STRIPE_PRICE_BUSINESS_MONTHLY', (float)(getenv('STRIPE_PRICE_BUSINESS_MONTHLY') ?: 499));
define('STRIPE_PRICE_BUSINESS_YEARLY', (float)(getenv('STRIPE_PRICE_BUSINESS_YEARLY') ?: 4990));
define('STRIPE_CURRENCY', getenv('STRIPE_CURRENCY') ?: 'inr');                                  // ISO currency code, e.g. 'usd', 'inr', 'eur'

define('APP_BASE_URL', getenv('APP_BASE_URL') ?: 'https://www.samalconsultancy.com');           // used for Stripe success/cancel URLs