<?php
session_start();
require __DIR__ . "/../database/connect_db.php";
$db = (new DatabaseConnection())->getConnection();

$name = $_POST['name'];
$type = $_POST['type'];
$description = $_POST['description'] ?? null;
$is_active = $_POST['is_active'];
$created_by = $_SESSION['user_id'];

if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    die("Няма качено изображение.");
}

$img = $_FILES['image'];
$uploadDir = 'uploads/templates/';
$relativePath = '../' . $uploadDir;
if (!is_dir($relativePath)) {
    mkdir($relativePath, 0755, true);
}
$fileName = basename($img['name']);
$path = $uploadDir . $fileName;
if (!move_uploaded_file($img['tmp_name'], $relativePath . $fileName)) {
    die("Грешка при качване на файл.");
}

$stmt = $db->prepare("
    INSERT INTO invitation_templates (name, type, image_path, description, is_active, created_by)
    VALUES (:name, :type, :image_path, :description, :is_active, :created_by)
");

$stmt->execute([
    ':name' => $name,
    ':type' => $type,
    ':image_path' => 'uploads/templates/' . $fileName,
    ':description' => $description,
    ':is_active' => $is_active,
    ':created_by' => $created_by
]);

header("Location: ../create_template.php?saved=1");
exit;
