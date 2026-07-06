USE taskvel_php;

-- Day-level default reportee + end-of-day accomplishments note
ALTER TABLE workdays
    ADD COLUMN report_to_email VARCHAR(190) NULL AFTER checkin_at,
    ADD COLUMN notes TEXT NULL AFTER summary_sent;

-- Per-task soft SLA + a manager-approval workflow (an employee marking a
-- task "done" moves it to pending-approval if it has a report-to email;
-- the reportee approves/rejects via a one-click emailed link, no login
-- required — or right inside manager.php if they're a Taskvel user too).
ALTER TABLE workday_tasks
    ADD COLUMN expected_minutes INT UNSIGNED NULL AFTER report_to_email,
    ADD COLUMN approval_status ENUM('auto','pending','approved','rejected') NOT NULL DEFAULT 'auto' AFTER status,
    ADD COLUMN approval_token VARCHAR(64) NULL AFTER approval_status,
    MODIFY COLUMN status ENUM('pending','in_progress','pending_approval','done') NOT NULL DEFAULT 'pending';

CREATE TABLE workday_breaks (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    workday_id  INT UNSIGNED NOT NULL,
    break_type  ENUM('lunch','tea','personal','other') NOT NULL DEFAULT 'other',
    started_at  DATETIME NOT NULL,
    ended_at    DATETIME NULL,
    FOREIGN KEY (workday_id) REFERENCES workdays(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Tab-level idle detection (mouse/keyboard inactivity while Taskvel is the
-- active tab) — a soft productivity signal, NOT screen/activity capture.
CREATE TABLE workday_idle_log (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    workday_id   INT UNSIGNED NOT NULL,
    idle_started_at DATETIME NOT NULL,
    idle_ended_at   DATETIME NOT NULL,
    idle_seconds INT UNSIGNED NOT NULL,
    FOREIGN KEY (workday_id) REFERENCES workdays(id) ON DELETE CASCADE
) ENGINE=InnoDB;
