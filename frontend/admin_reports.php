<?php
/**
 * File: admin_reports.php
 * Layer: Frontend Page
 * Module: Admin Reports
 * System: University Web Applications System B
 *
 * Description:
 * Admin view listing user-submitted reports against posts.
 * Admins can read the report reason and approve the report
 * (removing the post) or reject it.
 *
 * Features:
 * - Table of pending reports
 * - Approve / Reject action buttons per row
 * - Reported post preview and report reason
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
 * Author: Pelagia Koniotaki
 * Date: 2026
 */

session_start();
require_once "../backend/middleware/BanGuard.php";
enforceFrontendUserNotBanned();
// Έλεγχος αν ο χρήστης είναι admin
if(!isset($_SESSION['user_id']) || $_SESSION['role']!=='admin'){
    header("Location: posts.php");
    exit();
}

header("Location: admin_dashboard.php?section=reports");
exit();
 