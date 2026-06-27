<?php
session_start();
if (empty($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'teacher') {
    header('Location: login.html');
    exit;
}

require "database/connect_db.php";

$db = (new DatabaseConnection())->getConnection();

$id = (int)($_GET['id'] ?? 0);

$stmt = $db->prepare("
    SELECT *
    FROM course_editions
    WHERE id = :id
");

$stmt->execute([':id' => $id]);

$edition = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$edition) {
    header("Location: home_teacher.php");
    exit;
}

$_SESSION['teacher_edition_id'] = $edition['id'];
$_SESSION['teacher_edition_code'] = $edition['code'];
$_SESSION['teacher_edition_title'] = $edition['title'];
?>

<!DOCTYPE html>
<html lang="bg">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Начало - Издание на курса</title>
    <link rel="stylesheet" href="styles/course.css">
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

                        <a href="#">
                            <?= htmlspecialchars($_SESSION['teacher_edition_code']) ?> ▼
                        </a>

                        <ul class="dropdown-menu">
                            <li><a href="course_users.php">Потребители</a></li>
                            <li><a href="stats.php">Статистики</a></li>
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

        <section class="welcome-card">
            <h1>Добре дошли в курса <?php echo htmlspecialchars($edition['title'] ?: '[Име на Курса]'); ?>!</h1>
            <p>
                Следете активностите на студентите в изданието на курса.
            </p>
        </section>

        <section class="card-grid">

            <div class="feature-card">
                <h3>Регистрирани потребители</h3>
                <p>
                    Проверете кои са регистриралите се 
                    потребители.
                </p>
                <a href="create_invitation.php" class="btn">Вижте</a>
            </div>

            <div class="feature-card">
                <h3>Статистики</h3>
                <p>
                    Достъп до статистики за изданието с 
                    филтри по специалност и курс.
                </p>
                <a href="status.php" class="btn">Вижте</a>
            </div>

            <div class="feature-card">
                <h3>Натройки на изданието</h3>
                <p>
                    Управлявайте статуса, полезните линкове и 
                    друга информация за изданието.
                </p>
                <a href="profile.php" class="btn">Към настройките</a>
            </div>

        </section>

    </main>

    <footer>
        WEB Technologies Project - InviteMEme &copy; 2026
    </footer>

</body>

</html>