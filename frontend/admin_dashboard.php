<?php
session_start();
require_once "../backend/middleware/BanGuard.php";
enforceFrontendUserNotBanned();

require_once "../backend/config/database.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
	header("Location: posts.php");
	exit();
}

$adminUsername = (string) ($_SESSION['username'] ?? 'Admin');
$adminEmail = '';

try {
	$db = new Database();
	$conn = $db->connect();
	$stmt = $conn->prepare("SELECT email FROM users WHERE user_id = :user_id LIMIT 1");
	$stmt->execute([
		':user_id' => (int) $_SESSION['user_id']
	]);
	$adminEmail = (string) ($stmt->fetchColumn() ?: '');
} catch (Throwable $exception) {
	$adminEmail = '';
}

$adminDashboardCssVersion = filemtime(__DIR__ . '/css/admin_dashboard.css');
$postsCssVersion = filemtime(__DIR__ . '/css/post.css');
$jsVersion = filemtime(__DIR__ . '/js/admin_dashboard.js');
$i18nJsVersion = filemtime(__DIR__ . '/js/i18n.js');
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Moderation Panel</title>
    <link rel="stylesheet" href="css/post.css?v=<?php echo $postsCssVersion; ?>">
    <link rel="stylesheet" href="css/admin_dashboard.css?v=<?php echo $adminDashboardCssVersion; ?>">
</head>

<body>

