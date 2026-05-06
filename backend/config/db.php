<?php
/**
 * File: db.php
 * Layer: Backend Configuration
 * Module: Database Configuration
 * System: University Web Applications System B
 *
 * Description:
 * Legacy database connection configuration for PDO MySQL setup.
 * Provides direct connection credentials for alternative database access.
 *
 * Security:
 * - PDO prepared statements enabled
 * - Exception error mode for proper error handling
 * - UTF-8 character encoding
 *
 * Used By:
 * - Fallback/legacy code paths
 *
 * Author: Antriani Theofanous & Pelagia Koniotaki
 * Date: 2026
 */

require_once __DIR__ . '/env.php';

$host = $_ENV['DB_HOST'] ?? 'localhost';
$db   = $_ENV['DB_NAME'] ?? 'webvaria_student';
$user = $_ENV['DB_USER'] ?? 'webvaria_student';
$pass = $_ENV['DB_PASS'] ?? 'DL9pp[rir=f.!B*O';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
