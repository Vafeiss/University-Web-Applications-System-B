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
    header("Location: /University-Web-Applications-System-B/frontend/index.php");
    exit;
}

require_once "../backend/controllers/AuthController.php";

$message = "";
$is_ok = false;

// handle form
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $auth = new AuthController();

    $res = $auth->requestPasswordReset($_POST["email"] ?? "");

    $message = $res["message"] ?? "";
    $is_ok = !empty($res["ok"]);
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Forgot Password</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/University-Web-Applications-System-B/frontend/css/forgot_password.css">
</head>
<body>
    <div class="auth-shell">
        <div class="auth-stack">
            <div class="auth-brand">
                <span class="auth-brand-mark" aria-hidden="true">
                    <img src="/University-Web-Applications-System-B/frontend/imgs/unisupportlogo.png" alt="">
                </span>
                <div>
                    <h1>UniSupport</h1>
                    <p>Recover access to your student workspace with a secure password reset link.</p>
                </div>
            </div>

            <div class="auth-card">
                <h2>Forgot Password</h2>

                <p class="auth-intro">
                    Enter your email to receive a password reset link.
                </p>

                <?php if (!empty($message)): ?>
                    <div class="auth-alert <?= $is_ok ? "success" : "" ?>">
                        <?= htmlspecialchars($message) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="auth-field">
                        <label for="forgotPasswordEmail">Email</label>

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
                                required
                            >
                        </div>
                    </div>

                    <button type="submit" class="auth-submit">
                        Generate Reset Link
                    </button>
                </form>

                <div class="auth-links">
                    <div>
                        <a href="/University-Web-Applications-System-B/frontend/login.php">Back to Login</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
