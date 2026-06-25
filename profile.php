<?php
session_start();
if (empty($_SESSION['user_id'])) {
    header('Location: login.html');
    exit;
}

require 'database/connect_db.php';
$db = (new DatabaseConnection())->getConnection();

$stmt = $db->prepare('SELECT id, faculty_number, first_name, last_name, email, role, major, study_year, edition_id, created_at FROM users WHERE id = :id LIMIT 1');
$stmt->execute([':id' => $_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user) {
    echo 'User not found.';
    exit;
}

$fullName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));

$edition_stmt = $db->prepare('SELECT title FROM course_editions WHERE id = :eid');
$edition_stmt->execute([':eid' => $_SESSION['edition_id']]);
$edition_title = $edition_stmt->fetchColumn();

if ($user['role'] == 'student' && !$edition_title) {
    echo 'Edition not found.';
    exit;
}

?>
<!DOCTYPE html>
<html lang="bg">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Профил</title>
    <link rel="stylesheet" href="styles/home.css">
    <style>
        .profile-card {
            background: #fff;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            max-width: 720px;
            margin: 1rem auto;
        }

        .profile-row {
            display: flex;
            gap: 1rem;
            padding: .5rem 0;
        }

        .profile-key {
            width: 180px;
            font-weight: 700;
            color: #555;
        }

        h2 {
            text-align: center;
            margin-bottom: 1.5rem;
            color: #333;
        }
    </style>
</head>

<body>
    <header>
        <div class="header-container">
            <div class="logo">
                <?php if ((($user['role'] ?? $_SESSION['role'] ?? '') === 'teacher')): ?>
                    <a id="home-link" href="home_teacher.php">InviteMEme</a>
                <?php else: ?>
                    <a id="home-link" href="home_student.php">InviteMEme</a>
                <?php endif; ?>
            </div>

            <nav>
                <ul>
                    <?php if ((($user['role'] ?? $_SESSION['role'] ?? '') === 'teacher')): ?>
                        <li><a href="create_template.php">Създаване на шаблон</a></li>
                        <li><a href="edit_templates.php">Управление на шаблони</a></li>
                        <li><a href="stats.php">Статистики</a></li>
                        <li><a href="profile.php" id="active-menu">Профил</a></li>
                        <li><a href="auth/logout.php">Изход</a></li>
                    <?php else: ?>
                        <li><a href="create_invitation.php">Създаване на покана</a></li>
                        <li><a href="send_invitation.php">Изпращане</a></li>
                        <li><a href="status.php">Статус</a></li>
                        <li><a href="profile.php" id="active-menu">Профил</a></li>
                        <li><a href="auth/logout.php">Изход</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>
    <main>
        <h2>Моят профил</h2>
        <div class="profile-card">
            <div class="profile-row">
                <div class="profile-key">Име</div>
                <div><?php echo htmlspecialchars($fullName); ?></div>
            </div>
            
            <?php
            $roleText = $user['role'];

            if ($roleText === 'student') {
                $roleText = 'Студент';
            } elseif ($roleText === 'teacher') {
                $roleText = 'Преподавател';
            }
            ?>
            <div class="profile-row">
                <div class="profile-key">Роля</div>
                <div><?php echo htmlspecialchars($roleText); ?></div>
            </div>

            <?php if ((($user['role'] ?? $_SESSION['role'] ?? '') === 'student')): ?>
                <div class="profile-row">
                    <div class="profile-key">Факултетен номер</div>
                    <div><?php echo htmlspecialchars($user['faculty_number'] ?? ''); ?></div>
                </div>
                <div class="profile-row">
                    <div class="profile-key">Специалност</div>
                    <div><?php echo htmlspecialchars($user['major'] ?? ''); ?></div>
                </div>
                <div class="profile-row">
                    <div class="profile-key">Курс</div>
                    <div><?php echo htmlspecialchars($user['study_year'] ?? ''); ?></div>
                </div>
                <div class="profile-row">
                    <div class="profile-key">Издание на курса</div>
                    <div><?php echo htmlspecialchars($edition_title ?? ''); ?></div>
                </div>
            <?php endif; ?>
            <div class="profile-row">
                <div class="profile-key">Имейл</div>
                <div><?php echo htmlspecialchars($user['email']); ?></div>
            </div>
            <div class="profile-row">
                <div class="profile-key">Регистриран на</div>
                <div><?php echo htmlspecialchars($user['created_at']); ?></div>
            </div>
        </div>
    </main>
</body>

</html>