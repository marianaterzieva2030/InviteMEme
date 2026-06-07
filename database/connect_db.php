<?php

class DatabaseConnection
{
    private $connection;

    public function __construct()
    {
        $db_host = getenv('DB_HOST') ?: 'localhost';
        $db_name = getenv('DB_NAME') ?: 'inviteme';
        $username = getenv('DB_USER') ?: 'root';
        $user_password = getenv('DB_PASSWORD') ?: '';
        $dsn = "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4";

        try {
            $this->connection = new PDO($dsn, $username, $user_password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]
            );
        } catch(PDOException $e) {
            die("Connection failed: " . $e->getMessage());
        }
    }

    public function getConnection()
    {
        return $this->connection;
    }
}

?>