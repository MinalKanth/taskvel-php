USE taskvel_php;

ALTER TABLE teams
    ADD COLUMN plan VARCHAR(20) NOT NULL DEFAULT 'free',        -- 'free' | 'pro' | 'business'
    ADD COLUMN stripe_customer_id VARCHAR(100) NULL,
    ADD COLUMN stripe_subscription_id VARCHAR(100) NULL,
    ADD COLUMN plan_status VARCHAR(20) NOT NULL DEFAULT 'active'; -- 'active' | 'past_due' | 'canceled'

-- Free-plan limits, looked up by plan name — keeps limits editable without a deploy.
CREATE TABLE plan_limits (
    plan            VARCHAR(20) PRIMARY KEY,
    max_members     INT UNSIGNED NOT NULL,
    max_projects    INT UNSIGNED NOT NULL,
    max_attachment_mb INT UNSIGNED NOT NULL
) ENGINE=InnoDB;

INSERT INTO plan_limits (plan, max_members, max_projects, max_attachment_mb) VALUES
    ('free', 3, 1, 10),
    ('pro', 20, 999999, 100),
    ('business', 999999, 999999, 500);