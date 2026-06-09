<?php
session_start();

if (empty($_SESSION['user_id'])) {
    header('Location: login.html');
    exit;
}

require "database/connect_db.php";
$db = (new DatabaseConnection())->getConnection();

$stmt = $db->prepare("SELECT id, name, image_path, type, description FROM invitation_templates WHERE is_active = 1");
$stmt->execute();
$templates = $stmt->fetchAll();

$successMessage = '';
$errorMessage = '';
if (isset($_GET['saved']) && $_GET['saved'] === '1') {
    $successMessage = 'Поканата беше запазена успешно.';
}
if (isset($_GET['error']) && $_GET['error'] === 'missing') {
    $errorMessage = 'Моля, попълнете всички задължителни полета и повторете опита.';
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
                <a id="home-link" href="home_student.php">InviteMEme</a>
            </div>

            <nav>
                <ul>
                    <li>Създаване на покана</li>
                    <li><a href="#">Изпращане</a></li>
                    <li><a href="#">Статус</a></li>
                    <li><a href="#">Профил</a></li>
                    <li><a href="auth/logout.php">Изход</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <h2>Създаване на покана</h2>

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
                        <option value="">-- Избери шаблон --</option>
                        <?php foreach ($templates as $row): ?>
                            <option
                                value="<?= $row['id'] ?>"
                                data-image="<?= htmlspecialchars($row['image_path']) ?>"
                                data-type="<?= htmlspecialchars($row['type']) ?>"
                                data-description="<?= htmlspecialchars($row['description']) ?>">
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
                    <label for="presenterInput">Презентиращ</label>
                    <input type="text" id="presenterInput" name="presenter" placeholder="Име на презентиращия" required>
                </div>

                <div class="field-group">
                    <label for="faculty_number">Факултетен номер</label>
                    <input type="text" id="facultyNumberInput" name="faculty_number" placeholder="Факултетен номер" required>
                </div>

                <div class="field-group">
                    <label for="descriptionInput">Допълнителен текст</label>
                    <textarea id="descriptionInput" name="description" placeholder="Описание..."></textarea>
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

            </div>

        </div>
    </div>
    </div>

    <script src="javascript/create_invitation.js"></script>

</body>

</html>