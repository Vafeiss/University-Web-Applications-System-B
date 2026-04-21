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
 * Author:Antriani Theofanous & Pelagia Koniotaki
 * Date: 2026
 */

$host = 'localhost';
$db   = 'university_web';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}