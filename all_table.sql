-- =========================================================
-- STEP 1) Create database and switch to it
-- =========================================================
DROP DATABASE IF EXISTS club_management;
CREATE DATABASE club_management
  DEFAULT CHARACTER SET utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;
USE club_management;

-- For clean reruns in phpMyAdmin
SET FOREIGN_KEY_CHECKS = 0;

-- =========================================================
-- STEP 2) Base tables
-- =========================================================

-- Users of the whole system
CREATE TABLE IF NOT EXISTS users (
  user_id   INT AUTO_INCREMENT PRIMARY KEY,
  email     VARCHAR(100) NOT NULL,
  password  VARCHAR(255) NOT NULL,
  name      VARCHAR(200) NOT NULL,
  role      ENUM('admin','president','general member','applicant') NOT NULL DEFAULT 'applicant',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Clubs
CREATE TABLE IF NOT EXISTS clubs (
  club_id        INT AUTO_INCREMENT PRIMARY KEY,
  club_name      VARCHAR(255) NOT NULL,
  founding_year  YEAR,
  club_email     VARCHAR(255),
  created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_clubs_name (club_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================================================
-- STEP 3) Events (belongs to a club)
-- =========================================================
CREATE TABLE IF NOT EXISTS events (
  event_id    INT AUTO_INCREMENT PRIMARY KEY,
  name        VARCHAR(255) NOT NULL,
  location    VARCHAR(255),
  time        DATETIME NOT NULL,
  event_type  ENUM('current','upcoming','past') NOT NULL DEFAULT 'upcoming',
  club_id     INT NOT NULL,
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_events_club (club_id),
  CONSTRAINT fk_events_club
    FOREIGN KEY (club_id) REFERENCES clubs(club_id)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================================================
-- STEP 4) Child / link tables
-- =========================================================

-- Extra descriptions tied to events
CREATE TABLE IF NOT EXISTS event_descriptions (
  description_id INT AUTO_INCREMENT PRIMARY KEY,
  event_id       INT NOT NULL,
  description    TEXT,
  KEY idx_event_desc_event (event_id),
  CONSTRAINT fk_event_desc_event
    FOREIGN KEY (event_id) REFERENCES events(event_id)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Which club(s) host which event(s)
CREATE TABLE IF NOT EXISTS event_hosts (
  host_id  INT AUTO_INCREMENT PRIMARY KEY,
  event_id INT NOT NULL,
  club_id  INT NOT NULL,
  UNIQUE KEY uq_event_club_host (event_id, club_id),
  KEY idx_event_hosts_event (event_id),
  KEY idx_event_hosts_club (club_id),
  CONSTRAINT fk_event_hosts_event
    FOREIGN KEY (event_id) REFERENCES events(event_id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_event_hosts_club
    FOREIGN KEY (club_id) REFERENCES clubs(club_id)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Requests from users to join clubs
CREATE TABLE IF NOT EXISTS join_requests (
  request_id   INT AUTO_INCREMENT PRIMARY KEY,
  user_id      INT NOT NULL,
  club_id      INT NOT NULL,
  status       ENUM('pending','accepted','rejected') NOT NULL DEFAULT 'pending',
  requested_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_join_request (user_id, club_id),
  KEY idx_join_requests_user (user_id),
  KEY idx_join_requests_club (club_id),
  CONSTRAINT fk_join_requests_user
    FOREIGN KEY (user_id) REFERENCES users(user_id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_join_requests_club
    FOREIGN KEY (club_id) REFERENCES clubs(club_id)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Club members (many-to-many users <-> clubs)
CREATE TABLE IF NOT EXISTS club_members (
  club_id    INT NOT NULL,
  user_id    INT NOT NULL,
  joined_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (club_id, user_id),
  KEY idx_club_members_user (user_id),
  CONSTRAINT fk_club_members_club
    FOREIGN KEY (club_id) REFERENCES clubs(club_id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_club_members_user
    FOREIGN KEY (user_id) REFERENCES users(user_id)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Hiring posts created by clubs
CREATE TABLE IF NOT EXISTS club_hiring (
  hiring_id   INT AUTO_INCREMENT PRIMARY KEY,
  club_id     INT NOT NULL,
  position    VARCHAR(255) NOT NULL,
  start_date  DATE,
  end_date    DATE,
  created_by  INT NOT NULL, -- user_id of the poster
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_hiring_club (club_id),
  KEY idx_hiring_creator (created_by),
  CONSTRAINT fk_hiring_club
    FOREIGN KEY (club_id) REFERENCES clubs(club_id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_hiring_created_by
    FOREIGN KEY (created_by) REFERENCES users(user_id)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Comments on clubs
CREATE TABLE IF NOT EXISTS club_comments (
  comment_id INT AUTO_INCREMENT PRIMARY KEY,
  club_id    INT NOT NULL,
  user_id    INT NOT NULL,
  comment    TEXT,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_club_comments_club (club_id),
  KEY idx_club_comments_user (user_id),
  CONSTRAINT fk_club_comments_club
    FOREIGN KEY (club_id) REFERENCES clubs(club_id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_club_comments_user
    FOREIGN KEY (user_id) REFERENCES users(user_id)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Comments from volunteers on events
CREATE TABLE IF NOT EXISTS volunteer_comments (
  comment_id INT AUTO_INCREMENT PRIMARY KEY,
  event_id   INT NOT NULL,
  user_id    INT NOT NULL,
  comment    TEXT,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_vol_comments_event (event_id),
  KEY idx_vol_comments_user (user_id),
  CONSTRAINT fk_vol_comments_event
    FOREIGN KEY (event_id) REFERENCES events(event_id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_vol_comments_user
    FOREIGN KEY (user_id) REFERENCES users(user_id)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Club achievements
CREATE TABLE IF NOT EXISTS achievements (
  achievement_id INT AUTO_INCREMENT PRIMARY KEY,
  club_id        INT NOT NULL,
  name           VARCHAR(255) NOT NULL,
  award          VARCHAR(255),
  year           YEAR,
  KEY idx_achievements_club (club_id),
  CONSTRAINT fk_achievements_club
    FOREIGN KEY (club_id) REFERENCES clubs(club_id)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;

-- =========================================================
-- OPTIONAL sanity checks you can run after creating:
-- SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA='club_management';
-- SHOW CREATE TABLE users\G
-- =========================================================
