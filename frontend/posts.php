<?php
/**
 * File: posts.php
 * Layer: Frontend Page
 * Module: Posts Feed
 * System: University Web Applications System B
 *
 * Description:
 * Main posts feed interface with search, filtering, sorting, and notifications.
 * Displays posts from all users or filtered by interests/following with sidebar navigation.
 *
 * Features:
 * - Posts feed display (paginated, infinite scroll)
 * - Search bar with filters (keyword, category, date range, author)
 * - Sort options (newest, oldest, title A-Z, Z-A)
 * - Filter by followed users only
 * - Notifications dropdown panel
 * - Sidebar with user profile, navigation links
 * - Admin option to view rejected posts
 * - Token balance display
 * - Free daily download notice
 * - Language switcher
 *
 * Security:
 * - requireLogin() enforces authentication
 * - requireCompleteProfile() enforces profile setup
 * - Ban checking via BanGuard middleware
 *
 * Used By:
 * - Main page after login
 * - Navigation hub for all authenticated users
 *
 * Author: Pelagia Koniotaki & Antriani Theofanous 
 * Date: 2026
 */

require_once "../backend/config/database.php";
require_once "../backend/middleware/BanGuard.php";
session_start();    
enforceFrontendUserNotBanned();
/* Έλεγχος αν ο χρήστης είναι συνδεδεμένος */
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
/* Έλεγχος αν ο χρήστης είναι admin  για εμφανιση anonymous posts */
$isAdmin = ($_SESSION['role'] ?? '') === 'admin';

$db = new Database();
$conn = $db->connect();

function describeTransaction(int $tokenCharge, ?string $source = null): string {
    if ($source === 'advertisement_reward') {
        return "Advertisement reward";
    }

    if ($tokenCharge === 10) {
        return "Referral reward";
    }

    if ($tokenCharge === 1) {
        return "Approved upload reward";
    }

    if ($tokenCharge === 0) {
        return "Free daily download";
    }

    if ($tokenCharge === -1) {
        return "Download charge";
    }

    return $tokenCharge > 0 ? "Token gain" : "Token usage";
}

$tokenBalanceStmt = $conn->prepare(
    "SELECT token_balance FROM users WHERE user_id = :id LIMIT 1"
);
$tokenBalanceStmt->execute([":id" => $_SESSION['user_id']]);
$tokenBalance = (int) ($tokenBalanceStmt->fetchColumn() ?: 0);

$transactionsStmt = $conn->prepare(
    "SELECT *
     FROM (
        SELECT CONCAT('tx-', t.transaction_id) AS history_id,
               t.token_charge,
               t.timestamp,
               CASE
                   WHEN EXISTS (
                       SELECT 1
                       FROM ad_views av
                       WHERE av.user_id = t.user_id
                         AND av.viewed_at = t.timestamp
                   ) THEN 'advertisement_reward'
                   ELSE NULL
               END AS transaction_source
        FROM transactions t
        WHERE t.user_id = :id

        UNION ALL

        SELECT CONCAT('ad-', av.view_id) AS history_id,
               1 AS token_charge,
               av.viewed_at AS timestamp,
               'advertisement_reward' AS transaction_source
        FROM ad_views av
        WHERE av.user_id = :id
          AND NOT EXISTS (
              SELECT 1
              FROM transactions t
              WHERE t.user_id = av.user_id
                AND t.timestamp = av.viewed_at
                AND t.token_charge = 1
          )
     ) history_rows
     ORDER BY timestamp DESC, history_id DESC"
);
$transactionsStmt->execute([":id" => $_SESSION['user_id']]);
$transactions = $transactionsStmt->fetchAll(PDO::FETCH_ASSOC);

$hasUsedFreeDownloadTodayStmt = $conn->prepare(
    "SELECT transaction_id
     FROM transactions
     WHERE user_id = :id
     AND token_charge = 0
     AND DATE(timestamp) = CURDATE()
     LIMIT 1"
);
$hasUsedFreeDownloadTodayStmt->execute([":id" => $_SESSION['user_id']]);
$hasUsedFreeDownloadToday = (bool) $hasUsedFreeDownloadTodayStmt->fetch(PDO::FETCH_ASSOC);

