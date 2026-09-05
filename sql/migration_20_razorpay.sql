
-- ============================================================
-- Migration 20 — Razorpay as a second payment gateway (UPI/QR
-- support alongside the existing Stripe integration). Uses its own
-- customer/subscription id columns rather than reusing the stripe_*
-- ones, since Razorpay and Stripe subscription ids can both look like
-- "sub_..." and both gateways may be active for different users/orgs
-- at once. Every statement is idempotent, same style as migration_18.
-- ============================================================

-- 1) users: Razorpay identifiers + 'razorpay' as a recognised plan_source
--    (parallel to 'stripe' — recompute_user_plan() in includes/licensing.php
--    already leaves plan_source='stripe' untouched; 'razorpay' gets the
--    same treatment there once you add it, see includes/licensing.php note
--    below).
ALTER TABLE users
    MODIFY COLUMN plan_source ENUM('none','trial','stripe','org_seat','admin','razorpay') NOT NULL DEFAULT 'none';

SET @has_rp_customer := (
    SELECT COUNT(*) FROM information_schema.columns
    WHERE table_schema = DATABASE() AND table_name = 'users' AND column_name = 'razorpay_customer_id'
);
SET @sql := IF(@has_rp_customer = 0,
    'ALTER TABLE users ADD COLUMN razorpay_customer_id VARCHAR(100) NULL AFTER stripe_subscription_id',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @has_rp_sub := (
    SELECT COUNT(*) FROM information_schema.columns
    WHERE table_schema = DATABASE() AND table_name = 'users' AND column_name = 'razorpay_subscription_id'
);
SET @sql := IF(@has_rp_sub = 0,
    'ALTER TABLE users ADD COLUMN razorpay_subscription_id VARCHAR(100) NULL AFTER razorpay_customer_id',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 2) organizations: same pair, for org-seat / business-bundle
--    subscriptions paid via Razorpay instead of Stripe.
SET @has_org_rp_customer := (
    SELECT COUNT(*) FROM information_schema.columns
    WHERE table_schema = DATABASE() AND table_name = 'organizations' AND column_name = 'razorpay_customer_id'
);
SET @sql := IF(@has_org_rp_customer = 0,
    'ALTER TABLE organizations ADD COLUMN razorpay_customer_id VARCHAR(100) NULL AFTER stripe_subscription_id',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @has_org_rp_sub := (
    SELECT COUNT(*) FROM information_schema.columns
    WHERE table_schema = DATABASE() AND table_name = 'organizations' AND column_name = 'razorpay_subscription_id'
);
SET @sql := IF(@has_org_rp_sub = 0,
    'ALTER TABLE organizations ADD COLUMN razorpay_subscription_id VARCHAR(100) NULL AFTER razorpay_customer_id',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 3) organization_billing_history: a gateway-agnostic reference column
--    (stripe_invoice_id is Stripe-specific naming, kept as-is for Stripe rows).
SET @has_obh_rp_ref := (
    SELECT COUNT(*) FROM information_schema.columns
    WHERE table_schema = DATABASE() AND table_name = 'organization_billing_history' AND column_name = 'razorpay_payment_id'
);
SET @sql := IF(@has_obh_rp_ref = 0,
    'ALTER TABLE organization_billing_history ADD COLUMN razorpay_payment_id VARCHAR(100) NULL AFTER stripe_invoice_id',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 4) Idempotency guard for api/razorpay-webhook.php — same role
--    stripe_processed_events plays for api/stripe-webhook.php.
CREATE TABLE IF NOT EXISTS razorpay_processed_events (
    event_id     VARCHAR(100) PRIMARY KEY,
    processed_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 5) Cache of Razorpay Plan objects created via the API, keyed by a
--    deterministic cache_key ("user_pro_month", "org_seat_monthly",
--    "business_yearly", etc.) so repeat checkouts reuse the same Plan
--    instead of creating a new one every time — Razorpay has no inline/
--    dynamic pricing like Stripe's price_data, every Subscription must
--    point at a real, pre-created Plan object.
CREATE TABLE IF NOT EXISTS razorpay_plans (
    cache_key  VARCHAR(60) PRIMARY KEY,
    plan_id    VARCHAR(100) NOT NULL,
    amount     INT UNSIGNED NOT NULL,  -- paise, checked on reuse in case pricing changed
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;