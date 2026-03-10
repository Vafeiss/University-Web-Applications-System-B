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
body { background-color: #f5f5f5; }
</style>

</head>

<body>

<div class="container d-flex justify-content-center align-items-center" style="min-height: 100vh;">

<div class="card shadow-sm" style="width: 480px;">

<div class="card-body p-4">

<h2 class="mb-2 text-center">Forgot Password</h2>

<p class="text-center text-muted mb-4">
Enter your email to receive a password reset link.
</p>

<?php if (!empty($message)): ?>

<div class="alert <?= $is_ok ? "alert-success" : "alert-danger" ?>">
<?= htmlspecialchars($message) ?>
</div>

<?php endif; ?>

<form method="POST" action="">

<div class="mb-3">

<label class="form-label">Email</label>

<input
type="email"
name="email"
class="form-control"
value="<?= htmlspecialchars($_POST["email"] ?? "") ?>"
required
>

</div>

<button type="submit" class="btn btn-primary w-100">
Generate Reset Link
</button>

</form>

<div class="text-center mt-3">

<a href="/University-Web-Applications-System-B/frontend/login.php">
Back to Login
</a>

</div>

</div>
</div>
</div>

</body>
</html>