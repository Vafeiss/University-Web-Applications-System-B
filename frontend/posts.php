<?php
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

function describeTransaction(int $tokenCharge): string {
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
    "SELECT transaction_id, token_charge, timestamp
     FROM transactions
     WHERE user_id = :id
     ORDER BY timestamp DESC, transaction_id DESC"
);
$transactionsStmt->execute([":id" => $_SESSION['user_id']]);
$transactions = $transactionsStmt->fetchAll(PDO::FETCH_ASSOC);

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
                    <span>Student workspace</span>
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
                    <span class="feed-sidebar-kicker">Signed in as</span>
                    <strong><?= htmlspecialchars($_SESSION['username']) ?></strong>
                </div>
            </div>

            <nav class="feed-tabs feed-sidebar-tabs" aria-label="Feed navigation">
                <button type="button" id="createPostBtn" class="feed-tab">&#43; Create Post</button>
                <button type="button" id="postsFeedBtn" class="feed-tab is-active">Posts</button>
                <button type="button" id="followersFeedBtn" class="feed-tab">Followers</button>
                <button type="button" id="pendingPostsBtn" class="feed-tab">Pending Posts</button>
                <button type="button" id="pendingDeleteRequestsBtn" class="feed-tab">Pending Delete Requests</button>
                <button type="button" id="reportsBtn" class="feed-tab">Reports</button>
                <button type="button" id="tokenHistoryBtn" class="feed-tab">Token history</button>
            </nav>
        </aside>

        <div class="app-main-shell">
            <div class="feed-dashboard-topbar" aria-label="Workspace quick actions">
                <button type="button" id="feedSidebarToggle" class="feed-sidebar-toggle" aria-controls="feedSidebar" aria-expanded="true" aria-label="Hide side menu" title="Hide side menu">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" aria-hidden="true">
                        <path d="M4 7h16"></path>
                        <path d="M4 12h16"></path>
                        <path d="M4 17h16"></path>
                    </svg>
                    <span class="feed-sidebar-toggle-label">Hide menu</span>
                </button>
                <div id="feedTitle" class="feed-topbar-title">Posts Feed</div>
                <div class="feed-dashboard-toplinks">
                    <a href="profile_view.php" class="feed-dashboard-toplink">Profile settings</a>
                    <a href="profile_view.php" class="feed-dashboard-toplink">View &amp; Edit profile</a>
                    <a href="edit_interests.php" class="feed-dashboard-toplink">Edit interests</a>
                    <a href="category_request.php" class="feed-dashboard-toplink">Request category</a>
                </div>

                <div class="feed-header-actions app-topbar-actions">
                    <button type="button" id="infoToggleBtn" class="info-fab" aria-label="Open project information" aria-expanded="false" aria-controls="infoDialog">
                        <span aria-hidden="true">i</span>
                    </button>

                    <div class="token-balance-badge">
                        <span class="token-balance-label">Tokens</span>
                        <strong><?= $tokenBalance ?></strong>
                    </div>

                    <div class="notifications-wrap">
                        <button type="button" id="notificationsBtn" class="notifications-btn" aria-label="Open notifications" aria-haspopup="true" aria-expanded="false">
                            <svg class="notifications-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" width="20" height="20" aria-hidden="true" focusable="false">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082A23.848 23.848 0 0112 17.25c-1.013 0-2.006-.063-2.857-.168M12 3a6 6 0 00-6 6v3.586l-.707.707A1 1 0 006 15h12a1 1 0 00.707-1.707L18 12.586V9a6 6 0 00-6-6zM15 19a3 3 0 11-6 0"/>
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
                    <input type="text" id="feedSearchKeyword" class="feed-search-input" placeholder="Search posts by keyword">
                </label>

                <button type="button" id="feedSearchFiltersToggle" class="feed-search-filters-toggle" aria-expanded="false" aria-controls="feedSearchAdvanced" aria-label="Show search filters">
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
                    <button type="submit" class="feed-search-btn primary">Search</button>
                    <button type="button" id="feedSearchClear" class="feed-search-btn secondary">Clear</button>
                </div>
            </div>

            <div id="feedSearchAdvanced" class="feed-search-advanced" hidden>
            <?php else: ?>
            <input type="text" id="feedSearchKeyword" class="feed-search-input" placeholder="Search posts by keyword">
            <?php endif; ?>

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

            <?php if (!$isAdmin): ?>
            </div>
            <?php else: ?>
            <div class="feed-search-actions">
                <button type="submit" class="feed-search-btn primary">Search</button>
                <button type="button" id="feedSearchClear" class="feed-search-btn secondary">Clear</button>
            </div>
            <?php endif; ?>
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

    <section id="createPostPanel" class="create-post-panel" hidden>
        <div class="post-container create-post-card">
            <h2>Create New Post</h2>

            <form id="postForm" enctype="multipart/form-data">
                <input
                type="text"
                name="title"
                placeholder="Post title"
                required
                >

                <textarea
                name="content"
                placeholder="Write your content..."
                required
                ></textarea>

                <label>Category</label>
                <select name="category_id" required>
                    <option value="">Select Category</option>
                    <?php foreach ($categories as $category): ?>
                    <option value="<?= (int)$category['category_id'] ?>"><?= htmlspecialchars((string)$category['name'], ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>

                <div class="anonymous-setting">
                    <div class="anonymous-setting-text">
                        <span class="anonymous-setting-title">Publish anonymously</span>
                        <small class="anonymous-setting-hint">Your name will be hidden for users. Admins can still view the post owner.</small>
                    </div>

                    <label class="anonymous-switch" for="anonymousToggle">
                        <input type="checkbox" id="anonymousToggle" name="is_anonymous" value="1">
                        <span class="anonymous-slider" aria-hidden="true"></span>
                    </label>
                </div>

                <div class="attachments-upload">
                    <div class="attachments-head">
                        <div class="attachments-head-text">
                            <span class="attachments-title">Attachments</span>
                            <span class="attachments-hint">At least 1 file required, up to 5 files (jpg, png, pdf, doc, docx, txt, zip)</span>
                        </div>

                        <label for="attachmentsInput" class="attachments-choose-btn">Choose Files</label>
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

                <button type="submit">Publish</button>
            </form>

            <p id="response" class="response-message" aria-live="polite"></p>
        </div>
    </section>

    <section id="tokenHistoryPanel" class="token-history-panel" hidden>
        <section class="balance-card">
            <span class="balance-label">Current token balance</span>
            <div class="balance-value"><?= $tokenBalance ?></div>
        </section>

        <section class="history-card">
            <div class="history-head">
                <h2>Token History</h2>
                <p>See where you earned tokens and where you spent them.</p>
            </div>

            <?php if (!$transactions): ?>
                <div class="empty-state">No token transactions found yet.</div>
            <?php else: ?>
                <div class="history-filters">
                    <button type="button" class="history-filter-btn is-active" data-filter="all">All</button>
                    <button type="button" class="history-filter-btn" data-filter="earned">Earned</button>
                    <button type="button" class="history-filter-btn" data-filter="spent">Spent</button>
                </div>
                <table class="history-table">
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Amount</th>
                            <th>Date</th>
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
                                <td><?= htmlspecialchars(describeTransaction($tokenCharge)) ?></td>
                                <td class="<?= $amountClass ?>"><?= htmlspecialchars($amountText) ?></td>
                                <td><?= htmlspecialchars($transaction['timestamp']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <div id="historyEmptyFilter" class="history-empty-filter">No transactions in this category yet.</div>
            <?php endif; ?>
        </section>
    </section>

    <div id="postsList" class="pending-grid" aria-live="polite"></div>

    <?php if (!$isAdmin): ?>
    <?php endif; ?>

    <?php if (!$isAdmin): ?>
        </section>
    </div>
    <?php endif; ?>

</main>

<?php if (!$isAdmin): ?>
<div id="infoDialog" class="info-dialog" hidden>
    <div class="info-dialog-backdrop" data-info-close></div>
    <div class="info-dialog-card" role="dialog" aria-modal="true" aria-labelledby="infoDialogTitle">
        <button type="button" id="infoDialogClose" class="info-dialog-close" aria-label="Close information panel">&times;</button>
        <div class="info-dialog-grid">
            <section class="info-dialog-block" aria-labelledby="infoDialogTitle">
                <span class="info-dialog-kicker" id="infoDialogTitle">About UniSupport</span>
                <p class="info-dialog-text">
                    UniSupport is a student support platform for staying organized, sharing knowledge, and connecting with others in one place.
                </p>
                <div class="info-dialog-brandmark">
                    <img src="imgs/cut_logo.png" alt="Cyprus University of Technology" class="info-dialog-brandmark-image">
                </div>
            </section>

            <section class="info-dialog-block" aria-labelledby="infoDialogProjectTitle">
                <span class="info-dialog-kicker" id="infoDialogProjectTitle">Project Information</span>
                <p class="info-dialog-text">This system was developed by Pelagia Koniotaki, Antriani Theofanous, Panteleimoni Alexandrou, Paraskevas Vafeiadis and Panagiotis Panagiwtou, third-year students of the Department of Electrical Engineering, Computer Engineering and Informatics at the Cyprus University of Technology, under the supervision of Professor Andreas S. Andreou, as part of the course 'Software Technology Project and Professional Practice'.</p>
                <p class="info-dialog-text">Limassol, May 2026</p>
            </section>
        </div>
    </div>
</div>
<?php endif; ?>

<div id="rejectedPostDeleteDialog" class="comment-policy-dialog" hidden>
    <div class="comment-policy-card delete-request-form" role="dialog" aria-modal="true" aria-labelledby="rejectedPostDeleteTitle">
        <h4 id="rejectedPostDeleteTitle">Delete Rejected Post</h4>
        <p>This will permanently remove the rejected post from your history. This action cannot be undone.</p>
        <div class="comment-policy-actions">
            <button type="button" id="rejectedPostDeleteCancel" class="policy-link cancel">Cancel</button>
            <button type="button" id="rejectedPostDeleteConfirm" class="policy-link danger">Delete permanently</button>
        </div>
    </div>
</div>

<div id="postPolicyDialog" class="comment-policy-dialog" hidden>
    <div class="comment-policy-card" role="dialog" aria-modal="true" aria-labelledby="postPolicyTitle">
        <h4 id="postPolicyTitle">Confirm Publication</h4>
        <p>After publishing, this post cannot be deleted directly and requires a delete request.</p>
        <div class="comment-policy-actions">
            <button type="button" id="postPolicyCancel" class="policy-link cancel">Cancel</button>
            <button type="button" id="postPolicyAccept" class="policy-link accept">Accept</button>
        </div>
    </div>
</div>

<script>
const isAdmin = <?= $isAdmin ? 'true' : 'false' ?>;
const currentUserId = <?= (int)$_SESSION['user_id'] ?>;
</script>
<script src="js/posts.js?v=<?php echo $postsJsVersion; ?>"></script>
<script src="js/createPost.js?v=<?php echo $createPostJsVersion; ?>"></script>

</body>

</html>

