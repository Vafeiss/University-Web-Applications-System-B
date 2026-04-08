<?php
/**
 * File: forgot_password.php
 * Layer: Frontend
 * Module: Password Reset Request
 * System: University Web Applications System B
 *
 * Description:
 * This page allows users to request a password reset by entering
 * their registered email address.
 *
 * If the email exists in the system, a secure password reset
 * token is generated and sent via email using PHPMailer.
 *
 * The reset link allows the user to define a new password.
 *
 * Flow:
 * 1. User enters email address
 * 2. AuthController generates secure reset token
 * 3. Token stored in database with expiration time
 * 4. Email with reset link is sent to the user
 *
 * Security Measures:
 * - The system does NOT reveal whether an email exists
 * - Reset tokens are generated using cryptographically secure methods
 * - Reset tokens expire after 1 hour
 * - Output is escaped to prevent XSS
 *
 * Access Level:
 * - Public (accessible to non-authenticated users)
 *
 * Used By:
 * - AuthController::requestPasswordReset()
 *
 * Author: pela koniotaki
 * Date: 2026
 */

session_start();

/* =========================
   PREVENT ACCESS IF LOGGED IN
========================= */

if (isset($_SESSION["user_id"])) {
    header("Location: /University-Web-Applications-System-B/frontend/index.php");
    exit;
}

/* =========================
   LOAD AUTH CONTROLLER
========================= */

require_once "../backend/controllers/AuthController.php";

$message = "";
$is_ok = false;

/* =========================
   HANDLE FORM SUBMISSION
========================= */

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

<!-- Bootstrap -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

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

.auth-shell {
    min-height: 100vh;
    display: flex;
    align-items: flex-start;
    justify-content: center;
    padding: 24px 18px 32px;
}

.auth-stack {
    width: min(412px, 100%);
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 18px;
    margin-top: clamp(18px, 6vh, 52px);
}

.auth-brand {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 12px;
    text-align: center;
}

.auth-brand-mark {
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

.auth-brand-mark img {
    width: 72%;
    height: 72%;
    object-fit: contain;
}

.auth-brand h1 {
    margin: 0;
    font-size: 31px;
    font-weight: 800;
    letter-spacing: -0.02em;
    color: #173665;
}

.auth-brand p {
    margin: 0;
    max-width: 300px;
    font-size: 14px;
    line-height: 1.5;
    color: #51698f;
}

.auth-card {
    width: 100%;
    padding: 24px 24px 21px;
    border-radius: 16px;
    border: 1px solid rgba(255, 255, 255, 0.42);
    background: rgba(255, 255, 255, 0.74);
    box-shadow: 0 18px 40px rgba(23, 54, 101, 0.10);
    backdrop-filter: blur(16px);
}

.auth-card h2 {
    margin: 0 0 10px;
    text-align: center;
    font-size: 27px;
    font-weight: 800;
    color: #173665;
}

.auth-intro {
    margin: 0 0 18px;
    text-align: center;
    font-size: 14px;
    line-height: 1.5;
    color: #5b708f;
}

.auth-alert {
    margin-bottom: 18px;
    border: 1px solid #f1c0c6;
    border-radius: 12px;
    background: #fff3f5;
    color: #a12d3d;
    padding: 12px 14px;
    font-size: 14px;
    font-weight: 600;
}

.auth-alert.success {
    border-color: #b9e2cd;
    background: #f0fbf5;
    color: #1f7a49;
}

.auth-field {
    margin-bottom: 14px;
}

.auth-field label {
    display: block;
    margin-bottom: 7px;
    font-size: 13px;
    font-weight: 700;
    color: #28405f;
}

.auth-input-wrap {
    position: relative;
}

.auth-input-icon {
    position: absolute;
    left: 14px;
    top: 50%;
    width: 20px;
    height: 20px;
    transform: translateY(-50%);
    color: #4e83d8;
    pointer-events: none;
}

.auth-input {
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

.auth-input:focus {
    border-color: #5d8fe0;
    box-shadow: 0 0 0 4px rgba(93, 143, 224, 0.16);
    background: #ffffff;
}

.auth-submit {
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

.auth-submit:hover {
    transform: translateY(-1px);
    box-shadow: 0 16px 28px rgba(23, 54, 101, 0.20);
    filter: brightness(1.03);
}

.auth-links {
    margin-top: 16px;
    display: grid;
    gap: 7px;
    text-align: center;
}

.auth-links a {
    color: #3f6fc0;
    text-decoration: none;
    font-size: 13px;
    font-weight: 600;
}

.auth-links a:hover {
    text-decoration: underline;
}

@media (max-width: 520px) {
    .auth-card {
        padding: 22px 18px 19px;
    }

    .auth-brand h1 {
        font-size: 28px;
    }
}
</style>

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