$showDailyDownloadNotice = !$isAdmin
    && !empty($_SESSION['show_daily_download_notice'])
    && !$hasUsedFreeDownloadToday;
unset($_SESSION['show_daily_download_notice']);

$categoriesStmt = $conn->query(
    "SELECT category_id, name FROM categories ORDER BY name ASC"
);
$categories = $categoriesStmt->fetchAll(PDO::FETCH_ASSOC);
$success = $_GET['success'] ?? '';
if ($success === '' && isset($_SESSION['flash_success'])) {
    $success = (string)$_SESSION['flash_success'];
}
unset($_SESSION['flash_success']);

$postCssVersion = filemtime(__DIR__ . '/css/post.css');
$adminDashboardCssVersion = filemtime(__DIR__ . '/css/admin_dashboard.css');
$postsJsVersion = filemtime(__DIR__ . '/js/posts.js');
$createPostJsVersion = filemtime(__DIR__ . '/js/createPost.js');
$i18nJsVersion = filemtime(__DIR__ . '/js/i18n.js');
?>

<!DOCTYPE html>
<html>

<head>
<meta charset="UTF-8">  <!-- Ορίζει το charset σε UTF-8 για σωστή εμφάνιση χαρακτήρων -->
<meta name="viewport" content="width=device-width, initial-scale=1.0"> <!-- Κάνει τη σελίδα responsive σε κινητές συσκευές -->
<title>Posts Feed</title>   
<link rel="stylesheet" href="css/post.css?v=<?php echo $postCssVersion; ?>">
<link rel="stylesheet" href="css/admin_dashboard.css?v=<?php echo $adminDashboardCssVersion; ?>">
</head>

<body>

