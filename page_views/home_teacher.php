<?php
session_start();
if (empty($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'teacher') {
    header('Location: login.html');
    exit;
}
$fullName = trim(($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? ''));
?>

<!DOCTYPE html>
<html lang="bg">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Начало - Преподавател</title>
    <link rel="stylesheet" href="../styles/home.css">
</head>

<body>

    <header>
        <div class="header-container">
            <div class="logo">
                <a id="home-link" href="#">InviteMEme</a>
            </div>

            <nav>
                <ul>
                    <li><a href="#">Създаване на шаблон</a></li>
                    <li><a href="#">Управление на шаблони</a></li>
                    <li><a href="#">Статистики</a></li>
                    <li><a href="#">Профил</a></li>
                    <li><a href="../auth/logout.php">Изход</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main>

        <section class="welcome-card">
            <h1>Добре дошли, <?php echo htmlspecialchars($fullName ?: '[Име Фамилия]'); ?>!</h1>
            <p>
                Управлявайте шаблоните за покани и следете
                активността на студентите.
            </p>
        </section>

        <section class="card-grid">

            <div class="feature-card">
                <h3>Нов шаблон</h3>
                <p>
                    Създайте нов стандартен или meme шаблон
                    за студентите.
                </p>
                <a href="../php/create_template.php" class="btn">Създай</a>
            </div>

            <div class="feature-card">
                <h3>Управление на шаблони</h3>
                <p>
                    Активирайте, редактирайте или деактивирайте
                    съществуващи шаблони.
                </p>
                <a href="#" class="btn">Управлявай</a>
            </div>

            <div class="feature-card">
                <h3>Статистики</h3>
                <p>
                    Вижте кои студенти са изпратили покани
                    по имейл и кои не са.
                </p>
                <a href="#" class="btn">Отвори</a>
            </div>

            <div class="feature-card">
                <h3>Профил</h3>
                <p>
                    Прегледайте профилната информация
                    и настройките си.
                </p>
                <a href="#" class="btn">Към профила</a>
            </div>

        </section>

    </main>

    <footer>
        WEB Technologies Project - InviteMEme &copy; 2026
    </footer>

</body>

</html>