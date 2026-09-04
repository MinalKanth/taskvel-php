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
 