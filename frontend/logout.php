<?php
/**
 * File: logout.php
 * Layer: Frontend
 * Module: Session Management
 * System: University Web Applications System B
 *
 * Description:
 * Terminates the current user session and logs the user out
 * of the application.
 *
 * The script performs a complete session cleanup by:
 * - Removing all session variables
 * - Deleting the session cookie
 * - Destroying the session
 *
 * After the logout process is completed, the user is redirected
 * to the login page.
 *
 * Security Measures:
 * - Prevents session reuse
 * - Removes session cookie from the browser
 * - Destroys session data stored on the server
 *
 * Access Level:
 * - Authenticated users
 *
 * Author: Pela Koniotaki
 * Date: 2026
 */

session_start();

/* =========================
   CLEAR SESSION VARIABLES
========================= */

$_SESSION = [];

/* =========================
   DELETE SESSION COOKIE
========================= */

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

/* =========================
   DESTROY SESSION
========================= */

session_destroy();

/* =========================
   REDIRECT USER
========================= */

header("Location: /University-Web-Applications-System-B/frontend/login.php");
exit;