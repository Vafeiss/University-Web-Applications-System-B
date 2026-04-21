<?php
/**
 * File: admin.php
 * Layer: Frontend Page
 * Module: Admin Redirect
 * System: University Web Applications System B
 *
 * Description:
 * Simple redirect page verifying admin role and forwarding to admin dashboard.
 * Entry point for admin panel access with role verification.
 *
 * Security:
 * - requireAdmin() middleware enforces admin-only access
 * - Session validation before redirect
 *
 * Features:
 * - Admin role verification
 * - Secure redirect to dashboard
 *
 * Used By:
 * - Navigation links from index.php
 *
 * Author:
 * Date: 2026
 */

declare(strict_types=1);

require_once "../backend/middleware/AuthGuard.php";

requireAdmin();

header("Location: admin_dashboard.php");
exit();
