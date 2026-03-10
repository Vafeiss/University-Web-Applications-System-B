// Φορτώνει τα pending posts και χειρίζεται approve/reject actions.
document.addEventListener("DOMContentLoaded", function() {
    loadPendingPosts();

    const container = document.getElementById("pendingPosts");
    if (!container) {
        return;
    }

    container.addEventListener("click", async function(event) {
        const button = event.target.closest("button[data-action][data-id]");
        if (!button) {
            return;
        }

        const postId = button.dataset.id;
        const action = button.dataset.action;
        if (!postId || (action !== "approve" && action !== "reject")) {
            return;
        }

        try {
            const response = await fetch(
                `http://localhost/University-Web-Applications-System-B/backend/controllers/PostController.php?action=${action}&id=${postId}`
            );
            const result = await response.json();

            if (!response.ok) {
                throw new Error(result.message || "Request failed");
            }

            showFeedback(result.message || `Post ${action}d successfully.`, "success");

            loadPendingPosts();
        } catch (error) {
            console.error(`${action} failed:`, error);
            showFeedback(error.message || `Failed to ${action} post.`, "error");
        }
    });
});

let pendingFeedbackTimer;

function escapeHtml(value) {
    return String(value ?? "")
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/\"/g, "&quot;")
        .replace(/'/g, "&#39;");
}

function showFeedback(message, type) {
    const feedback = document.getElementById("pendingFeedback");
    if (!feedback) {
        return;
    }

    clearTimeout(pendingFeedbackTimer);

    feedback.textContent = message;
    feedback.className = `pending-feedback ${type}`;
    feedback.hidden = false;

    pendingFeedbackTimer = setTimeout(() => {
        feedback.hidden = true;
    }, 2800);
}

async function loadPendingPosts() {
    const container = document.getElementById("pendingPosts");
    if (!container) {
        return;
    }

    container.innerHTML = '<div class="pending-state">Loading pending posts...</div>';

    try {
        const response = await fetch(
            "http://localhost/University-Web-Applications-System-B/backend/controllers/PostController.php?action=pending"
        );
        const posts = await response.json();

        if (!response.ok) {
            throw new Error(posts.message || "Could not load pending posts");
        }

        container.innerHTML = "";

        if (!Array.isArray(posts) || posts.length === 0) {
            container.innerHTML = '<div class="pending-state">No pending posts at the moment.</div>';
            return;
        }

        posts.forEach((post) => {
            const card = document.createElement("div");
            const createdAt = post.timestamp ? new Date(post.timestamp).toLocaleString() : "Unknown date";

            card.className = "pending-card";
            card.innerHTML = `
                <h3><a class="pending-title-link" href="post.php?id=${post.post_id}&admin_preview=1">${escapeHtml(post.title)}</a></h3>
                <div class="pending-meta">
                    <span class="pending-chip">${escapeHtml(post.category || "General")}</span>
                    <span>Author: ${escapeHtml(post.username || "Unknown")}</span>
                    <span>Submitted: ${escapeHtml(createdAt)}</span>
                </div>
                <div class="pending-actions">
                    <button type="button" class="pending-btn approve" data-action="approve" data-id="${post.post_id}">Approve</button>
                    <button type="button" class="pending-btn reject" data-action="reject" data-id="${post.post_id}">Reject</button>
                </div>
            `;

            container.appendChild(card);
        });
    } catch (error) {
        console.error("Error loading pending posts:", error);
        container.innerHTML = '<div class="pending-state">Failed to load pending posts.</div>';
        showFeedback(error.message || "Could not fetch pending posts.", "error");
    }
}