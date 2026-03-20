document.addEventListener("DOMContentLoaded", function () {
    loadReports();

    const container = document.getElementById("reports");
    if (!container) {
        return;
    }

    container.addEventListener("click", async function (event) {
        const button = event.target.closest("button[data-action][data-id]");
        if (!button) {
            return;
        }

        const reportId = button.dataset.id;
        const action = button.dataset.action;
        if (!reportId || (action !== "approveReport" && action !== "rejectReport")) {
            return;
        }

        try {
            const { response, data: result } = await fetchJsonNoStore(
                `http://localhost/University-Web-Applications-System-B/backend/controllers/PostController.php?action=${action}&id=${reportId}`
            );

            if (!response.ok) {
                throw new Error(result.message || "Request failed");
            }

            showFeedback(result.message || "Action completed.", "success");
            loadReports();
        } catch (error) {
            console.error("Reports action error:", error);
            showFeedback(error.message || "Action failed.", "error");
        }
    });
});

window.addEventListener("pageshow", function (event) {
    if (event.persisted) {
        loadReports();
    }
});

let feedbackTimer;

async function fetchJsonNoStore(url, options = {}) {
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

function showFeedback(message, type) {
    const feedback = document.getElementById("reportsFeedback");
    if (!feedback) {
        return;
    }

    clearTimeout(feedbackTimer);

    feedback.textContent = message;
    feedback.className = `pending-feedback ${type}`;
    feedback.hidden = false;

    feedbackTimer = setTimeout(() => {
        feedback.hidden = true;
    }, 2800);
}

function reportTypeLabel(type) {
    return "Post report";
}

function renderReportedContent(contentType, contentId, postTitle) {
    const label = postTitle ? escapeHtml(postTitle) : `Post #${contentId}`;
    return `<a class="pending-title-link" href="post.php?id=${contentId}&admin_preview=1&admin_source=reports">${label}</a>`;
}

async function loadReports() {
    const container = document.getElementById("reports");
    if (!container) {
        return;
    }

    container.innerHTML = '<div class="pending-state">Loading reports...</div>';

    try {
        const { response, data: reports } = await fetchJsonNoStore(
            "http://localhost/University-Web-Applications-System-B/backend/controllers/PostController.php?action=reports"
        );

        if (!response.ok) {
            throw new Error(reports.message || "Failed to load reports");
        }

        container.innerHTML = "";

        if (!Array.isArray(reports) || reports.length === 0) {
            container.innerHTML = '<div class="pending-state">No pending reports.</div>';
            return;
        }

        reports.forEach((report) => {
            const card = document.createElement("div");
            const createdAt = report.created ? new Date(report.created).toLocaleString() : "Unknown date";

            card.className = "pending-card";
            card.innerHTML = `
                <h3>${renderReportedContent(report.content_type, report.content_id, report.post_title)}</h3>
                <div class="pending-meta">
                    <span class="pending-chip">${escapeHtml(reportTypeLabel(report.content_type))}</span>
                    <span>Reported by: ${escapeHtml(report.username || "Unknown")}</span>
                    <span>Submitted: ${escapeHtml(createdAt)}</span>
                </div>
                <div class="pending-content"><strong>Reason:</strong> ${escapeHtml(report.reason || "-")}</div>
                <div class="pending-actions">
                    <button type="button" class="pending-btn approve" data-action="approveReport" data-id="${report.report_id}">Remove Post</button>
                    <button type="button" class="pending-btn reject" data-action="rejectReport" data-id="${report.report_id}">Reject Report</button>
                </div>
            `;

            container.appendChild(card);
        });
    } catch (error) {
        console.error("Reports error:", error);
        container.innerHTML = '<div class="pending-state">Failed to load reports.</div>';
        showFeedback(error.message || "Could not fetch reports.", "error");
    }
}