<?php
/**
 * File: admin_comment_delete_requests.php
 * Layer: Frontend Page
 * Module: Admin Comment Delete Requests
 * System: University Web Applications System B
 *
 * Description:
 * Admin view listing user-submitted comment deletion requests.
 * Admins can review the reason and approve or reject each request.
 * Protected by BanGuard and requireAdmin.
 *
 * Features:
 * - Table of pending comment delete requests
 * - Approve / Reject action buttons per row
 * - Author, reason and timestamp display
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

header("Location: admin_dashboard.php?section=commentDeleteRequests");
exit();
$cssVersion = filemtime(__DIR__ . '/css/admin_pending_posts.css');
$jsVersion = filemtime(__DIR__ . '/js/admin_comment_delete_requests.js');
?>

<!DOCTYPE html>
<html>

<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Comment Delete Requests</title>
<link rel="stylesheet" href="css/admin_pending_posts.css?v=<?php echo $cssVersion; ?>">
</head>

<body>

<main class="pending-page">
	<header class="pending-page-header">
		<h1>Comment Delete Requests</h1>
		<p>Review user requests to remove comments and decide whether they should be deleted.</p>
	</header>

	<div id="commentDeleteFeedback" class="pending-feedback" hidden></div>

	<section id="commentDeleteRequests" class="pending-grid" aria-live="polite"></section>
</main>

<script src="js/admin_comment_delete_requests.js?v=<?php echo $jsVersion; ?>"></script>

</body>

</html>
