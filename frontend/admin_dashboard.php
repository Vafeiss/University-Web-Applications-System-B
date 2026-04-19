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
	margin: 16px 0 22px;
	padding: 16px 18px 18px;
	border: 1px solid rgba(214, 225, 242, 0.9);
	border-radius: 24px;
	background: linear-gradient(180deg, rgba(255, 255, 255, 0.9) 0%, rgba(248, 251, 255, 0.86) 100%);
	box-shadow:
		0 14px 32px rgba(15, 23, 42, 0.06),
		inset 0 1px 0 rgba(255, 255, 255, 0.65);
	backdrop-filter: blur(10px);
}

.dashboard-search-input,
.dashboard-search-select,
.dashboard-search-date {
	margin: 0;
	border: 1px solid #d9e2ef;
	border-radius: 14px;
	background: rgba(255, 255, 255, 0.94);
	font-size: 14px;
	padding: 0 14px;
	height: 46px;
	line-height: 46px;
	box-sizing: border-box;
	transition: border-color 0.22s ease, box-shadow 0.22s ease, background 0.22s ease, transform 0.22s ease;
}

.dashboard-search-input {
	flex: 1 1 165px;
	min-width: 138px;
	padding-left: 54px;
	border: 1px solid #d9e2ef;
	border-radius: 14px;
	background: rgba(255, 255, 255, 0.95);
	transition: all 0.25s ease;
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
	height: 46px;
	box-sizing: border-box;
}

.dashboard-search-users-toggle {
	width: 100%;
	display: inline-flex;
	align-items: center;
	justify-content: space-between;
	gap: 8px;
	padding: 0 14px;
	height: 46px;
	box-sizing: border-box;
	border: 1px solid #d9e2ef;
	border-radius: 14px;
	background: rgba(255, 255, 255, 0.94);
	color: #28405f;
	font-size: 14px;
	font-weight: 600;
	cursor: pointer;
	transition: border-color 0.22s ease, box-shadow 0.22s ease, background 0.22s ease, transform 0.22s ease;
}

.dashboard-search-users-toggle::after {
	content: "▾";
	color: #62708a;
	font-size: 12px;
}

.dashboard-search-users-menu {
	position: absolute;
	top: calc(100% + 12px);
	left: 0;
	min-width: 220px;
	max-height: 240px;
	overflow-y: auto;
	padding: 10px;
	border: 1px solid rgba(214, 225, 242, 0.95);
	border-radius: 18px;
	background: rgba(255, 255, 255, 0.96);
	box-shadow: 0 16px 34px rgba(15, 23, 42, 0.12);
	backdrop-filter: blur(10px);
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
	gap: 10px;
	margin-left: auto;
	flex-shrink: 0;
}

.dashboard-search-btn {
	width: auto;
	padding: 0 20px;
	height: 46px;
	box-sizing: border-box;
	border-radius: 14px;
	font-size: 14px;
	font-weight: 600;
	letter-spacing: 0.3px;
	border: 1px solid transparent;
	cursor: pointer;
	transition: transform 0.2s ease, box-shadow 0.2s ease, background 0.2s ease, border-color 0.2s ease;
}

.dashboard-search-btn.primary {
	background: linear-gradient(180deg, #355aa8 0%, #234784 100%);
	color: #ffffff;
	box-shadow: 0 10px 22px rgba(35, 71, 132, 0.18);
}

.dashboard-search-btn.secondary {
	background: rgba(241, 245, 250, 0.95);
	color: #4f5f78;
	border-color: #e1e8f2;
}

.dashboard-search-btn.primary:hover {
	background: linear-gradient(180deg, #2f529b 0%, #1f3e73 100%);
	transform: translateY(-1px);
	box-shadow: 0 6px 14px rgba(0, 0, 0, 0.08);
}

.dashboard-search-btn.secondary:hover {
	background: #e8eef6;
	transform: translateY(-1px);
	box-shadow: 0 6px 14px rgba(0, 0, 0, 0.08);
}

.dashboard-search-topbar {
	display: flex;
	align-items: center;
	gap: 14px;
	width: 100%;
	min-width: 0;
}

.dashboard-search-keyword-wrap {
	position: relative;
	flex: 1 1 auto;
	min-width: 260px;
}

.dashboard-search-keyword-wrap::before {
	content: "";
	position: absolute;
	left: 20px;
	top: 50%;
	width: 20px;
	height: 20px;
	transform: translateY(-50%);
	background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2350679d' stroke-width='2.2'%3E%3Ccircle cx='11' cy='11' r='7.25'/%3E%3Cpath stroke-linecap='round' d='m20 20-4.2-4.2'/%3E%3C/svg%3E");
	background-repeat: no-repeat;
	background-size: 100% 100%;
	pointer-events: none;
}

.dashboard-search-topbar .dashboard-search-keyword-wrap:focus-within {
	transform: translateY(-1px);
}

.dashboard-search-keyword-wrap:hover {
	transform: translateY(-1px);
}

.dashboard-search-select:hover,
.dashboard-search-date:hover {
	border-color: #b7cae8;
}

.dashboard-search-select:focus,
.dashboard-search-date:focus {
	border-color: #4c5bd4;
	box-shadow: 0 0 0 3px rgba(76, 91, 212, 0.12);
}

.dashboard-search-input:focus {
	border-color: #4c5bd4;
	box-shadow: 0 0 0 3px rgba(76, 91, 212, 0.15);
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
	color: #475569;
	font-size: 14px;
	font-weight: 500;
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

.admin-profile-dialog {
	position: fixed;
	inset: 0;
	z-index: 60;
	display: flex;
	align-items: center;
	justify-content: center;
	padding: 16px;
	background: rgba(15, 23, 42, 0.32);
}

.admin-profile-dialog[hidden] {
	display: none !important;
}

.admin-profile-card {
	width: min(360px, 100%);
	background: #ffffff;
	border: 1px solid #d7e1f0;
	border-radius: 14px;
	padding: 18px;
	box-shadow: 0 14px 30px rgba(15, 23, 42, 0.18);
}

.admin-profile-card h3 {
	margin: 0 0 6px;
	font-size: 20px;
	color: #142b53;
	text-align: center;
}

.admin-profile-card p {
	margin: 0 0 14px;
	font-size: 14px;
	color: #5f6f89;
	text-align: center;
}

.admin-profile-list {
	display: grid;
	gap: 12px;
}

.admin-profile-item {
	padding: 12px;
	border: 1px solid #dbe4f2;
	border-radius: 12px;
	background: #f8fbff;
}

.admin-profile-label {
	display: block;
	margin-bottom: 4px;
	font-size: 12px;
	font-weight: 700;
	letter-spacing: 0.02em;
	text-transform: uppercase;
	color: #6b7c99;
}

.admin-profile-value {
	font-size: 15px;
	font-weight: 600;
	color: #1f3659;
	word-break: break-word;
}

.admin-profile-actions {
	margin-top: 14px;
	display: flex;
	justify-content: center;
}

.admin-profile-close {
	border: none;
	border-radius: 8px;
	padding: 9px 14px;
	background: #eef2f8;
	color: #4f5f78;
	font-size: 13px;
	font-weight: 700;
	cursor: pointer;
}

.admin-profile-close:hover {
	background: #e4e9f1;
}
</style>
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

