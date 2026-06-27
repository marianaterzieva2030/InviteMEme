<?php
session_start();
if (empty($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'teacher') {
    header('Location: /InviteMEme/login.html');
    exit;
}

$edition_id = $_SESSION['teacher_edition_id'];

require 'database/connect_db.php';
$db = (new DatabaseConnection())->getConnection();

// List students who have saved invitations and counts of saved and sent
$stmt = $db->prepare(
    "SELECT u.id AS user_id, u.faculty_number, u.first_name,
            u.last_name, u.email, u.major, u.study_year, u.edition_id,
            COUNT(DISTINCT i.id) AS saved_count,
            COUNT(ir.id) AS sent_count

    FROM users u

    LEFT JOIN invitations i ON i.user_id = u.id

    LEFT JOIN invitation_recipients ir
        ON ir.invitation_id = i.id
        AND ir.status = 'sent'

    WHERE
        u.role = 'student'
        AND u.edition_id = :eid

    GROUP BY u.id
    ORDER BY saved_count DESC
");

$stmt->execute([':eid' => $edition_id]);
$rows = $stmt->fetchAll();
$stmt->execute([':eid' => $edition_id]);
$rows = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="bg">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Статистики</title>
    <link rel="stylesheet" href="styles/course.css">
    <style>
        th,td { padding: .5rem; border-bottom: 1px solid #ddd; }
    </style>
</head>
<body>
    <header>
        <div class="header-container">
            <div title="Home Page" class="logo">
                <a id="home-link" href="home_teacher.php">InviteMEme</a>
            </div>

            <nav>
                <ul>
                    <li class="dropdown">

                        <a href="course_edition.php?id=<?= $edition_id ?>">
                            <?= htmlspecialchars($_SESSION['teacher_edition_code']) ?> ▼
                        </a>

                        <ul class="dropdown-menu">
                            <li><a href="course_users.php">Потребители</a></li>
                            <li><a href="#">Статистики</a></li>
                            <li><a href="course_settings.php">Настройки</a></li>
                        </ul>

                    </li>

                    <li><a href="create_template.php">Създаване на шаблон</a></li>
                    <li><a href="edit_templates.php">Управление на шаблони</a></li>
                    <li><a href="profile.php">Профил</a></li>
                    <li><a href="auth/logout.php">Изход</a></li>
                </ul>
            </nav>
        </div>
    </header>
    <main>
        <h2>Статистики: запазени покани и изпратени имейли</h2> <br>
        <?php if (empty($rows)): ?>
            <p>Няма записи.</p>
        <?php else: ?>
            <table>
                <thead><tr><th>Студент</th><th>Фак. номер</th><th>Имейл</th><th>Запазени покани</th><th>Изпратени имейли</th></tr></thead>
                <tbody>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td><?php echo htmlspecialchars(trim($r['first_name'] . ' ' . $r['last_name'])); ?></td>
                        <td><?php echo htmlspecialchars($r['faculty_number'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($r['email']); ?></td>
                        <td><?php echo (int)$r['saved_count']; ?></td>
                        <td><?php echo (int)$r['sent_count']; ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </main>
</body>
</html>