<main class="pending-page feed-shell<?= !$isAdmin ? ' user-feed-shell' : '' ?>">
    <?php if (!$isAdmin): ?>
    <div class="feed-dashboard-layout app-shell">
        <aside id="feedSidebar" class="feed-sidebar" aria-label="User workspace navigation">
            <div class="feed-sidebar-brand">
                <span class="feed-sidebar-brand-mark" aria-hidden="true">
                    <img src="imgs/unisupportlogo.png" alt="" class="feed-sidebar-brand-image">
                </span>
                <div>
                    <strong>UniSupport</strong>
                    <span data-i18n="posts.student_workspace">Student workspace</span>
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
                    <?= htmlspecialchars(function_exists('mb_substr') ? mb_strtoupper(mb_substr((string)$_SESSION['username'], 0, 1)) : strtoupper(substr((string)$_SESSION['username'], 0, 1))) ?>
                </div>
                <div class="feed-sidebar-profile-copy">
                    <span class="feed-sidebar-kicker" data-i18n="posts.signed_in_as">Signed in as</span>
                    <strong><?= htmlspecialchars($_SESSION['username']) ?></strong>
                </div>
            </div>

            <nav class="feed-tabs feed-sidebar-tabs" aria-label="Feed navigation">
                <button type="button" id="createPostBtn" class="feed-tab"><span aria-hidden="true">&#43; </span><span data-i18n="posts.create_post">Create Post</span></button>
                <button type="button" id="postsFeedBtn" class="feed-tab is-active" data-i18n="posts.posts">Posts</button>
                <button type="button" id="followersFeedBtn" class="feed-tab" data-i18n="posts.followers">Followers</button>
                <button type="button" id="pendingPostsBtn" class="feed-tab feed-tab-compact" data-i18n="posts.pending_posts">Pending Posts</button>
                <button type="button" id="pendingDeleteRequestsBtn" class="feed-tab feed-tab-compact" data-i18n="posts.pending_delete_requests">Pending Delete Requests</button>
                <button type="button" id="reportsBtn" class="feed-tab" data-i18n="posts.reports">Reports</button>
                <button type="button" id="tokenHistoryBtn" class="feed-tab feed-tab-compact" data-i18n="posts.token_history">Token history</button>
            </nav>
        </aside>

        <div class="app-main-shell">
            <div class="feed-dashboard-topbar" aria-label="Workspace quick actions">
                <button type="button" id="feedSidebarToggle" class="feed-sidebar-toggle" aria-controls="feedSidebar" aria-expanded="true" aria-label="Hide side menu" title="Hide side menu" data-i18n-aria-label="common.hide_menu" data-i18n-title="common.hide_menu">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" aria-hidden="true">
                        <path d="M4 7h16"></path>
                        <path d="M4 12h16"></path>
                        <path d="M4 17h16"></path>
                    </svg>
                    <span class="feed-sidebar-toggle-label" data-i18n="common.hide_menu">Hide menu</span>
                </button>
                <div id="feedTitle" class="feed-topbar-title" data-i18n="posts.posts_feed">Posts Feed</div>
                <div class="feed-dashboard-toplinks">
                    <a href="profile_view.php" class="feed-dashboard-toplink" data-i18n="posts.view_edit_profile">View &amp; Edit profile</a>
                    <a href="edit_interests.php" class="feed-dashboard-toplink" data-i18n="posts.edit_interests">Edit interests</a>
                    <a href="category_request.php" class="feed-dashboard-toplink" data-i18n="posts.request_category">Request category</a>
                    <a href="ads_user.php" class="feed-dashboard-toplink" data-i18n="posts.watch_ads">Watch Ads</a>
                </div>

                <div class="language-switcher" data-language-switcher aria-label="Language switcher" data-i18n-aria-label="common.language_switcher">
                    <button type="button" class="language-switcher-btn is-active" data-language="en" aria-pressed="true">EN</button>
                    <button type="button" class="language-switcher-btn" data-language="el" aria-pressed="false">EL</button>
                </div>

                <div class="feed-header-actions app-topbar-actions">
                    <button type="button" id="infoToggleBtn" class="info-fab" aria-label="Open project information" aria-expanded="false" aria-controls="infoDialog" data-i18n-aria-label="posts.info_button_label">
                        <span aria-hidden="true">i</span>
                    </button>

                    <div class="token-balance-badge">
                        <span class="token-balance-label" data-i18n="posts.tokens">Tokens</span>
                        <strong><?= $tokenBalance ?></strong>
                    </div>

                    <div class="notifications-wrap">
                        <button type="button" id="notificationsBtn" class="notifications-btn" aria-label="Open notifications" aria-haspopup="true" aria-expanded="false" data-i18n-aria-label="common.notifications">
                            <svg class="notifications-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" width="20" height="20" aria-hidden="true" focusable="false">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082A23.848 23.848 0 0112 17.25c-1.013 0-2.006-.063-2.857-.168M12 3a6 6 0 00-6 6v3.586l-.707.707A1 1 0 006 15h12a1 1 0 00.707-1.707L18 12.586V9a6 6 0 00-6-6zM15 19a3 3 0 11-6 0"/>
                            </svg>
                            <span id="notificationsCount" class="notifications-count" hidden>0</span>
                        </button>

                        <div id="notificationsDropdown" class="notifications-dropdown" hidden>
                            <div class="notifications-header">
                                <span data-i18n="common.notifications">Notifications</span>
                                <button type="button" id="deleteReadNotifications" class="notifications-mark-all" data-i18n="common.delete_all_read">Delete all read</button>
                            </div>

                            <div id="notificationsList" class="notifications-list">
                                <div class="notifications-empty" data-i18n="common.no_notifications">No notifications yet.</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <section class="feed-main-section">
    <?php endif; ?>

    <?php if ($isAdmin): ?>
    <header class="feed-header">
        <div class="feed-header-row">
            <h1 id="feedTitle">Posts Feed</h1>
            <div class="feed-header-actions">
                <?php if (!$isAdmin): ?>
                <div class="token-balance-badge">
                    <span class="token-balance-label">Tokens</span>
                    <strong><?= $tokenBalance ?></strong>
                </div>
                <?php endif; ?>
                <!-- προσθετει στο UI κουδουνακι, unread counter, dropdown λιστα και κουμπί διαγραφής των read notifications -->
                <div class="notifications-wrap">
                    <button type="button" id="notificationsBtn" class="notifications-btn" aria-label="Open notifications" aria-haspopup="true" aria-expanded="false" data-i18n-aria-label="common.notifications">
                        <svg class="notifications-icon" xmlns="http://www.w3.org/2000/svg"
                             fill="none"
                             viewBox="0 0 24 24"
                             stroke-width="1.8"
                             stroke="currentColor"
                             width="20"
                             height="20"
                             aria-hidden="true"
                             focusable="false">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M14.857 17.082A23.848 23.848 0 0112 17.25c-1.013 0-2.006-.063-2.857-.168M12 3a6 6 0 00-6 6v3.586l-.707.707A1 1 0 006 15h12a1 1 0 00.707-1.707L18 12.586V9a6 6 0 00-6-6zM15 19a3 3 0 11-6 0"/>
                        </svg>

    <span id="notificationsCount" class="notifications-count" hidden>0</span>
