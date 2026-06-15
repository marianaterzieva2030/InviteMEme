<?php

return [
    'host' => getenv('MARIADB_HOST') ?: 'localhost',
    'port' => getenv('MARIADB_PORT') ?: 3306,
    'dbname' => getenv('MARIADB_DATABASE') ?: 'inviteme',
    'username' => getenv('MARIADB_USER') ?: 'root',
    'password' => getenv('MARIADB_PASSWORD') ?: ''
];
?>