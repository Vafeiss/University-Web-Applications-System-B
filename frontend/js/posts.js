const BASE_URL = "http://localhost/University-Web-Applications-System-B/backend/controllers/PostController.php";
const CAT_URL = "http://localhost/University-Web-Applications-System-B/backend/controllers/CategoryController.php";
const FOLLOW_URL = "http://localhost/University-Web-Applications-System-B/backend/controllers/FollowController.php";
const NOTIFICATION_URL = "http://localhost/University-Web-Applications-System-B/backend/controllers/NotificationController.php";
const SEARCH_URL = "http://localhost/University-Web-Applications-System-B/backend/controllers/search_controllers.php";
let activeFeedMode = "default";
let previousSearchMode = "default";
const followedUserIds = new Set();
let selectedFollowerFilters = ["__all__"];
let feedAutoRefreshTimerId = null;
let isFeedAutoRefreshInFlight = false;
let feedSearchInputDebounceId = null;
let activeFeedStatusFilter = 0;
let initialFeedTargetMode = null;
let initialFeedStatusFilter = null;
let cachedPendingPosts = [];
let cachedPendingDeleteRequests = [];
let cachedReports = [];
let rejectedPostDeleteId = null;
const FEED_SIDEBAR_STORAGE_KEY = "feedSidebarCollapsed";

function translate(key, fallback) {
    return window.UniSupportI18n?.t(key, fallback) ?? fallback;
}

function translateFormat(key, params, fallback) {
    const i18n = window.UniSupportI18n;
    if (i18n && typeof i18n.tf === "function") {
        return i18n.tf(key, params, fallback);
    }
    if (i18n && typeof i18n.format === "function") {
        return i18n.format(i18n.t ? i18n.t(key, fallback) : fallback, params);
    }
    return fallback;
}

function resolveNotificationMessage(rawMessage) {
    const text = String(rawMessage ?? "");
    if (!text) {
        return "";
    }

    const trimmed = text.trim();
    if (!trimmed.startsWith("{") || !trimmed.endsWith("}")) {
        return text;
    }

    try {
        const payload = JSON.parse(trimmed);
        if (payload && typeof payload === "object" && typeof payload.i18n_key === "string") {
            const params = (payload.params && typeof payload.params === "object") ? payload.params : {};
            const fallback = typeof payload.fallback === "string" ? payload.fallback : "";
            return translateFormat(payload.i18n_key, params, fallback || text);
        }
    } catch (error) {
        // Not a JSON payload, fall through and return the raw text
    }

    return text;
}

function mapStatusParamToNumeric(status) {
    const normalized = String(status ?? "").toLowerCase();
    if (normalized === "approved" || normalized === "1") {
        return 1;
    }
    if (normalized === "rejected" || normalized === "2") {
        return 2;
    }
    return 0;
}

function getFeedTargetFromUrl() {
    const params = new URLSearchParams(window.location.search);

    const mode = params.get("mode");
    const status = params.get("status");
    if (mode === "pending" && status !== null) {
        return {
            mode: "pending-posts",
            status: mapStatusParamToNumeric(status)
        };
    }

    const view = String(params.get("view") || "").toLowerCase();
    if (view === "reports") {
        return {
            mode: "reports",
            status: mapStatusParamToNumeric(status ?? "pending")
        };
    }

    if (view === "delete_requests") {
        return {
            mode: "pending-delete-requests",
            status: mapStatusParamToNumeric(status ?? "pending")
        };
    }

    return null;
}

function consumeInitialStatusForMode(mode) {
    if (initialFeedTargetMode === mode && initialFeedStatusFilter !== null) {
        activeFeedStatusFilter = Number(initialFeedStatusFilter);
        initialFeedTargetMode = null;
        initialFeedStatusFilter = null;
        return;
    }

    activeFeedStatusFilter = 0;
}

function getAuthorName(post) {
    if (post.is_anonymous == 1 && !isAdmin) {
        return translate("posts.anonymous", "Anonymous");
    }

    if (post.is_anonymous == 1 && isAdmin) {
        return `${translate("posts.anonymous", "Anonymous")} (${escapeHtml(post.username)})`;
    }

    return escapeHtml(post.username);
}

function escapeHtml(value) {
    return String(value ?? "")
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#39;");
}

function matchesStartsWithKeyword(keyword, values) {
    const normalizedKeyword = String(keyword ?? "").toLowerCase().trim();

    if (!normalizedKeyword) {
        return true;
    }

    return values.some((value) => String(value ?? "").toLowerCase().startsWith(normalizedKeyword));
}

function replaceOwnerTriggersWithPlainName(userId) {
    const ownerBlocks = document.querySelectorAll(`.owner-meta-block .post-owner-trigger[data-follow-id="${userId}"]`);

    ownerBlocks.forEach((trigger) => {
        const ownerMeta = trigger.closest(".owner-meta-block");
        const ownerNameEl = trigger.querySelector(".owner-name");
        if (ownerMeta && ownerNameEl) {
            ownerMeta.innerHTML = `<span>${escapeHtml(ownerNameEl.textContent || "")}</span>`;
        }
    });
}

async function fetchJSON(url, options = {}) {
    const response = await fetch(url, {
        cache: "no-store",
        ...options
    });

    let data = {};
    try {
        data = await response.json();
    } catch {
        data = {};
    }

    return { ok: response.ok, data };
}

async function refreshFollowingUsersState() {
    try {
        const { ok, data: users } = await fetchJSON(`${FOLLOW_URL}?action=followingList`);
        followedUserIds.clear();

        if (!ok || !Array.isArray(users)) {
            return;
        }

        users.forEach((user) => {
            const userId = Number(user.user_id);
            if (Number.isInteger(userId) && userId > 0) {
                followedUserIds.add(userId);
            }
        });
    } catch (error) {
        console.error("Could not refresh following users state:", error);
    }
}

function renderPosts(posts) {
    const container = document.getElementById("postsList");
    if (!container) return;

    container.innerHTML = "";

    if (!Array.isArray(posts) || posts.length === 0) {
        container.innerHTML = `<div class="pending-state">${escapeHtml(translate("posts.no_posts_available", "No posts available yet."))}</div>`;
        return;
    }

    posts.forEach((post) => {
        const card = document.createElement("article");
        card.className = "pending-card";
        const postOwnerId = Number(post.user_id);
        const isAnonymousPost = Number(post.is_anonymous) === 1;
        const isAlreadyFollowing = followedUserIds.has(postOwnerId);
        const showFollowButton = Number.isInteger(postOwnerId)
            && postOwnerId > 0
            && postOwnerId !== Number(currentUserId)
            && !isAlreadyFollowing
            && !isAnonymousPost;
        const authorName = getAuthorName(post);
        const authorBlock = showFollowButton
            ? `<button type="button" class="post-owner-trigger" data-follow-id="${postOwnerId}" title="${escapeHtml(translate("posts.follow_title", "Click to follow user"))}"><span class="owner-name">${authorName}</span><span class="owner-follow-hint">${escapeHtml(translate("posts.follow_action", "+ Follow"))}</span></button>`
            : `<span>${authorName}</span>`;

        const createdAt = post.timestamp ? new Date(post.timestamp).toLocaleString() : translate("posts.unknown_date", "Unknown date");
        const excerpt = String(post.content || "").trim().slice(0, 220);

        card.innerHTML = `
            <h3>
                <a class="feed-title-link" href="post.php?id=${encodeURIComponent(post.post_id)}">
                    ${escapeHtml(post.title || "Untitled post")}
                </a>
            </h3>
            <div class="pending-meta">
                <span class="pending-chip">${escapeHtml(post.category || "General")}</span>
                <span class="owner-meta-block">${authorBlock}</span>
                <span>${escapeHtml(createdAt)}</span>
            </div>
            ${excerpt ? `<div class="post-excerpt">${escapeHtml(excerpt)}</div>` : ""}
        `;

        container.appendChild(card);
    });
}
// Εμφανίζει τις ειδοποιήσεις στο dropdown menu, με ένδειξη για τις μη αναγνωσμένες 
// και κατάλληλο μήνυμα όταν δεν υπάρχουν ειδοποιήσεις
function renderNotifications(notifications) {
    const list = document.getElementById("notificationsList");
    const count = document.getElementById("notificationsCount");

    if (!list || !count) {
        return;
    }

    if (!Array.isArray(notifications) || notifications.length === 0) {
        list.innerHTML = `<div class="notifications-empty">${escapeHtml(translate("common.no_notifications", "No notifications yet."))}</div>`;
        count.hidden = true;
        return;
    }

    const unreadCount = notifications.filter((item) => Number(item.is_read) === 0).length;

    count.textContent = unreadCount;
    count.hidden = unreadCount === 0;

    list.innerHTML = "";

    notifications.forEach((notification) => {
        const item = document.createElement("button");
        item.type = "button";
        item.className = `notification-item${Number(notification.is_read) === 0 ? " unread" : ""}`;
        item.setAttribute("data-notification-id", notification.notification_id);
        item.setAttribute("data-reference-id", notification.reference_id || "");
        item.setAttribute("data-type", notification.type || "");

        const createdAt = notification.created_at
            ? new Date(notification.created_at).toLocaleString()
            : "";

        const localizedMessage = resolveNotificationMessage(notification.message);
        const deleteAriaLabel = translate("common.delete_notification", "Delete notification");
        const deleteTitle = translate("common.delete", "Delete");

        item.innerHTML = `
            <span class="notification-delete-btn" role="button" tabindex="0" aria-label="${escapeHtml(deleteAriaLabel)}" title="${escapeHtml(deleteTitle)}">x</span>
            <div class="notification-text">${escapeHtml(localizedMessage)}</div>
            <div class="notification-time">${escapeHtml(createdAt)}</div>
        `;

        list.appendChild(item);
    });
}

