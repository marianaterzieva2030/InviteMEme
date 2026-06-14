<?php
require_once 'database/connect_db.php';
require_once 'init_app.php';

$db = (new DatabaseConnection())->getConnection();

initializeDatabase($db);

// $stmt = $db->prepare("SELECT * FROM users WHERE role = 'teacher' LIMIT 1");
// $stmt->execute();
// $teacher = $stmt->fetch();
// echo "Проверка за съществуващ учител: " . ($teacher ? "Намерен " . $teacher['email'] : "Не е намерен") . "<br>";

$dirs = [
    __DIR__ . '/uploads',
    __DIR__ . '/uploads/custom',
    __DIR__ . '/uploads/templates'
];

foreach ($dirs as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0777, true);
    }

    @chmod($dir, 0777);
}

header("Location: login.html");
exit;
?>