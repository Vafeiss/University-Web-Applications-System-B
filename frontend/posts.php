<?php
require_once "../backend/config/database.php";
session_start();    
/* Έλεγχος αν ο χρήστης είναι συνδεδεμένος */
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
/* Έλεγχος αν ο χρήστης είναι admin  για εμφανιση anonymous posts */
$isAdmin = ($_SESSION['role'] ?? '') === 'admin';

$db = new Database();
$conn = $db->connect();

$tokenBalanceStmt = $conn->prepare(
    "SELECT token_balance FROM users WHERE user_id = :id LIMIT 1"
);
$tokenBalanceStmt->execute([":id" => $_SESSION['user_id']]);
$tokenBalance = (int) ($tokenBalanceStmt->fetchColumn() ?: 0);

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
$adminCssVersion = filemtime(__DIR__ . '/css/admin_pending_posts.css');
$postsJsVersion = filemtime(__DIR__ . '/js/posts.js');
?>

<!DOCTYPE html>
<html>

<head>
<meta charset="UTF-8">  <!-- Ορίζει το charset σε UTF-8 για σωστή εμφάνιση χαρακτήρων -->
<meta name="viewport" content="width=device-width, initial-scale=1.0"> <!-- Κάνει τη σελίδα responsive σε κινητές συσκευές -->
<title>Posts Feed</title>   
<link rel="stylesheet" href="css/post.css?v=<?php echo $postCssVersion; ?>">
<link rel="stylesheet" href="css/admin_pending_posts.css?v=<?php echo $adminCssVersion; ?>">
</head>

<body>

<main class="pending-page feed-shell">

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
                    <button type="button" id="notificationsBtn" class="notifications-btn" aria-label="Open notifications" aria-haspopup="true" aria-expanded="false">
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
                            <span>Notifications</span>
                            <button type="button" id="deleteReadNotifications" class="notifications-mark-all">Delete all read</button>
                        </div>

                        <div id="notificationsList" class="notifications-list">
                            <div class="notifications-empty">No notifications yet.</div>
                        </div>
                    </div>
                </div>   

                <details class="feed-menu" id="feedMenu">
                    <summary class="feed-menu-trigger" aria-label="Open feed menu" title="Menu">&#8942;</summary>

                    <div class="feed-menu-dropdown" role="menu" aria-label="Feed quick actions">
                        <?php if ($isAdmin): ?>
                        <a href="admin_dashboard.php" class="feed-menu-item" role="menuitem">Admin panel</a>
                        <?php else: ?>
                        <a href="token_history.php" class="feed-menu-item" role="menuitem">Token history</a>
                        <?php endif; ?>

                        <a href="profile_view.php" class="feed-menu-item" role="menuitem">View &amp; Edit profile</a>
                        <a href="edit_interests.php" class="feed-menu-item" role="menuitem">Edit interests</a>
                        <a href="category_request.php" class="feed-menu-item" role="menuitem">Request category</a>
                        <a href="logout.php" class="feed-menu-item danger" role="menuitem">Logout</a>
                    </div>
                </details>
            </div>
        </div>

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

        <form id="feedSearchForm" class="feed-search-panel">
            <input type="text" id="feedSearchKeyword" class="feed-search-input" placeholder="Search posts by keyword">

            <select id="feedSearchCategory" class="feed-search-select">
                <option value="">All categories</option>
                <?php foreach ($categories as $category): ?>
                <option value="<?= (int) $category['category_id'] ?>"><?= htmlspecialchars($category['name']) ?></option>
                <?php endforeach; ?>
            </select>

            <select id="feedSearchSort" class="feed-search-select">
                <option value="newest">Newest first</option>
                <option value="oldest">Oldest first</option>
                <option value="title_asc">Title A-Z</option>
                <option value="title_desc">Title Z-A</option>
            </select>

            <input type="date" id="feedSearchFrom" class="feed-search-date" aria-label="Search from date">
            <input type="date" id="feedSearchTo" class="feed-search-date" aria-label="Search to date">

            <?php if (!$isAdmin): ?>
            <div class="feed-search-followers" id="feedSearchFollowersFilter">
                <button type="button" id="feedSearchFollowersToggle" class="feed-search-followers-toggle" aria-haspopup="true" aria-expanded="false">
                    <span id="feedSearchFollowersLabel">All followers</span>
                </button>
                <div id="feedSearchFollowersMenu" class="feed-search-followers-menu" hidden>
                    <label class="feed-search-followers-option">
                        <input type="checkbox" value="__all__" checked>
                        <span>All followers</span>
                    </label>
                    <div id="feedSearchFollowersOptions"></div>
                </div>
            </div>
            <?php endif; ?>

            <div class="feed-search-actions">
                <button type="submit" class="feed-search-btn primary">Search</button>
                <button type="button" id="feedSearchClear" class="feed-search-btn secondary">Clear</button>
            </div>
        </form>

        <?php if ($success === 'interests_updated'): ?>
        <div class="alert alert-success py-2 px-3 mb-3" role="status">Interests updated successfully.</div>
        <?php endif; ?>

        <div id="feedModerationStatusFilters" class="feed-status-filters" hidden>
            <button type="button" class="feed-status-filter is-active" data-feed-status="0">Pending</button>
            <button type="button" class="feed-status-filter" data-feed-status="1">Approved</button>
            <button type="button" class="feed-status-filter" data-feed-status="2">Rejected</button>
        </div>

        <div id="interestsBanner"></div>
    </header>

    <div id="postsList" class="pending-grid" aria-live="polite"></div>

</main>

<script>
const isAdmin = <?= $isAdmin ? 'true' : 'false' ?>;
const currentUserId = <?= (int)$_SESSION['user_id'] ?>;
</script>
<script src="js/posts.js?v=<?php echo $postsJsVersion; ?>"></script>

</body>

</html>
