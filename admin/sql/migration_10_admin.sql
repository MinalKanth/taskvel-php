-- ============================================================
-- MIGRATION 10 — Admin panel
-- Adds a real role column (currently ANY logged-in user can hit
-- events-admin.php), guarantees the events tables exist, and adds
-- a table for contact/enquiry form submissions the admin can track.
--
-- After running, promote yourself:
--   UPDATE users SET role = 'admin' WHERE email = 'you@example.com';
-- ============================================================

ALTER TABLE users
    ADD COLUMN role ENUM('user','admin') NOT NULL DEFAULT 'user' AFTER is_active,
    ADD COLUMN last_login_at DATETIME NULL AFTER role;

-- ------------------------------------------------------------
-- Events (created earlier outside migrations — guarded here so the
-- migration is safe to run on a fresh database too)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS event_categories (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(120) NOT NULL UNIQUE,
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS events (
    id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title             VARCHAR(190) NOT NULL,
    slug              VARCHAR(190) NOT NULL UNIQUE,
    banner_image      VARCHAR(255) NULL,
    short_description VARCHAR(300) NULL,
    full_description  TEXT NULL,
    event_date        DATE NOT NULL,
    event_time        VARCHAR(60) NULL,
    location          VARCHAR(190) NULL,
    category_id       INT UNSIGNED NULL,
    status            ENUM('upcoming','ongoing','completed') NOT NULL DEFAULT 'upcoming',
    youtube_url       VARCHAR(255) NULL,
    booking_link      VARCHAR(255) NULL,
    organizer_name    VARCHAR(120) NULL,
    organizer_phone   VARCHAR(30)  NULL,
    organizer_email   VARCHAR(190) NULL,
    created_by        INT UNSIGNED NULL,
    created_at        DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at        DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status_date (status, event_date),
    FOREIGN KEY (category_id) REFERENCES event_categories(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS event_images (
    id        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_id  INT UNSIGNED NOT NULL,
    image_url VARCHAR(255) NOT NULL,
    position  TINYINT UNSIGNED DEFAULT 0,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS event_hotels (
    id        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_id  INT UNSIGNED NOT NULL,
    name      VARCHAR(190) NOT NULL,
    link      VARCHAR(255) NULL,
    distance  VARCHAR(60)  NULL,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Enquiries — public event/contact form submissions, tracked in admin
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS enquiries (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_id    INT UNSIGNED NULL,
    name        VARCHAR(120) NOT NULL,
    email       VARCHAR(190) NOT NULL,
    phone       VARCHAR(30)  NULL,
    message     TEXT NULL,
    status      ENUM('new','seen','replied','closed') NOT NULL DEFAULT 'new',
    ip_address  VARCHAR(45) NULL,
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_event (event_id),
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE SET NULL
) ENGINE=InnoDB;
