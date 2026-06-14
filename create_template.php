<?php
session_start();

if (empty($_SESSION['user_id'])) {
    header('Location: login.html');
    exit;
}

if (($_SESSION['user_role'] ?? '') !== 'teacher') {
    header('Location: login.html');
    exit;
}

require "database/connect_db.php";
$db = (new DatabaseConnection())->getConnection();

$successMessage = '';
if (isset($_GET['saved']) && $_GET['saved'] === '1') {
    $successMessage = 'Шаблонът беше създаден успешно.';
}
?>

<!DOCTYPE html>
<html lang="bg">

<head>
    <meta charset="UTF-8">
    <title>Създаване на шаблон</title>
    <link rel="stylesheet" href="styles/create.css">
    <style>
        .form-panel .field-group { margin-bottom: 1rem; }
        .form-panel .field-group label { display: block; margin-bottom: .35rem; }
        .form-panel .radio-row { display: flex; gap: 1rem; align-items: center; }
        .form-panel .radio-option { display: flex; gap: .4rem; align-items: center; }
    </style>
</head>

<body>
    <header>
        <div class="header-container">
            <div class="logo">
                <a id="home-link" href="home_teacher.php">InviteMEme</a>
            </div>

            <nav>
                <ul>
                    <li><a href="#" id="active-menu">Създаване на шаблон</a></li>
                    <li><a href="edit_templates.php">Управление на шаблони</a></li>
                    <li><a href="stats.php">Статистики</a></li>
                    <li><a href="profile.php">Профил</a></li>
                    <li><a href="auth/logout.php">Изход</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main>
    <h2><br>Създаване на шаблон</h2>

    <?php if ($successMessage): ?>
        <div class="message message-success"><?= htmlspecialchars($successMessage) ?></div>
    <?php endif; ?>

    <div class="editor">
        <div class="form-panel">
            <form action="php/save_template.php" method="POST" enctype="multipart/form-data">

                <div class="field-group">
                    <label for="name">Име на шаблона</label>
                    <input type="text" id="name" name="name" required>
                </div>

                <div class="field-group">
                    <label>Тип шаблон</label>

                    <div class="radio-row">
                        <label class="radio-option">
                            <input type="radio" name="type" value="standard" checked>
                            Стандартен
                        </label>

                        <label class="radio-option">
                            <input type="radio" name="type" value="meme">
                            Меме
                        </label>
                    </div>
                </div>

                <div class="field-group">
                    <label for="image">Качи изображение</label>
                    <input type="file" id="image" name="image" accept="image/*" required>
                </div>

                <div class="field-group">
                    <label for="is_active">Активен</label>
                    <select id="is_active" name="is_active">
                        <option value="1">Да</option>
                        <option value="0">Не</option>
                    </select>
                </div>

                <button class="btn" type="submit">Запази шаблон</button>

            </form>

        </div>
        <div class="preview">
            <div class="preview-label">Преглед на поканата</div>
            <div class="preview-area" id="previewArea">

                <canvas id="inviteCanvas" width="600" height="600"></canvas>

            </div>
        </div>
    </div>
    </main>
    <script src="javascript/create_template.js"></script>
</body>

</html>