<main class="pending-page feed-shell user-feed-shell">
	<div class="feed-dashboard-layout app-shell">
		<aside id="feedSidebar" class="feed-sidebar" aria-label="Admin workspace navigation">
			<div class="feed-sidebar-brand">
				<span class="feed-sidebar-brand-mark" aria-hidden="true">
					<img src="imgs/unisupportlogo.png" alt="" class="feed-sidebar-brand-image">
				</span>
				<div>
					<strong>UniSupport</strong>
					<span data-i18n="admin.moderation_workspace">Moderation workspace</span>
				</div>
				<a href="logout.php" class="feed-sidebar-logout" aria-label="Logout" title="Logout">
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
						<path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/>
						<path d="M10 17l-5-5 5-5"/>
						<path d="M5 12h12"/>
					</svg>
				</a>
			</div>

			<div class="feed-sidebar-profile">
				<div class="feed-sidebar-avatar" aria-hidden="true">
					<?= htmlspecialchars(function_exists('mb_substr') ? mb_strtoupper(mb_substr($adminUsername, 0, 1)) : strtoupper(substr($adminUsername, 0, 1))) ?>
				</div>
				<div class="feed-sidebar-profile-copy">
					<span class="feed-sidebar-kicker" data-i18n="admin.signed_in_as">Signed in as</span>
					<strong><?= htmlspecialchars($adminUsername, ENT_QUOTES, 'UTF-8') ?></strong>
				</div>
			</div>

			<nav class="feed-tabs feed-sidebar-tabs" aria-label="Admin moderation sections">
				<button type="button" class="feed-tab is-active" data-section="posts" data-i18n="admin.posts">Posts</button>
				<button type="button" class="feed-tab" data-section="pending" data-i18n="admin.pending_posts">Pending Posts</button>
				<button type="button" class="feed-tab" data-section="deleteRequests" data-i18n="admin.post_delete_requests">Post Delete Requests</button>
				<button type="button" class="feed-tab" data-section="commentDeleteRequests" data-i18n="admin.comment_delete_requests">Comment Delete Requests</button>
				<button type="button" class="feed-tab" data-section="categoryRequests" data-i18n="admin.category_requests">Category Requests</button>
				<button type="button" class="feed-tab" data-section="reports" data-i18n="admin.reports">Reports</button>
			</nav>
		</aside>

		<div class="app-main-shell">
			<div class="feed-dashboard-topbar" aria-label="Admin quick actions">
				<button type="button" id="feedSidebarToggle" class="feed-sidebar-toggle" aria-controls="feedSidebar" aria-expanded="true" aria-label="Hide side menu" title="Hide side menu" data-i18n-aria-label="common.hide_menu" data-i18n-title="common.hide_menu">
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" aria-hidden="true">
						<path d="M4 7h16"></path>
						<path d="M4 12h16"></path>
						<path d="M4 17h16"></path>
					</svg>
					<span class="feed-sidebar-toggle-label" data-i18n="common.hide_menu">Hide menu</span>
				</button>
				<div id="adminDashboardTitle" class="feed-topbar-title" data-i18n="admin.posts_title">Admin Posts</div>

				<div class="feed-header-actions app-topbar-actions">
					<div class="language-switcher" data-language-switcher aria-label="Language switcher" data-i18n-aria-label="common.language_switcher">
						<button type="button" class="language-switcher-btn is-active" data-language="en" aria-pressed="true">EN</button>
						<button type="button" class="language-switcher-btn" data-language="el" aria-pressed="false">EL</button>
					</div>

					<button type="button" id="infoToggleBtn" class="info-fab" aria-label="Open project information" aria-expanded="false" aria-controls="infoDialog" data-i18n-aria-label="posts.info_button_label">
						<span aria-hidden="true">i</span>
					</button>

					<button type="button" id="adminProfileOpenTop" class="feed-dashboard-toplink" data-i18n="admin.view_profile">View profile</button>

					<div class="notifications-wrap">
						<button type="button" id="adminNotificationsBtn" class="notifications-btn" aria-label="Open notifications" aria-haspopup="true" aria-expanded="false" data-i18n-aria-label="common.notifications">
							<svg class="notifications-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" width="20" height="20" aria-hidden="true" focusable="false">
								<path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082A23.848 23.848 0 0112 17.25c-1.013 0-2.006-.063-2.857-.168M12 3a6 6 0 00-6 6v3.586l-.707.707A1 1 0 006 15h12a1 1 0 00.707-1.707L18 12.586V9a6 6 0 00-6-6zM15 19a3 3 0 11-6 0"/>
							</svg>
							<span id="adminNotificationsCount" class="notifications-count" hidden>0</span>
						</button>

						<div id="adminNotificationsDropdown" class="notifications-dropdown" hidden>
							<div class="notifications-header">
								<span data-i18n="common.notifications">Notifications</span>
								<button type="button" id="adminDeleteReadNotifications" class="notifications-mark-all" data-i18n="common.delete_all_read">Delete all read</button>
							</div>
							<div id="adminNotificationsList" class="notifications-list">
								<div class="notifications-empty" data-i18n="common.no_notifications">No notifications yet.</div>
							</div>
						</div>
					</div>
				</div>
			</div>

			<section class="feed-main-section">
	<section id="dashboardSection-posts" class="dashboard-panel is-active" data-section-panel="posts">
		<form id="adminPostsSearchForm" class="dashboard-search-panel">
			<div class="dashboard-search-topbar">
				<label for="adminSearchKeyword" class="dashboard-search-keyword-wrap">
					<input type="text" id="adminSearchKeyword" class="dashboard-search-input" placeholder="Search by title, category, or author" data-i18n-placeholder="admin.search_placeholder">
				</label>

				<button type="button" id="adminSearchFiltersToggle" class="dashboard-search-filters-toggle" aria-label="Toggle search filters" aria-expanded="false" aria-controls="adminSearchAdvanced" data-i18n-aria-label="admin.toggle_search_filters">
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" aria-hidden="true">
						<path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M7 12h10M10 17h4"/>
						<circle cx="9" cy="7" r="1.5" fill="currentColor" stroke="none"/>
						<circle cx="15" cy="12" r="1.5" fill="currentColor" stroke="none"/>
						<circle cx="12" cy="17" r="1.5" fill="currentColor" stroke="none"/>
					</svg>
				</button>

				<div class="dashboard-search-actions">
					<button type="submit" class="dashboard-search-btn primary" data-i18n="common.search">Search</button>
					<button type="button" id="adminSearchClear" class="dashboard-search-btn secondary" data-i18n="common.clear">Clear</button>
				</div>
			</div>

			<div id="adminSearchAdvanced" class="dashboard-search-advanced" hidden>
				<div class="dashboard-search-inline-filters">
					<select id="adminSearchCategory" class="dashboard-search-select">
						<option value="" data-i18n="common.all_categories">All categories</option>
					</select>

					<select id="adminSearchSort" class="dashboard-search-select">
						<option value="newest" data-i18n="common.newest_first">Newest first</option>
						<option value="oldest" data-i18n="common.oldest_first">Oldest first</option>
						<option value="title_asc" data-i18n="common.title_asc">Title A-Z</option>
						<option value="title_desc" data-i18n="common.title_desc">Title Z-A</option>
					</select>

					<input type="date" id="adminSearchFrom" class="dashboard-search-date" aria-label="Search from date" data-i18n-aria-label="common.search_from_date">
					<input type="date" id="adminSearchTo" class="dashboard-search-date" aria-label="Search to date" data-i18n-aria-label="common.search_to_date">

					<div class="dashboard-search-users" id="adminSearchUsersFilter">
						<button type="button" id="adminSearchUsersToggle" class="dashboard-search-users-toggle" aria-haspopup="true" aria-expanded="false">
							<span id="adminSearchUsersLabel" data-i18n="admin.users">Users</span>
						</button>
						<div id="adminSearchUsersMenu" class="dashboard-search-users-menu" hidden>
							<label class="dashboard-search-users-option">
								<input type="checkbox" value="__all__" checked>
								<span data-i18n="admin.all_users">All users</span>
							</label>
							<div id="adminSearchUsersOptions"></div>
						</div>
					</div>
				</div>
			</div>
		</form>
		<div id="postsFeedback" class="pending-feedback" hidden></div>
		<div id="postsGrid" class="pending-grid" aria-live="polite"></div>
	</section>

	<section id="dashboardSection-pending" class="dashboard-panel" data-section-panel="pending">
		<header class="dashboard-panel-header">
			<p data-i18n="admin.panel_pending_desc">Review submitted posts and decide whether they should be published.</p>
		</header>
		<form id="pendingPostsSearchForm" class="dashboard-search-panel">
			<div class="dashboard-search-topbar">
				<label for="pendingSearchKeyword" class="dashboard-search-keyword-wrap">
					<input type="text" id="pendingSearchKeyword" class="dashboard-search-input" placeholder="Search by title, category, or author" data-i18n-placeholder="admin.search_placeholder">
				</label>

				<button type="button" id="pendingSearchFiltersToggle" class="dashboard-search-filters-toggle" aria-label="Toggle search filters" aria-expanded="false" aria-controls="pendingSearchAdvanced" data-i18n-aria-label="admin.toggle_search_filters">
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" aria-hidden="true">
						<path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M7 12h10M10 17h4"/>
						<circle cx="9" cy="7" r="1.5" fill="currentColor" stroke="none"/>
						<circle cx="15" cy="12" r="1.5" fill="currentColor" stroke="none"/>
						<circle cx="12" cy="17" r="1.5" fill="currentColor" stroke="none"/>
					</svg>
				</button>

				<div class="dashboard-search-actions">
					<button type="submit" class="dashboard-search-btn primary" data-i18n="common.search">Search</button>
					<button type="button" id="pendingSearchClear" class="dashboard-search-btn secondary" data-i18n="common.clear">Clear</button>
				</div>
			</div>

			<div id="pendingSearchAdvanced" class="dashboard-search-advanced" hidden>
				<div class="dashboard-search-inline-filters">
					<select id="pendingSearchCategory" class="dashboard-search-select">
						<option value="" data-i18n="common.all_categories">All categories</option>
					</select>

					<select id="pendingSearchSort" class="dashboard-search-select">
						<option value="newest" data-i18n="common.newest_first">Newest first</option>
						<option value="oldest" data-i18n="common.oldest_first">Oldest first</option>
						<option value="title_asc" data-i18n="common.title_asc">Title A-Z</option>
						<option value="title_desc" data-i18n="common.title_desc">Title Z-A</option>
					</select>

					<input type="date" id="pendingSearchFrom" class="dashboard-search-date" aria-label="Search from date" data-i18n-aria-label="common.search_from_date">
					<input type="date" id="pendingSearchTo" class="dashboard-search-date" aria-label="Search to date" data-i18n-aria-label="common.search_to_date">

					<div class="dashboard-search-users" id="pendingSearchUsersFilter">
						<button type="button" id="pendingSearchUsersToggle" class="dashboard-search-users-toggle" aria-haspopup="true" aria-expanded="false">
							<span id="pendingSearchUsersLabel" data-i18n="admin.users">Users</span>
						</button>
						<div id="pendingSearchUsersMenu" class="dashboard-search-users-menu" hidden>
							<label class="dashboard-search-users-option">
								<input type="checkbox" value="__all__" checked>
								<span data-i18n="admin.all_users">All users</span>
							</label>
							<div id="pendingSearchUsersOptions"></div>
						</div>
					</div>
				</div>
			</div>
		</form>
		<div class="dashboard-status-filters" aria-label="Pending posts status filters">
			<button type="button" class="dashboard-status-filter is-active" data-pending-status="0" data-i18n="admin.pending">Pending</button>
			<button type="button" class="dashboard-status-filter" data-pending-status="1" data-i18n="admin.approved">Approved</button>
			<button type="button" class="dashboard-status-filter" data-pending-status="2" data-i18n="admin.rejected">Rejected</button>
		</div>
		<div id="pendingFeedback" class="pending-feedback" hidden></div>
		<div id="pendingPosts" class="pending-grid" aria-live="polite"></div>
	</section>

	<section id="dashboardSection-deleteRequests" class="dashboard-panel" data-section-panel="deleteRequests">
		<header class="dashboard-panel-header">
			<p data-i18n="admin.panel_delete_desc">Review user deletion requests and decide whether the related posts should be removed.</p>
		</header>
		<div id="deleteRequestsFeedback" class="pending-feedback" hidden></div>
		<div id="deleteRequests" class="pending-grid" aria-live="polite"></div>
	</section>

	<section id="dashboardSection-commentDeleteRequests" class="dashboard-panel" data-section-panel="commentDeleteRequests">
		<header class="dashboard-panel-header">
			<p data-i18n="admin.panel_comment_delete_desc">Review user requests to remove comments and decide whether they should be deleted.</p>
		</header>
		<div id="commentDeleteFeedback" class="pending-feedback" hidden></div>
		<div id="commentDeleteRequests" class="pending-grid" aria-live="polite"></div>
	</section>

	<section id="dashboardSection-categoryRequests" class="dashboard-panel" data-section-panel="categoryRequests">
		<header class="dashboard-panel-header">
			<p data-i18n="admin.panel_category_desc">Review user suggestions for new categories and choose whether to create them.</p>
		</header>
		<div id="categoryRequestsFeedback" class="pending-feedback" hidden></div>
		<div id="categoryRequests" class="pending-grid" aria-live="polite"></div>
	</section>

	<section id="dashboardSection-reports" class="dashboard-panel" data-section-panel="reports">
		<header class="dashboard-panel-header">
			<p data-i18n="admin.panel_reports_desc">Review reported posts and decide whether the post should be removed.</p>
		</header>
		<div id="reportsFeedback" class="pending-feedback" hidden></div>
		<div id="reports" class="pending-grid" aria-live="polite"></div>
	</section>
			</section>
		</div>
	</div>
