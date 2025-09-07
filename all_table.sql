-- Create a new users table
CREATE TABLE users (
    user_id INT(11) NOT NULL AUTO_INCREMENT,
    EMAIL VARCHAR(100) NOT NULL UNIQUE,
    PASSWORD VARCHAR(255) NOT NULL,
    NAME VARCHAR(200),
    ROLE ENUM('admin','president','general member','applicant') DEFAULT NULL,
    member_of INT(11),
    PRIMARY KEY (user_id),
    FOREIGN KEY (member_of) REFERENCES clubs(club_id)
        ON DELETE SET NULL
        ON UPDATE CASCADE
) ENGINE=InnoDB;


-- 2️⃣ Events
-- ========================
CREATE TABLE events (
    event_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    location VARCHAR(255) NOT NULL,
    time DATETIME NOT NULL,
    event_type ENUM('current', 'upcoming', 'past') NOT NULL,
    club_id INT NOT NULL,
    FOREIGN KEY (club_id) REFERENCES clubs(club_id) ON DELETE CASCADE
);


-- ========================
-- 3️⃣ Event Descriptions
-- ========================
CREATE TABLE event_descriptions (
    description_id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT NOT NULL,
    description TEXT NOT NULL,
    FOREIGN KEY (event_id) REFERENCES events(event_id) ON DELETE CASCADE
);



-- ========================
-- 4️⃣ Event Hosts
-- ========================
CREATE TABLE event_hosts (
    host_id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT NOT NULL,
    club_id INT NOT NULL,
    FOREIGN KEY (event_id) REFERENCES events(event_id) ON DELETE CASCADE,
    FOREIGN KEY (club_id) REFERENCES clubs(club_id) ON DELETE CASCADE,
    UNIQUE(event_id, club_id)
);

INSERT INTO event_hosts (event_id, club_id) VALUES
(1, 1),
(2, 2),
(3, 3),
(4, 4),
(5, 5),
(6, 6),
(7, 7),
(8, 8),
(9, 9),
(10, 10);


-- Create clubs table
CREATE TABLE clubs (
    club_id INT AUTO_INCREMENT PRIMARY KEY,
    club_name VARCHAR(255) NOT NULL UNIQUE
    founding_year int defult 2010
);

CREATE TABLE IF NOT EXISTS club_members (
  id INT AUTO_INCREMENT PRIMARY KEY,
  club_id INT NOT NULL,
  user_id INT NOT NULL,
  joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_member (club_id, user_id),
  FOREIGN KEY (club_id) REFERENCES clubs(club_id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);


You sent
CREATE TABLE achievements (
    achievement_id INT AUTO_INCREMENT PRIMARY KEY,
    club_id INT NOT NULL,
    name VARCHAR(255),
    award VARCHAR(255),
    year YEAR,
    FOREIGN KEY (club_id) REFERENCES clubs(club_id) ON DELETE CASCADE
);

CREATE TABLE join_requests (
    request_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    club_id INT NOT NULL,
    status ENUM('pending','approved','rejected') DEFAULT 'pending',
    request_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (club_id) REFERENCES clubs(club_id) ON DELETE CASCADE
);
REATE TABLE club_comments ( comment_id INT AUTO_INCREMENT PRIMARY KEY, club_id INT NOT NULL, user_id INT NOT NULL, comment TEXT NOT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (club_id) REFERENCES clubs(club_id) ON DELETE CASCADE, FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE );