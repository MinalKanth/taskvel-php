<?php
define('RAZORPAY_KEY_ID', getenv('RAZORPAY_KEY_ID') ?: '');            // rzp_live_... / rzp_test_...
define('RAZORPAY_KEY_SECRET', getenv('RAZORPAY_KEY_SECRET') ?: '');
define('RAZORPAY_WEBHOOK_SECRET', getenv('RAZORPAY_WEBHOOK_SECRET') ?: ''); // set when you add the webhook in the Razorpay Dashboard

// Same whole-currency-unit amounts as config/stripe.php's pricing, kept as
// separate RAZORPAY_* constants (rather than reusing STRIPE_PRICE_*) so the
// two gateways' prices can be tuned independently later even though they
// default to matching values today.
define('RAZORPAY_PRICE_PRO', (float)(getenv('RAZORPAY_PRICE_PRO') ?: 99));
define('RAZORPAY_PRICE_ORG_SEAT_MONTHLY', (float)(getenv('RAZORPAY_PRICE_ORG_SEAT_MONTHLY') ?: 99));
define('RAZORPAY_PRICE_ORG_SEAT_YEARLY', (float)(getenv('RAZORPAY_PRICE_ORG_SEAT_YEARLY') ?: 1099));
define('RAZORPAY_PRICE_BUSINESS_MONTHLY', (float)(getenv('RAZORPAY_PRICE_BUSINESS_MONTHLY') ?: 499));
define('RAZORPAY_PRICE_BUSINESS_YEARLY', (float)(getenv('RAZORPAY_PRICE_BUSINESS_YEARLY') ?: 4990));

// Razorpay subscriptions require a fixed total_count of billing cycles up
// front (there's no "bill forever" option). For UPI Autopay specifically,
// NPCI caps a mandate's total validity at 30 years — going over that
// throws "expire_at cannot be more than 30 years for upi" when the
// subscription is created, even though card-based subscriptions would
// have accepted a longer count. 360 monthly cycles / 30 yearly cycles is
// exactly 30 years either way: the longest either mandate type allows,
// while still working for every payment method Checkout offers.
define('RAZORPAY_TOTAL_CYCLES_MONTHLY', 360);
define('RAZORPAY_TOTAL_CYCLES_YEARLY', 30);

define('RAZORPAY_CURRENCY', 'INR'); // standard Razorpay onboarding only supports INR