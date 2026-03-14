<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
	header("Location: posts.php");
	exit();
}

$cssVersion = filemtime(__DIR__ . '/css/admin_pending_posts.css');
$jsVersion = filemtime(__DIR__ . '/js/admin_dashboard.js');
?>

<!DOCTYPE html>
<html>

<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Moderation Panel</title>
<link rel="stylesheet" href="css/admin_pending_posts.css?v=<?php echo $cssVersion; ?>">
<style>
body {
	margin: 0;
}

.dashboard-shell {
	max-width: 1120px;
	margin: 0 auto;
	padding: 40px 16px 36px;
}

.dashboard-header {
	margin-bottom: 20px;
}

.dashboard-header h1 {
	margin: 0;
}

.dashboard-header p {
	margin: 8px 0 0;
	color: #5f6f89;
	font-size: 15px;
}

.dashboard-tabs {
	display: flex;
	flex-wrap: wrap;
	gap: 10px;
	margin-top: 18px;
}

.dashboard-tab {
	border: 1px solid #d7e1f0;
	border-radius: 999px;
	padding: 10px 16px;
	background: #ffffff;
	color: #28405f;
	font-size: 14px;
	font-weight: 700;
	cursor: pointer;
	transition: background 0.2s ease, border-color 0.2s ease, color 0.2s ease;
}

.dashboard-tab:hover {
	background: #f5f8fd;
	border-color: #c7d6ec;
}

.dashboard-tab.is-active {
	background: #163d76;
	border-color: #163d76;
	color: #ffffff;
}

.dashboard-panel {
	display: none;
	margin-top: 24px;
}

.dashboard-panel.is-active {
	display: block;
}

.dashboard-panel-header h2 {
	margin: 0;
	font-size: 28px;
	color: #142b53;
}

.dashboard-panel-header p {
	margin: 8px 0 0;
	color: #5f6f89;
	font-size: 15px;
}

.status-chip {
	display: inline-flex;
	align-items: center;
	padding: 3px 8px;
	border-radius: 999px;
	font-weight: 700;
}

.status-chip.approved {
	background: #e9f8ef;
	color: #1f6a3a;
}

.status-chip.pending {
	background: #fff5dd;
	color: #8a6200;
}

.status-chip.rejected {
	background: #fdeff0;
	color: #8b2330;
}

.post-excerpt {
	margin-top: 12px;
	color: #334155;
	line-height: 1.5;
	white-space: pre-wrap;
}
</style>
</head>

<body>

<main class="pending-page dashboard-shell">
	<header class="dashboard-header">
		<h1>Admin Moderation Panel</h1>

		<nav class="dashboard-tabs" aria-label="Admin moderation sections">
			<button type="button" class="dashboard-tab is-active" data-section="posts">Posts</button>
			<button type="button" class="dashboard-tab" data-section="pending">Pending Posts</button>
			<button type="button" class="dashboard-tab" data-section="deleteRequests">Post Delete Requests</button>
			<button type="button" class="dashboard-tab" data-section="commentDeleteRequests">Comment Delete Requests</button>
			<button type="button" class="dashboard-tab" data-section="reports">Reports</button>
		</nav>
	</header>

	<section id="dashboardSection-posts" class="dashboard-panel is-active" data-section-panel="posts">
		<header class="dashboard-panel-header">
			<h2>Posts</h2>
		</header>
		<div id="postsFeedback" class="pending-feedback" hidden></div>
		<div id="postsGrid" class="pending-grid" aria-live="polite"></div>
	</section>

	<section id="dashboardSection-pending" class="dashboard-panel" data-section-panel="pending">
		<header class="dashboard-panel-header">
			<h2>Pending Posts</h2>
			<p>Review submitted posts and decide whether they should be published.</p>
		</header>
		<div id="pendingFeedback" class="pending-feedback" hidden></div>
		<div id="pendingPosts" class="pending-grid" aria-live="polite"></div>
	</section>

	<section id="dashboardSection-deleteRequests" class="dashboard-panel" data-section-panel="deleteRequests">
		<header class="dashboard-panel-header">
			<h2>Post Delete Requests</h2>
			<p>Review user deletion requests and decide whether the related posts should be removed.</p>
		</header>
		<div id="deleteRequestsFeedback" class="pending-feedback" hidden></div>
		<div id="deleteRequests" class="pending-grid" aria-live="polite"></div>
	</section>

	<section id="dashboardSection-commentDeleteRequests" class="dashboard-panel" data-section-panel="commentDeleteRequests">
		<header class="dashboard-panel-header">
			<h2>Comment Delete Requests</h2>
			<p>Review user requests to remove comments and decide whether they should be deleted.</p>
		</header>
		<div id="commentDeleteFeedback" class="pending-feedback" hidden></div>
		<div id="commentDeleteRequests" class="pending-grid" aria-live="polite"></div>
	</section>

	<section id="dashboardSection-reports" class="dashboard-panel" data-section-panel="reports">
		<header class="dashboard-panel-header">
			<h2>Reports</h2>
			<p>Review reported posts and decide whether the post should be removed.</p>
		</header>
		<div id="reportsFeedback" class="pending-feedback" hidden></div>
		<div id="reports" class="pending-grid" aria-live="polite"></div>
	</section>
</main>

<script src="js/admin_dashboard.js?v=<?php echo $jsVersion; ?>"></script>

</body>

</html>