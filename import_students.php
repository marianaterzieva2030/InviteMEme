<?php
session_start();

if (empty($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'teacher') {
    header('Location: /InviteMEme/login.html');
    exit;
}

require 'database/connect_db.php';
$db = (new DatabaseConnection())->getConnection();

$edition_id = $_SESSION['teacher_edition_id'] ?? null;

if (!$edition_id) {
    die("Няма избрано course edition.");
}

$error = '';
$message = '';
$imported = 0;
$skipped = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        $error = "Моля, изберете JSON файл.";
    } else {

        $json = file_get_contents($_FILES['file']['tmp_name']);
        $data = json_decode($json, true);

        if (!is_array($data)) {
            $error = "Невалиден JSON файл.";
        } else {

            $check = $db->prepare("
                SELECT COUNT(*) 
                FROM users 
                WHERE email = :email AND edition_id = :eid
            ");

            $insert = $db->prepare("
                INSERT INTO users 
                (first_name, last_name, email, faculty_number, study_year, major, role, edition_id)
                VALUES 
                (:first_name, :last_name, :email, :faculty_number, :study_year, :major, 'student', :edition_id)
            ");

            foreach ($data as $student) {

                if (empty($student['email'])) {
                    $skipped++;
                    continue;
                }

                $check->execute([
                    ':email' => $student['email'],
                    ':eid' => $edition_id
                ]);

                if ($check->fetchColumn() > 0) {
                    $skipped++;
                    continue;
                }

                $insert->execute([
                    ':first_name' => $student['first_name'] ?? '',
                    ':last_name' => $student['last_name'] ?? '',
                    ':email' => $student['email'],
                    ':faculty_number' => $student['faculty_number'] ?? null,
                    ':study_year' => $student['study_year'] ?? null,
                    ':major' => $student['major'] ?? null,
                    ':edition_id' => $edition_id
                ]);

                $imported++;
            }

            $message = [
                'imported' => $imported,
                'skipped' => $skipped
            ];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="bg">

<head>
    <meta charset="UTF-8">
    <title>Импортирай студенти</title>
    <link rel="stylesheet" href="styles/import.css">
</head>

<body>

    <header>
        <div class="header-container">
            <div class="logo">
                <a href="course_users.php">Назад към курса</a>
            </div>
        </div>
    </header>

    <main>

        <h2>Импортирай студенти (JSON)</h2>

        <?php if (!empty($error)): ?>
            <div class="white-box">
                <p style="color:red;">
                    <?= htmlspecialchars($error) ?>
                </p>
            </div>
        <?php endif; ?>

        <?php if (!empty($message)): ?>
            <div <div class="white-box">
                <p>Импортирани: <?= $message['imported'] ?></p>
                <p>Пропуснати(дублирани): <?= $message['skipped'] ?></p>
            </div>
        <?php endif; ?>

        <div class="white-box">
            <form method="POST" enctype="multipart/form-data">

                <label>Избери JSON файл</label>
                <br><br>

                <input type="file" name="file" accept=".json" required>

                <br><br>

                <button type="submit" class="btn">
                    Импортирай
                </button>

            </form>
        </div>

    </main>

</body>

</html>