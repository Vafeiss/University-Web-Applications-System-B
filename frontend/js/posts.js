const BASE_URL = "http://localhost/University-Web-Applications-System-B/backend/controllers/PostController.php";
const CAT_URL = "http://localhost/University-Web-Applications-System-B/backend/controllers/CategoryController.php";
const FOLLOW_URL = "http://localhost/University-Web-Applications-System-B/backend/controllers/FollowController.php";
const NOTIFICATION_URL = "http://localhost/University-Web-Applications-System-B/backend/controllers/NotificationController.php";
const SEARCH_URL = "http://localhost/University-Web-Applications-System-B/backend/controllers/search_controllers.php";
let activeFeedMode = "default";
let previousSearchMode = "default";
const followedUserIds = new Set();
let selectedFollowerFilters = ["__all__"];

function getAuthorName(post) {
    if (post.is_anonymous == 1 && !isAdmin) {
        return "Anonymous";
    }

    if (post.is_anonymous == 1 && isAdmin) {
        return `Anonymous (${escapeHtml(post.username)})`;
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
        container.innerHTML = '<div class="pending-state">No posts available yet.</div>';
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
            ? `<button type="button" class="post-owner-trigger" data-follow-id="${postOwnerId}" title="Click to follow user"><span class="owner-name">${authorName}</span><span class="owner-follow-hint">+ Follow</span></button>`
            : `<span>${authorName}</span>`;

        const createdAt = post.timestamp ? new Date(post.timestamp).toLocaleString() : "Unknown date";
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
        list.innerHTML = '<div class="notifications-empty">No notifications yet.</div>';
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

        const createdAt = notification.created_at
            ? new Date(notification.created_at).toLocaleString()
            : "";

        item.innerHTML = `
            <div class="notification-text">${escapeHtml(notification.message || "")}</div>
            <div class="notification-time">${escapeHtml(createdAt)}</div>
        `;

        list.appendChild(item);
    });
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
// Σημειώνει όλες τις ειδοποιήσεις του χρήστη ως αναγνωσμένες στον server και ενημερώνει την εμφάνιση τους στο dropdown menu
async function markAllNotificationsRead() {
    const { ok } = await fetchJSON(`${NOTIFICATION_URL}?action=markAllRead`, {
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
// αν πατήσεις mark all as read, καθαρίζει το unread count και τα μηνύματα γίνονται read
function setupNotificationsUI() {
    const btn = document.getElementById("notificationsBtn");
    const dropdown = document.getElementById("notificationsDropdown");
    const markAllBtn = document.getElementById("markAllNotificationsRead");
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
        const item = event.target.closest(".notification-item");
        if (!item) {
            return;
        }

        const notificationId = Number(item.getAttribute("data-notification-id"));
        const referenceId = Number(item.getAttribute("data-reference-id"));

        if (notificationId > 0) {
            await markNotificationRead(notificationId);
        }

        if (referenceId > 0) {
            window.location.href = `post.php?id=${encodeURIComponent(referenceId)}`;
            return;
        }

        await loadNotifications();
    });

    if (markAllBtn) {
        markAllBtn.addEventListener("click", async () => {
            await markAllNotificationsRead();
        });
    }

    document.addEventListener("click", (event) => {
        if (!dropdown.hidden && !event.target.closest(".notifications-wrap")) {
            dropdown.hidden = true;
            btn.setAttribute("aria-expanded", "false");
        }
    });
}

// Επιστρέφει το κείμενο και την CSS κλάση που αντιστοιχεί στην κατάσταση ενός post, λαμβάνοντας υπόψη αν έχει διαγραφεί ή όχι
function getPostStatusInfo(status, deleted) {
    const numericStatus = Number(status);
    const isDeleted = Number(deleted) === 1;

    if (numericStatus === 0) {
        return { text: "Pending", className: "pending" };
    }

    if (numericStatus === 2) {
        return { text: "Rejected", className: "rejected" };
    }

    if (numericStatus === 1 && isDeleted) {
        return { text: "Deleted", className: "rejected" };
    }

    return { text: "Approved", className: "approved" };
}

function getRequestStatusInfo(status) {
    const numericStatus = Number(status);

    if (numericStatus === 0) {
        return { text: "Pending", className: "pending" };
    }

    if (numericStatus === 1) {
        return { text: "Approved", className: "approved" };
    }

    return { text: "Rejected", className: "rejected" };
}

function confirmUnfollowAction() {
    return new Promise((resolve) => {
        const dialog = document.createElement("div");
        dialog.className = "comment-policy-dialog";
        dialog.innerHTML = `
            <div class="comment-policy-card" role="dialog" aria-modal="true" aria-labelledby="unfollowConfirmTitle">
                <h4 id="unfollowConfirmTitle">Confirm Unfollow</h4>
                <p>Are you sure you want to unfollow this user?</p>
                <div class="comment-policy-actions">
                    <button type="button" class="policy-link cancel" data-unfollow-confirm="cancel">Cancel</button>
                    <button type="button" class="policy-link danger" data-unfollow-confirm="accept">Unfollow</button>
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

    banner.innerHTML = `
        <div class="interests-banner${isWarning ? " no-interests" : ""}">
            <div class="interests-label">${escapeHtml(label)}</div>
            <div class="interests-chips">
                <span class="pending-chip">${escapeHtml(text)}</span>
            </div>
        </div>`;
}

    function renderPendingPosts(posts) {
    const container = document.getElementById("postsList");
    if (!container) return;

    container.innerHTML = "";

    if (!Array.isArray(posts) || posts.length === 0) {
        container.innerHTML = '<div class="pending-state">No posts found.</div>';
        return;
    }

    posts.forEach((post) => {
        const card = document.createElement("article");
        card.className = "pending-card";

        const createdAt = post.timestamp ? new Date(post.timestamp).toLocaleString() : "Unknown date";
        const status = getPostStatusInfo(post.status, post.deleted);

        card.innerHTML = `
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
        `;

        container.appendChild(card);
    });
}

function renderPendingDeleteRequests(requests) {
    const container = document.getElementById("postsList");
    if (!container) return;

    container.innerHTML = "";

    if (!Array.isArray(requests) || requests.length === 0) {
        container.innerHTML = '<div class="pending-state">No delete requests found.</div>';
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
        container.innerHTML = '<div class="pending-state">No reports found.</div>';
        return;
    }

    reports.forEach((report) => {
        const card = document.createElement("article");
        card.className = "pending-card";

        const createdAt = report.created ? new Date(report.created).toLocaleString() : "Unknown date";
        const reportStatus = getRequestStatusInfo(report.status);
        const postDeleted = Number(report.deleted) === 1;
        const postStatus = postDeleted
            ? { text: "Removed", className: "rejected" }
            : { text: "Visible", className: "approved" };
        const title = report.post_title || (report.post_id ? `Post #${report.post_id}` : "Post unavailable");
        const link = report.post_id
            ? `<a class="feed-title-link" href="post.php?id=${encodeURIComponent(report.post_id)}">${escapeHtml(title)}</a>`
            : escapeHtml(title);

        card.innerHTML = `
            <h3>${link}</h3>
            <div class="pending-meta">
                <span class="status-chip ${reportStatus.className}">Report: ${escapeHtml(reportStatus.text)}</span>
                <span class="status-chip ${postStatus.className}">Post: ${escapeHtml(postStatus.text)}</span>
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
                    <div class="no-interests-text">You have not selected any interests yet.</div>
                    <div class="no-interests-sub">Showing all posts.</div>
                </div>`;
            return;
        }

        const chips = data
            .map((interest) => `<span class="pending-chip">${escapeHtml(interest.name)}</span>`)
            .join("");

        banner.innerHTML = `
            <div class="interests-banner">
                <div class="interests-label">Your interests</div>
                <div class="interests-chips">${chips}</div>
            </div>`;
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

        const followingChips = followingUsers
            .map((user) => {
                const userId = Number(user.user_id);
                return `<button type="button" class="follower-unfollow-trigger" data-unfollow-id="${userId}" title="Click to unfollow"><span class="follower-name">${escapeHtml(user.username)}</span><span class="follower-unfollow-hint">Unfollow</span></button>`;
            })
            .join("");

        const followersChips = followersUsers
            .map((user) => `<span class="follower-passive-chip"><span class="follower-name">${escapeHtml(user.username)}</span></span>`)
            .join("");

        banner.innerHTML = `
            <div class="followers-banners-grid">
                <div class="interests-banner${followingUsers.length === 0 ? " no-interests" : ""}">
                    <div class="interests-label">Following</div>
                    ${followingUsers.length === 0
                        ? `<div class="no-interests-text">You are not following anyone yet.</div>
                           <div class="no-interests-sub">Follow users to see their posts here.</div>`
                        : `<div class="interests-chips">${followingChips}</div>`}
                </div>
                <div class="interests-banner${followersUsers.length === 0 ? " no-interests" : ""}">
                    <div class="interests-label">Following you</div>
                    ${followersUsers.length === 0
                        ? `<div class="no-interests-text">No one is following you yet.</div>
                           <div class="no-interests-sub">Your followers will appear here.</div>`
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

async function loadDefaultFeed() {
    const container = document.getElementById("postsList");
    if (!container) return;

    container.innerHTML = '<div class="pending-state">Loading posts...</div>';

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

async function loadSearchResults() {
    const container = document.getElementById("postsList");
    const banner = document.getElementById("interestsBanner");
    const keywordInput = document.getElementById("feedSearchKeyword");
    const categoryInput = document.getElementById("feedSearchCategory");
    const sortInput = document.getElementById("feedSearchSort");
    const fromInput = document.getElementById("feedSearchFrom");
    const toInput = document.getElementById("feedSearchTo");
    const selectedAuthorIds = selectedFollowerFilters.filter((value) => value !== "__all__");
    const followedOnly = selectedFollowerFilters.length > 0;

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
    container.innerHTML = '<div class="pending-state">Searching posts...</div>';

    try {
        const { ok, data } = await fetchJSON(`${SEARCH_URL}?${params.toString()}`);

        if (!ok || !data.ok) {
            container.innerHTML = '<div class="pending-state">Could not load search results.</div>';
            return;
        }

        if (banner) {
            banner.innerHTML = `
                <div class="interests-banner">
                    <div class="interests-label">Search results</div>
                    <div class="interests-chips">
                        <span class="pending-chip">${escapeHtml(`${data.count ?? 0} posts found`)}</span>
                    </div>
                </div>`;
        }

        renderPosts(Array.isArray(data.data) ? data.data : []);
    } catch (error) {
        console.error("Error loading search results:", error);
        container.innerHTML = '<div class="pending-state">Failed to load search results.</div>';
    }
}

function updateFollowerFilterLabel() {
    const label = document.getElementById("feedSearchFollowersLabel");
    if (!label) return;

    if (selectedFollowerFilters.includes("__all__")) {
        label.textContent = "All followers";
        return;
    }

    if (selectedFollowerFilters.length === 0) {
        label.textContent = "Followers";
        return;
    }

    label.textContent = "Selected followers";
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

async function loadFollowersFeed() {
    const container = document.getElementById("postsList");
    if (!container) return;

    container.innerHTML = '<div class="pending-state">Loading followers posts...</div>';

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

async function loadPendingPostsFeed() {
    const container = document.getElementById("postsList");
    if (!container) return;

    container.innerHTML = '<div class="pending-state">Loading your posts...</div>';

    try {
        const { ok, data: posts } = await fetchJSON(`${BASE_URL}?action=myPosts`);

        if (!ok) {
            container.innerHTML = '<div class="pending-state">Could not load your posts.</div>';
            return;
        }

        renderSimpleBanner("Your pending posts", "Track moderation status: Pending, Approved, Rejected");
        renderPendingPosts(posts);
    } catch (error) {
        console.error("Error loading pending posts:", error);
        container.innerHTML = '<div class="pending-state">Failed to load your posts.</div>';
    }
}

async function loadPendingDeleteRequestsFeed() {
    const container = document.getElementById("postsList");
    if (!container) return;

    container.innerHTML = '<div class="pending-state">Loading delete requests...</div>';

    try {
        const { ok, data: requests } = await fetchJSON(`${BASE_URL}?action=myDeleteRequests`);

        if (!ok) {
            container.innerHTML = '<div class="pending-state">Could not load delete requests.</div>';
            return;
        }

        renderSimpleBanner("Your delete requests", "Track each request status and final post decision");
        renderPendingDeleteRequests(requests);
    } catch (error) {
        console.error("Error loading pending delete requests:", error);
        container.innerHTML = '<div class="pending-state">Failed to load delete requests.</div>';
    }
}

async function loadReportsFeed() {
    const container = document.getElementById("postsList");
    if (!container) return;

    container.innerHTML = '<div class="pending-state">Loading reports...</div>';

    try {
        const { ok, data: reports } = await fetchJSON(`${BASE_URL}?action=myReports`);

        if (!ok) {
            container.innerHTML = '<div class="pending-state">Could not load reports.</div>';
            return;
        }

        renderSimpleBanner("Your reports", "Track report status and moderation outcome");
        renderReports(reports);
    } catch (error) {
        console.error("Error loading reports:", error);
        container.innerHTML = '<div class="pending-state">Failed to load reports.</div>';
    }
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

    menu.querySelectorAll("[data-coming-soon]").forEach((button) => {
        button.addEventListener("click", () => {
            const feature = button.getAttribute("data-coming-soon") || "Feature";
            alert(`${feature} page is coming soon.`);
            menu.open = false;
        });
    });
}

function setupFeedModeToggle() {
    const title = document.getElementById("feedTitle");
    const postsButton = document.getElementById("postsFeedBtn");
    const followersButton = document.getElementById("followersFeedBtn");
    const pendingPostsButton = document.getElementById("pendingPostsBtn");
    const pendingDeleteRequestsButton = document.getElementById("pendingDeleteRequestsBtn");
    const reportsButton = document.getElementById("reportsBtn");
    const searchPanel = document.getElementById("feedSearchForm");

    if (!followersButton || !postsButton || !pendingPostsButton || !pendingDeleteRequestsButton || !reportsButton || !title) return;

    const setMode = (mode) => {
        activeFeedMode = mode;
        postsButton.classList.toggle("is-active", mode === "default" || mode === "search");
        followersButton.classList.toggle("is-active", mode === "followers");
        pendingPostsButton.classList.toggle("is-active", mode === "pending-posts");
        pendingDeleteRequestsButton.classList.toggle("is-active", mode === "pending-delete-requests");
        reportsButton.classList.toggle("is-active", mode === "reports");

        if (searchPanel) {
            const showSearchPanel = mode === "default" || mode === "search" || mode === "followers";
            searchPanel.hidden = !showSearchPanel;
            searchPanel.style.display = showSearchPanel ? "flex" : "none";
        }
    };

    postsButton.addEventListener("click", async () => {
        if (activeFeedMode === "default") {
            return;
        }

        setMode("default");
        title.textContent = "Posts Feed";

        await loadInterestsBanner();
        await loadDefaultFeed();
    });

    followersButton.addEventListener("click", async () => {
        if (activeFeedMode === "followers") {
            return;
        }

        setMode("followers");
        title.textContent = "Following";

        await loadFollowersBanner();
        await loadFollowersFeed();
    });

    pendingPostsButton.addEventListener("click", async () => {
        if (activeFeedMode === "pending-posts") {
            return;
        }

        setMode("pending-posts");
        title.textContent = "Pending Posts";
        await loadPendingPostsFeed();
    });

    pendingDeleteRequestsButton.addEventListener("click", async () => {
        if (activeFeedMode === "pending-delete-requests") {
            return;
        }

        setMode("pending-delete-requests");
        title.textContent = "Pending Delete Requests";
        await loadPendingDeleteRequestsFeed();
    });

    reportsButton.addEventListener("click", async () => {
        if (activeFeedMode === "reports") {
            return;
        }

        setMode("reports");
        title.textContent = "Reports";
        await loadReportsFeed();
    });
}

function setupSearchControls() {
    const form = document.getElementById("feedSearchForm");
    const clearButton = document.getElementById("feedSearchClear");
    const title = document.getElementById("feedTitle");
    const followersToggle = document.getElementById("feedSearchFollowersToggle");
    const followersMenu = document.getElementById("feedSearchFollowersMenu");
    const followersFilter = document.getElementById("feedSearchFollowersFilter");

    if (!form || !clearButton || !title) {
        return;
    }

    form.addEventListener("submit", async (event) => {
        event.preventDefault();
        previousSearchMode = activeFeedMode === "search" ? previousSearchMode : activeFeedMode;
        title.textContent = "Search Results";
        await loadSearchResults();
    });

    clearButton.addEventListener("click", async () => {
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

        updateFollowerFilterLabel();

        if (previousSearchMode === "followers") {
            title.textContent = "Following";
            await loadFollowersBanner();
            await loadFollowersFeed();
            return;
        }

        title.textContent = "Posts Feed";
        await loadInterestsBanner();
        await loadDefaultFeed();
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

document.addEventListener("DOMContentLoaded", () => {
    setupNotificationsUI();
    loadNotifications();
    setupFeedMenu();
    setupFeedModeToggle();
    setupSearchControls();
    setupFollowActions();
    setupFollowersBannerActions();

    refreshFollowingUsersState().finally(async () => {
        await loadFollowerFilterOptions();
        updateFollowerFilterLabel();
        loadInterestsBanner();
        loadDefaultFeed();
    });
});
