USE taskvel_php;

-- ------------------------------------------------------------
-- TEAM EVENTS  (fixes a pre-existing bug, unrelated to any AI/chat work)
--
-- api/team_events.php and the "📅 Events" section on team.php have always
-- referenced a `team_events` / `team_event_attendees` table, but no
-- migration in this project ever created them — every call to
-- api/team_events.php (e.g. ?action=all, ?action=list) has been failing
-- with a 500 "table doesn't exist" error since the feature was written.
-- This migration simply adds the tables the existing code already expects;
-- no PHP changes needed.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS team_events (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    team_id         INT UNSIGNED NOT NULL,
    project_id      INT UNSIGNED NULL,
    title           VARCHAR(190) NOT NULL,
    description     TEXT NULL,
    location        VARCHAR(190) NULL,
    event_date      DATE NOT NULL,
    start_time      TIME NULL,
    end_time        TIME NULL,
    created_by      INT UNSIGNED NOT NULL,
    created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX (team_id, event_date),
    INDEX (event_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS team_event_attendees (
    event_id    INT UNSIGNED NOT NULL,
    user_id     INT UNSIGNED NOT NULL,
    status      ENUM('going','declined','invited') NOT NULL DEFAULT 'invited',
    PRIMARY KEY (event_id, user_id),
    FOREIGN KEY (event_id) REFERENCES team_events(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
