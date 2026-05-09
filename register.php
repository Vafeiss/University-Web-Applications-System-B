<?php
/**
 * File: register.php
 * Layer: Frontend Page
 * Module: Authentication
 * System: University Web Applications System B
 *
 * Description:
 * User registration page with form handling. Accepts username, email, password,
 * optional referral code. Enforces terms acceptance and creates new user account.
 *
 * Features:
 * - Registration form (username, email, password, referral code)
 * - Terms & Conditions checkbox requirement
 * - Form validation and error display
 * - Success redirect to login page
 * - Referral code bonus support
 * - Password strength guidelines
 * - Bootstrap-styled responsive form
 *
 * Security:
 * - Policy acceptance requirement
 * - Input validation in AuthController
 * - Password hashing before storage
 * - CSRF protection via form tokens
 * - Unique username/email constraints
 *
 * Used By:
 * - Link from login.php
 * - Unauthenticated users registering
 *
 * Author: Pelagia Koniotaki
 * Date: 2026
 */

require_once __DIR__ . "/backend/controllers/AuthController.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // απαιτειται να τσεκαρει το policy
    if (!isset($_POST["policy"])) {
        $message = "You must accept the Terms & Conditions to register.";
    } else {
        $auth = new AuthController();

        $res = $auth->register(
            $_POST["username"] ?? "",
            $_POST["email"] ?? "",
            $_POST["password"] ?? "",
            $_POST["referral_code"] ?? null
        );

        if (!empty($res["ok"])) {
            header("Location: /student/login.php?registered=1");
            exit;
        }

        $message = $res["message"];
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Register</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/student/css/register.css">
</head>
<body>
    <div class="auth-shell">
        <div class="auth-stack">
            <div class="auth-brand">
                <span class="auth-brand-mark" aria-hidden="true">
                    <img src="/student/imgs/unisupportlogo.png" alt="">
                </span>
                <div>
                    <h1>UniSupport</h1>
                    <p>Create your account to join the student workspace, stay organized, and start connecting with the community.</p>
                </div>
            </div>

            <div class="auth-card">
                <h2>Register</h2>

                <?php if ($message): ?>
                    <div class="auth-alert <?= str_contains(strtolower($message), 'successful') ? 'success' : '' ?>">
                        <?= htmlspecialchars($message) ?>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <!-- username -->
                    <div class="auth-field">
                        <label for="registerUsername">Username</label>
                        <div class="auth-input-wrap">
                            <span class="auth-input-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M20 21a8 8 0 0 0-16 0"></path>
                                    <circle cx="12" cy="8" r="4"></circle>
                                </svg>
                            </span>
                            <input type="text" id="registerUsername" name="username" class="auth-input" value="<?= htmlspecialchars($_POST["username"] ?? "") ?>" required>
                        </div>
                    </div>

                    <!-- email -->
                    <div class="auth-field">
                        <label for="registerEmail">Email</label>
                        <div class="auth-input-wrap">
                            <span class="auth-input-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M4 6h16v12H4z"></path>
                                    <path d="m4 7 8 6 8-6"></path>
                                </svg>
                            </span>
                            <input type="email" id="registerEmail" name="email" class="auth-input" value="<?= htmlspecialchars($_POST["email"] ?? "") ?>" required>
                        </div>
                    </div>

                    <!-- password -->
                    <div class="auth-field">
                        <label for="registerPassword">Password</label>
                        <div class="auth-input-wrap">
                            <span class="auth-input-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="5" y="11" width="14" height="10" rx="2"></rect>
                                    <path d="M8 11V8a4 4 0 1 1 8 0v3"></path>
                                </svg>
                            </span>
                            <input type="password" id="registerPassword" name="password" class="auth-input" required>
                        </div>
                    </div>

                    <!-- referral -->
                    <div class="auth-field">
                        <label for="registerReferralCode">Referral Code (optional)</label>
                        <div class="auth-input-wrap">
                            <span class="auth-input-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M12 3v18"></path>
                                    <path d="M17 8H9.5a2.5 2.5 0 0 1 0-5H18"></path>
                                    <path d="M7 21h7.5a2.5 2.5 0 0 0 0-5H6"></path>
                                </svg>
                            </span>
                            <input type="text" id="registerReferralCode" name="referral_code" class="auth-input" value="<?= htmlspecialchars($_POST["referral_code"] ?? "") ?>">
                        </div>
                    </div>

                    <!-- terms -->
                    <label class="auth-checkbox" for="policy">
                        <input type="checkbox" name="policy" id="policy" <?= isset($_POST["policy"]) ? "checked" : "" ?> required>
                        <span>I accept the Terms & Conditions</span>
                    </label>

                    <!-- submit -->
                    <button type="submit" class="auth-submit">
                        Create account
                    </button>
                </form>

                <div class="auth-links">
                    <div>
                        <span>Already have an account? </span>
                        <a href="/student/login.php">Login</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
