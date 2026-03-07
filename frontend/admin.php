<?php
/**
 * File: admin.php
 * Layer: Frontend
 * Module: Admin Panel
 * System: University Web Applications System B
 *
 * Description:
 * Admin-only protected page.
 * Accessible only to authenticated users with role = 'admin'.
 *
 * Protection:
 * - requireAdmin() middleware
 *
 * Access Level:
 * - Admin only
 *
 * Security:
 * - Session-based access control
 * - Role validation handled in middleware
 * - Output escaping with htmlspecialchars()
 *
 * Author: Your Name
 * Date: 2026
 */

declare(strict_types=1);

// Load RBAC middleware
require_once "../backend/middleware/AuthGuard.php";

// This will:
// 1. Check if user is logged in
// 2. Check if role === 'admin'
// 3. Redirect if unauthorized
requireAdmin();
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Admin Panel</title>
</head>
<body>

<h1>Admin Panel</h1>

<p>
    Welcome <?= htmlspecialchars($_SESSION["username"]) ?> (Admin)
</p>

<a href="index.php">Back to Dashboard</a>

</body>
</html>