</button>

                    <div id="notificationsDropdown" class="notifications-dropdown" hidden>
                        <div class="notifications-header">
                            <span data-i18n="common.notifications">Notifications</span>
                            <button type="button" id="deleteReadNotifications" class="notifications-mark-all" data-i18n="common.delete_all_read">Delete all read</button>
                        </div>

                        <div id="notificationsList" class="notifications-list">
                            <div class="notifications-empty" data-i18n="common.no_notifications">No notifications yet.</div>
                        </div>
                    </div>
                </div>

                <?php if ($isAdmin): ?>
                <details class="feed-menu" id="feedMenu">
                    <summary class="feed-menu-trigger" aria-label="Open feed menu" title="Menu">&#8942;</summary>

                    <div class="feed-menu-dropdown" role="menu" aria-label="Feed quick actions">
                        <a href="admin_dashboard.php" class="feed-menu-item" role="menuitem">Admin panel</a>
                        <a href="logout.php" class="feed-menu-item danger" role="menuitem">Logout</a>
                    </div>
                </details>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($isAdmin): ?>
        <nav class="feed-tabs" aria-label="Feed navigation">
            <a href="create_post.php" class="feed-tab">&#43; Create Post</a>
            <button type="button" id="postsFeedBtn" class="feed-tab is-active">Posts</button>
            <button type="button" id="followersFeedBtn" class="feed-tab">Followers</button>
            <button type="button" id="pendingPostsBtn" class="feed-tab">Pending Posts</button>
            <button type="button" id="pendingDeleteRequestsBtn" class="feed-tab">Pending Delete Requests</button>
            <button type="button" id="reportsBtn" class="feed-tab">Reports</button>
            <?php if ($isAdmin): ?>
            <a href="admin_dashboard.php" class="feed-tab">&#9881; Admin Panel</a>
            <?php endif; ?>
        </nav>
        <?php endif; ?>
    </header>
    <?php endif; ?>

        <?php if ($showDailyDownloadNotice): ?>
        <div id="dailyDownloadNotice" class="daily-download-notice" role="status" aria-live="polite">
            <div class="daily-download-notice-copy">
                <strong data-i18n="posts.free_daily_title">Free daily download available</strong>
                <span data-i18n="posts.free_daily_desc">You still have one free daily download available today.</span>
            </div>
        </div>
        <?php endif; ?>

        <form id="feedSearchForm" class="feed-search-panel<?= !$isAdmin ? ' user-search-panel' : '' ?>">
            <?php if (!$isAdmin): ?>
            <div class="feed-search-topbar">
                <label for="feedSearchKeyword" class="feed-search-keyword-wrap">
                    <span class="feed-search-keyword-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="7"></circle>
                            <path d="M20 20L16.65 16.65"></path>
                        </svg>
                    </span>
                    <input type="text" id="feedSearchKeyword" class="feed-search-input" placeholder="Search posts by keyword" data-i18n-placeholder="posts.search_placeholder">
                </label>

                <button type="button" id="feedSearchFiltersToggle" class="feed-search-filters-toggle" aria-expanded="false" aria-controls="feedSearchAdvanced" aria-label="Show search filters" data-i18n-aria-label="common.filter_search">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="4" y1="6" x2="20" y2="6"></line>
                        <circle cx="9" cy="6" r="2"></circle>
                        <line x1="4" y1="12" x2="20" y2="12"></line>
                        <circle cx="15" cy="12" r="2"></circle>
                        <line x1="4" y1="18" x2="20" y2="18"></line>
                        <circle cx="11" cy="18" r="2"></circle>
                    </svg>
                </button>

                <div class="feed-search-actions user-search-actions">
                <button type="submit" class="feed-search-btn primary" data-i18n="common.search">Search</button>
                    <button type="button" id="feedSearchClear" class="feed-search-btn secondary" data-i18n="common.clear">Clear</button>
                </div>
            </div>

            <div id="feedSearchAdvanced" class="feed-search-advanced" hidden>
            <?php else: ?>
            <input type="text" id="feedSearchKeyword" class="feed-search-input" placeholder="Search posts by keyword" data-i18n-placeholder="posts.search_placeholder">
            <?php endif; ?>

            <select id="feedSearchCategory" class="feed-search-select">
                <option value="" data-i18n="common.all_categories">All categories</option>
                <?php foreach ($categories as $category): ?>
                <option value="<?= (int) $category['category_id'] ?>"><?= htmlspecialchars($category['name']) ?></option>
                <?php endforeach; ?>
            </select>

            <select id="feedSearchSort" class="feed-search-select">
                <option value="newest" data-i18n="common.newest_first">Newest first</option>
                <option value="oldest" data-i18n="common.oldest_first">Oldest first</option>
                <option value="title_asc" data-i18n="common.title_asc">Title A-Z</option>
                <option value="title_desc" data-i18n="common.title_desc">Title Z-A</option>
            </select>

            <input type="date" id="feedSearchFrom" class="feed-search-date" aria-label="Search from date" data-i18n-aria-label="common.search_from_date">
            <input type="date" id="feedSearchTo" class="feed-search-date" aria-label="Search to date" data-i18n-aria-label="common.search_to_date">

            <?php if (!$isAdmin): ?>
            <div class="feed-search-followers" id="feedSearchFollowersFilter">
                <button type="button" id="feedSearchFollowersToggle" class="feed-search-followers-toggle" aria-haspopup="true" aria-expanded="false">
                    <span id="feedSearchFollowersLabel" data-i18n="posts.all_followers">All followers</span>
                </button>
                <div id="feedSearchFollowersMenu" class="feed-search-followers-menu" hidden>
                    <label class="feed-search-followers-option">
                        <input type="checkbox" value="__all__" checked>
                        <span data-i18n="posts.all_followers">All followers</span>
                    </label>
                    <div id="feedSearchFollowersOptions"></div>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!$isAdmin): ?>
            </div>
            <?php else: ?>
            <div class="feed-search-actions">
                <button type="submit" class="feed-search-btn primary" data-i18n="common.search">Search</button>
                <button type="button" id="feedSearchClear" class="feed-search-btn secondary" data-i18n="common.clear">Clear</button>
            </div>
            <?php endif; ?>
        </form>

        <?php if ($success === 'interests_updated'): ?>
        <div class="alert alert-success py-2 px-3 mb-3" role="status">Interests updated successfully.</div>
        <?php endif; ?>

        
            

        <div id="feedModerationStatusFilters" class="feed-status-filters" hidden>
            <button type="button" class="feed-status-filter is-active" data-feed-status="0" data-i18n="admin.pending">Pending</button>
            <button type="button" class="feed-status-filter" data-feed-status="1" data-i18n="admin.approved">Approved</button>
            <button type="button" class="feed-status-filter" data-feed-status="2" data-i18n="admin.rejected">Rejected</button>
        </div>

        <div id="interestsBanner"></div>

    <section id="createPostPanel" class="create-post-panel" hidden>
        <div class="post-container create-post-card">
            <h2 data-i18n="posts.create_post_heading">Create New Post</h2>

            <form id="postForm" enctype="multipart/form-data">
                <input
                type="text"
                name="title"
                placeholder="Post title"
                data-i18n-placeholder="posts.post_title"
                required
                >

                <textarea
                name="content"
                placeholder="Write your content..."
                data-i18n-placeholder="posts.write_content"
                required
                ></textarea>

                <label for="inlineCategoryTrigger" data-i18n="posts.category_label">Category</label>
                <div class="post-category-field">
                    <div class="post-category-dropdown" id="inlineCategoryDropdown">
                        <button type="button" class="post-category-trigger" id="inlineCategoryTrigger" aria-haspopup="true" aria-expanded="false">
                            <span class="post-category-label" id="inlineCategoryLabel" data-i18n="posts.select_category">Select Category</span>
                        </button>
                        <div class="post-category-menu" id="inlineCategoryMenu" hidden>
                            <div class="post-category-options">
                                <?php foreach ($categories as $category): ?>
                                    <?php $catId = (int)$category['category_id']; ?>
                                    <label class="post-category-option" for="inlinePostCat<?= $catId ?>">
                                        <input type="radio" class="post-category-radio" name="category_id" value="<?= $catId ?>" id="inlinePostCat<?= $catId ?>" required>
                                        <span><?= htmlspecialchars((string)$category['name'], ENT_QUOTES, 'UTF-8') ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="anonymous-setting">
                    <div class="anonymous-setting-text">
                        <span class="anonymous-setting-title" data-i18n="posts.publish_anonymously">Publish anonymously</span>
                        <small class="anonymous-setting-hint" data-i18n="posts.anonymous_hint">Your name will be hidden for users. Admins can still view the post owner.</small>
                    </div>

                    <label class="anonymous-switch" for="anonymousToggle">
                        <input type="checkbox" id="anonymousToggle" name="is_anonymous" value="1">
                        <span class="anonymous-slider" aria-hidden="true"></span>
                    </label>
                </div>

                <div class="attachments-upload">
                    <div class="attachments-head">
                        <div class="attachments-head-text">
                            <span class="attachments-title" data-i18n="posts.attachments">Attachments</span>
                            <span class="attachments-hint" data-i18n="posts.attachments_hint">At least 1 file required, up to 5 files (jpg, png, pdf, doc, docx, txt, zip)</span>
                        </div>

                        <label for="attachmentsInput" class="attachments-choose-btn" data-i18n="posts.choose_files">Choose Files</label>
                    </div>

                    <input
                    type="file"
                    id="attachmentsInput"
                    class="attachments-native-input"
                    name="attachments[]"
                    multiple
                    accept=".jpg,.jpeg,.png,.pdf,.doc,.docx,.txt,.zip"
                    >

                    <small id="selectedFiles" class="selected-files"></small>
                </div>

                <button type="submit" data-i18n="posts.publish">Publish</button>
            </form>

            <p id="response" class="response-message" aria-live="polite"></p>
        </div>
    </section>

    <section id="tokenHistoryPanel" class="token-history-panel" hidden>
        <section class="balance-card">
            <span class="balance-label" data-i18n="posts.current_token_balance">Current token balance</span>
            <div class="balance-value"><?= $tokenBalance ?></div>
        </section>

        <section class="history-card">
            <div class="history-head">
                <h2 data-i18n="posts.token_history_heading">Token History</h2>
                <p data-i18n="posts.token_history_desc">See where you earned tokens and where you spent them.</p>
            </div>

            <?php if (!$transactions): ?>
                <div class="empty-state" data-i18n="posts.no_token_transactions">No token transactions found yet.</div>
            <?php else: ?>
                <div class="history-filters">
                    <button type="button" class="history-filter-btn is-active" data-filter="all" data-i18n="common.all">All</button>
                    <button type="button" class="history-filter-btn" data-filter="earned" data-i18n="posts.earned">Earned</button>
                    <button type="button" class="history-filter-btn" data-filter="spent" data-i18n="posts.spent">Spent</button>
                </div>
                <table class="history-table">
                    <thead>
                        <tr>
                            <th data-i18n="posts.type">Type</th>
                            <th data-i18n="posts.amount">Amount</th>
                            <th data-i18n="posts.date">Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($transactions as $transaction): ?>
                            <?php
                            $tokenCharge = (int) $transaction['token_charge'];
                            $amountClass = $tokenCharge > 0 ? 'amount-gain' : ($tokenCharge < 0 ? 'amount-loss' : 'amount-free');
                            $amountText = $tokenCharge > 0 ? '+' . $tokenCharge : (string) $tokenCharge;
                            $filterGroup = $tokenCharge > 0 ? 'earned' : 'spent';
                            ?>
                            <tr data-filter-group="<?= htmlspecialchars($filterGroup) ?>">
                                <td><?= htmlspecialchars(describeTransaction($tokenCharge, $transaction['transaction_source'] ?? null)) ?></td>
                                <td class="<?= $amountClass ?>"><?= htmlspecialchars($amountText) ?></td>
                                <td><?= htmlspecialchars($transaction['timestamp']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <div id="historyEmptyFilter" class="history-empty-filter" data-i18n="posts.no_transactions_filter">No transactions in this category yet.</div>
            <?php endif; ?>
        </section>
    </section>

    <div id="postsList" class="pending-grid" aria-live="polite"></div>

    <?php if (!$isAdmin): ?>
        </section>
    </div>
    <?php endif; ?>

</main>

<?php if (!$isAdmin): ?>
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
<?php endif; ?>

<div id="rejectedPostDeleteDialog" class="comment-policy-dialog" hidden>
    <div class="comment-policy-card delete-request-form" role="dialog" aria-modal="true" aria-labelledby="rejectedPostDeleteTitle">
        <h4 id="rejectedPostDeleteTitle" data-i18n="posts.delete_rejected_post">Delete Rejected Post</h4>
        <p data-i18n="posts.delete_rejected_post_desc">This will permanently remove the rejected post from your history. This action cannot be undone.</p>
        <div class="comment-policy-actions">
            <button type="button" id="rejectedPostDeleteCancel" class="policy-link cancel" data-i18n="common.cancel">Cancel</button>
            <button type="button" id="rejectedPostDeleteConfirm" class="policy-link danger" data-i18n="posts.delete_permanently">Delete permanently</button>
        </div>
    </div>
</div>

<div id="postPolicyDialog" class="comment-policy-dialog" hidden>
    <div class="comment-policy-card" role="dialog" aria-modal="true" aria-labelledby="postPolicyTitle">
        <h4 id="postPolicyTitle" data-i18n="posts.confirm_publication">Confirm Publication</h4>
        <p data-i18n="posts.confirm_publication_desc">After publishing, this post cannot be deleted directly and requires a delete request.</p>
        <div class="comment-policy-actions">
            <button type="button" id="postPolicyCancel" class="policy-link cancel" data-i18n="common.cancel">Cancel</button>
            <button type="button" id="postPolicyAccept" class="policy-link accept" data-i18n="common.accept">Accept</button>
        </div>
    </div>
</div>

<script>
const isAdmin = <?= $isAdmin ? 'true' : 'false' ?>;
const currentUserId = <?= (int)$_SESSION['user_id'] ?>;
</script>
<script src="js/i18n.js?v=<?php echo $i18nJsVersion; ?>"></script>
<script src="js/posts.js?v=<?php echo $postsJsVersion; ?>"></script>
<script src="js/createPost.js?v=<?php echo $createPostJsVersion; ?>"></script>

</body>

</html>

