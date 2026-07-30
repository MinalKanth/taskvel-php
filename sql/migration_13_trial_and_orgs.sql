USE taskvel_php;

-- ------------------------------------------------------------
-- Feature 4, Part A: 30-day free trial.
--
-- users.plan ('free'/'pro', migration_02) already exists and is now the
-- live source of truth (Features 2/3 read it via user_plan()). Trial just
-- needs to (a) know *why* a user is currently 'pro' so expiry only ever
-- downgrades trial users, never a real Stripe subscriber or an org-seat
-- holder, and (b) remember when the trial ends.
-- ------------------------------------------------------------
ALTER TABLE users
    ADD COLUMN plan_source ENUM('none','trial','stripe','org_seat') NOT NULL DEFAULT 'none' AFTER plan,
    ADD COLUMN trial_ends_at DATETIME NULL AFTER plan_source,
    ADD COLUMN must_change_password TINYINT(1) NOT NULL DEFAULT 0 AFTER password_hash,
    ADD COLUMN stripe_customer_id VARCHAR(100) NULL AFTER trial_ends_at,
    ADD COLUMN stripe_subscription_id VARCHAR(100) NULL AFTER stripe_customer_id;

-- Every *existing* row keeps plan_source='none' / trial_ends_at=NULL by virtue
-- of the defaults above — i.e. current users are never retroactively put on
-- a trial or affected by expiry. Only rows inserted after this migration
-- (via the updated register_user()) start a trial.

-- Idempotency log for the 7/3/1-day-before and on-expiry reminders, same
-- pattern as the existing push_digest_log (one row per milestone per user
-- so re-running the cron never double-sends).
CREATE TABLE trial_reminder_log (
    user_id     INT UNSIGNED NOT NULL,
    milestone   ENUM('7d','3d','1d','expired') NOT NULL,
    sent_at     DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, milestone),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Feature 4, Part B: enterprise seat-based licensing.
--
-- An Organization is a distinct billing entity from a Team (teams are for
-- day-to-day project/task collaboration; an Organization is who pays for
-- and administers seats). A user's Team memberships are unrelated to which
-- Organization licenses their seat — deliberately decoupled so an org can
-- license someone's account without forcing them into any particular team,
-- and so this reuses nothing from the Team system that doesn't belong here.
-- ------------------------------------------------------------
CREATE TABLE organizations (
    id                      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name                    VARCHAR(150) NOT NULL,
    owner_user_id           INT UNSIGNED NOT NULL,
    billing_cycle           ENUM('monthly','yearly') NOT NULL DEFAULT 'monthly',
    seats_purchased         INT UNSIGNED NOT NULL DEFAULT 0,
    stripe_customer_id      VARCHAR(100) NULL,
    stripe_subscription_id  VARCHAR(100) NULL,
    plan_status             ENUM('active','past_due','canceled') NOT NULL DEFAULT 'active',
    renewal_date            DATE NULL,
    created_at              DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (owner_user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- One row per licensed seat. status='suspended' still occupies the seat
-- (so the org doesn't lose the assignment and re-provisioning isn't
-- needed to bring someone back) but does NOT grant Pro access while
-- suspended — see recompute_user_plan() in includes/licensing.php.
-- Deleting the row (remove-member) is what actually frees the seat.
CREATE TABLE organization_members (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id INT UNSIGNED NOT NULL,
    user_id         INT UNSIGNED NOT NULL,
    role            ENUM('owner','admin','employee') NOT NULL DEFAULT 'employee',
    status          ENUM('active','suspended') NOT NULL DEFAULT 'active',
    invited_by      INT UNSIGNED NULL,
    invited_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
    joined_at       DATETIME NULL,   -- set once the (possibly brand-new) user first logs in
    UNIQUE KEY uniq_org_user (organization_id, user_id),
    FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (invited_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- "Billing history" ledger. Populated by add-seats/create (recorded as
-- completed once Stripe confirms — see api/stripe-webhook.php) so the org
-- dashboard has something real to show without fabricating payment data.
CREATE TABLE organization_billing_history (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id     INT UNSIGNED NOT NULL,
    description         VARCHAR(255) NOT NULL,
    seats               INT UNSIGNED NOT NULL,
    billing_cycle       ENUM('monthly','yearly') NOT NULL,
    amount_cents        INT UNSIGNED NULL,
    currency            VARCHAR(3) NOT NULL DEFAULT 'usd',
    stripe_invoice_id   VARCHAR(100) NULL,
    created_at          DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Seat-count integrity is enforced in application code (SELECT ... FOR
-- UPDATE inside a transaction in includes/licensing.php) rather than a
-- trigger, consistent with how the rest of this codebase enforces business
-- rules in PHP rather than in the schema.