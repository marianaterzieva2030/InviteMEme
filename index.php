<?php
require_once 'database/connect_db.php';
require_once 'init_app.php';

$db = (new DatabaseConnection())->getConnection();

initializeDatabase($db);

$stmt = $db->prepare("SELECT * FROM users WHERE edition_id = '1' LIMIT 1");
$stmt->execute();
$users_edition = $stmt->fetch();
if (!$users_edition) {
    echo "Няма потребители с edition_id = 1";
    exit;
} else {
    foreach ($users_edition as $u) {
        echo "Потребител: " . $u['email'] . "<br>";
    }
}

migrateDatabase($db);

// $stmt = $db->prepare("SELECT * FROM users WHERE role = 'teacher' LIMIT 1");
// $stmt->execute();
// $teacher = $stmt->fetch();
// echo "Проверка за съществуващ учител: " . ($teacher ? "Намерен " . $teacher['email'] : "Не е намерен") . "<br>";

header("Location: login.html");
exit;
?>