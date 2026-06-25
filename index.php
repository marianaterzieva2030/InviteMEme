<?php
require_once 'database/connect_db.php';
require_once 'init_app.php';

$db = (new DatabaseConnection())->getConnection();

initializeDatabase($db);

migrateDatabase($db);
echo "Migrated successfully!";

$stmt = $db->query("SELECT * FROM users");
echo "<pre>";
var_dump($stmt->fetchAll(PDO::FETCH_ASSOC));
echo "</pre>";

$inv_stmt = $db->prepare("SELECT * FROM invitations");
echo "<pre>";
var_dump($inv_stmt->fetchAll(PDO::FETCH_ASSOC));
echo "</pre>";


// $stmt = $db->prepare("SELECT * FROM users WHERE role = 'teacher' LIMIT 1");
// $stmt->execute();
// $teacher = $stmt->fetch();
// echo "Проверка за съществуващ учител: " . ($teacher ? "Намерен " . $teacher['email'] : "Не е намерен") . "<br>";

// header("Location: login.html");
exit;
?>