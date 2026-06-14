<!-- <?php
header("Location: login.html");
exit;
?> -->

<?php

require 'database/connect_db.php';

$db = (new DatabaseConnection())->getConnection();

$stmt = $db->query("SELECT * FROM users");

$rows = $stmt->fetchAll();

echo "<table border='1'>";

foreach ($rows as $row) {
    echo "<tr>";

    foreach ($row as $value) {
        echo "<td>" . htmlspecialchars($value) . "</td>";
    }

    echo "</tr>";
}

echo "</table>";