document.addEventListener("DOMContentLoaded", function () {
    loadDeleteRequests();

    const container = document.getElementById("deleteRequests");
    if (!container) {
        return;
    }

    container.addEventListener("click", async function (event) {
        const button = event.target.closest("button[data-action][data-id]");
        if (!button) {
            return;
        }

        const requestId = button.dataset.id;
        const action = button.dataset.action;
        if (!requestId || (action !== "approveDelete" && action !== "rejectDelete")) {
            return;
        }

        try {
            const response = await fetch(
                `http://localhost/University-Web-Applications-System-B/backend/controllers/PostController.php?action=${action}&id=${requestId}`
            );
            const result = await response.json();

            if (!response.ok) {
                throw new Error(result.message || "Request failed");
            }

            showFeedback(result.message || "Action completed.", "success");
            loadDeleteRequests();
        } catch (error) {
            console.error("Delete request action error:", error);
            showFeedback(error.message || "Action failed.", "error");
        }
    });
});

let deleteRequestsFeedbackTimer;

function escapeHtml(value) {
    return String(value ?? "")
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/\"/g, "&quot;")
        .replace(/'/g, "&#39;");
}

function showFeedback(message, type) {
    const feedback = document.getElementById("deleteRequestsFeedback");
    if (!feedback) {
        return;
    }

    clearTimeout(deleteRequestsFeedbackTimer);

    feedback.textContent = message;
    feedback.className = `pending-feedback ${type}`;
    feedback.hidden = false;

    deleteRequestsFeedbackTimer = setTimeout(() => {
        feedback.hidden = true;
    }, 2800);
}

async function loadDeleteRequests() {
    const container = document.getElementById("deleteRequests");
    if (!container) {
        return;
    }

    container.innerHTML = '<div class="pending-state">Loading delete requests...</div>';

    try {
        const response = await fetch(
            "http://localhost/University-Web-Applications-System-B/backend/controllers/PostController.php?action=deleteRequests"
        );

        const requests = await response.json();
        if (!response.ok) {
            throw new Error(requests.message || "Failed to load delete requests");
        }

        container.innerHTML = "";

        if (!Array.isArray(requests) || requests.length === 0) {
            container.innerHTML = '<div class="pending-state">No pending delete requests.</div>';
            return;
        }

        requests.forEach((req) => {
            const card = document.createElement("div");
            const createdAt = req.timestamp ? new Date(req.timestamp).toLocaleString() : "Unknown date";

            card.className = "pending-card";
            card.innerHTML = `
                <h3><a class="pending-title-link" href="post.php?id=${req.post_id}&admin_preview=1">${escapeHtml(req.title)}</a></h3>
                <div class="pending-meta">
                    <span class="pending-chip">Delete request</span>
                    <span>Requested by: ${escapeHtml(req.username || "Unknown")}</span>
                    <span>Submitted: ${escapeHtml(createdAt)}</span>
                </div>
                <div class="pending-content"><strong>Reason:</strong> ${escapeHtml(req.reason || "-")}</div>
                <div class="pending-actions">
                    <button type="button" class="pending-btn approve" data-action="approveDelete" data-id="${req.request_id}">Approve Delete</button>
                    <button type="button" class="pending-btn reject" data-action="rejectDelete" data-id="${req.request_id}">Reject</button>
                </div>
            `;

            container.appendChild(card);
        });
    } catch (error) {
        console.error("Delete requests error:", error);
        container.innerHTML = '<div class="pending-state">Failed to load delete requests.</div>';
        showFeedback(error.message || "Could not fetch delete requests.", "error");
    }
}