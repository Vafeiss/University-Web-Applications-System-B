<?php
/**
 * File: index.php
 * Layer: Frontend Page
 * Module: User Dashboard
 * System: University Web Applications System B
 *
 * Description:
 * Main dashboard displayed after successful login. Shows user welcome,
 * token balance, role display, and navigation to main features.
 * Entry point for authenticated users.
 *
 * Features:
 * - User welcome and profile summary
 * - Token balance display
 * - Admin panel link (admin-only)
 * - Navigation to posts, create post, profile, notifications
 * - Daily download notice for token rewards
 * - Logout functionality
 *
 * Security:
 * - requireLogin() enforces authentication
 * - requireCompleteProfile() enforces profile completion
 * - htmlspecialchars() for output escaping
 * - Session-based user identification
 *
 * Used By:
 * - Initial redirect after login
 * - Main app entry point
 *
 * Author: Pelagia Koniotaki & Antriani Theofanous
 * Date: 2026
 */

require_once "../backend/middleware/AuthGuard.php";
require_once "../backend/middleware/ProfileGuard.php";
require_once "../backend/config/database.php";
require_once "../backend/config/app.php";

/* =========================
   ACCESS CONTROL
========================= */

requireLogin();
requireCompleteProfile(); // Ensures profile is completed before accessing dashboard

$db = new Database();
$conn = $db->connect();

$tokenBalanceStmt = $conn->prepare(
    "SELECT token_balance FROM users WHERE user_id = :id LIMIT 1"
);
$tokenBalanceStmt->execute([":id" => $_SESSION["user_id"]]);
$tokenBalance = (int) ($tokenBalanceStmt->fetchColumn() ?: 0);

$indexCssVersion = filemtime(__DIR__ . '/css/index.css');

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
<link rel="stylesheet" href="<?php echo app_frontend_url('assets/style.css'); ?>">
<link rel="stylesheet" href="<?php echo app_frontend_url('css/index.css'); ?>?v=<?php echo $indexCssVersion; ?>">

</head>

<body>

<!-- Top Menu Bar -->
<div class="d-flex justify-content-end align-items-center gap-2 pt-4 pe-4 dashboard-top-menu">
     <a href="<?php echo app_frontend_url('profile_view.php'); ?>" class="btn btn-link">View &amp; Edit profile</a>
     <a href="<?php echo app_frontend_url('edit_interests.php'); ?>" class="btn btn-link">Edit interests</a>
     <a href="<?php echo app_frontend_url('category_request.php'); ?>" class="btn btn-link">Request category</a>
     <a href="<?php echo app_frontend_url('ads_user.php'); ?>" class="btn btn-warning fw-bold">Watch Ads</a>
     <span class="btn btn-outline-secondary disabled dashboard-token-badge">Tokens <strong><?= $tokenBalance ?></strong></span>
</div>

<div class="container auth-container">

<div class="card shadow-sm dashboard-card">

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
   href="<?php echo app_frontend_url('logout.php'); ?>">
Logout
</a>

<!-- =========================
     ADMIN PANEL
========================= -->

<?php if ($_SESSION["role"] === "admin"): ?>

<div class="mt-3">

<a href="admin_dashboard.php" class="btn btn-dark">
Go to Admin Panel
</a>

</div>

<?php endif; ?>

</div>
</div>
</div>

</body>
</html>
