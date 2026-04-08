<?php
/**
 * File: register.php
 * Layer: Frontend
 * Module: User Registration
 * System: University Web Applications System B
 *
 * Description:
 * Displays the registration form and handles new user
 * account creation.
 *
 * The registration process includes:
 * - Username, email, and password input
 * - Optional referral code
 * - Policy acceptance (Terms & Conditions)
 *
 * After submission:
 * The request is processed by AuthController which:
 * - Validates user input
 * - Checks duplicate accounts
 * - Generates referral codes
 * - Applies token reward logic
 * - Hashes the user's password securely
 *
 * Business Logic:
 * - Referral code validation
 * - Token reward distribution
 *
 * Security:
 * - Password hashing handled in AuthController
 * - Output escaping using htmlspecialchars()
 *
 * Access Level:
 * - Public (non-authenticated users)
 *
 * Author: Pela Koniotaki
 * Date: 2026
 */

require_once "../backend/controllers/AuthController.php";

$message = "";

/* =========================
   HANDLE FORM SUBMISSION
========================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    /* =========================================================
       POLICY ACCEPTANCE (SRS REQUIREMENT)
       User must accept Terms & Conditions before registration
    ========================================================= */

    if (!isset($_POST["policy"])) {

        $message = "You must accept the Terms & Conditions to register.";

    } else {

        /* Create AuthController instance */
        $auth = new AuthController();

        /* Call registration method */

        $res = $auth->register(
            $_POST["username"] ?? "",
            $_POST["email"] ?? "",
            $_POST["password"] ?? "",
            $_POST["referral_code"] ?? null
        );

        /* Store result message */
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
    margin: 0 0 18px;
    text-align: center;
    font-size: 27px;
    font-weight: 800;
    color: #173665;
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

.auth-checkbox {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    margin: 4px 0 16px;
    color: #526784;
    font-size: 13px;
    line-height: 1.45;
}

.auth-checkbox input {
    margin-top: 3px;
    accent-color: #214f95;
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

.auth-links span {
    color: #5f6f89;
    font-size: 13px;
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

<!-- =========================
     USERNAME
========================= -->

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

<!-- =========================
     EMAIL
========================= -->

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

<!-- =========================
     PASSWORD
========================= -->

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

<!-- =========================
     REFERRAL CODE (OPTIONAL)
========================= -->

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

<!-- =========================
     POLICY ACCEPTANCE
========================= -->

<label class="auth-checkbox" for="policy">
<input type="checkbox" name="policy" id="policy" <?= isset($_POST["policy"]) ? "checked" : "" ?> required>
<span>I accept the Terms & Conditions</span>
</label>

<!-- =========================
     SUBMIT BUTTON
========================= -->

<button type="submit" class="auth-submit">
Create account
</button>

</form>

<div class="auth-links">
<div>
<span>Already have an account? </span>
<a href="/University-Web-Applications-System-B/frontend/login.php">Login</a>
</div>
</div>

</div>
</div>
</div>

</body>
</html>
