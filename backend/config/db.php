<?php

declare(strict_types=1);

function createMysqlConnection(): PDO
{
    $host = getenv('SYSTEM_B_DB_HOST') ?: '127.0.0.1';
    $port = getenv('SYSTEM_B_DB_PORT') ?: '3306';
    $database = getenv('SYSTEM_B_DB_NAME') ?: 'university_web';
    $username = getenv('SYSTEM_B_DB_USER') ?: 'root';
    $password = getenv('SYSTEM_B_DB_PASS') ?: '';

    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $database);

    return new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
}

$pdo = createMysqlConnection();
