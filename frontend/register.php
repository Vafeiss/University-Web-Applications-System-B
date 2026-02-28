<?php
/**
 * File: register.php
 * Layer: Frontend
 * Module: User Registration
 *
 * Description:
 * Handles new user registration.
 * Includes:
 * - Policy acceptance validation (SRS requirement)
 * - Optional referral code input
 * - Secure password hashing (via controller)
 *
 * Business Logic:
 * - Referral code validation
 * - Token reward distribution
 *
 * Public Access: YES
 *
 * Author: Your Name
 */
require_once "../backend/controllers/AuthController.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // =========================================================
    // SRS REQUIREMENT:
    // Ο χρήστης ΠΡΕΠΕΙ να αποδεχτεί τους όρους (Policy)
    // πριν ολοκληρωθεί η εγγραφή.
    // =========================================================
    if (!isset($_POST["policy"])) {

        $message = "You must accept the Terms & Conditions to register.";

    } else {

        // Δημιουργία AuthController
        $auth = new AuthController();

        // Κλήση register method
        $res = $auth->register(
          $_POST["username"] ?? "",
          $_POST["email"] ?? "",
          $_POST["password"] ?? "",
          $_POST["referral_code"] ?? null
        );

        // Μήνυμα αποτελέσματος
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

  <style>
    body { background-color: #f5f5f5; }
  </style>
</head>
<body>

<div class="container d-flex justify-content-center align-items-center" style="min-height: 100vh;">
  <div class="card shadow-sm" style="width: 480px;">
    <div class="card-body p-4">
      <h2 class="mb-4 text-center">Register</h2>

<?php if ($message): ?>
  <div class="alert <?= str_contains($message, 'successful') ? 'alert-success' : 'alert-danger' ?>">
    <?= htmlspecialchars($message) ?>
  </div>
<?php endif; ?>

      <form method="POST">
        <div class="mb-3">
          <label class="form-label">Username</label>
          <input type="text" name="username" class="form-control" required>
        </div>

        <div class="mb-3">
          <label class="form-label">Email</label>
          <input type="email" name="email" class="form-control" required>
        </div>

        <div class="mb-3">
          <label class="form-label">Password</label>
          <input type="password" name="password" class="form-control" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Referral Code (optional)</label>
          <input type="text" name="referral_code" class="form-control">
        </div>
        <!-- =========================================================
        Policy Acceptance (SRS Requirement)
        ========================================================= -->
        <div class="form-check mb-3">
          <input class="form-check-input" type="checkbox" name="policy" id="policy" required>
          <label class="form-check-label" for="policy">
             I accept the Terms & Conditions
          </label>
        </div>

        <button type="submit" class="btn btn-success w-100">
          Create account
        </button>
      </form>

      <div class="text-center mt-3">
        <span>Already have an account?</span>
        <a href="/University-Web-Applications-System-B/frontend/login.php">
          Login
        </a>
      </div>

    </div>
  </div>
</div>

</body>
</html>