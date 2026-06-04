-- Sample data for InviteME database. Insert through 'SQL' menu in XAMPP's phpmyadmin.

USE inviteme;

-- TODO: add sample data
INSERT INTO users
(
    faculty_number,
    first_name,
    last_name,
    email,
    password_hash,
    role
)
VALUES
(
    NULL,
    'Maria',
    'Ivanova',
    'invitememe.team@gmail.com',
    '$2y$12$2EeBi7tQoL67cti46g6Zxui/PJY0ILYrEEM3VlEvbj8hIQLj2zaMW',
    'teacher'
),
(
    '5MI0600215',
    'Мариана',
    'Терзиева',
    'materzieva@uni-sofia.bg',
    '$2y$10$mG7bgSIa4nfk8ez156yJaeqqqufoMg11qz5GVEkTSuajD3ofRk.Sq',
    'student'
)
;