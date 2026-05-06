-- ============================================================
--  Job Search Website - jobsearch_db
--  Paste this into phpMyAdmin > SQL tab > Go
-- ============================================================

CREATE DATABASE IF NOT EXISTS jobsearch_db;
USE jobsearch_db;

CREATE TABLE users (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    name                VARCHAR(100) NOT NULL,
    email               VARCHAR(150) NOT NULL UNIQUE,
    password            VARCHAR(255) NOT NULL,
    role                ENUM('seeker','employer','admin') NOT NULL DEFAULT 'seeker',
    phone               VARCHAR(40) NULL,
    city                VARCHAR(120) NULL,
    bio                 TEXT NULL,
    skills              TEXT NULL,
    resume_url          VARCHAR(255) NULL,
    avatar_url          VARCHAR(255) NULL,
    company_website     VARCHAR(255) NULL,
    company_description TEXT NULL,
    is_active           TINYINT(1) NOT NULL DEFAULT 1,
    created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE admins (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE jobs (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    employer_id  INT NOT NULL,
    title        VARCHAR(150) NOT NULL,
    company      VARCHAR(150) NOT NULL,
    location     VARCHAR(150) NOT NULL,
    type         ENUM('Full-Time','Part-Time','Remote','Internship') DEFAULT 'Full-Time',
    status       ENUM('open','closed','archived') NOT NULL DEFAULT 'open',
    description  TEXT NOT NULL,
    requirements TEXT NULL,
    benefits     TEXT NULL,
    salary       VARCHAR(100),
    featured     TINYINT(1) NOT NULL DEFAULT 0,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employer_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE applications (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    job_id       INT NOT NULL,
    seeker_id    INT NOT NULL,
    cover_letter TEXT,
    status       ENUM('pending','reviewed','shortlisted','accepted','rejected') DEFAULT 'pending',
    applied_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE,
    FOREIGN KEY (seeker_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_application (job_id, seeker_id)
);

-- Password: admin123
INSERT INTO users (name, email, password, role)
VALUES ('Admin', 'admin@jobsearch.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

INSERT INTO admins (user_id)
SELECT id FROM users WHERE email = 'admin@jobsearch.com';
