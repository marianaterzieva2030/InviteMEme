<?php
session_start();

if (empty($_SESSION['user_id'])) {
    header('Location: /InviteMEme/login.html');
    exit;
}

require "database/connect_db.php";
$db = (new DatabaseConnection())->getConnection();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: create_invitation.php');
    exit;
}

$template_id = !empty($_POST['template_id']) ? $_POST['template_id'] : null;
$title = trim($_POST['title'] ?? '');
$presentation_date = trim($_POST['presentation_date'] ?? '');
$presentation_time = trim($_POST['presentation_time'] ?? '');
$room = trim($_POST['room'] ?? '');
$presenter = trim($_POST['presenter'] ?? '');
$description = trim($_POST['description'] ?? '');
$canvas_data = $_POST['canvas_data'] ?? '';

if ($title === '' || $presentation_date === '' || $presentation_time === '' || $room === '' || $canvas_data === '') {
    header('Location: create_invitation.php?error=missing');
    exit;
}

if (!preg_match('/^data:image\/png;base64,(.+)$/', $canvas_data, $matches)) {
    header('Location: create_invitation.php?error=missing');
    exit;
}

$imageData = base64_decode($matches[1]);
if ($imageData === false) {
    header('Location: create_invitation.php?error=missing');
    exit;
}

$uploadDir = __DIR__ . '/images/invitations';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

$filename = 'invite_' . time() . '_' . bin2hex(random_bytes(6)) . '.png';
$path = $uploadDir . '/' . $filename;
if (file_put_contents($path, $imageData) === false) {
    header('Location: create_invitation.php?error=missing');
    exit;
}

$generated_image_path = 'images/invitations/' . $filename;
$description_combined = trim(($presenter !== '' ? "Презентиращ: $presenter\n" : '') . $description);

$stmt = $db->prepare("INSERT INTO invitations (user_id, template_id, title, presentation_date, presentation_time, room, description, generated_image_path) VALUES (:user_id, :template_id, :title, :presentation_date, :presentation_time, :room, :description, :generated_image_path)");
$stmt->execute([
    ':user_id' => $_SESSION['user_id'],
    ':template_id' => $template_id,
    ':title' => $title,
    ':presentation_date' => $presentation_date,
    ':presentation_time' => $presentation_time,
    ':room' => $room,
    ':description' => $description_combined,
    ':generated_image_path' => $generated_image_path,
]);

header('Location: create_invitation.php?saved=1');
exit;