</main>

<div id="infoDialog" class="info-dialog" hidden>
	<div class="info-dialog-backdrop" data-info-close></div>
	<div class="info-dialog-card" role="dialog" aria-modal="true" aria-labelledby="infoDialogTitle">
		<button type="button" id="infoDialogClose" class="info-dialog-close" aria-label="Close information panel" data-i18n-aria-label="common.close">&times;</button>
		<div class="info-dialog-grid">
			<section class="info-dialog-block" aria-labelledby="infoDialogTitle">
				<span class="info-dialog-kicker" id="infoDialogTitle" data-i18n="posts.about_title">About UniSupport</span>
				<p class="info-dialog-text">
					<span data-i18n="posts.about_text">UniSupport is a student support platform for staying organized, sharing knowledge, and connecting with others in one place.</span>
				</p>
				<div class="info-dialog-brandmark">
					<img src="imgs/cut_logo.png" alt="Cyprus University of Technology" class="info-dialog-brandmark-image">
				</div>
			</section>

			<section class="info-dialog-block" aria-labelledby="infoDialogProjectTitle">
				<span class="info-dialog-kicker" id="infoDialogProjectTitle" data-i18n="posts.project_info">Project Information</span>
				<p class="info-dialog-text" data-i18n="posts.project_info_text">This system was developed by Pelagia Koniotaki, Antriani Theofanous, Panteleimoni Alexandrou, Paraskevas Vafeiadis and Panagiotis Panagiwtou, third-year students of the Department of Electrical Engineering, Computer Engineering and Informatics at the Cyprus University of Technology, under the supervision of Professor Andreas S. Andreou, as part of the course 'Software Technology Project and Professional Practice'.</p>
				<p class="info-dialog-text" data-i18n="posts.project_info_location">Limassol, May 2026</p>
			</section>
		</div>
	</div>
