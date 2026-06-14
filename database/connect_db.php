<?php

class DatabaseConnection
{
    private $connection;

    public function __construct()
    {
        $config = require 'config.php';
        $dsn =
            "mysql:host={$config['host']};" .
            "dbname={$config['dbname']};" .
            "charset=utf8mb4";

        try {
            $this->connection = new PDO(
                $dsn,
                $config['username'],
                $config['password'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]
            );
        } catch (PDOException $e) {
            die("Connection failed: " . $e->getMessage());
        }
    }

    public function getConnection()
    {
        return $this->connection;
    }
}
