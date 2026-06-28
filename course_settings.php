<?php
session_start();
if (empty($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'teacher') {
    header('Location: /InviteMEme/login.html');
    exit;
}

$edition_id = $_SESSION['teacher_edition_id'];

$message = $_SESSION['success_message'] ?? '';
unset($_SESSION['success_message']);

require "database/connect_db.php";

$db = (new DatabaseConnection())->getConnection();
$stmt = $db->prepare("SELECT * FROM course_editions WHERE id = :id");

$stmt->execute([':id' => $edition_id]);

$edition = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$edition) {
    exit('Невалидно издание.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $stmt = $db->prepare(
        "UPDATE course_editions
        SET
            code = :code,
            title = :title,
            is_active = :active,
            facebook_url = :facebook,
            moodle_url = :moodle
        WHERE id = :id
    ");

    $stmt->execute([
        ':code' => trim($_POST['code']),
        ':title' => trim($_POST['title']),
        ':active' => isset($_POST['is_active']) ? 1 : 0,
        ':facebook' => trim($_POST['facebook_url']),
        ':moodle' => trim($_POST['moodle_url']),
        ':id' => $edition_id
    ]);

    $_SESSION['teacher_edition_code'] = $_POST['code'];
    $_SESSION['teacher_edition_title'] = $_POST['title'];

    $_SESSION['success_message'] = 'Успешно запазихте промените!';
    header("Location: course_settings.php?saved=1");
    exit;
}
?>

<!DOCTYPE html>
<html lang="bg">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Статистики</title>
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

                        <a href="course_edition.php?id=<?= $edition_id ?>">
                            <?= htmlspecialchars($_SESSION['teacher_edition_code']) ?> ▼
                        </a>

                        <ul class="dropdown-menu">
                            <li><a href="course_users.php">Потребители</a></li>
                            <li><a href="course_stats.php">Статистики</a></li>
                            <li><a href="create_invitation.php">Създаване на покана</a></li>
                            <li><a href="send_invitation.php">Изпращане</a></li>
                            <li><a href="status.php">Статус</a></li>
                            <li><a href="#">Настройки</a></li>
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
        <h2>Настройки</h2> <br>
        <p class="success-message">
            <?= htmlspecialchars($message) ?>
        </p>
        
        <div class="settings-card">
            <form method="POST">

                <label>Код</label>
                <input
                    type="text"
                    name="code"
                    value="<?= htmlspecialchars($edition['code']) ?>"
                    required>

                <label>Име</label>
                <input
                    type="text"
                    name="title"
                    value="<?= htmlspecialchars($edition['title']) ?>"
                    required>

                <label>Facebook група</label>
                <input
                    type="url"
                    name="facebook_url"
                    value="<?= htmlspecialchars($edition['facebook_url'] ?? '') ?>">

                <label>Moodle</label>
                <input
                    type="url"
                    name="moodle_url"
                    value="<?= htmlspecialchars($edition['moodle_url']) ?? '' ?>">

                <label class="checkbox">

                    <input
                        type="checkbox"
                        name="is_active"
                        <?= $edition['is_active'] ? 'checked' : '' ?>>

                    Активно издание
                </label>

                <button type="submit">
                    Запази
                </button>
            </form>
        </div>
    </main>
</body>
</html>
