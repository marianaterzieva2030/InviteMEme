<?php
session_start();

if (empty($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'teacher') {
    die("Нямате достъп.");
}

require "database/connect_db.php";
$db = (new DatabaseConnection())->getConnection();

$ids = $_POST['template_ids'] ?? [];

if (empty($ids)) {
    die("Не сте избрали шаблони за export.");
}

$placeholders = implode(',', array_fill(0, count($ids), '?'));

$stmt = $db->prepare("
    SELECT * FROM invitation_templates
    WHERE id IN ($placeholders)
");

$stmt->execute($ids);
$templates = $stmt->fetchAll(PDO::FETCH_ASSOC);

$tmpDir = sys_get_temp_dir() . "/templates_export_" . time();
mkdir($tmpDir);
mkdir($tmpDir . "/images");

file_put_contents(
    $tmpDir . "/templates.json",
    json_encode($templates, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
);

foreach ($templates as $t) {
    if (!empty($t['image_path']) && file_exists($t['image_path'])) {
        copy($t['image_path'], $tmpDir . "/images/" . basename($t['image_path']));
    }
}

$zipPath = $tmpDir . ".zip";
$zip = new ZipArchive();
$zip->open($zipPath, ZipArchive::CREATE);

$files = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($tmpDir),
    RecursiveIteratorIterator::LEAVES_ONLY
);

foreach ($files as $file) {
    if (!$file->isDir()) {
        $filePath = $file->getRealPath();
        $relative = substr($filePath, strlen($tmpDir) + 1);
        $zip->addFile($filePath, $relative);
    }
}

$zip->close();

header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="templates.zip"');
readfile($zipPath);
exit;
