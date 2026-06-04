<?php

class DatabaseConnection
{
    private $connection;

    public function __construct()
    {
        $db_host = "localhost";
        $db_name = "inviteme";
        $username = "root";
        $user_password = "";
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