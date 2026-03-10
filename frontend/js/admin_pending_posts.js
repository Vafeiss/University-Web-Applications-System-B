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

            loadPendingPosts();
        } catch (error) {
            console.error(`${action} failed:`, error);
        }
    });
});

async function loadPendingPosts() {
    const container = document.getElementById("pendingPosts");
    if (!container) {
        return;
    }

    try {
        const response = await fetch(
            "http://localhost/University-Web-Applications-System-B/backend/controllers/PostController.php?action=pending"
        );
        const posts = await response.json();

        if (!response.ok) {
            throw new Error(posts.message || "Could not load pending posts");
        }

        container.innerHTML = "";

        posts.forEach((post) => {
            const card = document.createElement("div");
            card.innerHTML = `
                <h3>${post.title}</h3>
                <p>${post.content}</p>
                <small>Author: ${post.username}</small>
                <br>
                <button type="button" data-action="approve" data-id="${post.post_id}">Approve</button>
                <button type="button" data-action="reject" data-id="${post.post_id}">Reject</button>
                <hr>
            `;

            container.appendChild(card);
        });
    } catch (error) {
        console.error("Error loading pending posts:", error);
        container.innerHTML = "<p>Failed to load pending posts.</p>";
    }
}