function getAdminNotificationTarget(type) {
    const map = {
        admin_pending_post: "pending",
        admin_post_delete_request: "deleteRequests",
        admin_comment_delete_request: "commentDeleteRequests",
        admin_category_request: "categoryRequests",
        admin_post_report: "reports"
    };

    const section = map[String(type || "")];
    if (!section) {
        return "";
    }

    return `admin_dashboard.php?section=${encodeURIComponent(section)}`;
}

function handleNotificationClick(notification) {
    const type = String(notification?.type || "");
    const referenceId = Number(notification?.reference_id || 0);

    if (type === "post_approved") {
        window.location.href = "posts.php?mode=pending&status=1";
        return true;
    }

    if (type === "post_rejected") {
        window.location.href = "posts.php?mode=pending&status=2";
        return true;
    }

    if (type === "report_approved") {
        window.location.href = "posts.php?view=reports&status=approved";
        return true;
    }

    if (type === "report_rejected") {
        window.location.href = "posts.php?view=reports&status=rejected";
        return true;
    }

    if (type === "delete_approved") {
        window.location.href = "posts.php?view=delete_requests&status=approved";
        return true;
    }

    if (type === "delete_rejected") {
        window.location.href = "posts.php?view=delete_requests&status=rejected";
        return true;
    }

    if (type === "category_request_approved" || type === "category_request_rejected") {
        window.location.href = "category_request.php";
        return true;
    }

    const adminTarget = getAdminNotificationTarget(type);
    if (adminTarget) {
        window.location.href = adminTarget;
        return true;
    }

    if (referenceId > 0) {
        window.location.href = `post.php?id=${encodeURIComponent(referenceId)}`;
        return true;
    }

    return false;
}
// Φορτώνει τις ειδοποιήσεις του χρήστη από τον server και τις εμφανίζει στο dropdown menu
// με κατάλληλο χειρισμό σφαλμάτων σε περίπτωση αποτυχίας φόρτωσης
async function loadNotifications() {
    try {
        const { ok, data } = await fetchJSON(`${NOTIFICATION_URL}?action=list`);

        if (!ok) {
            return;
        }

        renderNotifications(data);
    } catch (error) {
        console.error("Could not load notifications:", error);
    }
}
// Σημειώνει μια συγκεκριμένη ειδοποίηση ως αναγνωσμένη στον server και ενημερώνει την εμφάνιση της στο dropdown menu
async function markNotificationRead(notificationId) {
    await fetchJSON(`${NOTIFICATION_URL}?action=markRead`, {
        method: "POST",
        headers: {
            "Content-Type": "application/json"
        },
        body: JSON.stringify({
            notification_id: notificationId
        })
    });
}

async function deleteNotification(notificationId) {
    await fetchJSON(`${NOTIFICATION_URL}?action=deleteOne`, {
        method: "POST",
        headers: {
            "Content-Type": "application/json"
        },
        body: JSON.stringify({
            notification_id: notificationId
        })
    });
}
// Σημειώνει όλες τις ειδοποιήσεις του χρήστη ως αναγνωσμένες στον server και ενημερώνει την εμφάνιση τους στο dropdown menu
async function deleteReadNotifications() {
    const { ok } = await fetchJSON(`${NOTIFICATION_URL}?action=deleteRead`, {
        method: "POST",
        headers: {
            "Content-Type": "application/json"
        }
    });

    if (ok) {
        await loadNotifications();
    }
}
// ανοιγει/κλεινει dropdown 
// πατάς notification ,κανει read,σε στελνει σχετικό post
// αν πατήσεις delete all read, διαγράφονται μόνο όσα notifications είναι ήδη read
function setupNotificationsUI() {
    const btn = document.getElementById("notificationsBtn");
    const dropdown = document.getElementById("notificationsDropdown");
    const deleteReadBtn = document.getElementById("deleteReadNotifications");
    const list = document.getElementById("notificationsList");

    if (!btn || !dropdown || !list) {
        return;
    }

    btn.addEventListener("click", async () => {
        const isHidden = dropdown.hidden;
        dropdown.hidden = !isHidden;
        btn.setAttribute("aria-expanded", isHidden ? "true" : "false");

        if (isHidden) {
            await loadNotifications();
        }
    });

    document.addEventListener("click", async (event) => {
        const deleteButton = event.target.closest(".notification-delete-btn");
        if (deleteButton) {
            const deleteItem = deleteButton.closest(".notification-item");
            if (!deleteItem) {
                return;
            }

            const deleteNotificationId = Number(deleteItem.getAttribute("data-notification-id"));
            if (deleteNotificationId > 0) {
                await deleteNotification(deleteNotificationId);
                await loadNotifications();
            }
            return;
        }

        const item = event.target.closest(".notification-item");
        if (!item) {
            return;
        }

        const notificationId = Number(item.getAttribute("data-notification-id"));
        const referenceId = Number(item.getAttribute("data-reference-id"));
        const notificationType = String(item.getAttribute("data-type") || "");

        if (notificationId > 0) {
            await markNotificationRead(notificationId);
        }

        if (handleNotificationClick({
            type: notificationType,
            reference_id: referenceId
        })) {
            return;
        }

        await loadNotifications();
    });

    if (deleteReadBtn) {
        deleteReadBtn.addEventListener("click", async () => {
            await deleteReadNotifications();
        });
    }

    document.addEventListener("click", (event) => {
        if (!dropdown.hidden && !event.target.closest(".notifications-wrap")) {
            dropdown.hidden = true;
            btn.setAttribute("aria-expanded", "false");
        }
    });

    // όταν αλλάξει η γλώσσα, ανανεώνουμε τη λίστα ειδοποιήσεων
    // ώστε τα μηνύματα να εμφανιστούν μεταφρασμένα χωρίς refresh
    window.addEventListener("unisupport:languagechange", () => {
        loadNotifications();
    });
}

// Επιστρέφει το κείμενο και την CSS κλάση που αντιστοιχεί στην κατάσταση ενός post, λαμβάνοντας υπόψη αν έχει διαγραφεί ή όχι
function getPostStatusInfo(status, deleted) {
    const numericStatus = Number(status);

    if (numericStatus === 0) {
        return { text: translate("admin.pending", "Pending"), className: "pending" };
    }

    if (numericStatus === 2) {
        return { text: translate("admin.rejected", "Rejected"), className: "rejected" };
    }

    return { text: translate("admin.approved", "Approved"), className: "approved" };
}

function getRequestStatusInfo(status) {
    const numericStatus = Number(status);

    if (numericStatus === 0) {
        return { text: translate("admin.pending", "Pending"), className: "pending" };
    }

    if (numericStatus === 1) {
        return { text: translate("admin.approved", "Approved"), className: "approved" };
    }

    return { text: translate("admin.rejected", "Rejected"), className: "rejected" };
}

function confirmUnfollowAction() {
    return new Promise((resolve) => {
        const dialog = document.createElement("div");
        dialog.className = "comment-policy-dialog";
        dialog.innerHTML = `
            <div class="comment-policy-card" role="dialog" aria-modal="true" aria-labelledby="unfollowConfirmTitle">
                <h4 id="unfollowConfirmTitle">${escapeHtml(translate("posts.confirm_unfollow_title", "Confirm Unfollow"))}</h4>
                <p>${escapeHtml(translate("posts.confirm_unfollow_desc", "Are you sure you want to unfollow this user?"))}</p>
                <div class="comment-policy-actions">
                    <button type="button" class="policy-link cancel" data-unfollow-confirm="cancel">${escapeHtml(translate("common.cancel", "Cancel"))}</button>
                    <button type="button" class="policy-link danger" data-unfollow-confirm="accept">${escapeHtml(translate("posts.unfollow", "Unfollow"))}</button>
                </div>
            </div>
        `;

        const cleanup = (result) => {
            dialog.remove();
            document.body.classList.remove("comment-dialog-open");
            resolve(result);
        };

        dialog.addEventListener("click", (event) => {
            const actionButton = event.target.closest("[data-unfollow-confirm]");
            if (!actionButton) {
                if (event.target === dialog) {
                    cleanup(false);
                }
                return;
            }

            cleanup(actionButton.getAttribute("data-unfollow-confirm") === "accept");
        });

        document.body.appendChild(dialog);
        document.body.classList.add("comment-dialog-open");
    });
}

