USE taskvel_php;

-- Sliding-window rate limiter backing store (login attempts, registration,
-- password-reset requests, etc. — anything that needs brute-force throttling).
CREATE TABLE rate_limits (
    id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    rl_key        VARCHAR(190) NOT NULL,
    attempts      INT UNSIGNED NOT NULL DEFAULT 1,
    window_start  DATETIME NOT NULL,
    UNIQUE KEY uniq_key (rl_key)
) ENGINE=InnoDB;

-- Security-relevant audit trail: auth events, permission denials, admin
-- actions. Separate from project_activity_log (which is user-facing
-- "who did what to this task" history) — this one is for incident response.
CREATE TABLE security_audit_log (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED NULL,
    event       VARCHAR(64) NOT NULL,
    ip_address  VARCHAR(45) NULL,
    user_agent  VARCHAR(255) NULL,
    meta        TEXT NULL,
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_event (user_id, event),
    INDEX idx_created (created_at)
) ENGINE=InnoDB;
