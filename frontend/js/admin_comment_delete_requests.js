document.addEventListener("DOMContentLoaded", function () {
	loadCommentDeleteRequests();

	const container = document.getElementById("commentDeleteRequests");
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
		if (!requestId || (action !== "approveCommentDelete" && action !== "rejectCommentDelete")) {
			return;
		}

		try {
			const { response, data: result } = await fetchJsonNoStore(
				`http://localhost/University-Web-Applications-System-B/backend/controllers/PostController.php?action=${action}&id=${requestId}`
			);

			if (!response.ok) {
				throw new Error(result.message || "Request failed");
			}

			showFeedback(result.message || "Action completed.", "success");
			loadCommentDeleteRequests();
		} catch (error) {
			console.error("Comment delete request action error:", error);
			showFeedback(error.message || "Action failed.", "error");
		}
	});
});

window.addEventListener("pageshow", function (event) {
	if (event.persisted) {
		loadCommentDeleteRequests();
	}
});

let commentDeleteFeedbackTimer;

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
	const feedback = document.getElementById("commentDeleteFeedback");
	if (!feedback) {
		return;
	}

	clearTimeout(commentDeleteFeedbackTimer);

	feedback.textContent = message;
	feedback.className = `pending-feedback ${type}`;
	feedback.hidden = false;

	commentDeleteFeedbackTimer = setTimeout(() => {
		feedback.hidden = true;
	}, 2800);
}

async function loadCommentDeleteRequests() {
	const container = document.getElementById("commentDeleteRequests");
	if (!container) {
		return;
	}

	container.innerHTML = '<div class="pending-state">Loading comment delete requests...</div>';

	try {
		const { response, data: requests } = await fetchJsonNoStore(
			"http://localhost/University-Web-Applications-System-B/backend/controllers/PostController.php?action=commentDeleteRequests"
		);

		if (!response.ok) {
			throw new Error(requests.message || "Failed to load comment delete requests");
		}

		container.innerHTML = "";

		if (!Array.isArray(requests) || requests.length === 0) {
			container.innerHTML = '<div class="pending-state">No pending comment delete requests.</div>';
			return;
		}

		requests.forEach((req) => {
			const card = document.createElement("div");
			const createdAt = req.created ? new Date(req.created).toLocaleString() : "Unknown date";
			const hasPostId = req.post_id !== null && req.post_id !== undefined && req.post_id !== "";
			const titleHtml = hasPostId
				? `<a class="pending-title-link" href="post.php?id=${encodeURIComponent(req.post_id)}&admin_preview=1&admin_source=comment_delete_requests">Comment ID: ${escapeHtml(req.comment_id)}</a>`
				: `Comment ID: ${escapeHtml(req.comment_id)}`;

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
					<button type="button" class="pending-btn approve" data-action="approveCommentDelete" data-id="${req.request_id}">Approve Delete</button>
					<button type="button" class="pending-btn reject" data-action="rejectCommentDelete" data-id="${req.request_id}">Reject</button>
				</div>
			`;

			container.appendChild(card);
		});
	} catch (error) {
		console.error("Comment delete requests error:", error);
		container.innerHTML = '<div class="pending-state">Failed to load comment delete requests.</div>';
		showFeedback(error.message || "Could not fetch comment delete requests.", "error");
	}
}