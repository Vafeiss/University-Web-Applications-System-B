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

.dashboard-search-panel {
	display: flex;
	align-items: center;
	flex-wrap: nowrap;
	gap: 8px;
	margin-top: 18px;
	padding: 10px;
	border: 1px solid #d7e1f0;
	border-radius: 16px;
	background: #ffffff;
	box-shadow: 0 8px 20px rgba(15, 23, 42, 0.06);
}

.dashboard-search-input,
.dashboard-search-select,
.dashboard-search-date {
	margin: 0;
	border: 1px solid #d3ddeb;
	border-radius: 9px;
	background: #fbfcfe;
	font-size: 12px;
	padding: 8px 10px;
	min-height: 38px;
}

.dashboard-search-input {
	flex: 1 1 190px;
	min-width: 160px;
}

.dashboard-search-select,
.dashboard-search-date {
	min-width: 118px;
}

.dashboard-search-users {
	position: relative;
	min-width: 150px;
}

.dashboard-search-users-toggle {
	width: 100%;
	display: inline-flex;
	align-items: center;
	justify-content: space-between;
	gap: 8px;
	padding: 8px 10px;
	border: 1px solid #d7e1f0;
	border-radius: 9px;
	background: #fbfcfe;
	color: #28405f;
	font-size: 12px;
	font-weight: 600;
	cursor: pointer;
}

.dashboard-search-users-toggle::after {
	content: "▾";
	color: #62708a;
	font-size: 12px;
}

.dashboard-search-users-menu {
	position: absolute;
	top: calc(100% + 8px);
	left: 0;
	min-width: 220px;
	max-height: 240px;
	overflow-y: auto;
	padding: 8px;
	border: 1px solid #d7e1f0;
	border-radius: 12px;
	background: #ffffff;
	box-shadow: 0 16px 32px rgba(15, 23, 42, 0.14);
	z-index: 20;
}

.dashboard-search-users-option {
	display: flex;
	align-items: center;
	gap: 8px;
	padding: 8px 10px;
	border-radius: 10px;
	color: #28405f;
}

.dashboard-search-users-option:hover {
	background: #f5f8fd;
}

.dashboard-search-actions {
	display: inline-flex;
	align-items: center;
	gap: 8px;
}

.dashboard-search-btn {
	width: auto;
	padding: 8px 12px;
	border-radius: 9px;
	font-size: 12px;
	font-weight: 700;
	border: 0;
	cursor: pointer;
}

.dashboard-search-btn.primary {
	background: #214f95;
	color: #ffffff;
}

.dashboard-search-btn.secondary {
	background: #eef2f8;
	color: #4f5f78;
}

.dashboard-status-filters {
	display: inline-flex;
	align-items: center;
	gap: 8px;
	margin-top: 12px;
}

.dashboard-status-filter {
	border: 1px solid #d7e1f0;
	border-radius: 999px;
	padding: 8px 12px;
	background: #ffffff;
	color: #28405f;
	font-size: 12px;
	font-weight: 700;
	cursor: pointer;
}

