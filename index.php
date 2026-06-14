<?php
require_once 'database/connect_db.php';
require_once 'init_app.php';

$db = (new DatabaseConnection())->getConnection();

initializeDatabase($db);

// $stmt = $db->prepare("SELECT * FROM users WHERE role = 'teacher' LIMIT 1");
// $stmt->execute();
// $teacher = $stmt->fetch();
// echo "Проверка за съществуващ учител: " . ($teacher ? "Намерен " . $teacher['email'] : "Не е намерен") . "<br>";

echo "<pre>";

echo "DOCUMENT_ROOT = " . $_SERVER['DOCUMENT_ROOT'] . "\n";

echo "custom files:\n";
print_r(glob(__DIR__ . '/uploads/custom/*'));

$stmt = $db->query("
SELECT id,title,generated_image_path
FROM invitations
");

print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

echo "</pre>";

// header("Location: login.html");
exit;
?>