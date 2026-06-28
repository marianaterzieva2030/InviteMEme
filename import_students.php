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
$updated = 0;
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

            // намираме студент по email (глобално)
            $find = $db->prepare("
                SELECT id, edition_id 
                FROM users 
                WHERE email = :email 
                AND role = 'student'
            ");

            // INSERT нов студент
            $insert = $db->prepare("
                INSERT INTO users 
                (first_name, last_name, email, faculty_number, study_year, major, role, edition_id)
                VALUES 
                (:first_name, :last_name, :email, :faculty_number, :study_year, :major, 'student', :edition_id)
            ");

            // UPDATE само курс (преместване)
            $update = $db->prepare("
                UPDATE users 
                SET edition_id = :edition_id
                WHERE id = :id
            ");

            foreach ($data as $student) {

                if (empty($student['email'])) {
                    $skipped++;
                    continue;
                }

                $find->execute([
                    ':email' => $student['email']
                ]);

                $existing = $find->fetch(PDO::FETCH_ASSOC);

                // Няма такъв студент → създаваме
                if (!$existing) {

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
                    continue;
                }

                // Ако вече е в същото издание → skip
                if ((int)$existing['edition_id'] === (int)$edition_id) {
                    $skipped++;
                    continue;
                }

                // ако съществува, но е в друг курс → местим го
                $update->execute([
                    ':edition_id' => $edition_id,
                    ':id' => $existing['id']
                ]);

                $updated++;
            }

            $message = [
                'imported' => $imported,
                'updated' => $updated,
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
        <a id="back" href="course_users.php">Назад към курса</a>
    </div>
</header>

<main>

<h2>Импортирай студенти (JSON)</h2>

<?php if (!empty($error)): ?>
    <div class="white-box">
        <p style="color:red;"><?= htmlspecialchars($error) ?></p>
    </div>
<?php endif; ?>

<?php if (!empty($message)): ?>
    <div class="white-box">
        <p>Импортирани (нови): <?= $message['imported'] ?></p>
        <p>Преместени в курс: <?= $message['updated'] ?></p>
        <p>Пропуснати: <?= $message['skipped'] ?></p>
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