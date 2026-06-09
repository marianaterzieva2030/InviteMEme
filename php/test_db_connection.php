// Execute this file in XAMPP to test the database connection

<?php

require_once '../database/connect_db.php';

$db = new DatabaseConnection();
$pdo = $db->getConnection();

echo "Connected successfully!";

echo exec('whoami');

?>