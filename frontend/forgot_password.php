<?php
/**
 * File: forgot_password.php
 * Module: Password Reset Request
 *
 * Description:
 * Allows user to request password reset.
 * Generates secure token and expiration time.
 *
 * Security:
 * - Does not reveal whether email exists
 * - Token expires after 1 hour
 *
 * Author: Your Name
 */
session_start();

if (isset($_SESSION["user_id"])) {
    header("Location: /University-Web-Applications-System-B/frontend/index.php");
    exit;
}

require_once "../backend/controllers/AuthController.php";

$message = "";
$is_ok = false;

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
  <style>
    body { background-color: #f5f5f5; }
  </style>
</head>
<body>

<div class="container d-flex justify-content-center align-items-center" style="min-height: 100vh;">
  <div class="card shadow-sm" style="width: 480px;">
    <div class="card-body p-4">
      <h2 class="mb-2 text-center">Forgot Password</h2>
      <p class="text-center text-muted mb-4">Enter your email to get a reset link.</p>

      <?php if (!empty($message)): ?>
        <div class="alert <?= $is_ok ? "alert-success" : "alert-danger" ?>">
          <?= $message ?>
        </div>
      <?php endif; ?>

      <form method="POST" action="">
        <div class="mb-3">
          <label class="form-label">Email</label>
          <input type="email" name="email" class="form-control"
                 value="<?= htmlspecialchars($_POST["email"] ?? "") ?>"
                 required>
        </div>

        <button type="submit" class="btn btn-primary w-100">Generate reset link</button>
      </form>

      <div class="text-center mt-3">
        <a href="/University-Web-Applications-System-B/frontend/login.php">Back to Login</a>
      </div>
    </div>
  </div>
</div>

</body>
</html>