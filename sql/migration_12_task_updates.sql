USE taskvel_php;

-- ------------------------------------------------------------
-- Feature 3: progress updates + full history/timeline for team tasks.
--
-- Distinct from `project_activity_log` (one-line strings, e.g. "reassigned
-- X") — this captures the full structured payload of each update (status
-- transition, percent, notes) so the timeline can render something richer
-- than a log line, and so "keep a history of all updates" has real data
-- to look back on, not just a message.
-- ------------------------------------------------------------
CREATE TABLE team_task_updates (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    team_task_id    INT UNSIGNED NOT NULL,
    user_id         INT UNSIGNED NOT NULL,
    status_from     ENUM('todo','in_progress','done') NULL,
    status_to       ENUM('todo','in_progress','done') NULL,
    progress_from   TINYINT UNSIGNED NULL,
    progress_to     TINYINT UNSIGNED NULL,
    notes           TEXT NULL,
    created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (team_task_id) REFERENCES team_tasks(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX (team_task_id, created_at)
) ENGINE=InnoDB;

-- Per-user preference: whether progress-update notifications should also be
-- emailed to the recipient, not just shown in-app. Defaults to on so the
-- "seniors receive email notifications" requirement works out of the box;
-- users can opt out from Settings.
ALTER TABLE users
    ADD COLUMN notify_task_updates_email TINYINT(1) NOT NULL DEFAULT 1 AFTER plan;

-- ------------------------------------------------------------
-- Generalize `attachments` (previously hard-wired to personal `tasks` only)
-- so a progress update can carry optional file attachments too, per spec.
-- task_id becomes nullable; exactly one of task_id / team_task_update_id
-- should be set per row (enforced in application code in api/attachments.php,
-- the same way project_tasks/team_tasks already split responsibilities
-- without a DB-level XOR constraint, to stay portable across MySQL versions).
-- ------------------------------------------------------------
ALTER TABLE attachments
    MODIFY COLUMN task_id INT UNSIGNED NULL,
    ADD COLUMN team_task_id INT UNSIGNED NULL AFTER task_id,          -- staging: uploaded before the update is submitted
    ADD COLUMN team_task_update_id INT UNSIGNED NULL AFTER team_task_id, -- final: linked once the update is submitted
    ADD FOREIGN KEY (team_task_id) REFERENCES team_tasks(id) ON DELETE CASCADE,
    ADD FOREIGN KEY (team_task_update_id) REFERENCES team_task_updates(id) ON DELETE CASCADE;