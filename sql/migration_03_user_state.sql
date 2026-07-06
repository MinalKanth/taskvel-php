USE taskvel;

-- Stores the entire client-side state of the original single-file Taskvel
-- app (tasks, remarks, notifications, focus log, streak, templates, theme,
-- accent) as one JSON blob per user. This guarantees zero feature/field loss
-- when syncing across devices — the client keeps its exact original logic
-- and localStorage shape; this table is just a mirror of it.
CREATE TABLE user_state (
    user_id     INT UNSIGNED PRIMARY KEY,
    state_json  LONGTEXT NOT NULL,
    updated_at  DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;
