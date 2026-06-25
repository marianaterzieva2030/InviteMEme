<?php

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	http_response_code(405);
	echo 'Method not allowed';
	exit;
}

require_once('../database/connect_db.php');

$faculty_number = trim($_POST['faculty_number'] ?? '');
$first_name = trim($_POST['first_name'] ?? '');
$last_name = trim($_POST['last_name'] ?? '');
$major = $_POST['major'] ?? '';
$study_year = $_POST['study_year'] ?? '';
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

$errors = [];

if (empty($first_name) || empty($last_name) || empty($email) || empty($password) 
	|| empty($confirm_password) || empty($major) || empty($study_year)) {
	$errors[] = 'Моля, попълнете всички задължителни полета.';
}

if ($password !== $confirm_password) {
	$errors[] = 'Паролите не съвпадат.';
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
	$errors[] = 'Невалиден имейл адрес.';
}

if (!empty($errors)) {
	http_response_code(400);
	foreach ($errors as $err) {
		echo '<p>' . htmlspecialchars($err) . '</p>';
	}
	echo '<p><a href="../register.html">Връщане към регистрацията</a></p>';
	exit;
}

$db = new DatabaseConnection();
$pdo = $db->getConnection();

try {
	$stmt = $pdo->prepare("SELECT id FROM users 
						   WHERE email = :email LIMIT 1");
	$stmt->execute(['email' => $email]);
	$existing = $stmt->fetch();

	if ($existing) {
		http_response_code(409);
		echo '<p>Вече съществува акаунт с този имейл.</p>';
		echo '<p><a href="../register.html">Пробвайте с друг имейл</a></p>';
		exit;
	}

	// Added current edition fetch from database
	$edition_stmt = $pdo->prepare("SELECT id FROM course_editions WHERE is_active = 1");
	$edition_stmt->execute();
	$edition_id = $edition_stmt->fetchColumn();

	if (!$edition_id) {
    	die("Няма активно издание на курса.");
	}

	$password_hash = password_hash($password, PASSWORD_DEFAULT);

	$insert = $pdo->prepare("INSERT INTO users (faculty_number, first_name, last_name, email, password_hash, major, study_year, edition_id) 
							 VALUES (:faculty_number, :first_name, :last_name, :email, :password_hash, :major, :study_year, :edition_id)");
	$insert->execute([
		'faculty_number' => $faculty_number ?: null,
		'first_name' => $first_name,
		'last_name' => $last_name,
		'email' => $email,
		'password_hash' => $password_hash,
		'major' => $major,
		'study_year' => $study_year,
		'edition_id' => $edition_id
	]);

	header('Location: ../login.html');
	exit;

} catch (PDOException $e) {
	http_response_code(500);
	echo '<p>Възникна грешка. Моля опитайте по-късно.</p>';
	// For debugging: echo '<pre>' . htmlspecialchars($e->getMessage()) . '</pre>';
	exit;
}

?>