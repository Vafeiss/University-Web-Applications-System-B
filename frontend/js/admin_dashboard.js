(function () {
    const BASE_URL = "http://localhost/University-Web-Applications-System-B/backend/controllers/PostController.php";
    const CATEGORY_URL = "http://localhost/University-Web-Applications-System-B/backend/controllers/CategoryController.php";
    const SEARCH_URL = "http://localhost/University-Web-Applications-System-B/backend/controllers/search_controllers.php";
    const NOTIFICATION_URL = "http://localhost/University-Web-Applications-System-B/backend/controllers/NotificationController.php";
    const ADMIN_SIDEBAR_STORAGE_KEY = "admin-feed-sidebar-collapsed";
    let selectedAdminAuthorFilters = ["__all__"];
    let publishedPostAuthors = [];
    let selectedPendingAuthorFilters = ["__all__"];
    let pendingPostAuthors = [];
    let activePendingStatus = 0;
    let adminSearchInputDebounceId = null;
    let pendingSearchInputDebounceId = null;
    let isAdminPostsSearchActive = false;
    let isPendingPostsSearchActive = false;
    let dashboardAutoRefreshTimerId = null;
    let isDashboardAutoRefreshInFlight = false;
    let rejectReasonDialogResolver = null;
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
        ensureRejectReasonDialog();
        setupAdminProfileDialog();
        setupInfoDialog();
        setupSidebarToggle();
        bindTabs();
        bindActions();
        setupSearchFilterPanels();
        setupAdminPostsSearch();
        setupPendingPostsSearch();
        setupNotificationsUI();

        const params = new URLSearchParams(window.location.search);
        const initialSection = params.get("section");
        activateSection(SECTION_CONFIG[initialSection] ? initialSection : "posts");
        loadNotifications();
        startDashboardAutoRefresh();
    });

    window.addEventListener("pageshow", function (event) {
        if (event.persisted) {
            reloadActiveSection();
        }
    });

    function bindTabs() {
        document.querySelectorAll("[data-section]").forEach((button) => {
            button.addEventListener("click", function () {
                activateSection(button.dataset.section || "posts");
            });
        });
    }

    function setupAdminProfileDialog() {
        const openButton = document.getElementById("adminProfileOpen");
        const topOpenButton = document.getElementById("adminProfileOpenTop");
        const closeButton = document.getElementById("adminProfileClose");
        const dialog = document.getElementById("adminProfileDialog");
        const menu = document.getElementById("adminMenu");

        if ((!openButton && !topOpenButton) || !closeButton || !dialog) {
            return;
        }

        const toggleDialog = (show) => {
            dialog.hidden = !show;
            document.body.classList.toggle("admin-dialog-open", show);
            if (!show && menu) {
                menu.open = false;
            }
        };

        [openButton, topOpenButton].filter(Boolean).forEach((button) => {
            button.addEventListener("click", function () {
                toggleDialog(true);
            });
        });

        closeButton.addEventListener("click", function () {
            toggleDialog(false);
        });

        dialog.addEventListener("click", function (event) {
            if (event.target === dialog) {
                toggleDialog(false);
            }
        });

        document.addEventListener("keydown", function (event) {
            if (event.key === "Escape" && !dialog.hidden) {
                toggleDialog(false);
            }
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

        toggleButton.addEventListener("click", function () {
            setOpen(dialog.hidden);
        });

        closeButton.addEventListener("click", function () {
            setOpen(false);
        });

        dialog.addEventListener("click", function (event) {
            if (event.target instanceof HTMLElement && event.target.hasAttribute("data-info-close")) {
                setOpen(false);
            }
        });

        document.addEventListener("keydown", function (event) {
            if (event.key === "Escape" && !dialog.hidden) {
                setOpen(false);
            }
        });
    }

    function setupSearchFilterPanels() {
        bindSearchFilterPanel("adminSearchFiltersToggle", "adminSearchAdvanced", "adminSearchUsersMenu", "adminSearchUsersToggle");
        bindSearchFilterPanel("pendingSearchFiltersToggle", "pendingSearchAdvanced", "pendingSearchUsersMenu", "pendingSearchUsersToggle");
    }

    function bindSearchFilterPanel(toggleId, panelId, usersMenuId, usersToggleId) {
        const toggle = document.getElementById(toggleId);
        const panel = document.getElementById(panelId);
        const usersMenu = document.getElementById(usersMenuId);
        const usersToggle = document.getElementById(usersToggleId);

        if (!toggle || !panel) {
            return;
        }

        toggle.addEventListener("click", function () {
            const willOpen = panel.hidden;
            panel.hidden = !willOpen;
            toggle.setAttribute("aria-expanded", willOpen ? "true" : "false");

            if (!willOpen && usersMenu && usersToggle) {
                usersMenu.hidden = true;
                usersToggle.setAttribute("aria-expanded", "false");
            }
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
            let url = `${BASE_URL}?action=${encodeURIComponent(action)}&id=${encodeURIComponent(itemId)}`;
            if (!action || !itemId) {
                return;
            }

            const section = button.dataset.section || "";
            const feedbackTarget = button.dataset.feedbackTarget || "";
            const reloadSection = button.dataset.reloadSection || section;

            if (action === "reject") {
                const reason = await promptRejectReason();
                if (!reason) {
                    return;
                }

                url += `&reason=${encodeURIComponent(reason)}`;
            }

            try {
                const { response, data: result } = await fetchJSON(url);

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

    function ensureRejectReasonDialog() {
        if (document.getElementById("adminRejectReasonDialog")) {
            return;
        }

        const wrapper = document.createElement("div");
        wrapper.innerHTML = `
            <div id="adminRejectReasonDialog" class="admin-reject-dialog" hidden>
                <form id="adminRejectReasonForm" class="admin-reject-card" role="dialog" aria-modal="true" aria-labelledby="adminRejectReasonTitle">
                    <h4 id="adminRejectReasonTitle">Reject Post</h4>
                    <p>Please explain why this post is being rejected.</p>
                    <textarea id="adminRejectReasonInput" class="admin-reject-textarea" rows="1" placeholder="Write your reason..." required></textarea>
                    <div id="adminRejectReasonError" class="admin-reject-error" hidden>Please enter a rejection reason.</div>
                    <div class="admin-reject-actions">
                        <button type="button" id="adminRejectReasonCancel" class="admin-reject-btn cancel">Cancel</button>
                        <button type="submit" class="admin-reject-btn danger">Submit rejection</button>
                    </div>
                </form>
            </div>
        `;

        document.body.appendChild(wrapper.firstElementChild);

        const dialog = document.getElementById("adminRejectReasonDialog");
        const form = document.getElementById("adminRejectReasonForm");
        const cancelButton = document.getElementById("adminRejectReasonCancel");
        const input = document.getElementById("adminRejectReasonInput");
        const error = document.getElementById("adminRejectReasonError");

        cancelButton?.addEventListener("click", function () {
            closeRejectReasonDialog(null);
        });

        dialog?.addEventListener("click", function (event) {
            if (event.target === dialog) {
                closeRejectReasonDialog(null);
            }
        });

        form?.addEventListener("submit", function (event) {
            event.preventDefault();

            const reason = String(input?.value || "").trim();
            if (reason === "") {
                if (error) {
                    error.hidden = false;
                }
                input?.focus();
                return;
            }

            if (error) {
                error.hidden = true;
            }

            closeRejectReasonDialog(reason);
        });

        input?.addEventListener("input", function () {
            autoResizeRejectReasonTextarea(input);
            if (error && String(input.value || "").trim() !== "") {
                error.hidden = true;
            }
        });

        document.addEventListener("keydown", function (event) {
            if (event.key === "Escape" && rejectReasonDialogResolver && dialog && !dialog.hidden) {
                closeRejectReasonDialog(null);
            }
        });
    }

    function promptRejectReason() {
        ensureRejectReasonDialog();

        const dialog = document.getElementById("adminRejectReasonDialog");
        const input = document.getElementById("adminRejectReasonInput");
        const error = document.getElementById("adminRejectReasonError");

        if (!dialog || !input) {
            return Promise.resolve(null);
        }

        input.value = "";
        input.style.height = "auto";
        if (error) {
            error.hidden = true;
        }

        dialog.hidden = false;
        document.body.classList.add("admin-dialog-open");

        window.setTimeout(() => {
            input.focus();
        }, 0);

        return new Promise((resolve) => {
            rejectReasonDialogResolver = resolve;
        });
    }

    function closeRejectReasonDialog(reason) {
        const dialog = document.getElementById("adminRejectReasonDialog");
        const input = document.getElementById("adminRejectReasonInput");
        const error = document.getElementById("adminRejectReasonError");

        if (dialog) {
            dialog.hidden = true;
        }

        document.body.classList.remove("admin-dialog-open");

        if (input) {
            input.value = "";
            input.style.height = "auto";
        }

        if (error) {
            error.hidden = true;
        }

        if (rejectReasonDialogResolver) {
            const resolve = rejectReasonDialogResolver;
            rejectReasonDialogResolver = null;
            resolve(reason);
        }
    }

    function autoResizeRejectReasonTextarea(textarea) {
        if (!textarea) {
            return;
        }

        textarea.style.height = "auto";
        const maxHeight = 180;
        const nextHeight = Math.min(textarea.scrollHeight, maxHeight);
        textarea.style.height = `${nextHeight}px`;
        textarea.style.overflowY = textarea.scrollHeight > maxHeight ? "auto" : "hidden";
    }

    function activateSection(section) {
        document.querySelectorAll("[data-section]").forEach((button) => {
            button.classList.toggle("is-active", button.dataset.section === section);
        });

        document.querySelectorAll("[data-section-panel]").forEach((panel) => {
            panel.classList.toggle("is-active", panel.dataset.sectionPanel === section);
        });

        const url = new URL(window.location.href);
        url.searchParams.set("section", section);
        window.history.replaceState({}, "", url);

        const title = document.getElementById("adminDashboardTitle");
        const sectionTitles = {
            posts: "Admin Posts",
            pending: "Pending Posts",
            deleteRequests: "Post Delete Requests",
            commentDeleteRequests: "Comment Delete Requests",
            categoryRequests: "Category Requests",
            reports: "Reports"
        };

        if (title) {
            title.textContent = sectionTitles[section] || "Admin Moderation Panel";
        }

        if (SECTION_CONFIG[section]) {
            SECTION_CONFIG[section].load();
        }
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
            toggle.setAttribute("aria-label", collapsed ? "Show side menu" : "Hide side menu");
            toggle.setAttribute("title", collapsed ? "Show side menu" : "Hide side menu");
            if (label) {
                label.textContent = collapsed ? "Show menu" : "Hide menu";
            }
        };

        applySidebarState(window.localStorage.getItem(ADMIN_SIDEBAR_STORAGE_KEY) === "1");

        toggle.addEventListener("click", () => {
            const collapsed = !layout.classList.contains("sidebar-collapsed");
            applySidebarState(collapsed);
            window.localStorage.setItem(ADMIN_SIDEBAR_STORAGE_KEY, collapsed ? "1" : "0");
        });
    }

    function getActiveSection() {
        const activeTab = document.querySelector(".feed-sidebar .feed-tab.is-active[data-section], .feed-dashboard-toplinks .feed-dashboard-toplink.is-active[data-section]");
        return activeTab ? activeTab.dataset.section || "posts" : "posts";
    }

    function reloadActiveSection(options = {}) {
        const activeSection = getActiveSection();
        if (activeSection === "posts" && isAdminPostsSearchActive) {
            return loadAdminSearchResults(options);
        }

        if (activeSection === "pending" && isPendingPostsSearchActive) {
            return loadPendingSearchResults(options);
        }

        if (SECTION_CONFIG[activeSection]) {
            return SECTION_CONFIG[activeSection].load(options);
        }

        return Promise.resolve();
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

    function renderNotifications(notifications) {
        const list = document.getElementById("adminNotificationsList");
        const count = document.getElementById("adminNotificationsCount");

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
            item.setAttribute("data-type", notification.type || "");

            const createdAt = notification.created_at
                ? new Date(notification.created_at).toLocaleString()
                : "";

            item.innerHTML = `
                <span class="notification-delete-btn" role="button" tabindex="0" aria-label="Delete notification" title="Delete">x</span>
                <div class="notification-text">${escapeHtml(notification.message || "")}</div>
                <div class="notification-time">${escapeHtml(createdAt)}</div>
            `;

            list.appendChild(item);
        });
    }

    async function loadNotifications() {
        try {
            const { response, data } = await fetchJSON(`${NOTIFICATION_URL}?action=list`);

            if (!response.ok) {
                return;
            }

            renderNotifications(data);
        } catch (error) {
            console.error("Could not load notifications:", error);
        }
    }

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

    async function deleteReadNotifications() {
        const { response } = await fetchJSON(`${NOTIFICATION_URL}?action=deleteRead`, {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            }
        });

        if (response.ok) {
            await loadNotifications();
        }
    }

    function getAdminNotificationTarget(type) {
        const sectionMap = {
            admin_pending_post: "pending",
            admin_post_delete_request: "deleteRequests",
            admin_comment_delete_request: "commentDeleteRequests",
            admin_category_request: "categoryRequests",
            admin_post_report: "reports"
        };

        return sectionMap[String(type || "")] || "";
    }

    function setupNotificationsUI() {
        const wrap = document.querySelector(".notifications-wrap");
        const btn = document.getElementById("adminNotificationsBtn");
        const dropdown = document.getElementById("adminNotificationsDropdown");
        const deleteReadBtn = document.getElementById("adminDeleteReadNotifications");

        if (!wrap || !btn || !dropdown) {
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

            const targetSection = getAdminNotificationTarget(notificationType);
            if (targetSection) {
                activateSection(targetSection);
                dropdown.hidden = true;
                btn.setAttribute("aria-expanded", "false");
                await loadNotifications();
                return;
            }

            if (referenceId > 0) {
                window.location.href = `post.php?id=${encodeURIComponent(referenceId)}&admin_preview=1`;
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
            if (!dropdown.hidden && !wrap.contains(event.target)) {
                dropdown.hidden = true;
                btn.setAttribute("aria-expanded", "false");
            }
        });
    }

    function startDashboardAutoRefresh() {
        if (dashboardAutoRefreshTimerId !== null) {
            window.clearInterval(dashboardAutoRefreshTimerId);
        }

        const refresh = async () => {
            if (document.hidden || isDashboardAutoRefreshInFlight) {
                return;
            }

            isDashboardAutoRefreshInFlight = true;
            try {
                await Promise.all([
                    loadNotifications(),
                    reloadActiveSection({ silent: true })
                ]);
            } finally {
                isDashboardAutoRefreshInFlight = false;
            }
        };

        dashboardAutoRefreshTimerId = window.setInterval(refresh, 5000);

        document.addEventListener("visibilitychange", () => {
            if (!document.hidden) {
                refresh();
            }
        });
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
            const rejectionReason = String(post.rejection_reason || "").trim();

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
                ${Number(post.status) === 2 ? `
                <div class="pending-content">rejection reason: ${escapeHtml(rejectionReason || "-")}</div>` : ""}
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
        if (activePendingStatus !== 0 && activePendingStatus !== 1 && activePendingStatus !== 2) {
            activePendingStatus = 0;
        }

        document.querySelectorAll(".dashboard-status-filter[data-pending-status]").forEach((button) => {
            button.classList.toggle("is-active", Number(button.dataset.pendingStatus) === activePendingStatus);
        });
    }

    async function loadPostsView(options = {}) {
        const silent = Boolean(options.silent);
        const container = silent
            ? document.getElementById("postsGrid")
            : setLoading("postsGrid", "Loading published posts...");
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

    async function loadPendingPosts(options = {}) {
        const silent = Boolean(options.silent);
        const container = silent
            ? document.getElementById("pendingPosts")
            : setLoading("pendingPosts", "Loading pending posts...");
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

    async function loadDeleteRequests(options = {}) {
        const silent = Boolean(options.silent);
        const container = silent
            ? document.getElementById("deleteRequests")
            : setLoading("deleteRequests", "Loading delete requests...");
        if (!container) {
            return;
        }

        try {
            const url = `${BASE_URL}?action=deleteRequests`;
            const { response, data: requests } = await fetchJSON(url);

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

    async function loadCommentDeleteRequests(options = {}) {
        const silent = Boolean(options.silent);
        const container = silent
            ? document.getElementById("commentDeleteRequests")
            : setLoading("commentDeleteRequests", "Loading comment delete requests...");
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

    async function loadReports(options = {}) {
        const silent = Boolean(options.silent);
        const container = silent
            ? document.getElementById("reports")
            : setLoading("reports", "Loading reports...");
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

    async function loadCategoryRequests(options = {}) {
        const silent = Boolean(options.silent);
        const container = silent
            ? document.getElementById("categoryRequests")
            : setLoading("categoryRequests", "Loading category requests...");
        if (!container) {
            return;
        }

        try {
            const { response, data: summary } = await fetchJSON(`${CATEGORY_URL}?action=summary`);

            if (!response.ok) {
                throw new Error(summary.message || "Failed to load category requests");
            }

            const requests = Array.isArray(summary.pending_requests)
                ? summary.pending_requests
                : [];

            const existingCategories = Array.isArray(summary.existing_categories)
                ? summary.existing_categories
                : [];

            container.innerHTML = "";

            const existingWrap = document.createElement("div");
            existingWrap.className = "pending-card";

            const existingTitle = document.createElement("h3");
            existingTitle.className = "dashboard-subtitle";
            existingTitle.textContent = "Existing Categories";
            existingWrap.appendChild(existingTitle);

            if (existingCategories.length === 0) {
                const emptyExisting = document.createElement("div");
                emptyExisting.className = "pending-state";
                emptyExisting.textContent = "No categories available.";
                existingWrap.appendChild(emptyExisting);
            } else {
                const chips = existingCategories
                    .map((category) => {
                        const id = Number(category.category_id || 0);
                        const name = escapeHtml(category.name || "");

                        return `<button type="button" class="admin-category-delete-trigger" data-category-id="${id}" data-category-name="${name}"><span class="admin-category-delete-name">${name}</span><span class="admin-category-delete-label">Delete</span></button>`;
                    })
                    .join(" ");

                existingWrap.insertAdjacentHTML("beforeend", `<div class="pending-content" style="display:flex;flex-wrap:wrap;gap:10px 8px;padding-top:4px;">${chips}</div>`);

                existingWrap.querySelectorAll(".admin-category-delete-trigger").forEach((button) => {
                    button.addEventListener("click", async () => {
                        const categoryId = Number(button.dataset.categoryId || 0);
                        const categoryName = String(button.dataset.categoryName || "category");

                        if (!categoryId) {
                            showFeedback("categoryRequestsFeedback", "Invalid category id.", "error");
                            return;
                        }

                        const confirmed = window.confirm(
                            `Delete category \"${categoryName}\" and all posts in this category? This cannot be undone.`
                        );

                        if (!confirmed) {
                            return;
                        }

                        button.disabled = true;

                        try {
                            const { response, data: result } = await fetchJSON(`${CATEGORY_URL}?action=deleteCategory`, {
                                method: "POST",
                                headers: {
                                    "Content-Type": "application/json"
                                },
                                body: JSON.stringify({ category_id: categoryId })
                            });

                            if (!response.ok) {
                                throw new Error(result.message || "Could not delete category");
                            }

                            showFeedback("categoryRequestsFeedback", result.message || "Category deleted.", "success");
                            await loadCategoryRequests();
                        } catch (error) {
                            console.error("Category delete error:", error);
                            showFeedback("categoryRequestsFeedback", error.message || "Could not delete category.", "error");
                            button.disabled = false;
                        }
                    });
                });

            }

            container.appendChild(existingWrap);

            const pendingTitle = document.createElement("h3");
            pendingTitle.className = "dashboard-subtitle";
            pendingTitle.style.marginTop = "14px";
            pendingTitle.textContent = "Pending Category Requests";
            container.appendChild(pendingTitle);

            if (requests.length === 0) {
                const emptyPending = document.createElement("div");
                emptyPending.className = "pending-state";
                emptyPending.textContent = "No pending category requests.";
                container.appendChild(emptyPending);
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

    async function loadAdminSearchResults(options = {}) {
        const silent = Boolean(options.silent);
        const container = silent
            ? document.getElementById("postsGrid")
            : setLoading("postsGrid", "Searching posts...");
        const keywordInput = document.getElementById("adminSearchKeyword");
        const categoryInput = document.getElementById("adminSearchCategory");
        const sortInput = document.getElementById("adminSearchSort");
        const fromInput = document.getElementById("adminSearchFrom");
        const toInput = document.getElementById("adminSearchTo");

        if (!container || !keywordInput || !categoryInput || !sortInput || !fromInput || !toInput) {
            return;
        }

        isAdminPostsSearchActive = Boolean(
            keywordInput.value.trim() ||
            categoryInput.value ||
            sortInput.value !== "newest" ||
            fromInput.value ||
            toInput.value ||
            selectedAdminAuthorFilters.some((value) => value !== "__all__")
        );

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
        const keywordInput = document.getElementById("adminSearchKeyword");
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

        if (keywordInput) {
            keywordInput.addEventListener("input", () => {
                clearTimeout(adminSearchInputDebounceId);
                adminSearchInputDebounceId = window.setTimeout(async () => {
                    await loadAdminSearchResults();
                }, 250);
            });
        }

        clearButton.addEventListener("click", async () => {
            clearTimeout(adminSearchInputDebounceId);
            form.reset();
            isAdminPostsSearchActive = false;
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

    async function loadPendingSearchResults(options = {}) {
        const silent = Boolean(options.silent);
        const container = silent
            ? document.getElementById("pendingPosts")
            : setLoading("pendingPosts", "Searching posts...");
        const keywordInput = document.getElementById("pendingSearchKeyword");
        const categoryInput = document.getElementById("pendingSearchCategory");
        const sortInput = document.getElementById("pendingSearchSort");
        const fromInput = document.getElementById("pendingSearchFrom");
        const toInput = document.getElementById("pendingSearchTo");

        if (!container || !keywordInput || !categoryInput || !sortInput || !fromInput || !toInput) {
            return;
        }

        isPendingPostsSearchActive = Boolean(
            keywordInput.value.trim() ||
            categoryInput.value ||
            sortInput.value !== "newest" ||
            fromInput.value ||
            toInput.value ||
            selectedPendingAuthorFilters.some((value) => value !== "__all__")
        );

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
        const keywordInput = document.getElementById("pendingSearchKeyword");
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

        if (keywordInput) {
            keywordInput.addEventListener("input", () => {
                clearTimeout(pendingSearchInputDebounceId);
                pendingSearchInputDebounceId = window.setTimeout(async () => {
                    await loadPendingSearchResults();
                }, 250);
            });
        }

        clearButton.addEventListener("click", async () => {
            clearTimeout(pendingSearchInputDebounceId);
            form.reset();
            isPendingPostsSearchActive = false;
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