</div>

<div id="adminProfileDialog" class="admin-profile-dialog" hidden>
	<div class="admin-profile-card" role="dialog" aria-modal="true" aria-labelledby="adminProfileTitle">
		<h3 id="adminProfileTitle" data-i18n="admin.profile_title">Admin Profile</h3>
		<p data-i18n="admin.profile_desc">Account details for the current administrator.</p>
		<div class="admin-profile-list">
			<div class="admin-profile-item">
				<span class="admin-profile-label" data-i18n="admin.username">Username</span>
				<div class="admin-profile-value"><?= htmlspecialchars($adminUsername, ENT_QUOTES, 'UTF-8') ?></div>
			</div>
			<div class="admin-profile-item">
				<span class="admin-profile-label" data-i18n="admin.email">Email</span>
				<div class="admin-profile-value"><?= htmlspecialchars($adminEmail !== '' ? $adminEmail : '-', ENT_QUOTES, 'UTF-8') ?></div>
			</div>
		</div>
		<div class="admin-profile-actions">
			<button type="button" id="adminProfileClose" class="admin-profile-close" data-i18n="common.close">Close</button>
		</div>
	</div>
</div>

<script src="js/i18n.js?v=<?php echo $i18nJsVersion; ?>"></script>
<script src="js/admin_dashboard.js?v=<?php echo $jsVersion; ?>"></script>

</body>

</html>

