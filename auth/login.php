<?php

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo 'Method not allowed';
    exit;
}

require_once('../database/connect_db.php');

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

$errors = [];

if (empty($email) || empty($password)) {
    $errors[] = 'Моля, попълнете имейл и парола.';
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Невалиден имейл адрес.';
}

if (!empty($errors)) {
    http_response_code(400);
    foreach ($errors as $err) {
        echo '<p>' . htmlspecialchars($err) . '</p>';
    }
    echo '<p><a href="../login.html">Връщане към входа</a></p>';
    exit;
}

$db = new DatabaseConnection();
$pdo = $db->getConnection();

try {
    $stmt = $pdo->prepare("SELECT id, email, password_hash, role, first_name, last_name, edition_id FROM users 
                           WHERE email = :email LIMIT 1");
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        http_response_code(401);
        echo '<p>Невалиден имейл или парола.</p>';
        echo '<p><a href="../login.html">Опитайте отново</a></p>';
        exit;
    }

    session_start();
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['first_name'] = $user['first_name'] ?? '';
    $_SESSION['last_name'] = $user['last_name'] ?? '';
    $_SESSION['edition_id'] = $user['edition_id'] ?? '';

    if ($user['role'] === 'teacher') {
        header('Location: ../home_teacher.php');
    } else {
        header('Location: ../home_student.php');
    }
    exit;

} catch (PDOException $e) {
    http_response_code(500);
    echo '<p>Възникна грешка. Моля опитайте по-късно.</p>';
    // For debugging: echo '<pre>' . htmlspecialchars($e->getMessage()) . '</pre>';
    exit;
}

?>