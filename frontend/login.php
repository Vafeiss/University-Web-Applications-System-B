<?php
/**
 * File: login.php
 * Layer: Frontend
 * Module: Authentication
 *
 * Description:
 * Displays login form and handles authentication requests.
 * On successful login:
 * - Regenerates session ID
 * - Stores user session data
 * - Redirects to dashboard
 *
 * Session Data Stored:
 * - user_id
 * - username
 * - role
 *
 * Security:
 * - Session regeneration (anti-session fixation)
 * - Escaped output (htmlspecialchars)
 *
 * Public Access: YES
 *
 * Author: Pelagia Koniotaki
 */
session_start();

if (isset($_SESSION["user_id"])) {
    header("Location: /University-Web-Applications-System-B/frontend/index.php");
    exit;
}

require_once "../backend/controllers/AuthController.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $auth = new AuthController();
    $res = $auth->login($_POST["username"] ?? "", $_POST["password"] ?? "");

    if ($res["ok"]) {
        // Regenerate session ID to prevent session fixation attacks
        session_regenerate_id(true);
        $_SESSION["user_id"] = $res["user"]["user_id"];
        $_SESSION["username"] = $res["user"]["username"];
        $_SESSION["role"] = $res["user"]["role"];
        header("Location: /University-Web-Applications-System-B/frontend/index.php");
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

  <style>
    body { background-color: #f5f5f5; }
  </style>
</head>
<body>

<div class="container d-flex justify-content-center align-items-center" style="min-height: 100vh;">
  <div class="card shadow-sm" style="width: 420px;">
    <div class="card-body p-4">
      <h2 class="mb-4 text-center">Login</h2>

      <?php if (!empty($message)): ?>
        <div class="alert alert-danger">
          <?= htmlspecialchars($message) ?>
        </div>
      <?php endif; ?>

      <form method="POST" action="">
        <div class="mb-3">
          <label class="form-label">Username</label>
          <input type="text" name="username" class="form-control" required>
        </div>

        <div class="mb-3">
          <label class="form-label">Password</label>
          <input type="password" name="password" class="form-control" required>
        </div>

        <button type="submit" class="btn btn-primary w-100">Login</button>
      </form>

      <div class="text-center mt-3">
        <span>No account?</span>
        <a href="/University-Web-Applications-System-B/frontend/register.php">Register</a>
      </div>
      <div class="text-center mt-2">
  <a href="/University-Web-Applications-System-B/frontend/forgot_password.php">
    Forgot password?
  </a>
</div>

    </div>
  </div>
</div>

</body>
</html>