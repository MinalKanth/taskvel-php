USE taskvel_php;

-- Daily Check-in / Office module — entirely optional and separate from both
-- the personal Taskvel app and the Teams/Projects module. A user "checks in"
-- once per day, logs the tasks they work on (each with an optional person
-- to report to by email), tracks how long each task actually took, and
-- "checks out" at day's end to send a full summary to whoever was cc'd
-- across that day's tasks.

CREATE TABLE workdays (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id       INT UNSIGNED NOT NULL,
    work_date     DATE NOT NULL,
    checkin_at    DATETIME NOT NULL,
    checkout_at   DATETIME NULL,
    summary_sent  TINYINT(1) DEFAULT 0,
    UNIQUE KEY uniq_user_date (user_id, work_date),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE workday_tasks (
    id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    workday_id        INT UNSIGNED NOT NULL,
    title             VARCHAR(255) NOT NULL,
    report_to_email   VARCHAR(190) NULL,
    status            ENUM('pending','in_progress','done') NOT NULL DEFAULT 'pending',
    started_at        DATETIME NULL,
    completed_at      DATETIME NULL,
    duration_seconds  INT UNSIGNED NULL,
    created_at        DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (workday_id) REFERENCES workdays(id) ON DELETE CASCADE
) ENGINE=InnoDB;
