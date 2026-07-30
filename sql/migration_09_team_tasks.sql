USE taskvel_php;

-- ------------------------------------------------------------
-- TEAM TASKS  (Feature 1: "Team Tasks" tab inside a Team)
--
-- Distinct from `project_tasks`: those live inside a Project and are
-- for structured, multi-task initiatives. `team_tasks` are for the
-- common case of "assign a task straight to a teammate" without first
-- having to create a Project wrapper for it. Same permission model as
-- project_tasks (owner/manager can assign to anyone; members can only
-- self-assign), so the two systems stay conceptually consistent and
-- can share UI conventions and future features (comments, attachments).
-- ------------------------------------------------------------
CREATE TABLE team_tasks (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    team_id         INT UNSIGNED NOT NULL,
    title           VARCHAR(255) NOT NULL,
    description     TEXT NULL,
    status          ENUM('todo','in_progress','done') NOT NULL DEFAULT 'todo',
    priority        ENUM('low','medium','high','urgent') NOT NULL DEFAULT 'medium',
    progress        TINYINT UNSIGNED NOT NULL DEFAULT 0,   -- 0-100, independent of status so "in progress" tasks can show partial completion
    assignee_id     INT UNSIGNED NULL,
    created_by      INT UNSIGNED NOT NULL,
    due_date        DATE NULL,
    created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE,
    FOREIGN KEY (assignee_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX (team_id, status),
    INDEX (assignee_id),
    CHECK (progress BETWEEN 0 AND 100)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Generalize the existing `notifications` table so future features
-- (team tasks now; task-update timelines and trial reminders next)
-- don't each need their own bespoke *_id column bolted on:
--   - link_url: where "view" should navigate to (any in-app page)
--   - team_task_id: optional FK so a notification can be traced back
--     to the team task that triggered it, same idea as the existing
--     nullable task_id column.
-- task_id stays as-is for backward compatibility with personal tasks.
-- ------------------------------------------------------------
ALTER TABLE notifications
    ADD COLUMN team_task_id INT UNSIGNED NULL AFTER task_id,
    ADD COLUMN link_url VARCHAR(255) NULL AFTER body,
    ADD FOREIGN KEY (team_task_id) REFERENCES team_tasks(id) ON DELETE CASCADE;