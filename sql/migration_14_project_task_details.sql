USE taskvel_php;

-- ============================================================
-- Feature: Task Detail Power-Up for Project (Kanban) tasks
--   1) Subtasks / checklists
--   2) Labels (project-scoped, coloured)
--   3) Dependencies ("blocked by")
--   4) Attachments (extend the shared attachments table)
-- ============================================================

-- ------------------------------------------------------------
-- 1) SUBTASKS — a lightweight checklist inside a board task.
-- Mirrors the existing `task_steps` pattern used by personal tasks,
-- kept as its own table (not reused) because project_tasks has its
-- own permission model (assignee / manager) that task_steps' checks
-- don't account for.
-- ------------------------------------------------------------
CREATE TABLE project_task_subtasks (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    task_id     INT UNSIGNED NOT NULL,
    title       VARCHAR(255) NOT NULL,
    done        TINYINT(1) NOT NULL DEFAULT 0,
    position    INT NOT NULL DEFAULT 0,
    created_by  INT UNSIGNED NOT NULL,
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (task_id) REFERENCES project_tasks(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX (task_id, position)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 2) LABELS — scoped per project (like Jira board labels / Asana
-- tags), each with a colour swatch chosen from a fixed palette.
-- ------------------------------------------------------------
CREATE TABLE project_labels (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    project_id  INT UNSIGNED NOT NULL,
    name        VARCHAR(40) NOT NULL,
    color       VARCHAR(20) NOT NULL DEFAULT '#4f46e5',
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_project_label (project_id, name),
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE project_task_labels (
    task_id  INT UNSIGNED NOT NULL,
    label_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (task_id, label_id),
    FOREIGN KEY (task_id) REFERENCES project_tasks(id) ON DELETE CASCADE,
    FOREIGN KEY (label_id) REFERENCES project_labels(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 3) DEPENDENCIES — "task_id is blocked by depends_on_id". Both
-- tasks must belong to the same project (enforced in application
-- code, same convention as the rest of this codebase). Self-reference
-- and duplicate links are rejected at the DB level.
-- ------------------------------------------------------------
CREATE TABLE project_task_dependencies (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    task_id       INT UNSIGNED NOT NULL,
    depends_on_id INT UNSIGNED NOT NULL,
    created_by    INT UNSIGNED NOT NULL,
    created_at    DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_dependency (task_id, depends_on_id),
    FOREIGN KEY (task_id) REFERENCES project_tasks(id) ON DELETE CASCADE,
    FOREIGN KEY (depends_on_id) REFERENCES project_tasks(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT chk_no_self_dependency CHECK (task_id <> depends_on_id)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 4) ATTACHMENTS — extend the shared table (already generalized for
-- team_task_id / team_task_update_id in migration_12) with a
-- project_task_id column so board tasks can carry files too.
-- ------------------------------------------------------------
ALTER TABLE attachments
    ADD COLUMN project_task_id INT UNSIGNED NULL AFTER team_task_update_id,
    ADD FOREIGN KEY (project_task_id) REFERENCES project_tasks(id) ON DELETE CASCADE;
