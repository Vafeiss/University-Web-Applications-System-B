<?php
/**
 * File: login.php
 * Layer: Frontend
 * Module: Authentication
 * System: University Web Applications System B
 *
 * Description:
 * Displays the login form and delegates authentication flow
 * decisions to AuthController.
 * After successful login the page:
 * - Regenerates the session ID
 * - Stores user session data
 * - Redirects to the target returned by AuthController
 *
 * Session Data Stored:
 * - user_id
 * - username
 * - role
 *
 * Security:
 * - Session regeneration (prevents session fixation)
 * - Escaped output using htmlspecialchars()
 * - Password verification and profile-completion checks handled in AuthController
 *
 * Access Level:
 * - Public (non-authenticated users only)
 *
 * Author: pela koniotaki
 * Date: 2026
 */

session_start();
require_once "../backend/middleware/BanGuard.php";

/* =========================
   REDIRECT IF ALREADY LOGGED IN
========================= */

if (isset($_SESSION["user_id"])) {
    if (isUserBanned((int) $_SESSION["user_id"])) {
        clearAuthenticatedSession();
        $_GET["ban_message"] = getBannedAccountMessage();
    } else {
    header("Location: /University-Web-Applications-System-B/frontend/posts.php");
    exit;
    }
}

/* =========================
   LOAD AUTH CONTROLLER
========================= */

require_once "../backend/controllers/AuthController.php";

$message = trim((string) ($_GET["ban_message"] ?? ""));
$isSuccessMessage = false;
if (isset($_GET["registered"])) {
    $message = "Registration successful. Please login.";
    $isSuccessMessage = true;
}

/* =========================
   HANDLE LOGIN REQUEST
========================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $auth = new AuthController();
    $res = $auth->login($_POST["username"] ?? "", $_POST["password"] ?? "");

    if ($res["ok"]) {

        /* Prevent session fixation attacks */
        session_regenerate_id(true);
        
        /* Store user session data */
        $_SESSION["user_id"] = $res["user"]["user_id"];
        $_SESSION["username"] = $res["user"]["username"];
        $_SESSION["role"] = $res["user"]["role"];
        header("Location: " . $res["redirect"]);
        exit;
    }

    $message = $res["message"];
}
?>
<!doctype html>
<html lang="en">

<head>

<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<title>Login</title>

