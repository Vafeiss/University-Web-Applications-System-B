<?php
/**
 * File: logout.php
 * Layer: Frontend Page
 * Module: Authentication (Logout)
 * System: University Web Applications System B
 *
 * Description:
 * Ends the current user session and redirects to the login page.
 * Clears all session variables and destroys the session cookie.
 *
 * Features:
 * - Destroys PHP session
 * - Unsets all session variables
 * - Redirects to login.php
 *
 * Security:
 * - session_destroy() clears all session data
 * - Session cookie cleared on client
 *
 * Used By:
 * - Linked from main dashboard (index.php) and admin_dashboard.php
 *
 * Author: Pelagia Koniotaki
 * Date: 2026
 */

session_start();

// αδειαζουμε session
$_SESSION = [];

// σβηνουμε το cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();

    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// destroy
session_destroy();

header("Location: /student/login.php");
exit;
