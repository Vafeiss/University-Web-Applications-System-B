<?php
// logout - κανει clear session και redirect στο login

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

header("Location: /University-Web-Applications-System-B/frontend/login.php");
exit;
