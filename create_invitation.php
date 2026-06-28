<?php
session_start();

if (empty($_SESSION['user_id'])) {
    header('Location: login.html');
    exit;
}

require "database/connect_db.php";
$db = (new DatabaseConnection())->getConnection();

$stmtUser = $db->prepare("SELECT first_name, last_name, faculty_number, role FROM users WHERE id = :id LIMIT 1");
$stmtUser->execute([':id' => $_SESSION['user_id']]);
$user = $stmtUser->fetch(PDO::FETCH_ASSOC);
$isTeacher = ($_SESSION['user_role'] === 'teacher');
$faculty = $user['faculty_number'] ?? '';

$stmt = $db->prepare("SELECT id, name, image_path, type, description FROM invitation_templates WHERE is_active = 1");
$stmt->execute();
$templates = $stmt->fetchAll();

$successMessage = '';
$errorMessage = '';
if (isset($_GET['saved']) && $_GET['saved'] === '1') {
    $successMessage = 'Поканата беше запазена успешно.';
}
if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'missinginfo':
            $errorMessage = 'Моля, попълнете всички задължителни полета и повторете опита.';
            break;
        case 'canvas':
            $errorMessage = 'Грешка при обработката на изображението. Моля, опитайте отново.';
            break;
        case 'noimage':
            $errorMessage = 'Невалидни данни за изображението. Моля, опитайте отново.';
            break;
        case 'failedmkdir':
            $errorMessage = 'Грешка при създаването на директория за качване. Моля, свържете се с администратора.';
            break;
        case 'notwritable':
            $errorMessage = 'Директорията за качване на изображението няма права за запис. Моля, свържете се с администратора.';
            break;
        default:
            $errorMessage = 'Възникна грешка. Моля, опитайте отново.';
    }
}
?>

<!DOCTYPE html>
<html lang="bg">

<head>
    <meta charset="UTF-8">
    <title>Създаване на покани</title>
    <link rel="stylesheet" href="styles/create.css">
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
                        <li><a href="create_invitation.php" id="active-menu">Създаване на покана</a></li>
                        <li><a href="send_invitation.php">Изпращане</a></li>
                        <li><a href="status.php">Статус</a></li>
                        <li><a href="profile.php">Профил</a></li>
                        <li><a href="auth/logout.php">Изход</a></li>
                    <?php else: ?>
                        <li><a href="create_invitation.php" id="active-menu">Създаване на покана</a></li>
                        <li><a href="send_invitation.php">Изпращане</a></li>
                        <li><a href="status.php">Статус</a></li>
                        <li><a href="profile.php">Профил</a></li>
                        <li><a href="auth/logout.php">Изход</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>

    <h2><br>Създаване на покана</h2>

    <?php if ($successMessage): ?>
        <div class="message message-success"><?= htmlspecialchars($successMessage) ?></div>
    <?php endif; ?>
    <?php if ($errorMessage): ?>
        <div class="message message-error"><?= htmlspecialchars($errorMessage) ?></div>
    <?php endif; ?>

    <div class="editor">
        <div class="form-panel">
            <form method="POST" action="php/save_invitation.php" enctype="multipart/form-data" id="inviteForm">
                <div class="field-group">
                    <label for="templateSelect">Избор на шаблон</label>
                    <select id="templateSelect" name="template_id">
                        <option value="">-- Без шаблон --</option>
                        <?php foreach ($templates as $row): ?>
                            <option
                                value="<?= $row['id'] ?>"
                                data-image="<?= htmlspecialchars($row['image_path']) ?>"
                                data-type="<?= htmlspecialchars($row['type']) ?>">
                                <?= htmlspecialchars($row['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="field-group">
                    <label for="imageInput">Качи снимка за фон</label>
                    <input type="file" id="imageInput" name="image" accept="image/*">
                </div>

                <div class="field-group">
                    <label>Тип шаблон</label>

                    <div class="type-options" id="typeOptions">
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
                    <label for="titleInput">Тема</label>
                    <input type="text" id="titleInput" name="title" placeholder="Тема на презентацията" required>
                </div>

                <div class="field-row">
                    <div class="field-group half">
                        <label for="dateInput">Дата</label>
                        <input type="date" id="dateInput" name="presentation_date" required>
                    </div>
                    <div class="field-group half">
                        <label for="timeInput">Час</label>
                        <input type="time" id="timeInput" name="presentation_time" required>
                    </div>
                </div>

                <div class="field-group">
                    <label for="roomInput">Зала</label>
                    <input type="text" id="roomInput" name="room" placeholder="Зала" required>
                </div>

                <div class="field-group">
                    <label for="descriptionInput">Допълнителен текст</label>
                    <textarea id="descriptionInput" name="description" placeholder="Описание..."></textarea>
                </div>

                <div class="field-group">
                    <label for="textInput">Текстове върху снимката</label>
                    <button type="button" id="addTextBtn">+ Добави текст</button>
                    <div id="textLayersContainer"></div>
                </div>

                <div class="field-row">
                    <div class="field-group half">
                        <label for="colorInput">Цвят на текста</label>
                        <input type="color" id="colorInput" value="#000000">
                    </div>
                    <div class="field-group half">
                        <label for="fontInput">Шрифт</label>
                        <select id="fontInput">
                            <option>Arial</option>
                            <option>Verdana</option>
                            <option>Times New Roman</option>
                            <option>Courier New</option>
                        </select>
                    </div>
                </div>

                <div class="field-group">
                    <label for="sizeInput">Размер на текста</label>
                    <input type="range" id="sizeInput" min="10" max="64" value="30">
                </div>

                <input type="hidden" name="textLayers" id="textLayersData">
                <input type="hidden" name="canvas_data" id="canvasData">

                <div class="button-row">
                    <button type="submit" id="saveBtn">Запази поканата</button>
                    <button type="button" id="downloadBtn">Изтегли PNG</button>
                    <button type="button" id="shareBtn">Сподели</button>
                </div>
            </form>
        </div>

        <div class="preview">
            <div class="preview-label">Преглед на поканата</div>
            <div class="preview-area" id="previewArea">
                <canvas id="inviteCanvas" width="600" height="600"></canvas>

                <div id="inviteInfo" class="invite-info">
                    <p>Тема: <span id="pTitle"></span></p>
                    <p>Дата: <span id="pDate"></span></p>
                    <p>Час: <span id="pTime"></span></p>
                    <p>Зала: <span id="pRoom"></span></p>
                    <p>Презентиращ: <?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></p>
                    <?php if (!$isTeacher): ?>
                        <p>Факултетен номер: <?= htmlspecialchars($faculty) ?></p>
                    <?php endif; ?>
                    <p>Описание: <span id="pDesc"></span></p>
                </div>
            </div>

        </div>
    </div>

    <script src="javascript/create_invitation.js"></script>

</body>

</html>