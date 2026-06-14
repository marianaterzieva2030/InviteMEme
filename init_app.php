<?php

function initializeDatabase(PDO $db): void
{
    $stmt = $db->query("
        SELECT COUNT(*)
        FROM information_schema.tables
        WHERE table_schema = DATABASE()
        AND table_name = 'users'
    ");

    if ($stmt->fetchColumn()) {
        return;
    }

    $schema = file_get_contents('database/init_db.sql');

    $db->exec($schema);

    $seed = file_get_contents('database/sample_data.sql');

    $db->exec($seed);

    echo "Добавени са начални данни.<br>";
}
?>