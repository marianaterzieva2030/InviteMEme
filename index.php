<?php
require_once 'database/connect_db.php';
require_once 'init_app.php';

$db = (new DatabaseConnection())->getConnection();
echo "<h1>Users</h1>";

$stmt = $db->query("SELECT * FROM users");

echo "<pre>";
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
echo "</pre>";

// initializeDatabase($db);

// header("Location: login.html");
exit;
?>