<?php
/**
 * File: index.php
 * Layer: Frontend
 * Module: User Dashboard
 * System: University Web Applications System B
 *
 * Description:
 * This page represents the main dashboard displayed after a
 * successful user login.
 *
 * It provides:
 * - Welcome message for the authenticated user
 * - Display of the user's role
 * - Logout functionality
 * - Access to the Admin Panel (only for administrators)
 *
 * Access Control:
 * - Only authenticated users can access this page
 * - Users must have completed their profile before entering
 *
 * Protection:
 * - requireLogin() middleware → ensures user is authenticated
 * - requireCompleteProfile() middleware → ensures profile is completed
 *
 * Security:
 * - Session-based authentication
 * - Output escaping using htmlspecialchars()
 *
 * Author: pela koniotaki
 * Date: 2026
 */

require_once "../backend/middleware/AuthGuard.php";
require_once "../backend/middleware/ProfileGuard.php";

/* =========================
   ACCESS CONTROL
========================= */

requireLogin();
requireCompleteProfile(); // Ensures profile is completed before accessing dashboard

?>
<!doctype html>
<html lang="en">

<head>

<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<title>Home</title>

<!-- Bootstrap -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<!-- Custom CSS -->
<link rel="stylesheet" href="/University-Web-Applications-System-B/frontend/assets/style.css">

</head>

<body>

<div class="container auth-container">

<div class="card shadow-sm" style="width:500px">

<div class="card-body p-4 text-center">

<!-- =========================
     USERNAME
========================= -->

<h2 class="mb-2">
Welcome, <?= htmlspecialchars($_SESSION["username"]) ?>!
</h2>

<!-- =========================
     ROLE DISPLAY
========================= -->

<p class="text-muted mb-4">
Your role: <strong><?= htmlspecialchars($_SESSION["role"]) ?></strong>
</p>

<!-- =========================
     LOGOUT BUTTON
========================= -->

<a class="btn btn-outline-danger"
   href="/University-Web-Applications-System-B/frontend/logout.php">
Logout
</a>

<!-- =========================
     ADMIN PANEL
========================= -->

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