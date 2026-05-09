<?php
/**
 * File: database.example.php
 * ----------------------------------------------------------
 * Template for database.php — copy this file as database.php
 * and fill in your own credentials before running the project.
 * The real database.php is git-ignored (never committed).
 */

class Database {
    private string $host = "localhost";
    private string $db_name = "YOUR_DATABASE_NAME";
    private string $username = "YOUR_DB_USER";
    private string $password = "YOUR_DB_PASSWORD";

    public function connect(): PDO {
        try {
            $conn = new PDO(
                "mysql:host={$this->host};dbname={$this->db_name};charset=utf8mb4",
                $this->username,
                $this->password
            );
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $conn;
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }
}
