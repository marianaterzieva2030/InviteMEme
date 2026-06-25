-- Sample data for InviteME database. Insert through 'SQL' menu in XAMPP's phpmyadmin.

USE DATABASE();


INSERT INTO course_editions (code, title, is_active) VALUES
('w26', 'WEB технологии 2025/2026', TRUE),
('w25', 'WEB технологии 2024/2025', FALSE);

INSERT INTO users
(
    faculty_number,
    first_name,
    last_name,
    email,
    password_hash,
    role,
    major,
    study_year,
    edition_id,
)
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
    '$2y$10$mG7bgSIa4nfk8ez156yJaeqqqufoMg11qz5GVEkTSuajD3ofRk.Sq',
    'student',
    'Софтуерно инженерство',
    3,
    1
);

INSERT INTO invitation_templates(name, type, description, image_path, created_by)
VALUES
('Spongebob Burning', 'meme', NULL, 'uploads/templates/1780957931_meme2.jpg', 1),
('Patrick TODO list', 'meme', NULL, 'uploads/templates/patrick-to-do-list-actually-blank-meme-43iacv.jpg', 1),
('Mountain', 'standard', NULL, 'uploads/templates/pexels-valko23-12149126.jpg', 1);

INSERT INTO invitations (user_id, edition_id, template_id, title, presentation_date, presentation_time, room, description, generated_image_path, created_at) VALUES
(2, 1, NULL, 'HTML5 Geolocation API', '2026-06-05', '05:03:00', '01', 'Презентиращ: Мариана Терзиева\nЙей', 'uploads/custom/invite_1780987838_07706209ef60.png', '2026-06-09 06:50:38'), 
(2, 1, NULL, 'CSS стилове', '2026-06-05', '09:00:00', '01', 'Презентиращ: Мариана Терзиева\nGraphic design is my Passion', 'uploads/custom/invite_1780994673_712b647405d6.png', '2026-06-09 08:44:33'),
(2, 1, NULL, 'HTML5 Geolocation API', '2026-06-05', '15:01:00', '301', 'Презентиращ: Мариана Терзиева\nЙей', 'uploads/custom/invite_1781014522_45306616bc15.png', '2026-06-09 14:15:22'),
(2, 1, NULL, 'Coffee script', '2026-05-31', '13:02:00', '01', 'Презентиращ: Harry Potter\nЙей', 'uploads/custom/invite_1781015552_0d15bae82536.png', '2026-06-09 14:32:32');