function renderSimpleBanner(label, text, isWarning = false) {
    const banner = document.getElementById("interestsBanner");
    if (!banner) return;

    if (!label && !text) {
        banner.innerHTML = "";
        return;
    }

    banner.innerHTML = `
        <div class="interests-banner${isWarning ? " no-interests" : ""}">
            <div class="interests-label">${escapeHtml(label)}</div>
            <div class="interests-chips">
                <span class="pending-chip">${escapeHtml(text)}</span>
            </div>
        </div>`;
}

function parseDateValue(value) {
    const timestamp = value ? new Date(value).getTime() : NaN;
    return Number.isNaN(timestamp) ? null : timestamp;
}

function getCurrentFeedSearchFilters() {
    return {
        keyword: document.getElementById("feedSearchKeyword")?.value.trim() || "",
        category: document.getElementById("feedSearchCategory")?.value || "",
        from: document.getElementById("feedSearchFrom")?.value || "",
        to: document.getElementById("feedSearchTo")?.value || "",
        sort: document.getElementById("feedSearchSort")?.value || "newest"
    };
}

function updateFeedStatusButtons() {
    document.querySelectorAll(".feed-status-filter[data-feed-status]").forEach((button) => {
        button.classList.toggle("is-active", Number(button.dataset.feedStatus) === activeFeedStatusFilter);
    });
}

function filterPendingPostsData(posts, filters) {
    const keyword = filters.keyword.toLowerCase();
    const fromTime = filters.from ? new Date(`${filters.from}T00:00:00`).getTime() : null;
    const toTime = filters.to ? new Date(`${filters.to}T23:59:59`).getTime() : null;

    let filtered = Array.isArray(posts) ? posts.filter((post) => {
        if (Number(post.status) !== activeFeedStatusFilter) {
            return false;
        }

        if (filters.category && String(post.category_id ?? "") !== filters.category) {
            return false;
        }

        if (!matchesStartsWithKeyword(keyword, [post.title, post.content, post.category])) {
            return false;
        }

        const createdTime = parseDateValue(post.timestamp);
        if (fromTime !== null && (createdTime === null || createdTime < fromTime)) {
            return false;
        }

        if (toTime !== null && (createdTime === null || createdTime > toTime)) {
            return false;
        }

        return true;
    }) : [];

    filtered.sort((a, b) => {
        const aTime = parseDateValue(a.timestamp) ?? 0;
        const bTime = parseDateValue(b.timestamp) ?? 0;

        switch (filters.sort) {
            case "oldest":
                return aTime - bTime;
            case "title_asc":
                return String(a.title ?? "").localeCompare(String(b.title ?? ""));
            case "title_desc":
                return String(b.title ?? "").localeCompare(String(a.title ?? ""));
            default:
                return bTime - aTime;
        }
    });

    return filtered;
}

function filterPendingDeleteRequestsData(requests, filters) {
    const keyword = filters.keyword.toLowerCase();
    const fromTime = filters.from ? new Date(`${filters.from}T00:00:00`).getTime() : null;
    const toTime = filters.to ? new Date(`${filters.to}T23:59:59`).getTime() : null;

    let filtered = Array.isArray(requests) ? requests.filter((request) => {
        if (Number(request.status) !== activeFeedStatusFilter) {
            return false;
        }

        if (!matchesStartsWithKeyword(keyword, [request.title, request.reason])) {
            return false;
        }

        const createdTime = parseDateValue(request.timestamp);
        if (fromTime !== null && (createdTime === null || createdTime < fromTime)) {
            return false;
        }

        if (toTime !== null && (createdTime === null || createdTime > toTime)) {
            return false;
        }

        return true;
    }) : [];

    filtered.sort((a, b) => {
        const aTime = parseDateValue(a.timestamp) ?? 0;
        const bTime = parseDateValue(b.timestamp) ?? 0;
        return filters.sort === "oldest" ? aTime - bTime : bTime - aTime;
    });

    return filtered;
}

function filterReportsData(reports, filters) {
    const keyword = filters.keyword.toLowerCase();
    const fromTime = filters.from ? new Date(`${filters.from}T00:00:00`).getTime() : null;
    const toTime = filters.to ? new Date(`${filters.to}T23:59:59`).getTime() : null;

    let filtered = Array.isArray(reports) ? reports.filter((report) => {
        if (Number(report.status) !== activeFeedStatusFilter) {
            return false;
        }

        if (!matchesStartsWithKeyword(keyword, [report.post_title, report.reason])) {
            return false;
        }

        const createdTime = parseDateValue(report.created);
        if (fromTime !== null && (createdTime === null || createdTime < fromTime)) {
            return false;
        }

        if (toTime !== null && (createdTime === null || createdTime > toTime)) {
            return false;
        }

        return true;
    }) : [];

    filtered.sort((a, b) => {
        const aTime = parseDateValue(a.created) ?? 0;
        const bTime = parseDateValue(b.created) ?? 0;
        return filters.sort === "oldest" ? aTime - bTime : bTime - aTime;
    });

    return filtered;
}

function renderPendingPosts(posts) {
    const container = document.getElementById("postsList");
    if (!container) return;

    container.innerHTML = "";

    if (!Array.isArray(posts) || posts.length === 0) {
        container.innerHTML = `<div class="pending-state">${escapeHtml(translate("posts.no_posts_found", "No posts found."))}</div>`;
        return;
    }

    posts.forEach((post) => {
        const card = document.createElement("article");
        card.className = "pending-card";

        const createdAt = post.timestamp ? new Date(post.timestamp).toLocaleString() : "Unknown date";
        const status = getPostStatusInfo(post.status, post.deleted);
        const rejectionReason = String(post.rejection_reason || "").trim();
        const deleteButtonHtml = Number(post.status) === 2
            ? `<button type="button" class="rejected-post-hard-delete-btn" data-hard-delete-rejected-post="1" data-post-id="${escapeHtml(post.post_id)}" aria-label="${escapeHtml(translate("posts.delete_rejected_post", "Delete Rejected Post"))}" title="${escapeHtml(translate("posts.delete_permanently", "Delete permanently"))}">x</button>`
            : "";

        card.innerHTML = `
            ${deleteButtonHtml}
            <h3>
                <a class="feed-title-link" href="post.php?id=${encodeURIComponent(post.post_id)}">
                    ${escapeHtml(post.title || "Untitled post")}
                </a>
            </h3>
            <div class="pending-meta">
                <span class="pending-chip">${escapeHtml(post.category || "General")}</span>
                <span class="status-chip ${status.className}">${escapeHtml(status.text)}</span>
                <span>${escapeHtml(createdAt)}</span>
            </div>
            ${Number(post.status) === 2 ? `<div class="pending-content">rejection reason: ${escapeHtml(rejectionReason || "-")}</div>` : ""}
        `;

        container.appendChild(card);
    });
}

function toggleRejectedPostDeleteDialog(show) {
    const dialog = document.getElementById("rejectedPostDeleteDialog");
    if (!dialog) return;

    dialog.hidden = !show;
    document.body.classList.toggle("comment-dialog-open", show);

    if (!show) {
        rejectedPostDeleteId = null;
    }
}

function setupRejectedPostDeleteDialog() {
    const dialog = document.getElementById("rejectedPostDeleteDialog");
    if (!dialog) return;

    document.addEventListener("click", async (event) => {
        const openButton = event.target.closest("[data-hard-delete-rejected-post][data-post-id]");
        if (openButton) {
            rejectedPostDeleteId = openButton.dataset.postId || null;
            if (!rejectedPostDeleteId) {
                return;
            }

            toggleRejectedPostDeleteDialog(true);
            return;
        }

        if (event.target.id === "rejectedPostDeleteCancel") {
            toggleRejectedPostDeleteDialog(false);
            return;
        }

        if (event.target.id === "rejectedPostDeleteConfirm") {
            if (!rejectedPostDeleteId) {
                toggleRejectedPostDeleteDialog(false);
                return;
            }

            const postIdToDelete = Number(rejectedPostDeleteId);

            try {
                const { ok, data } = await fetchJSON(`${BASE_URL}?action=hardDeleteRejected`, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json"
                    },
                    body: JSON.stringify({
                        post_id: postIdToDelete
                    })
                });

                if (!ok) {
                    throw new Error(data.message || "Could not delete rejected post.");
                }

                toggleRejectedPostDeleteDialog(false);
                await loadPendingPostsFeed();
            } catch (error) {
                console.error("Rejected post hard delete failed:", error);
                alert(error.message || "Could not delete rejected post.");
            }
            return;
        }

        if (event.target === dialog) {
            toggleRejectedPostDeleteDialog(false);
        }
    });

    document.addEventListener("keydown", (event) => {
        if (event.key === "Escape" && !dialog.hidden) {
            toggleRejectedPostDeleteDialog(false);
        }
    });
}