.dashboard-status-filter.is-active {
	background: #163d76;
	border-color: #163d76;
	color: #ffffff;
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
			<button type="button" class="dashboard-tab" data-section="categoryRequests">Category Requests</button>
			<button type="button" class="dashboard-tab" data-section="reports">Reports</button>
		</nav>
	</header>

	<section id="dashboardSection-posts" class="dashboard-panel is-active" data-section-panel="posts">
		<header class="dashboard-panel-header">
			<h2>Posts</h2>
		</header>
		<form id="adminPostsSearchForm" class="dashboard-search-panel">
			<input type="text" id="adminSearchKeyword" class="dashboard-search-input" placeholder="Search posts by keyword">

			<select id="adminSearchCategory" class="dashboard-search-select">
				<option value="">All categories</option>
			</select>

			<select id="adminSearchSort" class="dashboard-search-select">
				<option value="newest">Newest first</option>
				<option value="oldest">Oldest first</option>
				<option value="title_asc">Title A-Z</option>
				<option value="title_desc">Title Z-A</option>
			</select>

			<input type="date" id="adminSearchFrom" class="dashboard-search-date" aria-label="Search from date">
			<input type="date" id="adminSearchTo" class="dashboard-search-date" aria-label="Search to date">

			<div class="dashboard-search-users" id="adminSearchUsersFilter">
				<button type="button" id="adminSearchUsersToggle" class="dashboard-search-users-toggle" aria-haspopup="true" aria-expanded="false">
					<span id="adminSearchUsersLabel">Users</span>
				</button>
				<div id="adminSearchUsersMenu" class="dashboard-search-users-menu" hidden>
					<label class="dashboard-search-users-option">
						<input type="checkbox" value="__all__" checked>
						<span>All users</span>
					</label>
					<div id="adminSearchUsersOptions"></div>
				</div>
			</div>

			<div class="dashboard-search-actions">
				<button type="submit" class="dashboard-search-btn primary">Search</button>
				<button type="button" id="adminSearchClear" class="dashboard-search-btn secondary">Clear</button>
			</div>
		</form>
		<div id="postsFeedback" class="pending-feedback" hidden></div>
		<div id="postsGrid" class="pending-grid" aria-live="polite"></div>
	</section>

	<section id="dashboardSection-pending" class="dashboard-panel" data-section-panel="pending">
		<header class="dashboard-panel-header">
			<h2>Pending Posts</h2>
			<p>Review submitted posts and decide whether they should be published.</p>
		</header>
		<form id="pendingPostsSearchForm" class="dashboard-search-panel">
			<input type="text" id="pendingSearchKeyword" class="dashboard-search-input" placeholder="Search posts by keyword">

			<select id="pendingSearchCategory" class="dashboard-search-select">
				<option value="">All categories</option>
			</select>

			<select id="pendingSearchSort" class="dashboard-search-select">
				<option value="newest">Newest first</option>
				<option value="oldest">Oldest first</option>
				<option value="title_asc">Title A-Z</option>
				<option value="title_desc">Title Z-A</option>
			</select>

			<input type="date" id="pendingSearchFrom" class="dashboard-search-date" aria-label="Search from date">
			<input type="date" id="pendingSearchTo" class="dashboard-search-date" aria-label="Search to date">

			<div class="dashboard-search-users" id="pendingSearchUsersFilter">
				<button type="button" id="pendingSearchUsersToggle" class="dashboard-search-users-toggle" aria-haspopup="true" aria-expanded="false">
					<span id="pendingSearchUsersLabel">Users</span>
				</button>
				<div id="pendingSearchUsersMenu" class="dashboard-search-users-menu" hidden>
					<label class="dashboard-search-users-option">
						<input type="checkbox" value="__all__" checked>
						<span>All users</span>
					</label>
					<div id="pendingSearchUsersOptions"></div>
				</div>
			</div>

			<div class="dashboard-search-actions">
				<button type="submit" class="dashboard-search-btn primary">Search</button>
				<button type="button" id="pendingSearchClear" class="dashboard-search-btn secondary">Clear</button>
			</div>
		</form>
		<div class="dashboard-status-filters" aria-label="Pending posts status filters">
			<button type="button" class="dashboard-status-filter is-active" data-pending-status="0">Pending</button>
			<button type="button" class="dashboard-status-filter" data-pending-status="1">Approved</button>
		</div>
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

	<section id="dashboardSection-categoryRequests" class="dashboard-panel" data-section-panel="categoryRequests">
		<header class="dashboard-panel-header">
			<h2>Category Requests</h2>
			<p>Review user suggestions for new categories and choose whether to create them.</p>
		</header>
		<div id="categoryRequestsFeedback" class="pending-feedback" hidden></div>
		<div id="categoryRequests" class="pending-grid" aria-live="polite"></div>
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
