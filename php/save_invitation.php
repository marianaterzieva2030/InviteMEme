<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();

if (empty($_SESSION['user_id'])) {
    header('Location: ../login.html');
    exit;
}

require '../database/connect_db.php';
$db = (new DatabaseConnection())->getConnection();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../create_invitation.php');
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
    header('Location: ../create_invitation.php?error=missinginfo');
    exit;
}

if (!preg_match('/^data:image\/png;base64,(.+)$/', $canvas_data, $matches)) {
    header('Location: ../create_invitation.php?error=canvas');
    exit;
}

$imageData = base64_decode($matches[1]);
if ($imageData === false) {
    header('Location: ../create_invitation.php?error=noimage');
    exit;
}

$uploadDir = __DIR__ . '/../uploads/custom/';

echo "<pre>";
echo "uploadDir = $uploadDir\n";
echo "exists = " . (is_dir($uploadDir) ? "YES" : "NO") . "\n";
echo "writable = " . (is_writable($uploadDir) ? "YES" : "NO") . "\n";

if (is_dir($uploadDir)) {
    echo "perms = " . substr(sprintf('%o', fileperms($uploadDir)), -4) . "\n";
    echo "owner = " . fileowner($uploadDir) . "\n";
}

echo "</pre>";
exit;

if (!is_dir($uploadDir)) {
    if (!mkdir($uploadDir, 0777, true)) {
        error_log('save_invitation: failed to create upload dir: ' . $uploadDir);
        header('Location: ../create_invitation.php?error=failedmkdir');
        exit;
    }
}

// Ensure directory is writable by PHP
if (!is_writable($uploadDir)) {
    @chmod($uploadDir, 0777);
    if (!is_writable($uploadDir)) {
        error_log('save_invitation: upload dir not writable: ' . $uploadDir);
        header('Location: ../create_invitation.php?error=notwritable');
        exit;
    }
}

$filename = 'invite_' . time() . '_' . bin2hex(random_bytes(6)) . '.png';
$generated_image_path = 'uploads/custom/' . $filename;
$bytes = file_put_contents($uploadDir . $filename, $imageData, LOCK_EX);
if ($bytes === false) {
    $err = error_get_last();
    error_log('save_invitation: failed writing file: ' . $generated_image_path . ' -- ' . print_r($err, true));
    header('Location: ../create_invitation.php?error=failedwrite');
    exit;
}

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

header('Location: ../send_invitation.php');
exit;