function renderPendingDeleteRequests(requests) {
    const container = document.getElementById("postsList");
    if (!container) return;

    container.innerHTML = "";

    if (!Array.isArray(requests) || requests.length === 0) {
        container.innerHTML = `<div class="pending-state">${escapeHtml(translate("posts.no_delete_requests", "No delete requests found."))}</div>`;
        return;
    }

    requests.forEach((request) => {
        const card = document.createElement("article");
        card.className = "pending-card";

        const createdAt = request.timestamp ? new Date(request.timestamp).toLocaleString() : "Unknown date";
        const requestStatus = getRequestStatusInfo(request.status);
        const postStatus = getPostStatusInfo(request.post_status, request.deleted);

        card.innerHTML = `
            <h3>
                <a class="feed-title-link" href="post.php?id=${encodeURIComponent(request.post_id)}">
                    ${escapeHtml(request.title || "Untitled post")}
                </a>
            </h3>
            <div class="pending-meta">
                <span class="status-chip ${requestStatus.className}">Request: ${escapeHtml(requestStatus.text)}</span>
                <span class="status-chip ${postStatus.className}">Post: ${escapeHtml(postStatus.text)}</span>
                <span>${escapeHtml(createdAt)}</span>
            </div>
            <div class="pending-content"><strong>Reason:</strong> ${escapeHtml(request.reason || "-")}</div>
        `;

        container.appendChild(card);
    });
}

function renderReports(reports) {
    const container = document.getElementById("postsList");
    if (!container) return;

    container.innerHTML = "";

    if (!Array.isArray(reports) || reports.length === 0) {
        container.innerHTML = `<div class="pending-state">${escapeHtml(translate("posts.no_reports", "No reports found."))}</div>`;
        return;
    }

    reports.forEach((report) => {
        const card = document.createElement("article");
        card.className = "pending-card";

        const createdAt = report.created ? new Date(report.created).toLocaleString() : "Unknown date";
        const reportStatus = getRequestStatusInfo(report.status);
        const postDeleted = Number(report.deleted) === 1;
        const showPostStatus = Number(report.status) !== 2;
        const postStatus = postDeleted
            ? { text: translate("posts.removed", "Removed"), className: "rejected" }
            : { text: translate("posts.visible", "Visible"), className: "approved" };
        const title = report.post_title || (report.post_id ? `Post #${report.post_id}` : "Post unavailable");
        const link = report.post_id
            ? `<a class="feed-title-link" href="post.php?id=${encodeURIComponent(report.post_id)}">${escapeHtml(title)}</a>`
            : escapeHtml(title);

        card.innerHTML = `
            <h3>${link}</h3>
            <div class="pending-meta">
                <span class="status-chip ${reportStatus.className}">Report: ${escapeHtml(reportStatus.text)}</span>
                ${showPostStatus ? `<span class="status-chip ${postStatus.className}">Post: ${escapeHtml(postStatus.text)}</span>` : ""}
                <span>${escapeHtml(createdAt)}</span>
            </div>
            <div class="pending-content"><strong>Reason:</strong> ${escapeHtml(report.reason || "-")}</div>
        `;

        container.appendChild(card);
    });
}

async function loadInterestsBanner() {
    const banner = document.getElementById("interestsBanner");
    if (!banner) return;

    try {
        const { ok, data } = await fetchJSON(`${CAT_URL}?action=userInterests`);

        if (!ok || !Array.isArray(data) || data.length === 0) {
            banner.innerHTML = `
                <div class="interests-banner no-interests">
                    <div class="no-interests-text">${escapeHtml(translate("posts.no_interests_selected", "You have not selected any interests yet."))}</div>
                    <div class="no-interests-sub">${escapeHtml(translate("posts.showing_all_posts", "Showing all posts."))}</div>
                </div>`;
            return;
        }

        const chips = data
            .map((interest) => `<span class="pending-chip">${escapeHtml(interest.name)}</span>`)
            .join("");

        banner.innerHTML = `
            <div class="interests-banner">
                <div class="interests-label" data-i18n="posts.your_interests">Your interests</div>
                <div class="interests-chips">${chips}</div>
            </div>`;
        window.UniSupportI18n?.applyTranslations?.(banner);
    } catch (error) {
        console.error("Could not load interests:", error);
    }
}

async function loadFollowersBanner() {
    const banner = document.getElementById("interestsBanner");
    if (!banner) return;

    try {
        const [followingResponse, followersResponse] = await Promise.all([
            fetchJSON(`${FOLLOW_URL}?action=followingList`),
            fetchJSON(`${FOLLOW_URL}?action=followersList`)
        ]);

        const followingUsers = followingResponse.ok && Array.isArray(followingResponse.data)
            ? followingResponse.data
            : [];
        const followersUsers = followersResponse.ok && Array.isArray(followersResponse.data)
            ? followersResponse.data
            : [];

        followedUserIds.clear();
        followingUsers.forEach((user) => {
            const userId = Number(user.user_id);
            if (Number.isInteger(userId) && userId > 0) {
                followedUserIds.add(userId);
            }
        });

        const unfollowTitle = translate("posts.click_to_unfollow", "Click to unfollow");
        const unfollowLabel = translate("posts.unfollow_short", "Unfollow");

        const followingChips = followingUsers
            .map((user) => {
                const userId = Number(user.user_id);
                return `<button type="button" class="follower-unfollow-trigger" data-unfollow-id="${userId}" title="${escapeHtml(unfollowTitle)}"><span class="follower-name">${escapeHtml(user.username)}</span><span class="follower-unfollow-hint">${escapeHtml(unfollowLabel)}</span></button>`;
            })
            .join("");

        const followersChips = followersUsers
            .map((user) => `<span class="follower-passive-chip"><span class="follower-name">${escapeHtml(user.username)}</span></span>`)
            .join("");

        banner.innerHTML = `
            <div class="followers-banners-grid">
                <div class="interests-banner${followingUsers.length === 0 ? " no-interests" : ""}">
                <div class="interests-label">${escapeHtml(translate("posts.following", "Following"))}</div>
                    ${followingUsers.length === 0
                        ? `<div class="no-interests-text">${escapeHtml(translate("posts.not_following_anyone", "You are not following anyone yet."))}</div>
                           <div class="no-interests-sub">${escapeHtml(translate("posts.follow_users_hint", "Follow users to see their posts here."))}</div>`
                        : `<div class="interests-chips">${followingChips}</div>`}
                </div>
                <div class="interests-banner${followersUsers.length === 0 ? " no-interests" : ""}">
                    <div class="interests-label">${escapeHtml(translate("posts.following_you", "Following you"))}</div>
                    ${followersUsers.length === 0
                        ? `<div class="no-interests-text">${escapeHtml(translate("posts.no_followers_yet", "No one is following you yet."))}</div>
                           <div class="no-interests-sub">${escapeHtml(translate("posts.followers_will_appear", "Your followers will appear here."))}</div>`
                        : `<div class="interests-chips">${followersChips}</div>`}
                </div>
            </div>`;
    } catch (error) {
        console.error("Could not load followers data:", error);
    }
}

function setupFollowersBannerActions() {
    const banner = document.getElementById("interestsBanner");
    if (!banner) return;

    banner.addEventListener("click", async (event) => {
        const button = event.target.closest("button[data-unfollow-id]");
        if (!button) return;

        const targetId = Number(button.getAttribute("data-unfollow-id"));
        if (!Number.isInteger(targetId) || targetId <= 0) {
            return;
        }

        const confirmed = await confirmUnfollowAction();
        if (!confirmed) {
            return;
        }

        button.disabled = true;

        try {
            const { ok, data } = await fetchJSON(`${FOLLOW_URL}?action=unfollow`, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({ user_id: targetId })
            });

            if (!ok) {
                if (data.message === "You do not follow this user") {
                    followedUserIds.delete(targetId);
                    await loadFollowerFilterOptions();
                    updateFollowerFilterLabel();

                    if (activeFeedMode === "followers") {
                        await loadFollowersBanner();
                        await loadFollowersFeed();
                    } else if (activeFeedMode === "default") {
                        await loadDefaultFeed();
                    }
                    return;
                }

                alert(data.message || "Could not unfollow user.");
                button.disabled = false;
                return;
            }

            followedUserIds.delete(targetId);
            await loadFollowerFilterOptions();
            updateFollowerFilterLabel();

            if (activeFeedMode === "followers") {
                await loadFollowersBanner();
                await loadFollowersFeed();
            } else if (activeFeedMode === "default") {
                await loadDefaultFeed();
            }
        } catch (error) {
            console.error("Unfollow request failed:", error);
            alert("Could not unfollow user.");
            button.disabled = false;
        }
    });
}

