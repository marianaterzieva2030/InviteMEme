<?php

function initializeDatabase(PDO $db): void
{
    $stmt = $db->query("
        SELECT COUNT(*)
        FROM information_schema.tables
        WHERE table_schema = DATABASE()
        AND table_name = 'users'
    ");

    if ($stmt->fetchColumn()) {
        return;
    }

    $db->exec("CREATE TABLE IF NOT EXISTS course_editions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        code VARCHAR(10) NOT NULL UNIQUE,
        title VARCHAR(100) NOT NULL,
        is_active BOOLEAN DEFAULT TRUE
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS users (
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
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS invitation_templates (
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
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS invitations (
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

        FOREIGN KEY (user_id)
            REFERENCES users(id),

        FOREIGN KEY (template_id)
            REFERENCES invitation_templates(id),

        FOREIGN KEY (edition_id)
            REFERENCES course_editions(id)
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS invitation_recipients (
        id INT AUTO_INCREMENT PRIMARY KEY,

        invitation_id INT NOT NULL,
        recipient_email VARCHAR(255) NOT NULL,

        status ENUM('pending', 'sent', 'failed')
            DEFAULT 'pending',

        sent_at DATETIME NULL,

        FOREIGN KEY (invitation_id)
            REFERENCES invitations(id)
            ON DELETE CASCADE
    )");

    $db->exec("INSERT INTO course_editions (id, code, title, is_active) 
        VALUES
        (1, 'w26', 'WEB технологии 2025/2026', TRUE),
        (2, 'w25', 'WEB технологии 2024/2025', FALSE)
    ");

    $db->exec("INSERT INTO users(faculty_number, first_name, last_name, email, password_hash, role, major, study_year, edition_id)
        VALUES
        (
            NULL,
            'Maria',
            'Ivanova',
            'invitememe.team@gmail.com',
            '$2y$12$2EeBi7tQoL67cti46g6Zxui/PJY0ILYrEEM3VlEvbj8hIQLj2zaMW',
            'teacher',
            NULL,
            NULL,
            NULL
        ),
        (
            '5MI0600215',
            'Мариана',
            'Терзиева',
            'materzieva@uni-sofia.bg',
            '$2y$10\$mG7bgSIa4nfk8ez156yJaeqqqufoMg11qz5GVEkTSuajD3ofRk.Sq',
            'student',
            'Софтуерно инженерство',
            3,
            1
        )
    ");

    $db->exec("INSERT INTO invitation_templates(name, type, description, image_path, created_by)
        VALUES
        ('Spongebob Burning', 'meme', NULL, 'uploads/templates/1780957931_meme2.jpg', 1),
        ('Patrick TODO list', 'meme', NULL, 'uploads/templates/patrick-to-do-list-actually-blank-meme-43iacv.jpg', 1),
        ('Mountain', 'standard', NULL, 'uploads/templates/pexels-valko23-12149126.jpg', 1)
    ");

    $db->exec("INSERT INTO invitations (user_id, edition_id, template_id, title, presentation_date, presentation_time, room, description, generated_image_path, created_at) 
        VALUES
        (2, 1, NULL, 'HTML5 Geolocation API', '2026-06-05', '05:03:00', '01', 'Презентиращ: Мариана Терзиева\nЙей', 'uploads/custom/invite_1780987838_07706209ef60.png', '2026-06-09 06:50:38'), 
        (2, 1, NULL, 'CSS стилове', '2026-06-05', '09:00:00', '01', 'Презентиращ: Мариана Терзиева\nGraphic design is my Passion', 'uploads/custom/invite_1780994673_712b647405d6.png', '2026-06-09 08:44:33'),
        (2, 1, NULL, 'HTML5 Geolocation API', '2026-06-05', '15:01:00', '301', 'Презентиращ: Мариана Терзиева\nЙей', 'uploads/custom/invite_1781014522_45306616bc15.png', '2026-06-09 14:15:22'),
        (2, 1, NULL, 'Coffee script', '2026-05-31', '13:02:00', '01', 'Презентиращ: Harry Potter\nЙей', 'uploads/custom/invite_1781015552_0d15bae82536.png', '2026-06-09 14:32:32')
    ");

    echo "Добавени са начални данни.<br>";
}

function migrateDatabase(PDO $db): void
{
    // update docker database if needed
}

?>