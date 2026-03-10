<?php
/**
 * File: ProfileGuard.php
 * Layer: Middleware
 * Module: Profile Completion Verification
 * System: University Web Applications System B
 *
 * Description:
 * Middleware responsible for ensuring that an authenticated user
 * has completed their basic profile information before accessing
 * certain parts of the application.
 *
 * The middleware checks whether the user has selected:
 * - University
 * - Year of study
 *
 * If the profile is incomplete, the user is redirected to the
 * profile setup page in order to complete their information.
 *
 * Responsibilities:
 * - Validate profile completion
 * - Protect pages that require a completed profile
 *
 * Security:
 * - Uses session-based authentication
 * - Queries the database using PDO prepared statements
 *
 * Database Tables Used:
 * - users
 *
 * Used By:
 * - index.php
 * - posts.php
 * - create_post.php
 *
 * Author: Pela Koniotaki
 * Date: 2026
 */

require_once __DIR__ . "/../config/database.php";

/**
 * Ensures that the currently logged-in user has completed
 * their profile (university and year of study).
 *
 * Redirects to profile_setup.php if incomplete.
 */
function requireCompleteProfile(): void
{
    // If no user session exists, stop execution
    if (!isset($_SESSION["user_id"])) {
        return;
    }
    // Admin does not require profile completion
    if ($_SESSION["role"] === "admin") {
        return;
    }

    // Connect to database
    $db = new Database();
    $conn = $db->connect();

    // Check if profile fields are filled
    $stmt = $conn->prepare("
        SELECT university, year
        FROM users
        WHERE user_id = :id
        LIMIT 1
    ");

    $stmt->execute([
        ":id" => $_SESSION["user_id"]
    ]);

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // If profile incomplete → redirect to setup page
    if (!$user || $user["university"] === null || $user["year"] === null) {

        header("Location: /University-Web-Applications-System-B/frontend/profile_setup.php");
        exit;
    }
}