<?php
require_once 'database/connect_db.php';
require_once 'init_app.php';

$db = (new DatabaseConnection())->getConnection();

initializeDatabase($db);

$stmt = $db->prepare("SELECT * FROM users");
$stmt->execute();
$users_edition = $stmt->fetch();
if (!$users_edition) {
    echo "Няма потребители";
    exit;
} else {
    foreach ($users_edition as $u) {
        echo "Потребител: " . $u['email'] . "<br>";
    }
}

$inv_stmt = $db->prepare("SELECT * FROM invitations");
$inv_stmt->execute();
$invitations = $inv_stmt->fetch();
if (!$invitations) {
    echo "Няма покани";
    exit;
} else {
    foreach ($invitations as $i) {
        echo "Покана: " . $i['id'] . " Потребител: " . $i['user_id'] . " Създадена на: " . $i['created_at'];
    }
}

// migrateDatabase($db);

// $stmt = $db->prepare("SELECT * FROM users WHERE role = 'teacher' LIMIT 1");
// $stmt->execute();
// $teacher = $stmt->fetch();
// echo "Проверка за съществуващ учител: " . ($teacher ? "Намерен " . $teacher['email'] : "Не е намерен") . "<br>";

header("Location: login.html");
exit;
?>