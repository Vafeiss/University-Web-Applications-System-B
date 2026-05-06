<?php
/**
 * File: login.php
 * Layer: Frontend Page
 * Module: Authentication
 * System: University Web Applications System B
 *
 * Description:
 * Login page with form processing. Handles user authentication, session creation,
 * and login error display. Redirects logged-in users to dashboard.
 *
 * Features:
 * - Login form with username/password
 * - Ban message display if user account banned
 * - Success message from registration
 * - Form validation and error handling
 * - Session regeneration for security
 * - Automatic redirect if already logged in
 * - Support for i18n (internationalization)
 *
 * Security:
 * - Ban detection before redirect
 * - Session regeneration on successful login
 * - Input validation via AuthController
 * - Password verification
 *
 * Used By:
 * - Initial page for unauthenticated users
 * - Post-logout destination
 *
 * Author: Pelagia Koniotaki
 * Date: 2026
 */

session_start();
require_once "../backend/middleware/BanGuard.php";
require_once "../backend/config/app.php";

// redirect αν ηδη logged in
if (isset($_SESSION["user_id"])) {
    if (isUserBanned((int) $_SESSION["user_id"])) {
        clearAuthenticatedSession();
        $_GET["ban_message"] = getBannedAccountMessage();
    } else {
        header("Location: " . app_frontend_url("posts.php"));
        exit;
    }
}

require_once "../backend/controllers/AuthController.php";

$loginCssVersion = filemtime(__DIR__ . '/css/login.css');
$i18nJsVersion = filemtime(__DIR__ . '/js/i18n.js');

$message = trim((string) ($_GET["ban_message"] ?? ""));
$isSuccessMessage = false;

if (isset($_GET["registered"])) {
    $message = "Account created. Log in when you're ready.";
    $isSuccessMessage = true;
}

// handle login
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $auth = new AuthController();
    $res = $auth->login($_POST["username"] ?? "", $_POST["password"] ?? "");

    if ($res["ok"]) {
        // νεο session ID για ασφαλεια
        session_regenerate_id(true);

        $_SESSION["user_id"] = $res["user"]["user_id"];
        $_SESSION["username"] = $res["user"]["username"];
        $_SESSION["role"] = $res["user"]["role"];
        $_SESSION["show_daily_download_notice"] = true;

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

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo app_frontend_url('assets/style.css'); ?>">
    <link rel="stylesheet" href="<?php echo app_frontend_url('css/login.css'); ?>?v=<?php echo $loginCssVersion; ?>">
</head>
<body>
    <div class="login-shell">
        <div class="login-stack">
            <div class="login-language-switcher" data-language-switcher aria-label="Language switcher" data-i18n-aria-label="common.language_switcher">
                <button type="button" class="language-switcher-btn is-active" data-language="en" aria-pressed="true">EN</button>
                <button type="button" class="language-switcher-btn" data-language="el" aria-pressed="false">EL</button>
            </div>

            <div class="login-brand">
                <span class="login-brand-mark" aria-hidden="true">
                    <img src="<?php echo app_frontend_url('imgs/unisupportlogo.png'); ?>" alt="">
                </span>
                <div>
                    <h1>UniSupport</h1>
                    <p data-i18n="login.subtitle">Sign in to access your student workspace, stay organized, and keep up with the latest activity.</p>
                </div>
            </div>

            <div class="login-card">
                <h2 data-i18n="login.title">Login</h2>

                <?php if (!empty($message)): ?>
                    <div class="login-alert <?= $isSuccessMessage ? 'success' : '' ?>">
                        <?= htmlspecialchars($message) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="login-field">
                        <label for="loginUsername" data-i18n="login.username">Username</label>
                        <div class="login-input-wrap">
                            <span class="login-input-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M20 21a8 8 0 0 0-16 0"></path>
                                    <circle cx="12" cy="8" r="4"></circle>
                                </svg>
                            </span>
                            <input type="text" id="loginUsername" name="username" class="login-input" required data-i18n-placeholder="login.username" placeholder="Username">
                        </div>
                    </div>

                    <div class="login-field">
                        <label for="loginPassword" data-i18n="login.password">Password</label>
                        <div class="login-input-wrap">
                            <span class="login-input-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="5" y="11" width="14" height="10" rx="2"></rect>
                                    <path d="M8 11V8a4 4 0 1 1 8 0v3"></path>
                                </svg>
                            </span>
                            <input type="password" id="loginPassword" name="password" class="login-input" required data-i18n-placeholder="login.password" placeholder="Password">
                        </div>
                    </div>

                    <button type="submit" class="login-submit" data-i18n="login.submit">Login</button>
                </form>

                <div class="login-links">
                    <div>
                        <span data-i18n="login.no_account">No account?</span>
                        <a href="<?php echo app_frontend_url('register.php'); ?>" data-i18n="login.register">Register</a>
                    </div>
                    <div>
                        <a href="<?php echo app_frontend_url('forgot_password.php'); ?>" data-i18n="login.forgot_password">Forgot password?</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="js/i18n.js?v=<?php echo $i18nJsVersion; ?>"></script>
</body>
</html>
