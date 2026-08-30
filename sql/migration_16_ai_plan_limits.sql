-- ============================================================
-- MIGRATION 16 — Daily AI usage quota per plan
-- Free-plan users get a small number of AI actions per day (combined
-- across AI Suggest, Quick Add, Daily Focus, and the workday summary);
-- Pro/Business get effectively unlimited. Run this once:
--   mysql -u root -p taskvel_php < sql/migration_16_ai_plan_limits.sql
-- ============================================================
USE taskvel_php;

ALTER TABLE plan_limits
    ADD COLUMN max_ai_daily INT UNSIGNED NOT NULL DEFAULT 3 AFTER max_attachment_mb;

UPDATE plan_limits SET max_ai_daily = 3      WHERE plan = 'free';
UPDATE plan_limits SET max_ai_daily = 999999 WHERE plan IN ('pro', 'business');
