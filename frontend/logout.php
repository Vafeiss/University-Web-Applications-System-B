<?php
/**
 * File: logout.php
 * Module: Session Management
 *
 * Description:
 * Destroys user session and redirects to login page.
 *
 * Security:
 * - session_unset()
 * - session_destroy()
 *
 * Author: Pelagia Koniotaki
 */
session_start();

/**
 * Clear all session variables
 */
$_SESSION = [];

/**
 * Delete session cookie (important for security)
 */
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

/**
 * Destroy the session completely
 */
session_destroy();


/**
 * Redirect to login page
 */
header("Location: /University-Web-Applications-System-B/frontend/login.php");
exit;