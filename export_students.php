<?php
session_start();

if (empty($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'teacher') {
    die("Нямате достъп.");
}

require "database/connect_db.php";
$db = (new DatabaseConnection())->getConnection();

$edition_id = $_SESSION['teacher_edition_id'] ?? 0;

$ids = $_POST['student_ids'] ?? [];

if (empty($ids)) {
    die("Не сте избрали студенти за export.");
}

$placeholders = implode(',', array_fill(0, count($ids), '?'));

$sql = "
SELECT
    first_name,
    last_name,
    email,
    faculty_number,
    study_year,
    major
FROM users
WHERE edition_id = ?
AND role = 'student'
AND id IN ($placeholders)
";

$params = array_merge([$edition_id], $ids);

$stmt = $db->prepare($sql);
$stmt->execute($params);

$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
header('Content-Disposition: attachment; filename="students.json"');

echo json_encode($students, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
exit;
