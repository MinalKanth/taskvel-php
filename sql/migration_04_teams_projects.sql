USE taskvel_php;

-- A team is a group of coworkers. Each member has a role:
--   owner   — created the team, full control, can promote/demote/remove anyone
--   manager — can create/edit/delete/assign any task in the team's projects,
--             and can invite/remove regular members (but not the owner)
--   member  — can create tasks for themself, and update the status of any
--             task assigned to them, but cannot edit/delete/assign others' tasks
CREATE TABLE teams (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(120) NOT NULL,
    created_by  INT UNSIGNED NOT NULL,
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE team_members (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    team_id     INT UNSIGNED NOT NULL,
    user_id     INT UNSIGNED NOT NULL,
    role        ENUM('owner','manager','member') NOT NULL DEFAULT 'member',
    joined_at   DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_team_user (team_id, user_id),
    FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE projects (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    team_id     INT UNSIGNED NOT NULL,
    name        VARCHAR(150) NOT NULL,
    description TEXT NULL,
    color       VARCHAR(20) DEFAULT '#4f46e5',
    created_by  INT UNSIGNED NOT NULL,
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    archived    TINYINT(1) DEFAULT 0,
    FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE project_tasks (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    project_id   INT UNSIGNED NOT NULL,
    title        VARCHAR(255) NOT NULL,
    description  TEXT NULL,
    status       ENUM('todo','in_progress','done') NOT NULL DEFAULT 'todo',
    priority     ENUM('low','medium','high','critical') NOT NULL DEFAULT 'medium',
    assignee_id  INT UNSIGNED NULL,
    created_by   INT UNSIGNED NOT NULL,
    due_date     DATE NULL,
    created_at   DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at   DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (assignee_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE project_task_comments (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    task_id     INT UNSIGNED NOT NULL,
    user_id     INT UNSIGNED NOT NULL,
    body        TEXT NOT NULL,
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (task_id) REFERENCES project_tasks(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE project_activity_log (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    project_id  INT UNSIGNED NOT NULL,
    user_id     INT UNSIGNED NOT NULL,
    message     VARCHAR(255) NOT NULL,
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Tracks whether a user has already received today's "overdue/due-today"
-- push digest, so the reminder cron (cron/send_reminders.php) never spams
-- the same reminder twice in one day.
CREATE TABLE push_digest_log (
    user_id     INT UNSIGNED NOT NULL,
    sent_date   DATE NOT NULL,
    PRIMARY KEY (user_id, sent_date),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;