async function loadDefaultFeed(options = {}) {
    const container = document.getElementById("postsList");
    if (!container) return;

    const silent = Boolean(options.silent);

    if (!silent) {
        container.innerHTML = '<div class="pending-state">Loading posts...</div>';
    }

    try {
        const { ok, data: posts } = await fetchJSON(`${BASE_URL}?action=list`);

        if (!ok) {
            container.innerHTML = '<div class="pending-state">Could not load posts.</div>';
            return;
        }

        renderPosts(posts);
    } catch (error) {
        console.error("Error loading posts:", error);
        container.innerHTML = '<div class="pending-state">Failed to load posts.</div>';
    }
}

async function loadSearchResults(options = {}) {
    const container = document.getElementById("postsList");
    const banner = document.getElementById("interestsBanner");
    const keywordInput = document.getElementById("feedSearchKeyword");
    const categoryInput = document.getElementById("feedSearchCategory");
    const sortInput = document.getElementById("feedSearchSort");
    const fromInput = document.getElementById("feedSearchFrom");
    const toInput = document.getElementById("feedSearchTo");
    const selectedAuthorIds = selectedFollowerFilters.filter((value) => value !== "__all__");
    const followedOnly = selectedFollowerFilters.length > 0;
    const silent = Boolean(options.silent);

    if (!container || !keywordInput || !categoryInput || !sortInput || !fromInput || !toInput) {
        return;
    }

    const params = new URLSearchParams();
    const keyword = keywordInput.value.trim();
    const category = categoryInput.value;
    const sort = sortInput.value;
    const from = fromInput.value;
    const to = toInput.value;
    if (keyword) params.set("keyword", keyword);
    if (category) params.set("category", category);
    if (sort) params.set("sort", sort);
    if (from) params.set("from", from);
    if (to) params.set("to", to);
    if (followedOnly) params.set("followed_only", "1");
    if (selectedAuthorIds.length > 0) params.set("author_ids", selectedAuthorIds.join(","));

    activeFeedMode = "search";
    if (!silent) {
        container.innerHTML = '<div class="pending-state">Searching posts...</div>';
    }

    try {
        const { ok, data } = await fetchJSON(`${SEARCH_URL}?${params.toString()}`);

        if (!ok || !data.ok) {
            container.innerHTML = '<div class="pending-state">Could not load search results.</div>';
            return;
        }

        if (banner) {
            banner.innerHTML = `
                <div class="interests-banner">
                    <div class="interests-label">${escapeHtml(translate("posts.search_results", "Search Results"))}</div>
                    <div class="interests-chips">
                        <span class="pending-chip">${escapeHtml(`${data.count ?? 0} ${translate("posts.posts", "Posts").toLowerCase()} found`)}</span>
                    </div>
                </div>`;
        }

        renderPosts(Array.isArray(data.data) ? data.data : []);
    } catch (error) {
        console.error("Error loading search results:", error);
        container.innerHTML = `<div class="pending-state">${escapeHtml(translate("posts.failed_search_results", "Failed to load search results."))}</div>`;
    }
}

function updateFollowerFilterLabel() {
    const label = document.getElementById("feedSearchFollowersLabel");
    if (!label) return;

    if (selectedFollowerFilters.includes("__all__")) {
        label.textContent = translate("posts.all_followers", "All followers");
        return;
    }

    if (selectedFollowerFilters.length === 0) {
        label.textContent = translate("posts.followers_short", "Followers");
        return;
    }

    label.textContent = translate("posts.followers", "Followers");
}

async function loadFollowerFilterOptions() {
    const optionsContainer = document.getElementById("feedSearchFollowersOptions");
    if (!optionsContainer) return;

    try {
        const { ok, data: users } = await fetchJSON(`${FOLLOW_URL}?action=followingList`);

        if (!ok || !Array.isArray(users) || users.length === 0) {
            optionsContainer.innerHTML = `
                <label class="feed-search-followers-option">
                    <input type="checkbox" disabled>
                    <span>No followed users yet</span>
                </label>
            `;
            selectedFollowerFilters = [];
            updateFollowerFilterLabel();
            return;
        }

        optionsContainer.innerHTML = users.map((user) => `
            <label class="feed-search-followers-option">
                <input type="checkbox" value="${Number(user.user_id)}">
                <span>${escapeHtml(user.username)}</span>
            </label>
        `).join("");

        selectedFollowerFilters = ["__all__"];
        updateFollowerFilterLabel();
    } catch (error) {
        console.error("Could not load follower filter options:", error);
    }
}

async function loadFollowersFeed(options = {}) {
    const container = document.getElementById("postsList");
    if (!container) return;

    const silent = Boolean(options.silent);

    if (!silent) {
        container.innerHTML = '<div class="pending-state">Loading followers posts...</div>';
    }

    try {
        await refreshFollowingUsersState();
        const { ok, data: posts } = await fetchJSON(`${BASE_URL}?action=followingFeed`);

        if (!ok) {
            container.innerHTML = '<div class="pending-state">Could not load followers posts.</div>';
            return;
        }

        renderPosts(posts);
    } catch (error) {
        console.error("Error loading followers feed:", error);
        container.innerHTML = '<div class="pending-state">Failed to load followers posts.</div>';
    }
}

async function loadPendingPostsFeed(options = {}) {
    const container = document.getElementById("postsList");
    if (!container) return;

    const silent = Boolean(options.silent);
    const filters = options.filters || {
        keyword: "",
        category: "",
        from: "",
        to: "",
        sort: "newest"
    };

    if (!silent) {
        container.innerHTML = '<div class="pending-state">Loading your posts...</div>';
    }

    try {
        const { ok, data: posts } = await fetchJSON(`${BASE_URL}?action=myPosts`);

        if (!ok) {
            container.innerHTML = '<div class="pending-state">Could not load your posts.</div>';
            return;
        }

        cachedPendingPosts = Array.isArray(posts) ? posts : [];
        renderSimpleBanner("", "");
        renderPendingPosts(filterPendingPostsData(cachedPendingPosts, filters));
    } catch (error) {
        console.error("Error loading pending posts:", error);
        container.innerHTML = '<div class="pending-state">Failed to load your posts.</div>';
    }
}

async function loadPendingDeleteRequestsFeed(options = {}) {
    const container = document.getElementById("postsList");
    if (!container) return;

    const silent = Boolean(options.silent);
    const filters = options.filters || {
        keyword: "",
        from: "",
        to: "",
        sort: "newest"
    };

    if (!silent) {
        container.innerHTML = '<div class="pending-state">Loading delete requests...</div>';
    }

    try {
        const { ok, data: requests } = await fetchJSON(`${BASE_URL}?action=myDeleteRequests`);

        if (!ok) {
            container.innerHTML = '<div class="pending-state">Could not load delete requests.</div>';
            return;
        }

        cachedPendingDeleteRequests = Array.isArray(requests) ? requests : [];
        renderSimpleBanner("", "");
        renderPendingDeleteRequests(filterPendingDeleteRequestsData(cachedPendingDeleteRequests, filters));
    } catch (error) {
        console.error("Error loading pending delete requests:", error);
        container.innerHTML = '<div class="pending-state">Failed to load delete requests.</div>';
    }
}

async function loadReportsFeed(options = {}) {
    const container = document.getElementById("postsList");
    if (!container) return;

    const silent = Boolean(options.silent);
    const filters = options.filters || {
        keyword: "",
        from: "",
        to: "",
        sort: "newest"
    };

    if (!silent) {
        container.innerHTML = '<div class="pending-state">Loading reports...</div>';
    }

    try {
        const { ok, data: reports } = await fetchJSON(`${BASE_URL}?action=myReports`);

        if (!ok) {
            container.innerHTML = '<div class="pending-state">Could not load reports.</div>';
            return;
        }

        cachedReports = Array.isArray(reports) ? reports : [];
        renderSimpleBanner("", "");
        renderReports(filterReportsData(cachedReports, filters));
    } catch (error) {
        console.error("Error loading reports:", error);
        container.innerHTML = '<div class="pending-state">Failed to load reports.</div>';
    }
}

