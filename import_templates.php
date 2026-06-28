<?php
session_start();

if (empty($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'teacher') {
    die("Нямате достъп.");
}

require "database/connect_db.php";
$db = (new DatabaseConnection())->getConnection();

function deleteFolder($folder)
{
    if (!is_dir($folder)) return;

    $files = array_diff(scandir($folder), ['.', '..']);

    foreach ($files as $file) {
        $path = $folder . DIRECTORY_SEPARATOR . $file;

        if (is_dir($path)) {
            deleteFolder($path);
        } else {
            unlink($path);
        }
    }

    rmdir($folder);
}

$message = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!isset($_FILES['zip_file']) || $_FILES['zip_file']['error'] !== UPLOAD_ERR_OK) {
        $message = ['error' => 'Моля, изберете ZIP файл.'];
    } else {

        $zip = new ZipArchive();
        $tmpZip = $_FILES['zip_file']['tmp_name'];

        $extractPath = "temp_import_" . time() . "/";

        if (!mkdir($extractPath)) {
            $message = ['error' => 'Не може да се създаде временна директория.'];
        } else {

            if ($zip->open($tmpZip) === TRUE) {

                $zip->extractTo($extractPath);
                $zip->close();

                $jsonFile = $extractPath . "templates.json";

                if (!file_exists($jsonFile)) {
                    $message = ['error' => 'Липсва templates.json в ZIP файла.'];
                } else {

                    $templates = json_decode(file_get_contents($jsonFile), true);

                    if ($templates === null) {
                        $message = ['error' => 'Невалиден JSON файл.'];
                    } else {

                        $inserted = 0;
                        $skipped = 0;

                        $checkStmt = $db->prepare("
                            SELECT COUNT(*) FROM invitation_templates WHERE name = :name
                        ");

                        $insertStmt = $db->prepare("
                            INSERT INTO invitation_templates
                            (name, type, image_path, description, is_active)
                            VALUES
                            (:name, :type, :image_path, :description, :is_active)
                        ");

                        foreach ($templates as $t) {

                            $checkStmt->execute([':name' => $t['name']]);

                            if ($checkStmt->fetchColumn() > 0) {
                                $skipped++;
                                continue;
                            }

                            $oldImage = $extractPath . "images/" . basename($t['image_path']);
                            $newImage = "uploads/templates/" . basename($t['image_path']);

                            if (file_exists($oldImage)) {
                                copy($oldImage, $newImage);
                            } else {
                                $newImage = null;
                            }

                            $insertStmt->execute([
                                ':name' => $t['name'],
                                ':type' => $t['type'],
                                ':image_path' => $newImage,
                                ':description' => $t['description'],
                                ':is_active' => $t['is_active']
                            ]);

                            $inserted++;
                        }

                        $message = [
                            'inserted' => $inserted,
                            'skipped' => $skipped
                        ];
                    }
                }

                deleteFolder($extractPath);
            } else {
                $message = ['error' => 'ZIP файлът не може да се отвори.'];
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="bg">

<head>
    <meta charset="UTF-8">
    <title>Import Templates ZIP</title>
    <link rel="stylesheet" href="styles/import.css">
</head>

<body>
    <header>
        <div class="header-container">
            <div class="logo">
                <a id="home-link" href="home_teacher.php">InviteMEme</a>
            </div>

            <nav>
                <ul>
                    <li><a href="create_template.php">Създаване на шаблон</a></li>
                    <li><a href="edit_templates.php" id="active-menu">Управление на шаблони</a></li>
                    <li><a href="profile.php">Профил</a></li>
                    <li><a href="auth/logout.php">Изход</a></li>
                </ul>
            </nav>
        </div>
    </header>
    <main>
        <h2>Import Templates (ZIP)</h2>

        <?php if (!empty($message['error'])): ?>
            <p style="color:red;">
                <?= htmlspecialchars($message['error']) ?>
            </p>
        <?php endif; ?>

        <?php if (!empty($message) && isset($message['inserted'])): ?>
            <div <div class="white-box">
                <p>Импортирани: <?= $message['inserted'] ?></p>
                <p>Пропуснати: <?= $message['skipped'] ?></p>
            </div>
        <?php endif; ?>
        <div class="white-box">
            <form method="POST" enctype="multipart/form-data">
                <input type="file" name="zip_file" accept=".zip" required>
                <br><br>
                <button type="submit">Импортирай</button>
            </form>
        </div>
    </main>
</body>

</html>