USE taskvel_php;

-- ============================================================
-- Migration 18 — Org branding, billing grace period, org
-- templates, and admin-granted access.
--
-- This formalizes changes that were previously only applied by hand via
-- the loose, undated `sql/new sql.sql` scratch file. That file was never
-- part of the numbered migration sequence the README tells deployers to
-- run ("run all sql/ migrations in numeric order"), so a fresh install
-- following those instructions ends up missing columns and tables that
-- api/organizations.php, api/org-templates.php, and includes/licensing.php
-- already depend on (organizations.logo_url/brand_color, plan_status
-- 'locked', organizations.grace_ends_at, org_templates,
-- org_billing_reminder_log, users.admin_extended_until). Every statement
-- below is idempotent — safe to run whether or not `new sql.sql` was
-- already applied by hand on this database.
-- ============================================================

-- ------------------------------------------------------------
-- 1) organizations: branding + grace-period lock state
-- ------------------------------------------------------------
SET @has_logo_url := (
    SELECT COUNT(*) FROM information_schema.columns
    WHERE table_schema = DATABASE() AND table_name = 'organizations' AND column_name = 'logo_url'
);
SET @sql := IF(@has_logo_url = 0,
    'ALTER TABLE organizations ADD COLUMN logo_url VARCHAR(500) NULL AFTER name',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @has_brand_color := (
    SELECT COUNT(*) FROM information_schema.columns
    WHERE table_schema = DATABASE() AND table_name = 'organizations' AND column_name = 'brand_color'
);
SET @sql := IF(@has_brand_color = 0,
    'ALTER TABLE organizations ADD COLUMN brand_color VARCHAR(7) NULL AFTER logo_url',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @has_grace_ends_at := (
    SELECT COUNT(*) FROM information_schema.columns
    WHERE table_schema = DATABASE() AND table_name = 'organizations' AND column_name = 'grace_ends_at'
);
SET @sql := IF(@has_grace_ends_at = 0,
    'ALTER TABLE organizations ADD COLUMN grace_ends_at DATE NULL AFTER renewal_date',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- plan_status must include 'locked' (recompute_org_grace_status() in
-- includes/licensing.php sets this after the 7-day grace period ends).
-- MODIFY COLUMN is always safe to re-run.
ALTER TABLE organizations
    MODIFY COLUMN plan_status ENUM('pending','active','past_due','locked','canceled') NOT NULL DEFAULT 'pending';

-- ------------------------------------------------------------
-- 2) Billing-reminder idempotency log for the org grace-period cron
--    (cron/org_grace_check.php), same pattern as trial_reminder_log.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS org_billing_reminder_log (
    organization_id INT UNSIGNED NOT NULL,
    milestone        ENUM('3d','1d','locked') NOT NULL,
    sent_at          DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (organization_id, milestone),
    FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 3) Org-wide task templates (api/org-templates.php) — an admin
--    publishes a template, any member of the org can use it.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS org_templates (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id  INT UNSIGNED NOT NULL,
    name             VARCHAR(120) NOT NULL,
    payload          JSON NOT NULL,          -- same shape as the personal `templates` table
    created_by       INT UNSIGNED NULL,
    created_at       DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 4) plan_limits columns for team/AI caps (safe no-op if migration_16
--    or an earlier hand-applied patch already added these).
-- ------------------------------------------------------------
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

UPDATE plan_limits SET max_teams = 2,      max_members = 5      WHERE plan = 'free';
UPDATE plan_limits SET max_teams = 999999, max_members = 999999 WHERE plan = 'pro';
UPDATE plan_limits SET max_teams = 999999                       WHERE plan = 'business';

UPDATE plan_limits SET max_ai_daily = 3      WHERE plan = 'free';
UPDATE plan_limits SET max_ai_daily = 999999 WHERE plan IN ('pro', 'business');

-- ------------------------------------------------------------
-- 5) users: admin-granted access (admin/subscriptions.php lets a Taskvel
--    admin manually extend Pro access, e.g. for an offline/cash payment)
--    — its own plan_source so it survives independently of trial/Stripe/
--    org-seat state.
-- ------------------------------------------------------------
ALTER TABLE users
    MODIFY COLUMN plan_source ENUM('none','trial','stripe','org_seat','admin') NOT NULL DEFAULT 'none';

SET @has_admin_extended_until := (
    SELECT COUNT(*) FROM information_schema.columns
    WHERE table_schema = DATABASE() AND table_name = 'users' AND column_name = 'admin_extended_until'
);
SET @sql := IF(@has_admin_extended_until = 0,
    'ALTER TABLE users ADD COLUMN admin_extended_until DATETIME NULL AFTER trial_ends_at',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