async function refreshActiveFeedWithoutRefresh() {
    const filters = getCurrentFeedSearchFilters();

    switch (activeFeedMode) {
        case "followers":
            await loadFollowersBanner();
            await loadFollowersFeed({ silent: true });
            break;
        case "pending-posts":
            await loadPendingPostsFeed({ silent: true, filters });
            break;
        case "pending-delete-requests":
            await loadPendingDeleteRequestsFeed({ silent: true, filters });
            break;
        case "reports":
            await loadReportsFeed({ silent: true, filters });
            break;
        case "search":
            await loadSearchResults({ silent: true });
            break;
        case "default":
        default:
            await loadInterestsBanner();
            await loadDefaultFeed({ silent: true });
            break;
    }
}

function startFeedAutoRefresh() {
    if (feedAutoRefreshTimerId !== null) {
        window.clearInterval(feedAutoRefreshTimerId);
    }

    const refresh = async () => {
        if (document.hidden || isFeedAutoRefreshInFlight) {
            return;
        }

        isFeedAutoRefreshInFlight = true;
        try {
            await Promise.all([
                loadNotifications(),
                refreshActiveFeedWithoutRefresh()
            ]);
        } finally {
            isFeedAutoRefreshInFlight = false;
        }
    };

    feedAutoRefreshTimerId = window.setInterval(refresh, 5000);

    document.addEventListener("visibilitychange", () => {
        if (!document.hidden) {
            refresh();
        }
    });
}

function setupFeedMenu() {
    const menu = document.getElementById("feedMenu");
    if (!menu) return;

    document.addEventListener("click", (event) => {
        if (!menu.open) return;
        if (!menu.contains(event.target)) {
            menu.open = false;
        }
    });
}

function setFeedTitle(titleElement, key, fallback) {
    if (!titleElement) {
        return;
    }

    const text = translate(key, fallback);
    const compactTitles = new Set([
        "posts.token_history",
        "posts.pending_posts",
        "posts.pending_delete_requests"
    ]);

    titleElement.dataset.i18n = key;
    titleElement.textContent = text;
    titleElement.classList.toggle("feed-topbar-title-compact", compactTitles.has(key));
    titleElement.classList.toggle("feed-topbar-title-xcompact", key === "posts.pending_delete_requests");
}

function setupFeedModeToggle() {
    const title = document.getElementById("feedTitle");
    const createPostButton = document.getElementById("createPostBtn");
    const tokenHistoryButton = document.getElementById("tokenHistoryBtn");
    const postsButton = document.getElementById("postsFeedBtn");
    const followersButton = document.getElementById("followersFeedBtn");
    const pendingPostsButton = document.getElementById("pendingPostsBtn");
    const pendingDeleteRequestsButton = document.getElementById("pendingDeleteRequestsBtn");
    const reportsButton = document.getElementById("reportsBtn");
    const searchPanel = document.getElementById("feedSearchForm");
    const statusFilters = document.getElementById("feedModerationStatusFilters");
    const followersFilter = document.getElementById("feedSearchFollowersFilter");
    const createPostPanel = document.getElementById("createPostPanel");
    const tokenHistoryPanel = document.getElementById("tokenHistoryPanel");
    const postsList = document.getElementById("postsList");
    const interestsBanner = document.getElementById("interestsBanner");
    const siteFooter = document.querySelector(".site-footer");

    if (!createPostButton || !tokenHistoryButton || !followersButton || !postsButton || !pendingPostsButton || !pendingDeleteRequestsButton || !reportsButton || !title) return;

    const setMode = (mode) => {
        activeFeedMode = mode;
        createPostButton.classList.toggle("is-active", mode === "create-post");
        tokenHistoryButton.classList.toggle("is-active", mode === "token-history");
        postsButton.classList.toggle("is-active", mode === "default" || mode === "search");
        followersButton.classList.toggle("is-active", mode === "followers");
        pendingPostsButton.classList.toggle("is-active", mode === "pending-posts");
        pendingDeleteRequestsButton.classList.toggle("is-active", mode === "pending-delete-requests");
        reportsButton.classList.toggle("is-active", mode === "reports");

        if (searchPanel) {
            const showSearchPanel = mode === "default" || mode === "search" || mode === "followers" || mode === "pending-posts" || mode === "pending-delete-requests" || mode === "reports";
            searchPanel.hidden = !showSearchPanel;
            searchPanel.style.display = showSearchPanel ? "flex" : "none";
        }

        if (statusFilters) {
            const showStatusFilters = mode === "pending-posts" || mode === "pending-delete-requests" || mode === "reports";
            statusFilters.hidden = !showStatusFilters;
            statusFilters.style.display = showStatusFilters ? "inline-flex" : "none";
        }

        if (followersFilter) {
            const showFollowersFilter = mode === "default" || mode === "search" || mode === "followers";
            followersFilter.hidden = !showFollowersFilter;
            followersFilter.style.display = showFollowersFilter ? "block" : "none";
        }

        if (createPostPanel) {
            createPostPanel.hidden = mode !== "create-post";
        }

        if (tokenHistoryPanel) {
            tokenHistoryPanel.hidden = mode !== "token-history";
        }

        if (postsList) {
            postsList.hidden = mode === "create-post" || mode === "token-history";
        }

        if (interestsBanner) {
            interestsBanner.hidden = mode === "create-post" || mode === "token-history";
        }

        if (siteFooter) {
            siteFooter.hidden = mode === "create-post" || mode === "token-history";
        }
    };

    setMode("default");

    createPostButton.addEventListener("click", () => {
        if (activeFeedMode === "create-post") {
            return;
        }

        setMode("create-post");
        setFeedTitle(title, "posts.create_post", "Create Post");
    });

    tokenHistoryButton.addEventListener("click", () => {
        if (activeFeedMode === "token-history") {
            return;
        }

        setMode("token-history");
        setFeedTitle(title, "posts.token_history", "Token History");
    });

    postsButton.addEventListener("click", async () => {
        if (activeFeedMode === "default") {
            return;
        }

        setMode("default");
        setFeedTitle(title, "posts.posts_feed", "Posts Feed");

        await loadInterestsBanner();
        await loadDefaultFeed();
    });

    followersButton.addEventListener("click", async () => {
        if (activeFeedMode === "followers") {
            return;
        }

        setMode("followers");
        setFeedTitle(title, "posts.following", "Following");

        await loadFollowersBanner();
        await loadFollowersFeed();
    });

    pendingPostsButton.addEventListener("click", async () => {
        if (activeFeedMode === "pending-posts") {
            return;
        }

        consumeInitialStatusForMode("pending-posts");
        updateFeedStatusButtons();
        setMode("pending-posts");
        setFeedTitle(title, "posts.pending_posts", "Pending Posts");
        await loadPendingPostsFeed();
    });

    pendingDeleteRequestsButton.addEventListener("click", async () => {
        if (activeFeedMode === "pending-delete-requests") {
            return;
        }

        consumeInitialStatusForMode("pending-delete-requests");
        updateFeedStatusButtons();
        setMode("pending-delete-requests");
        setFeedTitle(title, "posts.pending_delete_requests", "Pending Delete Requests");
        await loadPendingDeleteRequestsFeed();
    });

    reportsButton.addEventListener("click", async () => {
        if (activeFeedMode === "reports") {
            return;
        }

        consumeInitialStatusForMode("reports");
        updateFeedStatusButtons();
        setMode("reports");
        setFeedTitle(title, "posts.reports", "Reports");
        await loadReportsFeed();
    });
}

