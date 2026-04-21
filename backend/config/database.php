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
 * - connect() → returns PDO instance for database operations
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
//obj oriented συνδεση με τη βάση δεδομένων
class Database {
    // Database credentials
    private string $host = "localhost";
    private string $db_name = "system_b_support";
    private string $username = "root";
    private string $password = "";
    //connect method returns a PDO instance
    public function connect(): PDO {
        try {
            $conn = new PDO(
                "mysql:host={$this->host};dbname={$this->db_name};charset=utf8mb4",
                $this->username,
                $this->password
            );
            // Enable exceptions for error handling
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $conn;
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }
}