<!-- Bootstrap CDN -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<!-- Custom Styles -->
<link rel="stylesheet" href="/University-Web-Applications-System-B/frontend/assets/style.css">
<style>
body {
    min-height: 100vh;
    margin: 0;
    font-family: Arial, Helvetica, sans-serif;
    background:
        radial-gradient(circle at top left, rgba(255, 255, 255, 0.72), transparent 32%),
        linear-gradient(135deg, #e9eef6 0%, #dbe4f0 42%, #cfd9e8 100%);
    color: #173665;
}

.login-shell {
    min-height: 100vh;
    display: flex;
    align-items: flex-start;
    justify-content: center;
    padding: 24px 18px 32px;
}

.login-stack {
    width: min(412px, 100%);
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 18px;
    margin-top: clamp(18px, 6vh, 52px);
}

.login-brand {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 12px;
    text-align: center;
}

.login-brand-mark {
    width: 78px;
    height: 78px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 24px;
    background: rgba(255, 255, 255, 0.68);
    border: 1px solid rgba(255, 255, 255, 0.42);
    box-shadow: 0 16px 32px rgba(23, 54, 101, 0.08);
    backdrop-filter: blur(10px);
}

.login-brand-mark img {
    width: 72%;
    height: 72%;
    object-fit: contain;
}

.login-brand h1 {
    margin: 0;
    font-size: 31px;
    font-weight: 800;
    letter-spacing: -0.02em;
    color: #173665;
}

.login-brand p {
    margin: 0;
    max-width: 300px;
    font-size: 14px;
    line-height: 1.5;
    color: #51698f;
}

.login-card {
    width: 100%;
    padding: 24px 24px 21px;
    border-radius: 16px;
    border: 1px solid rgba(255, 255, 255, 0.42);
    background: rgba(255, 255, 255, 0.74);
    box-shadow: 0 18px 40px rgba(23, 54, 101, 0.10);
    backdrop-filter: blur(16px);
}

.login-card h2 {
    margin: 0 0 18px;
    text-align: center;
    font-size: 27px;
    font-weight: 800;
    color: #173665;
}

.login-alert {
    margin-bottom: 18px;
    border: 1px solid #f1c0c6;
    border-radius: 12px;
    background: #fff3f5;
    color: #a12d3d;
    padding: 12px 14px;
    font-size: 14px;
    font-weight: 600;
}

.login-alert.success {
    border-color: #b9e2cd;
    background: #f0fbf5;
    color: #1f7a49;
}

.login-field {
    margin-bottom: 14px;
}

.login-field label {
    display: block;
    margin-bottom: 7px;
    font-size: 13px;
    font-weight: 700;
    color: #28405f;
}

.login-input-wrap {
    position: relative;
}

.login-input-icon {
    position: absolute;
    left: 14px;
    top: 50%;
    width: 20px;
    height: 20px;
    transform: translateY(-50%);
    color: #4e83d8;
    pointer-events: none;
}

.login-input {
    width: 100%;
    height: 44px;
    border: 1px solid #d0d7e2;
    border-radius: 14px;
    background: rgba(255, 255, 255, 0.9);
    padding: 0 14px 0 44px;
    font-size: 14px;
    color: #1e3760;
    outline: none;
    transition: border-color 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
}

.login-input:focus {
    border-color: #5d8fe0;
    box-shadow: 0 0 0 4px rgba(93, 143, 224, 0.16);
    background: #ffffff;
}

.login-submit {
    width: 100%;
    height: 44px;
    border: none;
    border-radius: 14px;
    background: linear-gradient(180deg, #214f95 0%, #173665 100%);
    color: #ffffff;
    font-size: 14px;
    font-weight: 700;
    box-shadow: 0 12px 24px rgba(23, 54, 101, 0.16);
    transition: transform 0.2s ease, box-shadow 0.2s ease, filter 0.2s ease;
}

.login-submit:hover {
    transform: translateY(-1px);
    box-shadow: 0 16px 28px rgba(23, 54, 101, 0.20);
    filter: brightness(1.03);
}

.login-links {
    margin-top: 16px;
    display: grid;
    gap: 7px;
    text-align: center;
}

.login-links span {
    color: #5f6f89;
    font-size: 13px;
}

.login-links a {
    color: #3f6fc0;
    text-decoration: none;
    font-size: 13px;
    font-weight: 600;
}

.login-links a:hover {
    text-decoration: underline;
}

@media (max-width: 520px) {
    .login-card {
        padding: 22px 18px 19px;
    }

    .login-brand h1 {
        font-size: 28px;
    }
}
</style>

</head>

<body>

<div class="login-shell">
    <div class="login-stack">
        <div class="login-brand">
            <span class="login-brand-mark" aria-hidden="true">
                <img src="/University-Web-Applications-System-B/frontend/imgs/unisupportlogo.png" alt="">
            </span>
            <div>
                <h1>UniSupport</h1>
                <p>Sign in to access your student workspace, stay organized, and keep up with the latest activity.</p>
            </div>
        </div>

        <div class="login-card">
            <h2>Login</h2>

            <?php if (!empty($message)): ?>
            <div class="login-alert <?= $isSuccessMessage ? 'success' : '' ?>">
                <?= htmlspecialchars($message) ?>
            </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="login-field">
                    <label for="loginUsername">Username</label>
                    <div class="login-input-wrap">
                        <span class="login-input-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M20 21a8 8 0 0 0-16 0"></path>
                                <circle cx="12" cy="8" r="4"></circle>
                            </svg>
                        </span>
                        <input type="text" id="loginUsername" name="username" class="login-input" required>
                    </div>
                </div>

                <div class="login-field">
                    <label for="loginPassword">Password</label>
                    <div class="login-input-wrap">
                        <span class="login-input-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="5" y="11" width="14" height="10" rx="2"></rect>
                                <path d="M8 11V8a4 4 0 1 1 8 0v3"></path>
                            </svg>
                        </span>
                        <input type="password" id="loginPassword" name="password" class="login-input" required>
                    </div>
                </div>

                <button type="submit" class="login-submit">Login</button>
            </form>

            <div class="login-links">
                <div>
                    <span>No account? </span>
                    <a href="/University-Web-Applications-System-B/frontend/register.php">Register</a>
                </div>
                <div>
                    <a href="/University-Web-Applications-System-B/frontend/forgot_password.php">Forgot password?</a>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>
