<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
	header("Location: posts.php");
	exit();
}

$adminDashboardCssVersion = filemtime(__DIR__ . '/css/admin_dashboard.css');
$jsVersion = filemtime(__DIR__ . '/js/admin_dashboard.js');
?>

<!DOCTYPE html>
<html>

<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Moderation Panel</title>
<link rel="stylesheet" href="css/admin_dashboard.css?v=<?php echo $adminDashboardCssVersion; ?>">
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

.dashboard-header-top {
	display: flex;
	align-items: center;
	justify-content: space-between;
	gap: 12px;
}

.dashboard-header-actions {
	display: inline-flex;
	align-items: center;
	gap: 8px;
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
	flex-wrap: wrap;
	gap: 6px;
	margin: 16px 0 18px;
	padding: 8px;
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
	font-size: 11px;
	padding: 0 9px;
	height: 36px;
	line-height: 36px;
	box-sizing: border-box;
}

.dashboard-search-input {
	flex: 1 1 165px;
	min-width: 138px;
}

.dashboard-search-date {
	flex: 1 1 130px;
	min-width: 120px;
}

.dashboard-search-select {
	flex: 1 1 120px;
	min-width: 108px;
}

.dashboard-search-users {
	position: relative;
	flex: 1 1 122px;
	min-width: 110px;
	height: 36px;
	box-sizing: border-box;
}

.dashboard-search-users-toggle {
	width: 100%;
	display: inline-flex;
	align-items: center;
	justify-content: space-between;
	gap: 8px;
	padding: 0 9px;
	height: 36px;
	box-sizing: border-box;
	border: 1px solid #d7e1f0;
	border-radius: 9px;
	background: #fbfcfe;
	color: #28405f;
	font-size: 11px;
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
	box-shadow: 0 8px 20px rgba(15, 23, 42, 0.12);
	z-index: 40;
}

.dashboard-search-users-option {
	display: flex;
	align-items: center;
	gap: 8px;
	padding: 8px 10px;
	border-radius: 10px;
	color: #28405f;
	font-size: 14px;
	font-weight: 600;
}

.dashboard-search-users-option:hover {
	background: #f5f8fd;
}

.dashboard-search-actions {
	display: inline-flex;
	align-items: center;
	gap: 8px;
	margin-left: auto;
	flex-shrink: 0;
}

