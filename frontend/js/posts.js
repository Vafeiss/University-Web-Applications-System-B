const BASE_URL = "http://localhost/University-Web-Applications-System-B/backend/controllers/PostController.php";
const CAT_URL = "http://localhost/University-Web-Applications-System-B/backend/controllers/CategoryController.php";
const FOLLOW_URL = "http://localhost/University-Web-Applications-System-B/backend/controllers/FollowController.php";
let activeFeedMode = "default";
const followedUserIds = new Set();

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

    if (!followersButton || !postsButton || !pendingPostsButton || !pendingDeleteRequestsButton || !reportsButton || !title) return;

    const setMode = (mode) => {
        activeFeedMode = mode;
        postsButton.classList.toggle("is-active", mode === "default");
        followersButton.classList.toggle("is-active", mode === "followers");
        pendingPostsButton.classList.toggle("is-active", mode === "pending-posts");
        pendingDeleteRequestsButton.classList.toggle("is-active", mode === "pending-delete-requests");
        reportsButton.classList.toggle("is-active", mode === "reports");
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
    setupFeedMenu();
    setupFeedModeToggle();
    setupFollowActions();
    setupFollowersBannerActions();

    refreshFollowingUsersState().finally(() => {
        loadInterestsBanner();
        loadDefaultFeed();
    });
});
