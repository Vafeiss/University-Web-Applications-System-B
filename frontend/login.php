<?php
/**
 * File: login.php
 * Layer: Frontend
 * Module: Authentication
 * System: University Web Applications System B
 *
 * Description:
 * Displays the login form and processes authentication requests.
 * After successful login the system:
 * - Regenerates the session ID
 * - Stores user session data
 * - Checks whether the user's profile is completed
 * - Redirects the user to the appropriate page
 *
 * Session Data Stored:
 * - user_id
 * - username
 * - role
 *
 * Security:
 * - Session regeneration (prevents session fixation)
 * - Escaped output using htmlspecialchars()
 * - Password verification handled in AuthController
 *
 * Access Level:
 * - Public (non-authenticated users only)
 *
 * Author: pela koniotaki
 * Date: 2026
 */

session_start();

/* =========================
   REDIRECT IF ALREADY LOGGED IN
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

/* =========================
   HANDLE LOGIN REQUEST
========================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $auth = new AuthController();
    $res = $auth->login($_POST["username"] ?? "", $_POST["password"] ?? "");

    if ($res["ok"]) {

        /* Prevent session fixation attacks */
        session_regenerate_id(true);
        
        /* Store user session data */
        $_SESSION["user_id"] = $res["user"]["user_id"];
        $_SESSION["username"] = $res["user"]["username"];
        $_SESSION["role"] = $res["user"]["role"];
        // If the user is an admin, redirect to admin dashboard
    if ($res["user"]["role"] === "admin") {
      header("Location: /University-Web-Applications-System-B/frontend/admin.php");
      exit;
  }
        /* =========================
           CHECK PROFILE COMPLETION
        ========================= */

        require_once "../backend/config/database.php";

        $db = new Database();
        $conn = $db->connect();

        $stmt = $conn->prepare("
            SELECT university, year
            FROM users
            WHERE user_id = :id
        ");

        $stmt->execute([
            ":id" => $res["user"]["user_id"]
        ]);

        $profile = $stmt->fetch(PDO::FETCH_ASSOC);

        /* Redirect based on profile status */

        if (!$profile || $profile["university"] === null || $profile["year"] === null) {

            header("Location: /University-Web-Applications-System-B/frontend/profile_setup.php");

        } else {

            header("Location: /University-Web-Applications-System-B/frontend/index.php");

        }

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

<!-- Custom Styles -->
<link rel="stylesheet" href="/University-Web-Applications-System-B/frontend/assets/style.css">

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

<button type="submit" class="btn btn-primary w-100">
Login
</button>

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