function setupSearchControls() {
    const form = document.getElementById("feedSearchForm");
    const clearButton = document.getElementById("feedSearchClear");
    const keywordInput = document.getElementById("feedSearchKeyword");
    const fromInput = document.getElementById("feedSearchFrom");
    const toInput = document.getElementById("feedSearchTo");
    const title = document.getElementById("feedTitle");
    const filtersToggle = document.getElementById("feedSearchFiltersToggle");
    const advancedFilters = document.getElementById("feedSearchAdvanced");
    const followersToggle = document.getElementById("feedSearchFollowersToggle");
    const followersMenu = document.getElementById("feedSearchFollowersMenu");
    const followersFilter = document.getElementById("feedSearchFollowersFilter");
    const statusButtons = document.querySelectorAll(".feed-status-filter[data-feed-status]");

    if (!form || !clearButton || !title) {
        return;
    }

    const minimumFilterDate = "2026-01-01";
    [fromInput, toInput].forEach((input) => {
        if (!input) {
            return;
        }

        input.min = minimumFilterDate;

        if (input.value && input.value < minimumFilterDate) {
            input.value = "";
        }
    });

    if (filtersToggle && advancedFilters) {
        filtersToggle.addEventListener("click", () => {
            const shouldOpen = advancedFilters.hidden;
            advancedFilters.hidden = !shouldOpen;
            filtersToggle.setAttribute("aria-expanded", shouldOpen ? "true" : "false");

            if (!shouldOpen && followersMenu) {
                followersMenu.hidden = true;
            }

            if (!shouldOpen && followersToggle) {
                followersToggle.setAttribute("aria-expanded", "false");
            }
        });
    }

    form.addEventListener("submit", async (event) => {
        event.preventDefault();
        const keyword = document.getElementById("feedSearchKeyword")?.value.trim() || "";

        if (activeFeedMode === "pending-posts") {
            renderPendingPosts(filterPendingPostsData(cachedPendingPosts, {
                keyword,
                category: document.getElementById("feedSearchCategory")?.value || "",
                from: document.getElementById("feedSearchFrom")?.value || "",
                to: document.getElementById("feedSearchTo")?.value || "",
                sort: document.getElementById("feedSearchSort")?.value || "newest"
            }));
            return;
        }

        if (activeFeedMode === "pending-delete-requests") {
            renderPendingDeleteRequests(filterPendingDeleteRequestsData(cachedPendingDeleteRequests, {
                keyword,
                from: document.getElementById("feedSearchFrom")?.value || "",
                to: document.getElementById("feedSearchTo")?.value || "",
                sort: document.getElementById("feedSearchSort")?.value || "newest"
            }));
            return;
        }

        if (activeFeedMode === "reports") {
            renderReports(filterReportsData(cachedReports, {
                keyword,
                from: document.getElementById("feedSearchFrom")?.value || "",
                to: document.getElementById("feedSearchTo")?.value || "",
                sort: document.getElementById("feedSearchSort")?.value || "newest"
            }));
            return;
        }

        previousSearchMode = activeFeedMode === "search" ? previousSearchMode : activeFeedMode;
        setFeedTitle(title, "posts.search_results", "Search Results");
        await loadSearchResults();
    });

    if (keywordInput) {
        keywordInput.addEventListener("input", () => {
            clearTimeout(feedSearchInputDebounceId);
            feedSearchInputDebounceId = window.setTimeout(async () => {
                const keyword = keywordInput.value.trim();

                if (activeFeedMode === "pending-posts") {
                    renderPendingPosts(filterPendingPostsData(cachedPendingPosts, {
                        keyword,
                        category: document.getElementById("feedSearchCategory")?.value || "",
                        from: document.getElementById("feedSearchFrom")?.value || "",
                        to: document.getElementById("feedSearchTo")?.value || "",
                        sort: document.getElementById("feedSearchSort")?.value || "newest"
                    }));
                    return;
                }

                if (activeFeedMode === "pending-delete-requests") {
                    renderPendingDeleteRequests(filterPendingDeleteRequestsData(cachedPendingDeleteRequests, {
                        keyword,
                        from: document.getElementById("feedSearchFrom")?.value || "",
                        to: document.getElementById("feedSearchTo")?.value || "",
                        sort: document.getElementById("feedSearchSort")?.value || "newest"
                    }));
                    return;
                }

                if (activeFeedMode === "reports") {
                    renderReports(filterReportsData(cachedReports, {
                        keyword,
                        from: document.getElementById("feedSearchFrom")?.value || "",
                        to: document.getElementById("feedSearchTo")?.value || "",
                        sort: document.getElementById("feedSearchSort")?.value || "newest"
                    }));
                    return;
                }

                previousSearchMode = activeFeedMode === "search" ? previousSearchMode : activeFeedMode;
                setFeedTitle(title, "posts.search_results", "Search Results");
                await loadSearchResults({ silent: true });
            }, 250);
        });
    }

    clearButton.addEventListener("click", async () => {
        clearTimeout(feedSearchInputDebounceId);
        form.reset();
        selectedFollowerFilters = ["__all__"];

        const allOption = followersMenu?.querySelector('input[value="__all__"]');
        if (allOption) {
            allOption.checked = true;
        }

        followersMenu?.querySelectorAll('#feedSearchFollowersOptions input[type="checkbox"]').forEach((input) => {
            input.checked = false;
        });

        if (followersMenu) {
            followersMenu.hidden = true;
        }

        if (followersToggle) {
            followersToggle.setAttribute("aria-expanded", "false");
        }

        if (filtersToggle && advancedFilters) {
            advancedFilters.hidden = true;
            filtersToggle.setAttribute("aria-expanded", "false");
        }

        updateFollowerFilterLabel();

        if (previousSearchMode === "followers") {
            setFeedTitle(title, "posts.following", "Following");
            await loadFollowersBanner();
            await loadFollowersFeed();
            return;
        }

        if (activeFeedMode === "pending-posts") {
            activeFeedStatusFilter = 0;
            updateFeedStatusButtons();
            setFeedTitle(title, "posts.pending_posts", "Pending Posts");
            await loadPendingPostsFeed();
            return;
        }

        if (activeFeedMode === "pending-delete-requests") {
            activeFeedStatusFilter = 0;
            updateFeedStatusButtons();
            setFeedTitle(title, "posts.pending_delete_requests", "Pending Delete Requests");
            await loadPendingDeleteRequestsFeed();
            return;
        }

        if (activeFeedMode === "reports") {
            activeFeedStatusFilter = 0;
            updateFeedStatusButtons();
            setFeedTitle(title, "posts.reports", "Reports");
            await loadReportsFeed();
            return;
        }

        setFeedTitle(title, "posts.posts_feed", "Posts Feed");
        await loadInterestsBanner();
        await loadDefaultFeed();
    });

    statusButtons.forEach((button) => {
        button.addEventListener("click", async () => {
            activeFeedStatusFilter = Number(button.dataset.feedStatus);
            updateFeedStatusButtons();

            if (activeFeedMode === "pending-posts") {
                renderPendingPosts(filterPendingPostsData(cachedPendingPosts, {
                    keyword: document.getElementById("feedSearchKeyword")?.value.trim() || "",
                    category: document.getElementById("feedSearchCategory")?.value || "",
                    from: document.getElementById("feedSearchFrom")?.value || "",
                    to: document.getElementById("feedSearchTo")?.value || "",
                    sort: document.getElementById("feedSearchSort")?.value || "newest"
                }));
                return;
            }

            if (activeFeedMode === "pending-delete-requests") {
                renderPendingDeleteRequests(filterPendingDeleteRequestsData(cachedPendingDeleteRequests, {
                    keyword: document.getElementById("feedSearchKeyword")?.value.trim() || "",
                    from: document.getElementById("feedSearchFrom")?.value || "",
                    to: document.getElementById("feedSearchTo")?.value || "",
                    sort: document.getElementById("feedSearchSort")?.value || "newest"
                }));
                return;
            }

            if (activeFeedMode === "reports") {
                renderReports(filterReportsData(cachedReports, {
                    keyword: document.getElementById("feedSearchKeyword")?.value.trim() || "",
                    from: document.getElementById("feedSearchFrom")?.value || "",
                    to: document.getElementById("feedSearchTo")?.value || "",
                    sort: document.getElementById("feedSearchSort")?.value || "newest"
                }));
            }
        });
    });

    if (followersToggle && followersMenu && followersFilter) {
        followersToggle.addEventListener("click", () => {
            const shouldOpen = followersMenu.hidden;
            followersMenu.hidden = !shouldOpen;
            followersToggle.setAttribute("aria-expanded", shouldOpen ? "true" : "false");
        });

        followersMenu.addEventListener("change", (event) => {
            const input = event.target.closest('input[type="checkbox"]');
            if (!input) return;

            const allOption = followersMenu.querySelector('input[value="__all__"]');
            const userOptions = followersMenu.querySelectorAll('#feedSearchFollowersOptions input[type="checkbox"]');

            if (input.value === "__all__") {
                if (input.checked) {
                    selectedFollowerFilters = ["__all__"];
                    userOptions.forEach((option) => {
                        option.checked = false;
                    });
                } else {
                    selectedFollowerFilters = [];
                }
            } else {
                if (allOption) {
                    allOption.checked = false;
                }

                selectedFollowerFilters = Array.from(userOptions)
                    .filter((option) => option.checked)
                    .map((option) => option.value);

                if (selectedFollowerFilters.length === 0 && allOption) {
                    allOption.checked = true;
                    selectedFollowerFilters = ["__all__"];
                }
            }

            updateFollowerFilterLabel();
        });

        document.addEventListener("click", (event) => {
            if (!followersFilter.contains(event.target)) {
                followersMenu.hidden = true;
                followersToggle.setAttribute("aria-expanded", "false");
            }
        });
    }
}

