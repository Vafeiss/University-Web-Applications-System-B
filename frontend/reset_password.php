<?php
/**
 * File: reset_password.php
 * Layer: Frontend
 * Module: Password Reset
 * System: University Web Applications System B
 *
 * Description:
 * This page allows users to reset their password using
 * a secure reset token received via email.
 *
 * The reset process works as follows:
 * 1. User clicks the reset link sent to their email
 * 2. The link contains a unique reset token
 * 3. The user submits a new password
 * 4. AuthController validates the token and updates the password
 *
 * Security Measures:
 * - Reset tokens are verified against hashed tokens stored in DB
 * - Tokens have expiration time
 * - Password is securely hashed before storage
 * - Output is escaped using htmlspecialchars()
 *
 * Access Level:
 * - Public (users accessing via email reset link)
 *
 * Used By:
 * - AuthController::resetPassword()
 *
 * Author: Pelagia Koniotaki
 * Date: 2026
 */

require_once "../backend/controllers/AuthController.php";

/* =========================
   GET TOKEN FROM URL
========================= */

$token = $_GET["token"] ?? "";

$message = "";
$is_ok = false;

/* =========================
   HANDLE FORM SUBMISSION
========================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $auth = new AuthController();

    $res = $auth->resetPassword(
        $_POST["token"] ?? "",
        $_POST["password"] ?? ""
    );

    $message = $res["message"] ?? "";
    $is_ok = !empty($res["ok"]);
}

?>
<!doctype html>
<html lang="en">

<head>

<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<title>Reset Password</title>

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

<h2 class="mb-4 text-center">Reset Password</h2>

<?php if (!empty($message)): ?>

<div class="alert <?= $is_ok ? "alert-success" : "alert-danger" ?>">
<?= htmlspecialchars($message) ?>
</div>

<?php endif; ?>

<form method="POST">

<!-- =========================
     RESET TOKEN
========================= -->

<input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

<!-- =========================
     NEW PASSWORD
========================= -->

<div class="mb-3">

<label class="form-label">New Password</label>

<input
type="password"
name="password"
class="form-control"
required
>

</div>

<!-- =========================
     SUBMIT BUTTON
========================= -->

<button type="submit" class="btn btn-primary w-100">
Reset Password
</button>

</form>

</div>
</div>
</div>

</body>
</html>