<?php
session_start();

if (empty($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'teacher') {
    die("Нямате достъп.");
}

require "database/connect_db.php";
$db = (new DatabaseConnection())->getConnection();

$edition_id = (int)($_GET['edition_id'] ?? 0);

$stmt = $db->prepare("
    SELECT first_name, last_name, email, faculty_number, study_year, major
    FROM users
    WHERE edition_id = :eid AND role = 'student'
");

$stmt->execute([':eid' => $edition_id]);

$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
header('Content-Disposition: attachment; filename="students.json"');

echo json_encode($students, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
exit;