function confirmFollowAction() {
    return new Promise((resolve) => {
        const dialog = document.createElement("div");
        dialog.className = "comment-policy-dialog";
        dialog.innerHTML = `
            <div class="comment-policy-card" role="dialog" aria-modal="true" aria-labelledby="followConfirmTitle">
                <h4 id="followConfirmTitle">Confirm Follow</h4>
                <p>Are you sure you want to follow this user?</p>
                <div class="comment-policy-actions">
                    <button type="button" class="policy-link cancel" data-follow-confirm="cancel">Cancel</button>
                    <button type="button" class="policy-link accept" data-follow-confirm="accept">Accept</button>
                </div>
            </div>
        `;

        const cleanup = (result) => {
            dialog.remove();
            document.body.classList.remove("comment-dialog-open");
            resolve(result);
        };

        dialog.addEventListener("click", (event) => {
            const actionButton = event.target.closest("[data-follow-confirm]");
            if (!actionButton) {
                if (event.target === dialog) {
                    cleanup(false);
                }
                return;
            }

            cleanup(actionButton.getAttribute("data-follow-confirm") === "accept");
        });

        document.body.appendChild(dialog);
        document.body.classList.add("comment-dialog-open");
    });
}

function setupFollowActions() {
    const container = document.getElementById("postsList");
    if (!container) return;

    container.addEventListener("click", async (event) => {
        const button = event.target.closest("button.post-owner-trigger[data-follow-id]");
        if (!button) return;

        const targetId = Number(button.getAttribute("data-follow-id"));
        if (!Number.isInteger(targetId) || targetId <= 0) {
            return;
        }

        if (targetId === Number(currentUserId)) {
            alert("You cannot follow yourself.");
            return;
        }

        const confirmed = await confirmFollowAction();
        if (!confirmed) {
            return;
        }

        button.disabled = true;

        try {
            const { ok, data } = await fetchJSON(`${FOLLOW_URL}?action=follow`, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({ user_id: targetId })
            });

            if (!ok) {
                if (data.message === "You already follow this user") {
                    followedUserIds.add(targetId);
                    replaceOwnerTriggersWithPlainName(targetId);
                    await loadFollowerFilterOptions();
                    updateFollowerFilterLabel();

                    if (activeFeedMode === "followers") {
                        await loadFollowersBanner();
                    }
                    return;
                }

                alert(data.message || "Could not follow user.");
                return;
            }

            button.textContent = "Following";
            button.classList.add("is-following");
            button.disabled = true;
            button.removeAttribute("data-follow-id");

            followedUserIds.add(targetId);
            replaceOwnerTriggersWithPlainName(targetId);
            await loadFollowerFilterOptions();
            updateFollowerFilterLabel();
        } catch (error) {
            console.error("Follow request failed:", error);
            alert("Could not follow user.");
        } finally {
            if (!button.classList.contains("is-following")) {
                button.disabled = false;
            }
        }
    });
}

function setupSidebarToggle() {
    const layout = document.querySelector(".feed-dashboard-layout");
    const sidebar = document.getElementById("feedSidebar");
    const toggle = document.getElementById("feedSidebarToggle");
    const label = toggle?.querySelector(".feed-sidebar-toggle-label");

    if (!layout || !sidebar || !toggle) {
        return;
    }

    const applySidebarState = (collapsed) => {
        layout.classList.toggle("sidebar-collapsed", collapsed);
        toggle.setAttribute("aria-expanded", String(!collapsed));
        toggle.setAttribute("aria-label", collapsed ? translate("common.show_menu", "Show menu") : translate("common.hide_menu", "Hide menu"));
        toggle.setAttribute("title", collapsed ? translate("common.show_menu", "Show menu") : translate("common.hide_menu", "Hide menu"));
        if (label) {
            label.textContent = collapsed ? translate("common.show_menu", "Show menu") : translate("common.hide_menu", "Hide menu");
        }
    };

    applySidebarState(window.localStorage.getItem(FEED_SIDEBAR_STORAGE_KEY) === "1");

    toggle.addEventListener("click", () => {
        const collapsed = !layout.classList.contains("sidebar-collapsed");
        applySidebarState(collapsed);
        window.localStorage.setItem(FEED_SIDEBAR_STORAGE_KEY, collapsed ? "1" : "0");
    });
}

window.addEventListener("unisupport:languagechange", () => {
    const title = document.getElementById("feedTitle");
    const key = title?.dataset?.i18n;
    if (title && key) {
        setFeedTitle(title, key, title.textContent || "");
    }

    updateFollowerFilterLabel();

    const toggle = document.getElementById("feedSidebarToggle");
    if (toggle) {
        const layout = document.querySelector(".feed-dashboard-layout");
        const collapsed = layout?.classList.contains("sidebar-collapsed") ?? false;
        toggle.setAttribute("aria-label", collapsed ? translate("common.show_menu", "Show menu") : translate("common.hide_menu", "Hide menu"));
        toggle.setAttribute("title", collapsed ? translate("common.show_menu", "Show menu") : translate("common.hide_menu", "Hide menu"));
        const label = toggle.querySelector(".feed-sidebar-toggle-label");
        if (label) {
            label.textContent = collapsed ? translate("common.show_menu", "Show menu") : translate("common.hide_menu", "Hide menu");
        }
    }

    // Ξανα-φορτώνουμε το banner ενδιαφερόντων / ακολούθων ώστε το κείμενο
    // να εμφανίζεται αμέσως στη νέα γλώσσα αντί να περιμένει refresh ή αλλαγή tab
    const interestsBanner = document.getElementById("interestsBanner");
    if (interestsBanner && !interestsBanner.hidden) {
        if (activeFeedMode === "followers") {
            loadFollowersBanner();
        } else {
            loadInterestsBanner();
        }
    }
});

function setupTokenHistoryFilters() {
    const filterButtons = document.querySelectorAll(".history-filter-btn");
    const rows = document.querySelectorAll(".history-table tbody tr[data-filter-group]");
    const emptyState = document.getElementById("historyEmptyFilter");

    if (!filterButtons.length || !rows.length || !emptyState) {
        return;
    }

    filterButtons.forEach((button) => {
        button.addEventListener("click", () => {
            const filter = button.dataset.filter || "all";
            let visibleCount = 0;

            filterButtons.forEach((item) => item.classList.toggle("is-active", item === button));

            rows.forEach((row) => {
                const matches = filter === "all" || row.dataset.filterGroup === filter;
                row.hidden = !matches;
                if (matches) {
                    visibleCount += 1;
                }
            });

            emptyState.classList.toggle("is-visible", visibleCount === 0);
        });
    });
}

function setupInfoDialog() {
    const toggleButton = document.getElementById("infoToggleBtn");
    const dialog = document.getElementById("infoDialog");
    const closeButton = document.getElementById("infoDialogClose");

    if (!toggleButton || !dialog || !closeButton) {
        return;
    }

    const setOpen = (shouldOpen) => {
        if (shouldOpen) {
            dialog.hidden = false;
            requestAnimationFrame(() => dialog.classList.add("is-open"));
        } else {
            dialog.classList.remove("is-open");
            window.setTimeout(() => {
                if (!dialog.classList.contains("is-open")) {
                    dialog.hidden = true;
                }
            }, 220);
        }

        toggleButton.setAttribute("aria-expanded", shouldOpen ? "true" : "false");
        document.body.classList.toggle("info-dialog-open", shouldOpen);
    };

    toggleButton.addEventListener("click", () => {
        setOpen(dialog.hidden);
    });

    closeButton.addEventListener("click", () => {
        setOpen(false);
    });

    dialog.addEventListener("click", (event) => {
        if (event.target instanceof HTMLElement && event.target.hasAttribute("data-info-close")) {
            setOpen(false);
        }
    });

    document.addEventListener("keydown", (event) => {
        if (event.key === "Escape" && !dialog.hidden) {
            setOpen(false);
        }
    });
}

function setupDailyDownloadNotice() {
    return;
}

document.addEventListener("DOMContentLoaded", () => {
    const feedTargetFromUrl = getFeedTargetFromUrl();

    if (feedTargetFromUrl !== null) {
        initialFeedTargetMode = feedTargetFromUrl.mode;
        initialFeedStatusFilter = feedTargetFromUrl.status;
        activeFeedStatusFilter = feedTargetFromUrl.status;
    }

    setupNotificationsUI();
    loadNotifications();
    setupFeedMenu();
    setupRejectedPostDeleteDialog();
    setupSidebarToggle();
    setupTokenHistoryFilters();
    setupFeedModeToggle();
    setupSearchControls();
    setupInfoDialog();
    setupDailyDownloadNotice();
    setupFollowActions();
    setupFollowersBannerActions();
    startFeedAutoRefresh();

    refreshFollowingUsersState().finally(async () => {
        await loadFollowerFilterOptions();
        updateFollowerFilterLabel();

        if (feedTargetFromUrl !== null) {
            if (feedTargetFromUrl.mode === "reports") {
                document.getElementById("reportsBtn")?.click();
                return;
            }

            if (feedTargetFromUrl.mode === "pending-delete-requests") {
                document.getElementById("pendingDeleteRequestsBtn")?.click();
                return;
            }

            document.getElementById("pendingPostsBtn")?.click();
            return;
        }

        loadInterestsBanner();
        loadDefaultFeed();
    });
});
