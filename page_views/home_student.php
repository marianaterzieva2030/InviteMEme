<?php
session_start();
if (empty($_SESSION['user_id'])) {
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
    <title>Начало</title>
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
                <li><a href="../create_invitation.php">Създаване на покана</a></li>
                <li><a href="#">Изпращане</a></li>
                <li><a href="#">Статус</a></li>
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
                Оттук можете да създавате покани за презентации,
                да ги изпращате по имейл и да следите статуса им.
            </p>
        </section>

        <section class="card-grid">

        <div class="feature-card">
            <h3>Създаване на покана</h3>
            <p>
                Изберете шаблон или качете собствено изображение,
                добавете текст и експортирайте като PNG.
            </p>
            <a href="../create_invitation.php" class="btn">Създай</a>
        </div>

            <div class="feature-card">
                <h3>Изпращане</h3>
                <p>
                    Изпратете вече създадена покана по имейл
                    или отворете Facebook групата.
                </p>
                <a href="#" class="btn">Изпрати</a>
            </div>

            <div class="feature-card">
                <h3>Статус</h3>
                <p>
                    Проверете кои покани са изпратени успешно
                    и кои са неуспешни.
                </p>
                <a href="#" class="btn">Виж статус</a>
            </div>

            <div class="feature-card">
                <h3>Профил</h3>
                <p>
                    Управлявайте профилната си информация
                    и променете паролата си.
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