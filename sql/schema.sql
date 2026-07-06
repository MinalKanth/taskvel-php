-- ============================================================
-- TASKVEL — full schema
-- Charset utf8mb4 throughout, InnoDB for FK support.
-- ============================================================

CREATE DATABASE IF NOT EXISTS taskvel CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE taskvel;

SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------------------
-- USERS
-- ------------------------------------------------------------
CREATE TABLE users (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(120)        NOT NULL,
    email           VARCHAR(190)        NOT NULL UNIQUE,
    password_hash   VARCHAR(255)        NOT NULL,
    avatar_url      VARCHAR(255)        NULL,
    accent_color    VARCHAR(20)         DEFAULT '#0a0a0a',
    theme           ENUM('light','dark','system') DEFAULT 'system',
    timezone        VARCHAR(60)         DEFAULT 'Asia/Kolkata',
    is_active       TINYINT(1)          DEFAULT 1,
    email_verified_at DATETIME          NULL,
    remember_token  VARCHAR(100)        NULL,
    created_at      DATETIME            DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME            DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- PASSWORD RESETS
-- ------------------------------------------------------------
CREATE TABLE password_resets (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email       VARCHAR(190) NOT NULL,
    token       VARCHAR(100) NOT NULL,
    expires_at  DATETIME NOT NULL,
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX (email), INDEX (token)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- TAGS  (per-user)
-- ------------------------------------------------------------
CREATE TABLE tags (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED NOT NULL,
    name        VARCHAR(60)  NOT NULL,
    color       VARCHAR(20)  DEFAULT '#888888',
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_user_tag (user_id, name),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- TASKS
-- ------------------------------------------------------------
CREATE TABLE tasks (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    owner_id        INT UNSIGNED NOT NULL,           -- creator
    title           VARCHAR(255) NOT NULL,
    description     TEXT NULL,
    status          ENUM('todo','in_progress','done') DEFAULT 'todo',
    priority        ENUM('low','medium','high','urgent') DEFAULT 'medium',
    urgent          TINYINT(1) DEFAULT 0,             -- Eisenhower axis
    important       TINYINT(1) DEFAULT 1,             -- Eisenhower axis
    due_date        DATE NULL,
    due_time        TIME NULL,
    recurrence_rule VARCHAR(120) NULL,                -- e.g. FREQ=WEEKLY;INTERVAL=1
    pinned          TINYINT(1) DEFAULT 0,
    position        INT DEFAULT 0,
    estimate_minutes INT UNSIGNED NULL,
    completed_at    DATETIME NULL,
    created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX (owner_id, status),
    INDEX (due_date)
) ENGINE=InnoDB;

-- Subtasks / checklist steps ("eSteps")
CREATE TABLE task_steps (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    task_id     INT UNSIGNED NOT NULL,
    title       VARCHAR(255) NOT NULL,
    done        TINYINT(1) DEFAULT 0,
    position    INT DEFAULT 0,
    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Task <-> Tags
CREATE TABLE task_tags (
    task_id INT UNSIGNED NOT NULL,
    tag_id  INT UNSIGNED NOT NULL,
    PRIMARY KEY (task_id, tag_id),
    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id)  REFERENCES tags(id)  ON DELETE CASCADE
) ENGINE=InnoDB;

-- File attachments
CREATE TABLE attachments (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    task_id     INT UNSIGNED NOT NULL,
    uploaded_by INT UNSIGNED NOT NULL,
    file_name   VARCHAR(255) NOT NULL,
    file_path   VARCHAR(255) NOT NULL,
    file_size   INT UNSIGNED NOT NULL,
    mime_type   VARCHAR(120) NULL,
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Remarks / comment timeline (supports draft autosave via is_draft)
CREATE TABLE remarks (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    task_id     INT UNSIGNED NOT NULL,
    user_id     INT UNSIGNED NOT NULL,
    body        TEXT NOT NULL,
    is_draft    TINYINT(1) DEFAULT 0,
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- SHARING / EMAIL INVITES  (collaboration on a task)
-- ------------------------------------------------------------
CREATE TABLE task_shares (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    task_id             INT UNSIGNED NOT NULL,
    owner_id            INT UNSIGNED NOT NULL,          -- who shared it
    shared_with_user_id INT UNSIGNED NULL,               -- filled once accepted (existing user)
    invite_email        VARCHAR(190) NOT NULL,
    permission          ENUM('view','edit') DEFAULT 'edit',
    status              ENUM('pending','accepted','declined','revoked') DEFAULT 'pending',
    invite_token        VARCHAR(64) NOT NULL,
    invited_at          DATETIME DEFAULT CURRENT_TIMESTAMP,
    responded_at        DATETIME NULL,
    FOREIGN KEY (task_id)  REFERENCES tasks(id) ON DELETE CASCADE,
    FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (shared_with_user_id) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY uniq_task_email (task_id, invite_email),
    INDEX (invite_token)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- NOTIFICATIONS
-- ------------------------------------------------------------
CREATE TABLE notifications (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED NOT NULL,
    type        VARCHAR(40) NOT NULL,          -- due_soon, shared, comment, completed, streak, system
    title       VARCHAR(200) NOT NULL,
    body        VARCHAR(500) NULL,
    task_id     INT UNSIGNED NULL,
    is_read     TINYINT(1) DEFAULT 0,
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
    INDEX (user_id, is_read)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- TIME TRACKING (per-tag report) & POMODORO FOCUS SESSIONS
-- ------------------------------------------------------------
CREATE TABLE time_logs (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    task_id         INT UNSIGNED NULL,
    user_id         INT UNSIGNED NOT NULL,
    tag             VARCHAR(60) NULL,
    started_at      DATETIME NOT NULL,
    ended_at        DATETIME NULL,
    duration_seconds INT UNSIGNED DEFAULT 0,
    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE SET NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX (user_id, started_at)
) ENGINE=InnoDB;

CREATE TABLE focus_sessions (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id         INT UNSIGNED NOT NULL,
    task_id         INT UNSIGNED NULL,
    mode            ENUM('focus','short_break','long_break','deep_work','custom') DEFAULT 'focus',
    ambient_sound   VARCHAR(40) NULL,
    duration_seconds INT UNSIGNED NOT NULL,
    started_at      DATETIME NOT NULL,
    ended_at        DATETIME NULL,
    completed       TINYINT(1) DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- STREAKS
-- ------------------------------------------------------------
CREATE TABLE streaks (
    user_id         INT UNSIGNED PRIMARY KEY,
    current_streak  INT UNSIGNED DEFAULT 0,
    longest_streak  INT UNSIGNED DEFAULT 0,
    last_active_date DATE NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- TEMPLATES (save-as-template / quick-use)
-- ------------------------------------------------------------
CREATE TABLE templates (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED NOT NULL,
    name        VARCHAR(120) NOT NULL,
    payload     JSON NOT NULL,       -- title/description/priority/steps/tags snapshot
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- ACTIVITY LOG (audit trail / weekly review feed)
-- ------------------------------------------------------------
CREATE TABLE activity_log (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED NOT NULL,
    task_id     INT UNSIGNED NULL,
    action      VARCHAR(60) NOT NULL,   -- created, completed, updated, commented, shared, deleted...
    meta        JSON NULL,
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE SET NULL,
    INDEX (user_id, created_at)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- EXPORT JOBS (optional history of export center runs)
-- ------------------------------------------------------------
CREATE TABLE export_jobs (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED NOT NULL,
    format      ENUM('csv','xlsx','pdf','json') NOT NULL,
    filters     JSON NULL,
    file_path   VARCHAR(255) NULL,
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

SET FOREIGN_KEY_CHECKS = 1;
