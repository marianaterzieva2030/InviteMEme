<?php
require_once 'database/connect_db.php';
require_once 'init_app.php';

$db = (new DatabaseConnection())->getConnection();

initializeDatabase($db);
migrateDatabase($db);

header("Location: login.html");
exit;
?>