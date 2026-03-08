<?php
/**
 * File: database.php
 * Layer: Backend Configuration
 * Module: Database Connection
 * System: University Web Applications System B
 *
 * Description:
 * Provides an object-oriented database connection using PHP PDO.
 * This class is responsible for establishing a secure connection
 * to the MySQL database and returning a reusable PDO instance.
 *
 * The Database class is used across the application by controllers
 * and modules to interact with the database.
 *
 * Features:
 * - PDO-based database connection
 * - UTF-8 character encoding
 * - Exception-based error handling
 * - Centralized database configuration
 *
 * Security:
 * - PDO prepared statements are used throughout the system
 *   to prevent SQL injection.
 * - Error mode is set to PDO::ERRMODE_EXCEPTION for proper
 *   error handling and debugging.
 *
 * Database:
 * - MySQL
 * - Charset: utf8mb4
 *
 * Used By:
 * - AuthController
 * - ProfileController
 * - PostController
 * - Modules (PostModule, ProfileModule, etc.)
 *
 * Author: Pela Koniotaki
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