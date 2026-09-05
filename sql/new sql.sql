new sql.sql

03/sept/2026

ALTER TABLE organizations
    MODIFY COLUMN plan_status ENUM('pending','active','past_due','locked','canceled') NOT NULL DEFAULT 'pending',
    ADD COLUMN grace_ends_at DATE NULL AFTER renewal_date;

CREATE TABLE org_billing_reminder_log (
    organization_id INT UNSIGNED NOT NULL,
    milestone        ENUM('3d','1d','locked') NOT NULL,
    sent_at          DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (organization_id, milestone),
    FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE
) ENGINE=InnoDB;





CREATE TABLE org_templates (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id  INT UNSIGNED NOT NULL,
    name             VARCHAR(120) NOT NULL,
    payload          JSON NOT NULL,          -- same shape as the personal `templates` table
    created_by       INT UNSIGNED NULL,
    created_at       DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;







-- Safe, idempotent patch for plan_limits — only adds each column if it's
-- actually missing, so it's safe to run even if some migrations already
-- partially applied. Run this once in phpMyAdmin's SQL tab.

SET @has_max_teams := (
    SELECT COUNT(*) FROM information_schema.columns
    WHERE table_schema = DATABASE() AND table_name = 'plan_limits' AND column_name = 'max_teams'
);
SET @sql := IF(@has_max_teams = 0,
    'ALTER TABLE plan_limits ADD COLUMN max_teams INT UNSIGNED NOT NULL DEFAULT 1 AFTER plan',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @has_max_ai_daily := (
    SELECT COUNT(*) FROM information_schema.columns
    WHERE table_schema = DATABASE() AND table_name = 'plan_limits' AND column_name = 'max_ai_daily'
);
SET @sql := IF(@has_max_ai_daily = 0,
    'ALTER TABLE plan_limits ADD COLUMN max_ai_daily INT UNSIGNED NOT NULL DEFAULT 3 AFTER max_attachment_mb',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Now that both columns definitely exist, set the correct values for
-- every plan (safe to re-run — these are plain UPDATEs, not inserts).
UPDATE plan_limits SET max_teams = 2,      max_members = 5      WHERE plan = 'free';
UPDATE plan_limits SET max_teams = 999999, max_members = 999999 WHERE plan = 'pro';
UPDATE plan_limits SET max_teams = 999999                       WHERE plan = 'business';

UPDATE plan_limits SET max_ai_daily = 3      WHERE plan = 'free';
UPDATE plan_limits SET max_ai_daily = 999999 WHERE plan IN ('pro', 'business');

-- Confirm the fix:
SELECT * FROM plan_limits;






ALTER TABLE organizations
    ADD COLUMN logo_url VARCHAR(500) NULL AFTER name,
    ADD COLUMN brand_color VARCHAR(7) NULL AFTER logo_url;  -- hex, e.g. '#4f46e5'



    ALTER TABLE users
    MODIFY COLUMN plan_source ENUM('none','trial','stripe','org_seat','admin') NOT NULL DEFAULT 'none',
    ADD COLUMN admin_extended_until DATETIME NULL AFTER trial_ends_at;
 





 06-sept-2026



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