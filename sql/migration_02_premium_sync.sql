USE taskvel_php;

-- Premium tier flag (used to gate premium-only features in the API/UI)
ALTER TABLE users
    ADD COLUMN plan ENUM('free','pro') DEFAULT 'free' AFTER theme;

-- Web Push subscriptions (one browser/device per row) — enables due-date & reminder
-- notifications to land on the device even when the tab is closed.
CREATE TABLE push_subscriptions (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED NOT NULL,
    endpoint    VARCHAR(500) NOT NULL,
    p256dh      VARCHAR(255) NOT NULL,
    auth        VARCHAR(255) NOT NULL,
    device_label VARCHAR(120) NULL,
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY uniq_endpoint (endpoint(255))
) ENGINE=InnoDB;

-- Per-user private token so they can subscribe to a live ICS calendar feed
-- (Google Calendar / Apple Calendar / Outlook "subscribe by URL") — syncs
-- due dates across every device automatically, no app install needed there.
CREATE TABLE calendar_feed_tokens (
    user_id     INT UNSIGNED PRIMARY KEY,
    token       VARCHAR(64) NOT NULL UNIQUE,
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Track which task an occurrence was spawned from, so recurring tasks
-- chain correctly across devices instead of relying on client-side state.
ALTER TABLE tasks
    ADD COLUMN recurrence_parent_id INT UNSIGNED NULL AFTER recurrence_rule,
    ADD CONSTRAINT fk_recurrence_parent FOREIGN KEY (recurrence_parent_id) REFERENCES tasks(id) ON DELETE SET NULL;

-- Device-agnostic "last seen" so Settings can show "synced across N devices"
CREATE TABLE user_devices (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED NOT NULL,
    device_label VARCHAR(120) NOT NULL,
    last_seen_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    user_agent  VARCHAR(255) NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;
