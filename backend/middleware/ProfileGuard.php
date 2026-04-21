<?php
/**
 * File: ProfileGuard.php
 * Layer: Middleware
 * Module: Profile Completion Verification
 * System: University Web Applications System B
 *
 * Description:
 * Middleware enforcing profile completion before main app access.
 * Verifies user has set university and year of study. Redirects to
 * profile setup if incomplete. Exempts admin users.
 *
 * Functions:
 * - requireCompleteProfile() → enforces profile completion check
 *
 * Security:
 * - Session validation before profile check
 * - PDO prepared statements for database queries
 * - Admin bypass for system administrators
 * - Graceful handling of missing profile data
 *
 * Used By:
 * - index.php
 * - posts.php
 * - create_post.php
 * - profile_view.php
 * - All pages requiring complete profile
 *
 * Author: Pelagia Koniotaki
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