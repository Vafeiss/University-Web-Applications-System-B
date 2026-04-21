<?php
/**
 * File: admin_delete_requests.php
 * Layer: Frontend Page
 * Module: Admin Post Delete Requests
 * System: University Web Applications System B
 *
 * Description:
 * Admin view listing user-submitted post deletion requests.
 * Admins can review the reason and approve or reject each request.
 *
 * Features:
 * - Table of pending post delete requests
 * - Approve / Reject action buttons per row
 * - Post title, author and reason display
 * - AJAX refresh after each action
 *
 * Security:
 * - session_start() and BanGuard
 * - requireAdmin() via AuthGuard
 * - htmlspecialchars() for output escaping
 *
 * Used By:
 * - Linked from admin_dashboard.php
 *
 * Author:
 * Date: 2026
 */

session_start();
require_once "../backend/middleware/BanGuard.php";
enforceFrontendUserNotBanned();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: posts.php");
    exit();
}

header("Location: admin_dashboard.php?section=deleteRequests");
exit();
