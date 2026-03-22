(function () {
    const BASE_URL = "http://localhost/University-Web-Applications-System-B/backend/controllers/PostController.php";
    const CATEGORY_URL = "http://localhost/University-Web-Applications-System-B/backend/controllers/CategoryController.php";
    const SEARCH_URL = "http://localhost/University-Web-Applications-System-B/backend/controllers/search_controllers.php";
    let selectedAdminAuthorFilters = ["__all__"];
    let publishedPostAuthors = [];
    let selectedPendingAuthorFilters = ["__all__"];
    let pendingPostAuthors = [];
    let activePendingStatus = 0;
    const SECTION_CONFIG = {
        posts: {
            load: loadPostsView
        },
        pending: {
            load: loadPendingPosts
        },
        deleteRequests: {
            load: loadDeleteRequests
        },
        commentDeleteRequests: {
            load: loadCommentDeleteRequests
        },
        categoryRequests: {
            load: loadCategoryRequests
        },
        reports: {
            load: loadReports
        }
    };
    document.addEventListener("DOMContentLoaded", function () {
        bindTabs();
        bindActions();
        setupAdminPostsSearch();
        setupPendingPostsSearch();

        const params = new URLSearchParams(window.location.search);
        const initialSection = params.get("section");
        activateSection(SECTION_CONFIG[initialSection] ? initialSection : "posts");
    });

    window.addEventListener("pageshow", function (event) {
        if (event.persisted) {
            reloadActiveSection();
        }
    });

    function bindTabs() {
        document.querySelectorAll(".dashboard-tab[data-section]").forEach((button) => {
            button.addEventListener("click", function () {
                activateSection(button.dataset.section || "posts");
            });
        });
    }

    function bindActions() {
        document.addEventListener("click", async function (event) {
            const button = event.target.closest("button[data-action][data-id]");
            if (!button) {
                return;
            }

            const action = button.dataset.action;
            const itemId = button.dataset.id;
            if (!action || !itemId) {
                return;
            }

            const section = button.dataset.section || "";
            const feedbackTarget = button.dataset.feedbackTarget || "";
            const reloadSection = button.dataset.reloadSection || section;

            try {
                const { response, data: result } = await fetchJSON(
                    `${BASE_URL}?action=${encodeURIComponent(action)}&id=${encodeURIComponent(itemId)}`
                );

                if (!response.ok) {
                    throw new Error(result.message || "Action failed");
                }

                showFeedback(feedbackTarget, result.message || "Action completed.", "success");
                if (SECTION_CONFIG[reloadSection]) {
                    await SECTION_CONFIG[reloadSection].load();
                }
            } catch (error) {
                console.error("Admin dashboard action error:", error);
                showFeedback(feedbackTarget, error.message || "Action failed.", "error");
            }
        });
    }

    function activateSection(section) {
        document.querySelectorAll(".dashboard-tab[data-section]").forEach((button) => {
            button.classList.toggle("is-active", button.dataset.section === section);
        });

        document.querySelectorAll("[data-section-panel]").forEach((panel) => {
            panel.classList.toggle("is-active", panel.dataset.sectionPanel === section);
        });

        const url = new URL(window.location.href);
        url.searchParams.set("section", section);
        window.history.replaceState({}, "", url);

        if (SECTION_CONFIG[section]) {
            SECTION_CONFIG[section].load();
        }
    }

    function getActiveSection() {
        const activeTab = document.querySelector(".dashboard-tab.is-active[data-section]");
        return activeTab ? activeTab.dataset.section || "posts" : "posts";
    }

    function reloadActiveSection() {
        const activeSection = getActiveSection();
        if (SECTION_CONFIG[activeSection]) {
            SECTION_CONFIG[activeSection].load();
        }
    }

    async function fetchJSON(url, options = {}) {
        const response = await fetch(url, {
            cache: "no-store",
            ...options
        });
        const data = await response.json();

        return { response, data };
    }

    function escapeHtml(value) {
        return String(value ?? "")
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/\"/g, "&quot;")
            .replace(/'/g, "&#39;");
    }

    function showFeedback(elementId, message, type) {
        const feedback = document.getElementById(elementId);
        if (!feedback) {
            return;
        }

        feedback.textContent = message;
        feedback.className = `pending-feedback ${type}`;
        feedback.hidden = false;

        window.clearTimeout(feedback.__timerId);
        feedback.__timerId = window.setTimeout(() => {
            feedback.hidden = true;
        }, 2800);
    }

    function setLoading(containerId, message) {
        const container = document.getElementById(containerId);
        if (!container) {
            return null;
        }

        container.innerHTML = `<div class="pending-state">${escapeHtml(message)}</div>`;
        return container;
    }

    function statusLabel(post) {
        if (Number(post.status) === 1) {
            return { text: "Published", className: "approved" };
        }

        if (Number(post.status) === 2) {
            return { text: "Rejected", className: "rejected" };
        }

        return { text: "Pending", className: "pending" };
    }

    function buildPostLink(postId, label, source) {
        return `<a class="pending-title-link" href="post.php?id=${encodeURIComponent(postId)}&admin_preview=1&admin_source=${encodeURIComponent(source)}">${label}</a>`;
    }

    function buildPublicPostLink(postId, label) {
        return `<a class="pending-title-link" href="post.php?id=${encodeURIComponent(postId)}&admin_source=dashboard_posts">${label}</a>`;
    }

    function renderPublishedPosts(container, posts) {
        container.innerHTML = "";

        if (!Array.isArray(posts) || posts.length === 0) {
            container.innerHTML = '<div class="pending-state">No published posts available.</div>';
            return;
        }

        posts.forEach((post) => {
            const card = document.createElement("article");
            const createdAt = post.timestamp ? new Date(post.timestamp).toLocaleString() : "Unknown date";
            const status = statusLabel(post);
            const excerpt = String(post.content || "").trim().slice(0, 220);
            const postId = post.post_id ?? post.id;

            card.className = "pending-card";
            card.innerHTML = `
                <h3>${buildPublicPostLink(postId, escapeHtml(post.title || "Untitled post"))}</h3>
                <div class="pending-meta">
                    <span class="pending-chip">${escapeHtml(post.category || "General")}</span>
                    <span class="status-chip ${status.className}">${escapeHtml(status.text)}</span>
                    <span>Author: ${escapeHtml(post.username || "Unknown")}</span>
                    <span>Submitted: ${escapeHtml(createdAt)}</span>
                </div>
                <div class="post-excerpt">${escapeHtml(excerpt || "No content preview available.")}</div>
            `;

            container.appendChild(card);
        });
    }

    function updateAdminUsersLabel() {
        const label = document.getElementById("adminSearchUsersLabel");
        if (!label) {
            return;
        }

        if (selectedAdminAuthorFilters.includes("__all__")) {
            label.textContent = "All users";
            return;
        }

        if (selectedAdminAuthorFilters.length === 0) {
            label.textContent = "Users";
            return;
        }

        label.textContent = "Selected users";
    }

    function renderAdminUserOptions() {
        const optionsContainer = document.getElementById("adminSearchUsersOptions");
        if (!optionsContainer) {
            return;
        }

        if (!publishedPostAuthors.length) {
            optionsContainer.innerHTML = `
                <label class="dashboard-search-users-option">
                    <input type="checkbox" disabled>
                    <span>No post authors found</span>
                </label>
            `;
            return;
        }

        optionsContainer.innerHTML = publishedPostAuthors.map((user) => `
            <label class="dashboard-search-users-option">
                <input type="checkbox" value="${Number(user.user_id)}">
                <span>${escapeHtml(user.username)}</span>
            </label>
        `).join("");
    }

    function populateAdminSearchCategories(posts) {
        const select = document.getElementById("adminSearchCategory");
        if (!select) {
            return;
        }

        const currentValue = select.value;
        const categoryMap = new Map();

        posts.forEach((post) => {
            const id = Number(post.category_id);
            const name = String(post.category || "").trim();
            if (id > 0 && name !== "" && !categoryMap.has(id)) {
                categoryMap.set(id, name);
            }
        });

        select.innerHTML = '<option value="">All categories</option>' +
            Array.from(categoryMap.entries())
                .sort((a, b) => a[1].localeCompare(b[1]))
                .map(([id, name]) => `<option value="${id}">${escapeHtml(name)}</option>`)
                .join("");

        if (currentValue && categoryMap.has(Number(currentValue))) {
            select.value = currentValue;
        }
    }

    function renderPendingModerationPosts(container, posts) {
        container.innerHTML = "";

        if (!Array.isArray(posts) || posts.length === 0) {
            container.innerHTML = '<div class="pending-state">No posts found for this status.</div>';
            return;
        }

        posts.forEach((post) => {
            const card = document.createElement("article");
            const createdAt = post.timestamp ? new Date(post.timestamp).toLocaleString() : "Unknown date";
            const status = statusLabel(post);
            const postId = post.post_id ?? post.id;
            const excerpt = String(post.content || "").trim().slice(0, 220);

            card.className = "pending-card";
            card.innerHTML = `
                <h3>${buildPostLink(postId, escapeHtml(post.title || "Untitled post"), "dashboard_pending")}</h3>
                <div class="pending-meta">
                    <span class="pending-chip">${escapeHtml(post.category || "General")}</span>
                    <span class="status-chip ${status.className}">${escapeHtml(status.text)}</span>
                    <span>Author: ${escapeHtml(post.username || "Unknown")}</span>
                    <span>Submitted: ${escapeHtml(createdAt)}</span>
                </div>
                <div class="post-excerpt">${escapeHtml(excerpt || "No content preview available.")}</div>
                ${Number(post.status) === 0 ? `
                <div class="pending-actions">
                    <button type="button" class="pending-btn approve" data-section="pending" data-feedback-target="pendingFeedback" data-reload-section="pending" data-action="approve" data-id="${escapeHtml(postId)}">Approve</button>
                    <button type="button" class="pending-btn reject" data-section="pending" data-feedback-target="pendingFeedback" data-reload-section="pending" data-action="reject" data-id="${escapeHtml(postId)}">Reject</button>
                </div>` : ""}
            `;

            container.appendChild(card);
        });
    }

    function updatePendingUsersLabel() {
        const label = document.getElementById("pendingSearchUsersLabel");
        if (!label) {
            return;
        }

        if (selectedPendingAuthorFilters.includes("__all__")) {
            label.textContent = "All users";
            return;
        }

        if (selectedPendingAuthorFilters.length === 0) {
            label.textContent = "Users";
            return;
        }

        label.textContent = "Selected users";
    }

    function renderPendingUserOptions() {
        const optionsContainer = document.getElementById("pendingSearchUsersOptions");
        if (!optionsContainer) {
            return;
        }

        if (!pendingPostAuthors.length) {
            optionsContainer.innerHTML = `
                <label class="dashboard-search-users-option">
                    <input type="checkbox" disabled>
                    <span>No post authors found</span>
                </label>
            `;
            return;
        }

        optionsContainer.innerHTML = pendingPostAuthors.map((user) => `
            <label class="dashboard-search-users-option">
                <input type="checkbox" value="${Number(user.user_id)}">
                <span>${escapeHtml(user.username)}</span>
            </label>
        `).join("");
    }

    function populatePendingSearchCategories(posts) {
        const select = document.getElementById("pendingSearchCategory");
        if (!select) {
            return;
        }

        const currentValue = select.value;
        const categoryMap = new Map();

        posts.forEach((post) => {
            const id = Number(post.category_id);
            const name = String(post.category || "").trim();
            if (id > 0 && name !== "" && !categoryMap.has(id)) {
                categoryMap.set(id, name);
            }
        });

        select.innerHTML = '<option value="">All categories</option>' +
            Array.from(categoryMap.entries())
                .sort((a, b) => a[1].localeCompare(b[1]))
                .map(([id, name]) => `<option value="${id}">${escapeHtml(name)}</option>`)
                .join("");

        if (currentValue && categoryMap.has(Number(currentValue))) {
            select.value = currentValue;
        }
    }

    function updatePendingStatusButtons() {
        if (activePendingStatus !== 0 && activePendingStatus !== 1) {
            activePendingStatus = 0;
        }

        document.querySelectorAll(".dashboard-status-filter[data-pending-status]").forEach((button) => {
            button.classList.toggle("is-active", Number(button.dataset.pendingStatus) === activePendingStatus);
        });
    }

    async function loadPostsView() {
        const container = setLoading("postsGrid", "Loading published posts...");
        if (!container) {
            return;
        }

        try {
            const { response, data: posts } = await fetchJSON(`${BASE_URL}?action=adminList`);

            if (!response.ok) {
                throw new Error(posts.message || "Could not load published posts");
            }

            publishedPostAuthors = Array.isArray(posts)
                ? Array.from(
                    new Map(
                        posts
                            .filter((post) => Number(post.user_id) > 0 && String(post.username || "").trim() !== "")
                            .map((post) => [Number(post.user_id), { user_id: Number(post.user_id), username: String(post.username).trim() }])
                    ).values()
                ).sort((a, b) => a.username.localeCompare(b.username))
                : [];

            populateAdminSearchCategories(Array.isArray(posts) ? posts : []);
            renderAdminUserOptions();
            updateAdminUsersLabel();
            renderPublishedPosts(container, posts);
        } catch (error) {
            console.error("Posts view error:", error);
            container.innerHTML = '<div class="pending-state">Failed to load published posts.</div>';
            showFeedback("postsFeedback", error.message || "Could not fetch published posts.", "error");
        }
    }

    async function loadPendingPosts() {
        const container = setLoading("pendingPosts", "Loading pending posts...");
        if (!container) {
            return;
        }

        try {
            const params = new URLSearchParams({
                sort: "newest",
                status: String(activePendingStatus)
            });
            const { response, data: result } = await fetchJSON(`${SEARCH_URL}?${params.toString()}`);

            if (!response.ok || !result.ok) {
                throw new Error(result.error || "Could not load pending posts");
            }

            const posts = Array.isArray(result.data) ? result.data : [];
            pendingPostAuthors = Array.from(
                new Map(
                    posts
                        .filter((post) => Number(post.user_id) > 0 && String(post.username || "").trim() !== "")
                        .map((post) => [Number(post.user_id), { user_id: Number(post.user_id), username: String(post.username).trim() }])
                ).values()
            ).sort((a, b) => a.username.localeCompare(b.username));

            populatePendingSearchCategories(posts);
            renderPendingUserOptions();
            updatePendingUsersLabel();
            updatePendingStatusButtons();
            renderPendingModerationPosts(container, posts);
        } catch (error) {
            console.error("Pending posts error:", error);
            container.innerHTML = '<div class="pending-state">Failed to load pending posts.</div>';
            showFeedback("pendingFeedback", error.message || "Could not fetch pending posts.", "error");
        }
    }

    async function loadDeleteRequests() {
        const container = setLoading("deleteRequests", "Loading delete requests...");
        if (!container) {
            return;
        }

        try {
            const { response, data: requests } = await fetchJSON(`${BASE_URL}?action=deleteRequests`);

            if (!response.ok) {
                throw new Error(requests.message || "Failed to load delete requests");
            }

            container.innerHTML = "";

            if (!Array.isArray(requests) || requests.length === 0) {
                container.innerHTML = '<div class="pending-state">No pending delete requests.</div>';
                return;
            }

            requests.forEach((req) => {
                const card = document.createElement("article");
                const createdAt = req.timestamp ? new Date(req.timestamp).toLocaleString() : "Unknown date";

                card.className = "pending-card";
                card.innerHTML = `
                    <h3>${buildPostLink(req.post_id, escapeHtml(req.title || "Untitled post"), "dashboard_delete_requests")}</h3>
                    <div class="pending-meta">
                        <span class="pending-chip">Delete request</span>
                        <span>Requested by: ${escapeHtml(req.username || "Unknown")}</span>
                        <span>Submitted: ${escapeHtml(createdAt)}</span>
                    </div>
                    <div class="pending-content"><strong>Reason:</strong> ${escapeHtml(req.reason || "-")}</div>
                    <div class="pending-actions">
                        <button type="button" class="pending-btn approve" data-section="deleteRequests" data-feedback-target="deleteRequestsFeedback" data-reload-section="deleteRequests" data-action="approveDelete" data-id="${escapeHtml(req.request_id)}">Approve Delete</button>
                        <button type="button" class="pending-btn reject" data-section="deleteRequests" data-feedback-target="deleteRequestsFeedback" data-reload-section="deleteRequests" data-action="rejectDelete" data-id="${escapeHtml(req.request_id)}">Reject</button>
                    </div>
                `;

                container.appendChild(card);
            });
        } catch (error) {
            console.error("Delete requests error:", error);
            container.innerHTML = '<div class="pending-state">Failed to load delete requests.</div>';
            showFeedback("deleteRequestsFeedback", error.message || "Could not fetch delete requests.", "error");
        }
    }

    async function loadCommentDeleteRequests() {
        const container = setLoading("commentDeleteRequests", "Loading comment delete requests...");
        if (!container) {
            return;
        }

        try {
            const { response, data: requests } = await fetchJSON(`${BASE_URL}?action=commentDeleteRequests`);

            if (!response.ok) {
                throw new Error(requests.message || "Failed to load comment delete requests");
            }

            container.innerHTML = "";

            if (!Array.isArray(requests) || requests.length === 0) {
                container.innerHTML = '<div class="pending-state">No pending comment delete requests.</div>';
                return;
            }

            requests.forEach((req) => {
                const card = document.createElement("article");
                const createdAt = req.created ? new Date(req.created).toLocaleString() : "Unknown date";
                const title = escapeHtml(`Comment ID: ${req.comment_id}`);
                const titleHtml = req.post_id
                    ? buildPostLink(req.post_id, title, "dashboard_comment_delete_requests")
                    : title;

                card.className = "pending-card";
                card.innerHTML = `
                    <h3>${titleHtml}</h3>
                    <div class="pending-meta">
                        <span class="pending-chip">Delete request</span>
                        <span>Requested by: ${escapeHtml(req.username || "Unknown")}</span>
                        <span>Submitted: ${escapeHtml(createdAt)}</span>
                    </div>
                    <div class="pending-content"><strong>Comment:</strong> ${escapeHtml(req.comment_content || "-")}</div>
                    <div class="pending-content"><strong>Reason:</strong> ${escapeHtml(req.reason || "-")}</div>
                    <div class="pending-actions">
                        <button type="button" class="pending-btn approve" data-section="commentDeleteRequests" data-feedback-target="commentDeleteFeedback" data-reload-section="commentDeleteRequests" data-action="approveCommentDelete" data-id="${escapeHtml(req.request_id)}">Approve Delete</button>
                        <button type="button" class="pending-btn reject" data-section="commentDeleteRequests" data-feedback-target="commentDeleteFeedback" data-reload-section="commentDeleteRequests" data-action="rejectCommentDelete" data-id="${escapeHtml(req.request_id)}">Reject</button>
                    </div>
                `;

                container.appendChild(card);
            });
        } catch (error) {
            console.error("Comment delete requests error:", error);
            container.innerHTML = '<div class="pending-state">Failed to load comment delete requests.</div>';
            showFeedback("commentDeleteFeedback", error.message || "Could not fetch comment delete requests.", "error");
        }
    }

    async function loadReports() {
        const container = setLoading("reports", "Loading reports...");
        if (!container) {
            return;
        }

        try {
            const { response, data: reports } = await fetchJSON(`${BASE_URL}?action=reports`);

            if (!response.ok) {
                throw new Error(reports.message || "Failed to load reports");
            }

            container.innerHTML = "";

            if (!Array.isArray(reports) || reports.length === 0) {
                container.innerHTML = '<div class="pending-state">No pending reports.</div>';
                return;
            }

            reports.forEach((report) => {
                const card = document.createElement("article");
                const createdAt = report.created ? new Date(report.created).toLocaleString() : "Unknown date";
                const title = report.post_title ? escapeHtml(report.post_title) : escapeHtml(`Post #${report.content_id}`);

                card.className = "pending-card";
                card.innerHTML = `
                    <h3>${buildPostLink(report.content_id, title, "dashboard_reports")}</h3>
                    <div class="pending-meta">
                        <span class="pending-chip">Post report</span>
                        <span>Reported by: ${escapeHtml(report.username || "Unknown")}</span>
                        <span>Submitted: ${escapeHtml(createdAt)}</span>
                    </div>
                    <div class="pending-content"><strong>Reason:</strong> ${escapeHtml(report.reason || "-")}</div>
                    <div class="pending-actions">
                        <button type="button" class="pending-btn approve" data-section="reports" data-feedback-target="reportsFeedback" data-reload-section="reports" data-action="approveReport" data-id="${escapeHtml(report.report_id)}">Remove Post</button>
                        <button type="button" class="pending-btn reject" data-section="reports" data-feedback-target="reportsFeedback" data-reload-section="reports" data-action="rejectReport" data-id="${escapeHtml(report.report_id)}">Reject Report</button>
                    </div>
                `;

                container.appendChild(card);
            });
        } catch (error) {
            console.error("Reports error:", error);
            container.innerHTML = '<div class="pending-state">Failed to load reports.</div>';
            showFeedback("reportsFeedback", error.message || "Could not fetch reports.", "error");
        }
    }

    async function loadCategoryRequests() {
        const container = setLoading("categoryRequests", "Loading category requests...");
        if (!container) {
            return;
        }

        try {
            const { response, data: requests } = await fetchJSON(`${CATEGORY_URL}?action=list`);

            if (!response.ok) {
                throw new Error(requests.message || "Failed to load category requests");
            }

            container.innerHTML = "";

            if (!Array.isArray(requests) || requests.length === 0) {
                container.innerHTML = '<div class="pending-state">No pending category requests.</div>';
                return;
            }

            requests.forEach((request) => {
                const card = document.createElement("article");
                const createdAt = request.created_at ? new Date(request.created_at).toLocaleString() : "Unknown date";

                card.className = "pending-card";
                card.innerHTML = `
                    <h3>${escapeHtml(request.suggested_name || "Unnamed category")}</h3>
                    <div class="pending-meta">
                        <span class="pending-chip">Category request</span>
                        <span>Requested by: ${escapeHtml(request.username || "Unknown")}</span>
                        <span>Submitted: ${escapeHtml(createdAt)}</span>
                    </div>
                    <div class="pending-actions">
                        <button type="button" class="pending-btn approve">Create Category</button>
                        <button type="button" class="pending-btn reject">Reject</button>
                    </div>
                `;

                const approveButton = card.querySelector(".pending-btn.approve");
                const rejectButton = card.querySelector(".pending-btn.reject");

                if (approveButton) {
                    approveButton.addEventListener("click", async () => {
                        await handleCategoryDecision("approve", request, approveButton, rejectButton);
                    });
                }

                if (rejectButton) {
                    rejectButton.addEventListener("click", async () => {
                        await handleCategoryDecision("reject", request, approveButton, rejectButton);
                    });
                }

                container.appendChild(card);
            });
        } catch (error) {
            console.error("Category requests error:", error);
            container.innerHTML = '<div class="pending-state">Failed to load category requests.</div>';
            showFeedback("categoryRequestsFeedback", error.message || "Could not fetch category requests.", "error");
        }
    }

    async function handleCategoryDecision(action, request, approveButton, rejectButton) {
        const requestId = Number(request?.request_id || 0);
        if (!requestId) {
            showFeedback("categoryRequestsFeedback", "Invalid request id.", "error");
            return;
        }

        const buttons = [approveButton, rejectButton].filter(Boolean);
        buttons.forEach((button) => {
            button.disabled = true;
        });

        try {
            const payload = { request_id: requestId };
            if (action === "approve") {
                payload.name = String(request?.suggested_name || "").trim();
            }

            const { response, data: result } = await fetchJSON(`${CATEGORY_URL}?action=${encodeURIComponent(action)}`, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify(payload)
            });

            if (!response.ok) {
                throw new Error(result.message || "Action failed");
            }

            showFeedback("categoryRequestsFeedback", result.message || "Action completed.", "success");
            await loadCategoryRequests();
        } catch (error) {
            console.error("Category request decision error:", error);
            showFeedback("categoryRequestsFeedback", error.message || "Action failed.", "error");
            buttons.forEach((button) => {
                button.disabled = false;
            });
        }
    }

    async function loadAdminSearchResults() {
        const container = setLoading("postsGrid", "Searching posts...");
        const keywordInput = document.getElementById("adminSearchKeyword");
        const categoryInput = document.getElementById("adminSearchCategory");
        const sortInput = document.getElementById("adminSearchSort");
        const fromInput = document.getElementById("adminSearchFrom");
        const toInput = document.getElementById("adminSearchTo");

        if (!container || !keywordInput || !categoryInput || !sortInput || !fromInput || !toInput) {
            return;
        }

        const params = new URLSearchParams({
            keyword: keywordInput.value.trim(),
            sort: sortInput.value || "newest",
            status: "1"
        });

        if (categoryInput.value) {
            params.set("category", categoryInput.value);
        }

        if (fromInput.value) {
            params.set("from", fromInput.value);
        }

        if (toInput.value) {
            params.set("to", toInput.value);
        }

        const selectedAuthorIds = selectedAdminAuthorFilters.filter((value) => value !== "__all__");
        if (selectedAuthorIds.length > 0) {
            params.set("author_ids", selectedAuthorIds.join(","));
        }

        try {
            const { response, data: result } = await fetchJSON(`${SEARCH_URL}?${params.toString()}`);
            if (!response.ok || !result.ok) {
                throw new Error(result.error || "Could not search posts");
            }

            renderPublishedPosts(container, Array.isArray(result.data) ? result.data : []);
        } catch (error) {
            console.error("Admin search error:", error);
            container.innerHTML = '<div class="pending-state">Failed to load search results.</div>';
            showFeedback("postsFeedback", error.message || "Could not search posts.", "error");
        }
    }

    function setupAdminPostsSearch() {
        const form = document.getElementById("adminPostsSearchForm");
        const clearButton = document.getElementById("adminSearchClear");
        const usersToggle = document.getElementById("adminSearchUsersToggle");
        const usersMenu = document.getElementById("adminSearchUsersMenu");
        const usersFilter = document.getElementById("adminSearchUsersFilter");

        if (!form || !clearButton) {
            return;
        }

        form.addEventListener("submit", async (event) => {
            event.preventDefault();
            await loadAdminSearchResults();
        });

        clearButton.addEventListener("click", async () => {
            form.reset();
            selectedAdminAuthorFilters = ["__all__"];

            const allOption = usersMenu?.querySelector('input[value="__all__"]');
            if (allOption) {
                allOption.checked = true;
            }

            usersMenu?.querySelectorAll('#adminSearchUsersOptions input[type="checkbox"]').forEach((input) => {
                input.checked = false;
            });

            if (usersMenu) {
                usersMenu.hidden = true;
            }

            if (usersToggle) {
                usersToggle.setAttribute("aria-expanded", "false");
            }

            updateAdminUsersLabel();
            await loadPostsView();
        });

        if (usersToggle && usersMenu && usersFilter) {
            usersToggle.addEventListener("click", () => {
                const shouldOpen = usersMenu.hidden;
                usersMenu.hidden = !shouldOpen;
                usersToggle.setAttribute("aria-expanded", shouldOpen ? "true" : "false");
            });

            usersMenu.addEventListener("change", (event) => {
                const input = event.target.closest('input[type="checkbox"]');
                if (!input) {
                    return;
                }

                const allOption = usersMenu.querySelector('input[value="__all__"]');
                const userOptions = usersMenu.querySelectorAll('#adminSearchUsersOptions input[type="checkbox"]');

                if (input.value === "__all__") {
                    if (input.checked) {
                        selectedAdminAuthorFilters = ["__all__"];
                        userOptions.forEach((option) => {
                            option.checked = false;
                        });
                    } else {
                        selectedAdminAuthorFilters = [];
                    }

                    updateAdminUsersLabel();
                    return;
                }

                if (allOption) {
                    allOption.checked = false;
                }

                selectedAdminAuthorFilters = Array.from(userOptions)
                    .filter((option) => option.checked)
                    .map((option) => option.value);

                if (selectedAdminAuthorFilters.length === 0 && allOption) {
                    allOption.checked = true;
                    selectedAdminAuthorFilters = ["__all__"];
                }

                updateAdminUsersLabel();
            });

            document.addEventListener("click", (event) => {
                if (!usersFilter.contains(event.target)) {
                    usersMenu.hidden = true;
                    usersToggle.setAttribute("aria-expanded", "false");
                }
            });
        }
    }

    async function loadPendingSearchResults() {
        const container = setLoading("pendingPosts", "Searching posts...");
        const keywordInput = document.getElementById("pendingSearchKeyword");
        const categoryInput = document.getElementById("pendingSearchCategory");
        const sortInput = document.getElementById("pendingSearchSort");
        const fromInput = document.getElementById("pendingSearchFrom");
        const toInput = document.getElementById("pendingSearchTo");

        if (!container || !keywordInput || !categoryInput || !sortInput || !fromInput || !toInput) {
            return;
        }

        const params = new URLSearchParams({
            keyword: keywordInput.value.trim(),
            sort: sortInput.value || "newest",
            status: String(activePendingStatus)
        });

        if (categoryInput.value) {
            params.set("category", categoryInput.value);
        }

        if (fromInput.value) {
            params.set("from", fromInput.value);
        }

        if (toInput.value) {
            params.set("to", toInput.value);
        }

        const selectedAuthorIds = selectedPendingAuthorFilters.filter((value) => value !== "__all__");
        if (selectedAuthorIds.length > 0) {
            params.set("author_ids", selectedAuthorIds.join(","));
        }

        try {
            const { response, data: result } = await fetchJSON(`${SEARCH_URL}?${params.toString()}`);
            if (!response.ok || !result.ok) {
                throw new Error(result.error || "Could not search posts");
            }

            renderPendingModerationPosts(container, Array.isArray(result.data) ? result.data : []);
        } catch (error) {
            console.error("Pending search error:", error);
            container.innerHTML = '<div class="pending-state">Failed to load search results.</div>';
            showFeedback("pendingFeedback", error.message || "Could not search pending posts.", "error");
        }
    }

    function setupPendingPostsSearch() {
        const form = document.getElementById("pendingPostsSearchForm");
        const clearButton = document.getElementById("pendingSearchClear");
        const usersToggle = document.getElementById("pendingSearchUsersToggle");
        const usersMenu = document.getElementById("pendingSearchUsersMenu");
        const usersFilter = document.getElementById("pendingSearchUsersFilter");

        if (!form || !clearButton) {
            return;
        }

        form.addEventListener("submit", async (event) => {
            event.preventDefault();
            await loadPendingSearchResults();
        });

        clearButton.addEventListener("click", async () => {
            form.reset();
            selectedPendingAuthorFilters = ["__all__"];

            const allOption = usersMenu?.querySelector('input[value="__all__"]');
            if (allOption) {
                allOption.checked = true;
            }

            usersMenu?.querySelectorAll('#pendingSearchUsersOptions input[type="checkbox"]').forEach((input) => {
                input.checked = false;
            });

            if (usersMenu) {
                usersMenu.hidden = true;
            }

            if (usersToggle) {
                usersToggle.setAttribute("aria-expanded", "false");
            }

            updatePendingUsersLabel();
            await loadPendingPosts();
        });

        if (usersToggle && usersMenu && usersFilter) {
            usersToggle.addEventListener("click", () => {
                const shouldOpen = usersMenu.hidden;
                usersMenu.hidden = !shouldOpen;
                usersToggle.setAttribute("aria-expanded", shouldOpen ? "true" : "false");
            });

            usersMenu.addEventListener("change", (event) => {
                const input = event.target.closest('input[type="checkbox"]');
                if (!input) {
                    return;
                }

                const allOption = usersMenu.querySelector('input[value="__all__"]');
                const userOptions = usersMenu.querySelectorAll('#pendingSearchUsersOptions input[type="checkbox"]');

                if (input.value === "__all__") {
                    if (input.checked) {
                        selectedPendingAuthorFilters = ["__all__"];
                        userOptions.forEach((option) => {
                            option.checked = false;
                        });
                    } else {
                        selectedPendingAuthorFilters = [];
                    }

                    updatePendingUsersLabel();
                    return;
                }

                if (allOption) {
                    allOption.checked = false;
                }

                selectedPendingAuthorFilters = Array.from(userOptions)
                    .filter((option) => option.checked)
                    .map((option) => option.value);

                if (selectedPendingAuthorFilters.length === 0 && allOption) {
                    allOption.checked = true;
                    selectedPendingAuthorFilters = ["__all__"];
                }

                updatePendingUsersLabel();
            });

            document.addEventListener("click", (event) => {
                if (!usersFilter.contains(event.target)) {
                    usersMenu.hidden = true;
                    usersToggle.setAttribute("aria-expanded", "false");
                }
            });
        }

        document.querySelectorAll(".dashboard-status-filter[data-pending-status]").forEach((button) => {
            button.addEventListener("click", async () => {
                activePendingStatus = Number(button.dataset.pendingStatus);
                updatePendingStatusButtons();
                await loadPendingPosts();
            });
        });
    }
})();
