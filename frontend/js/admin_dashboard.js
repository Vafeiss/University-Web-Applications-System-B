(function () {
    const BASE_URL = "http://localhost/University-Web-Applications-System-B/backend/controllers/PostController.php";
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
        reports: {
            load: loadReports
        }
    };
    document.addEventListener("DOMContentLoaded", function () {
        bindTabs();
        bindActions();

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

                card.className = "pending-card";
                card.innerHTML = `
                    <h3>${buildPublicPostLink(post.post_id, escapeHtml(post.title || "Untitled post"))}</h3>
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
            const { response, data: posts } = await fetchJSON(`${BASE_URL}?action=pending`);

            if (!response.ok) {
                throw new Error(posts.message || "Could not load pending posts");
            }

            container.innerHTML = "";

            if (!Array.isArray(posts) || posts.length === 0) {
                container.innerHTML = '<div class="pending-state">No pending posts at the moment.</div>';
                return;
            }

            posts.forEach((post) => {
                const card = document.createElement("article");
                const createdAt = post.timestamp ? new Date(post.timestamp).toLocaleString() : "Unknown date";

                card.className = "pending-card";
                card.innerHTML = `
                    <h3>${buildPostLink(post.post_id, escapeHtml(post.title || "Untitled post"), "dashboard_pending")}</h3>
                    <div class="pending-meta">
                        <span class="pending-chip">${escapeHtml(post.category || "General")}</span>
                        <span>Author: ${escapeHtml(post.username || "Unknown")}</span>
                        <span>Submitted: ${escapeHtml(createdAt)}</span>
                    </div>
                    <div class="pending-actions">
                        <button type="button" class="pending-btn approve" data-section="pending" data-feedback-target="pendingFeedback" data-reload-section="pending" data-action="approve" data-id="${escapeHtml(post.post_id)}">Approve</button>
                        <button type="button" class="pending-btn reject" data-section="pending" data-feedback-target="pendingFeedback" data-reload-section="pending" data-action="reject" data-id="${escapeHtml(post.post_id)}">Reject</button>
                    </div>
                `;

                container.appendChild(card);
            });
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
})();