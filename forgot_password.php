<?php
/**
 * File: forgot_password.php
 * Layer: Frontend Page
 * Module: Password Recovery
 * System: University Web Applications System B
 *
 * Description:
 * Public page where users request a password reset email.
 * Submits the email to AuthController which sends a reset link.
 *
 * Features:
 * - Email input form
 * - Calls AuthController::forgotPassword
 * - Inline success / error feedback
 * - Link back to login page
 *
 * Security:
 * - Email validation
 * - Generic success response (no user enumeration)
 * - Token-based reset link with expiry
 *
 * Used By:
 * - Linked from login.php
 *
 * Author: Pelagia Koniotaki
 * Date: 2026
 */

session_start();

// οχι για logged-in users
if (isset($_SESSION["user_id"])) {
    header("Location: /student/index.php");
    exit;
}

require_once __DIR__ . "/backend/controllers/AuthController.php";

$forgotPasswordCssVersion = filemtime(__DIR__ . '/css/forgot_password.css');
$i18nJsVersion = filemtime(__DIR__ . '/js/i18n.js');

$message = "";
$is_ok = false;
$messageKey = "";

$messageKeyMap = [
    "If the email exists, we sent you a link." => "forgot_password.message_email_sent",
    "Email setup is still missing on this project." => "forgot_password.message_email_setup_missing",
];

// handle form
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $auth = new AuthController();

    $res = $auth->requestPasswordReset($_POST["email"] ?? "");

    $message = $res["message"] ?? "";
    $is_ok = !empty($res["ok"]);
    $messageKey = $messageKeyMap[$message] ?? "";
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Forgot Password</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/student/css/forgot_password.css?v=<?php echo $forgotPasswordCssVersion; ?>">
</head>
<body>
    <div class="auth-shell">
        <div class="auth-stack">
            <div class="auth-language-switcher" data-language-switcher aria-label="Language switcher" data-i18n-aria-label="common.language_switcher">
                <button type="button" class="language-switcher-btn is-active" data-language="en" aria-pressed="true">EN</button>
                <button type="button" class="language-switcher-btn" data-language="el" aria-pressed="false">EL</button>
            </div>

            <div class="auth-brand">
                <span class="auth-brand-mark" aria-hidden="true">
                    <img src="/student/imgs/unisupportlogo.png" alt="">
                </span>
                <div>
                    <h1>UniSupport</h1>
                    <p data-i18n="forgot_password.subtitle">Recover access to your student workspace with a secure password reset link.</p>
                </div>
            </div>

            <div class="auth-card">
                <h2 data-i18n="forgot_password.title">Forgot Password</h2>

                <p class="auth-intro" data-i18n="forgot_password.intro">
                    Enter your email to receive a password reset link.
                </p>

                <?php if (!empty($message)): ?>
                    <div class="auth-alert <?= $is_ok ? "success" : "" ?>"<?php echo $messageKey !== "" ? ' data-i18n="' . htmlspecialchars($messageKey, ENT_QUOTES, 'UTF-8') . '"' : ''; ?>>
                        <?= htmlspecialchars($message) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="auth-field">
                        <label for="forgotPasswordEmail" data-i18n="forgot_password.email">Email</label>

                        <div class="auth-input-wrap">
                            <span class="auth-input-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M4 6h16v12H4z"></path>
                                    <path d="m4 7 8 6 8-6"></path>
                                </svg>
                            </span>

                            <input
                                type="email"
                                id="forgotPasswordEmail"
                                name="email"
                                class="auth-input"
                                value="<?= htmlspecialchars($_POST["email"] ?? "") ?>"
                                placeholder="Email"
                                data-i18n-placeholder="forgot_password.email"
                                required
                            >
                        </div>
                    </div>

                    <button type="submit" class="auth-submit" data-i18n="forgot_password.submit">
                        Generate Reset Link
                    </button>
                </form>

                <div class="auth-links">
                    <div>
                        <a href="/student/login.php" data-i18n="forgot_password.back_to_login">Back to Login</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="js/i18n.js?v=<?php echo $i18nJsVersion; ?>"></script>
</body>
</html>
