-- ============================================================
-- MIGRATION 15 — Team Chat (7-day rolling retention)
-- Run this once against your existing taskvel_php database:
--   mysql -u root -p taskvel_php < sql/migration_15_team_chat.sql
-- ============================================================
USE taskvel_php;

CREATE TABLE IF NOT EXISTS team_chat_messages (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    team_id     INT UNSIGNED NOT NULL,
    user_id     INT UNSIGNED NOT NULL,
    message     TEXT NOT NULL,
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    -- Every list/send request filters + purges by (team_id, created_at),
    -- so this composite index keeps both operations fast even as the
    -- table grows.
    INDEX idx_team_created (team_id, created_at),
    FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
