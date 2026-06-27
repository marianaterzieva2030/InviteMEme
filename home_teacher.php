<?php
session_start();
if (empty($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'teacher') {
    header('Location: login.html');
    exit;
}
$fullName = trim(($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? ''));

require "database/connect_db.php";

$db = (new DatabaseConnection())->getConnection();

$stmt = $db->query("
    SELECT *
    FROM course_editions
    ORDER BY id ASC
");

$editions = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="bg">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Начало - Преподавател</title>
    <link rel="stylesheet" href="styles/home.css">
</head>

<body>

    <header>
        <div class="header-container">
            <div title="Home Page" class="logo">
                <a id="home-link" href="#">InviteMEme</a>
            </div>

            <nav>
                <ul>
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
            <h1>Добре дошли, <?php echo htmlspecialchars($fullName ?: '[Име Фамилия]'); ?>!</h1>
            <p>
                Изберете издание на курса и прегледайте участниците в него, 
                както и статистики за създадените покани.
            </p>
        </section>

        <section class="dashboard">

            <?php foreach($editions as $edition): ?>
                <div class="edition-card">
                    <h2><?= htmlspecialchars(strtoupper($edition['code'])) ?></h2>
                    <p><?= htmlspecialchars($edition['title']) ?></p>

                    <?php if($edition['is_active']): ?>
                        <span class="badge active">
                            Активно издание
                        </span>
                    <?php else: ?>
                        <span class="badge archive">
                            Архив
                        </span>
                    <?php endif; ?>
                    
                    <!-- send edition's id to course_edition.php -->
                    <a class="btn" href="course_edition.php?id=<?= $edition['id'] ?>">
                        Отвори
                    </a>

                </div>
            <?php endforeach; ?>

        </section>

    </main>

    <footer>
        WEB Technologies Project - InviteMEme &copy; 2026
    </footer>

</body>

</html>