<?php
/**
 * File: admin.php
 * Layer: Frontend
 * Module: Admin Panel
 * System: University Web Applications System B
 *
 * Description:
 * This page represents the administrative dashboard of the system.
 * It is accessible only to authenticated users with the role "admin".
 *
 * The admin panel serves as the central interface for system
 * moderation and administrative management tasks.
 *
 * Possible administrative actions include:
 * - Moderating reported posts
 * - Managing delete requests
 * - Monitoring platform activity
 * - Managing users or content
 *
 * Access Control:
 * - Only authenticated users can access this page
 * - Only users with role = 'admin' are authorized
 *
 * Protection:
 * - requireAdmin() middleware
 *
 * Security Measures:
 * - Session-based authentication
 * - Role verification handled in middleware
 * - Output escaping using htmlspecialchars()
 *
 * Author: Pela Koniotaki
 * Date: 2026
 */

declare(strict_types=1);

/* =========================
   LOAD AUTH MIDDLEWARE
========================= */

require_once "../backend/middleware/AuthGuard.php";

/* =========================
   ACCESS CONTROL
   (Login + Role check)
========================= */

requireAdmin();

?>

<!doctype html>
<html lang="en">

<head>

<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<title>Admin Panel</title>

<!-- Bootstrap -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

</head>

<body style="background-color:#f5f5f5">

<div class="container d-flex justify-content-center align-items-center" style="min-height:100vh">

<div class="card shadow-sm" style="width:520px">

<div class="card-body p-4 text-center">

<h2 class="mb-3">Admin Panel</h2>

<p class="text-muted">
Welcome <strong><?= htmlspecialchars($_SESSION["username"]) ?></strong> (Administrator)
</p>

<hr>

<!-- =========================
     ADMIN ACTIONS
========================= -->

<div class="d-grid gap-2 mt-4">

<button class="btn btn-dark">
Manage Reported Posts
</button>

<button class="btn btn-dark">
Review Delete Requests
</button>

<button class="btn btn-dark">
View System Activity
</button>

</div>

<hr class="mt-4">

<!-- =========================
     NAVIGATION
========================= -->

<a href="index.php" class="btn btn-primary mt-2">
Back to Dashboard
</a>

</div>
</div>
</div>

</body>
</html>