.dashboard-search-btn {
	width: auto;
	padding: 0 11px;
	height: 36px;
	box-sizing: border-box;
	border-radius: 9px;
	font-size: 11px;
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

.dashboard-search-btn.primary:hover {
	background: #183f79;
}

.dashboard-search-btn.secondary:hover {
	background: #e4e9f1;
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

.dashboard-subtitle {
	margin: 6px 0 10px;
	font-size: 18px;
	font-weight: 700;
	color: #243559;
}

.admin-category-delete-trigger {
	display: inline-flex;
	align-items: center;
	gap: 8px;
	padding: 6px 14px;
	border: 1px solid #d7e1f0;
	border-radius: 999px;
	background: #f8fbff;
	color: #28405f;
	font: inherit;
	cursor: pointer;
	transition: border-color 0.2s ease, background 0.2s ease;
	line-height: 1.2;
}

.admin-category-delete-name {
	line-height: 1.2;
	color: #2f4772;
	font-weight: 700;
}

.admin-category-delete-label {
	line-height: 1.2;
	font-size: 11px;
	font-weight: 700;
	color: #9c2f3e;
	letter-spacing: 0.01em;
}

.admin-category-delete-trigger:hover {
	background: #fff2f2;
	border-color: #e8c1c1;
}

.admin-category-delete-trigger:disabled {
	opacity: 0.6;
	cursor: wait;
}

.notifications-wrap {
	position: relative;
}

.notifications-btn {
	position: relative;
	display: inline-flex;
	align-items: center;
	justify-content: center;
	width: 36px;
	height: 36px;
	border: 1px solid #d7e1f0;
	border-radius: 10px;
	background: #ffffff;
	color: #1f3659;
	font-size: 20px;
	line-height: 1;
	cursor: pointer;
	user-select: none;
	transition: background 0.2s ease, border-color 0.2s ease;
}

.notifications-icon {
	display: block;
	width: 20px;
	height: 20px;
	flex-shrink: 0;
	stroke: currentColor;
	pointer-events: none;
}

.notifications-btn:hover {
	background: #f5f8fd;
	border-color: #c7d6ec;
}

.notifications-count {
	position: absolute;
	top: -6px;
	right: -6px;
	display: inline-flex;
	align-items: center;
	justify-content: center;
	min-width: 18px;
	height: 18px;
	padding: 0 4px;
	background: #ef4444;
	color: #ffffff;
	font-size: 11px;
	font-weight: 700;
	border-radius: 9px;
	border: 2px solid #ffffff;
}

.notifications-count[hidden] {
	display: none;
}

.notifications-dropdown {
	position: absolute;
	top: calc(100% + 8px);
	right: 0;
	width: 320px;
	max-height: 400px;
	display: flex;
	flex-direction: column;
	padding: 0;
	border: 1px solid #d7e1f0;
	border-radius: 12px;
	background: #ffffff;
	box-shadow: 0 8px 20px rgba(15, 23, 42, 0.12);
	z-index: 30;
	overflow: hidden;
}

.notifications-dropdown[hidden] {
	display: none;
}

.notifications-header {
	display: flex;
	align-items: center;
	justify-content: space-between;
	gap: 8px;
	padding: 12px 14px;
	border-bottom: 1px solid #e5ebf3;
	background: #f9fbff;
}

.notifications-header > span {
	font-size: 14px;
	font-weight: 700;
	color: #142b53;
}

.notifications-mark-all {
	padding: 4px 8px;
	border: none;
	border-radius: 6px;
	background: transparent;
	color: #3b82f6;
	font-size: 12px;
	font-weight: 600;
	cursor: pointer;
	transition: background 0.2s ease;
}

.notifications-mark-all:hover {
	background: #e0e7ff;
}

.notifications-list {
	display: flex;
	flex-direction: column;
	overflow-y: auto;
	flex: 1;
	max-height: 340px;
}

.notifications-empty {
	display: flex;
	align-items: center;
	justify-content: center;
	padding: 32px 16px;
	color: #9ca3af;
	font-size: 14px;
	text-align: center;
}

.notification-item {
	position: relative;
	padding: 12px 14px;
	padding-right: 34px;
	border-bottom: 1px solid #f0f3f8;
	background: #ffffff;
	cursor: pointer;
	transition: background 0.2s ease;
	text-align: left;
	border: none;
}

.notification-delete-btn {
	position: absolute;
	top: 10px;
	right: 12px;
	background: none;
	border: none;
	border-radius: 0;
	box-shadow: none;
	appearance: none;
	text-decoration: none;
	background: none;
	color: #b6bcc8;
	opacity: 0.55;
	font-size: 14px;
	font-weight: 500;
	line-height: 1;
	padding: 0;
	width: 16px;
	height: 16px;
	cursor: pointer;
	transition: color 0.2s ease, opacity 0.2s ease, font-weight 0.2s ease;
}

.notification-delete-btn:hover {
	color: #dc2626;
	opacity: 1;
	font-weight: 700;
	background: none;
	text-decoration: none;
}

.notification-delete-btn:focus,
.notification-delete-btn:focus-visible {
	outline: none;
	color: #dc2626;
	opacity: 1;
	font-weight: 700;
	background: none;
	text-decoration: none;
}

.notification-item:hover {
	background: #f5f8fd;
}

.notification-item:last-child {
	border-bottom: none;
}

.notification-item.unread {
	background: #f0f4ff;
	border-left: 3px solid #3b82f6;
	padding-left: 11px;
}

.notification-item.unread:hover {
	background: #e8ecff;
}

.notification-text {
	font-size: 13px;
	color: #28405f;
	line-height: 1.4;
}

.notification-time {
	display: block;
	margin-top: 4px;
	font-size: 11px;
	color: #9ca3af;
}

.feed-menu {
	position: relative;
}

.feed-menu-trigger {
	display: inline-flex;
	align-items: center;
	justify-content: center;
	width: 36px;
	height: 36px;
	border: 1px solid #d7e1f0;
	border-radius: 10px;
	background: #ffffff;
	color: #1f3659;
	font-size: 24px;
	line-height: 1;
	cursor: pointer;
	user-select: none;
	transition: background 0.2s ease, border-color 0.2s ease;
}

.feed-menu-trigger:hover {
	background: #f5f8fd;
	border-color: #c7d6ec;
}

.feed-menu summary {
	list-style: none;
}

.feed-menu summary::-webkit-details-marker {
	display: none;
}

.feed-menu-dropdown {
	position: absolute;
	top: calc(100% + 8px);
	right: 0;
	min-width: 180px;
	padding: 8px;
	border: 1px solid #d7e1f0;
	border-radius: 12px;
	background: #ffffff;
	box-shadow: 0 8px 20px rgba(15, 23, 42, 0.12);
	z-index: 30;
}

.feed-menu-item {
	width: 100%;
	display: flex;
	align-items: center;
	border: none;
	border-radius: 8px;
	background: transparent;
	color: #28405f;
	font-size: 14px;
	font-weight: 600;
	text-decoration: none;
	padding: 10px 10px;
	cursor: pointer;
}

.feed-menu-item:visited {
	color: #28405f;
}

.feed-menu-item:hover {
	background: #f5f8fd;
}

.feed-menu-item.danger {
	color: #a12d3d;
}

.feed-menu-item.danger:hover {
	background: #fdeff0;
}
</style>
</head>

<body>

<main class="pending-page dashboard-shell">
	<header class="dashboard-header">
		<div class="dashboard-header-top">
			<h1>Admin Moderation Panel</h1>
			<div class="dashboard-header-actions">
				<div class="notifications-wrap">
					<button type="button" id="adminNotificationsBtn" class="notifications-btn" aria-label="Open notifications" aria-haspopup="true" aria-expanded="false">
						<svg class="notifications-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" width="20" height="20" aria-hidden="true" focusable="false">
							<path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082A23.848 23.848 0 0112 17.25c-1.013 0-2.006-.063-2.857-.168M12 3a6 6 0 00-6 6v3.586l-.707.707A1 1 0 006 15h12a1 1 0 00.707-1.707L18 12.586V9a6 6 0 00-6-6zM15 19a3 3 0 11-6 0"/>
						</svg>
						<span id="adminNotificationsCount" class="notifications-count" hidden>0</span>
					</button>

					<div id="adminNotificationsDropdown" class="notifications-dropdown" hidden>
						<div class="notifications-header">
							<span>Notifications</span>
							<button type="button" id="adminDeleteReadNotifications" class="notifications-mark-all">Delete all read</button>
						</div>
						<div id="adminNotificationsList" class="notifications-list">
							<div class="notifications-empty">No notifications yet.</div>
						</div>
					</div>
				</div>

				<details class="feed-menu" id="adminMenu">
					<summary class="feed-menu-trigger" aria-label="Open admin menu" title="Menu">&#8942;</summary>
					<div class="feed-menu-dropdown" role="menu" aria-label="Admin quick actions">
						<a href="logout.php" class="feed-menu-item danger" role="menuitem">Logout</a>
					</div>
				</details>
			</div>
		</div>

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
