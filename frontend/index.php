<?php
/**
 * File: index.php
 * Layer: Frontend
 * Module: User Dashboard
 *
 * Description:
 * Main dashboard page after login.
 * Displays:
 * - Welcome message
 * - Protection: requireLogin()
 * - Admin panel link (if role = admin)
 *
 * Protection:
 * - requireLogin() middleware
 *
 * Access Level:
 * - Authenticated users only
 *
 * Author: Pelagia Koniotaki
 */
/**
 * Dashboard Page
 * Accessible only by authenticated users.
 */

require_once "../backend/middleware/AuthGuard.php";
requireLogin();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Home</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <style>
    body { background-color: #f5f5f5; }
  </style>
</head>
<body>

<div class="container d-flex justify-content-center align-items-center" style="min-height: 100vh;">
  <div class="card shadow-sm" style="width: 500px;">
    <div class="card-body p-4 text-center">

      <!-- Username -->
      <h2 class="mb-2">
        Welcome, <?= htmlspecialchars($_SESSION["username"]) ?>!
      </h2>

      <!-- Role display -->
      <p class="text-muted mb-4">
        Your role: <strong><?= htmlspecialchars($_SESSION["role"]) ?></strong>
      </p>

      <!-- Logout button -->
      <a class="btn btn-outline-danger"
         href="/University-Web-Applications-System-B/frontend/logout.php">
         Logout
      </a>

      <!-- Admin-only section -->
      <?php if ($_SESSION["role"] === "admin"): ?>
        <div class="mt-3">
          <a href="admin.php" class="btn btn-dark">
            Go to Admin Panel
          </a>
        </div>
      <?php endif; ?>

    </div>
  </div>
</div>

</body>
</html>