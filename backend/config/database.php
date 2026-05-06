<?php
/**
 * File: database.php
 * Layer: Backend Configuration
 * Module: Database Connection
 * System: University Web Applications System B
 *
 * Description:
 * Provides an object-oriented database connection using PHP PDO.
 * Establishes a secure, reusable connection to the MySQL database
 * with UTF-8 encoding and exception-based error handling.
 *
 * Functions:
 * - connect() -> returns PDO instance for database operations
 *
 * Security:
 * - PDO prepared statements prevent SQL injection
 * - Exception mode enabled (PDO::ERRMODE_EXCEPTION)
 * - Centralized credential management
 *
 * Used By:
 * - AuthController
 * - ProfileController
 * - PostController
 * - All Model classes (CategoryModel, CommentModel, etc.)
 *
 * Author: Pelagia Koniotaki
 * Date: 2026
 */

require_once __DIR__ . '/env.php';

class Database
{
    private string $host;
    private string $db_name;
    private string $username;
    private string $password;

    public function __construct()
    {
        $this->host = $_ENV['DB_HOST'] ?? 'localhost';
        $this->db_name = $_ENV['DB_NAME'] ?? 'webvaria_student';
        $this->username = $_ENV['DB_USER'] ?? 'webvaria_student';
        $this->password = $_ENV['DB_PASS'] ?? 'DL9pp[rir=f.!B*O';
    }

    public function connect(): PDO
    {
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
