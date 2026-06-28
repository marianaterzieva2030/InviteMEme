-- CREATE DATABASE IF NOT EXISTS inviteme;


-- USE inviteme;
 
 CREATE TABLE IF NOT EXISTS course_editions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(10) NOT NULL UNIQUE,
    title VARCHAR(100) NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    facebook_url VARCHAR(255) NULL,
    moodle_url VARCHAR(255) NULL
);

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    faculty_number VARCHAR(10) NULL UNIQUE,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('student', 'teacher') DEFAULT 'student',
    major VARCHAR(100) NULL,
    study_year INT NULL,
    edition_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (edition_id)
        REFERENCES course_editions(id)
);

CREATE TABLE IF NOT EXISTS invitation_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    type ENUM('standard', 'meme') NOT NULL,
    image_path VARCHAR(255) NULL,
    description TEXT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (created_by)
        REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS invitations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    edition_id INT NOT NULL,
    template_id INT NULL,

    title VARCHAR(255) NOT NULL,
    presentation_date DATE NOT NULL,
    presentation_time TIME NOT NULL,
    room VARCHAR(100) NOT NULL,
    description TEXT NULL,
    generated_image_path VARCHAR(255) NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fb_link VARCHAR(500) NULL,
    is_approved ENUM ('pending', 'approved', 'declined') DEFAULT 'pending',

    FOREIGN KEY (user_id)
        REFERENCES users(id),

    FOREIGN KEY (template_id)
        REFERENCES invitation_templates(id),

    FOREIGN KEY (edition_id)
        REFERENCES course_editions(id)
);

CREATE TABLE IF NOT EXISTS invitation_recipients (
    id INT AUTO_INCREMENT PRIMARY KEY,

    invitation_id INT NOT NULL,
    recipient_email VARCHAR(255) NOT NULL,

    status ENUM('pending', 'sent', 'failed')
        DEFAULT 'pending',

    sent_at DATETIME NULL,

    FOREIGN KEY (invitation_id)
        REFERENCES invitations(id)
        ON DELETE CASCADE

    -- TO ADD LATER: A recipient can only be associated with a specific invitation once
    -- UNIQUE (invitation_id, recipient_email)
);

