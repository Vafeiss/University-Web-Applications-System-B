/* Μετατροπή timestamp σε "time ago" */

function timeAgo(dateString){

    const date = new Date(dateString);
    const seconds = Math.floor((new Date() - date) / 1000);

    const intervals = [
        {label: "year", seconds: 31536000},
        {label: "month", seconds: 2592000},
        {label: "day", seconds: 86400},
        {label: "hour", seconds: 3600},
        {label: "minute", seconds: 60}
    ];

    for (let i of intervals) {

        const count = Math.floor(seconds / i.seconds);

        if (count >= 1) {
            return count + " " + i.label + (count > 1 ? "s" : "") + " ago";
        }
    }

    return "Just now";
}

function autoResizeCommentTextarea(textarea){
    if (!textarea) {
        return;
    }

    textarea.style.height = "auto";
    const maxHeight = 220;
    const nextHeight = Math.min(textarea.scrollHeight, maxHeight);
    textarea.style.height = `${nextHeight}px`;
    textarea.style.overflowY = textarea.scrollHeight > maxHeight ? "auto" : "hidden";
}

function updateCommentSubmitState(textarea){
    const submitBtn = document.querySelector("#commentForm .comment-send-btn");
    if (!textarea || !submitBtn) {
        return;
    }

    submitBtn.disabled = textarea.value.trim() === "";
}

function initCommentComposer(){
    const textarea = document.getElementById("commentContent");
    autoResizeCommentTextarea(textarea);
    updateCommentSubmitState(textarea);
}

let commentPolicyAccepted = false;

function toggleCommentPolicyNotice(show){
    const dialog = document.getElementById("commentPolicyDialog");
    if (!dialog) {
        return;
    }

    dialog.hidden = !show;
    document.body.classList.toggle("comment-dialog-open", show);
}


/* Φόρτωση ενός post από το backend */

async function loadPost(postId){

    const response = await fetch(
        "http://localhost/University-Web-Applications-System-B/backend/controllers/PostController.php?action=get&id=" + postId
    );

    const post = await response.json();

    const container = document.getElementById("post");

    container.innerHTML = `
    <div class="single-post">

        <h1 class="single-title">${post.title}</h1>

        <div class="single-meta">

            <span class="category-badge">
                ${post.category ?? "General"}
            </span>

            <span class="meta-separator">•</span>

            <span class="author">
                ${post.username}
            </span>

            <span class="meta-separator">•</span>

            <span class="time">
                ${timeAgo(post.timestamp)}
            </span>

        </div>
        
        <div class="single-content">
            ${post.content}
        </div>

        ${renderAttachments(post.attachments)} 
        ${renderCommentsSection(postId)} 
    </div>
    `;
    loadComments(postId); // Φόρτωση των σχολίων μετά την απόδοση του post
}
// Εμφάνιση των συνημμένων αρχείων
function renderAttachments(attachments){

        if(!attachments || attachments.length === 0){
            return "";
        }

        let html = `
        <section class="attachments-panel">
            <button type="button" class="attachments-toggle" id="attachmentsToggle" aria-expanded="false" aria-controls="attachmentsList">
                <span>View Attachments</span>
                <span class="attachments-count">${attachments.length}</span>
            </button>
            <div class="attachments-list" id="attachmentsList" hidden>
        `;

        attachments.forEach(file => {

            const fileUrl = file.file_url || file.file_path || "#";
            const fileName = file.file_name || "Attachment";
            const fileType = (file.file_type || "FILE").toUpperCase();
            const fileSize = file.file_size ? `${Math.max(1, Math.round(file.file_size / 1024))} KB` : "Unknown size";

            html += `
            <article class="attachment-item">
                <div class="attachment-meta">
                    <span class="attachment-name-wrap">
                    <span class="attachment-doc-badge">${fileType}</span>
                    <span class="attachment-name">${fileName}</span>
                    </span>
                    <span class="attachment-size">${fileSize}</span>
                </div>
                <a class="attachment-action" href="${fileUrl}" download>Download</a>
            </article>
            `;
        });

        html += `
            </div>
        </section>
        `;

        return html;
}
// Εμφάνιση φόρμας σχολιασμού και λίστας σχολίων
function renderCommentsSection(postId){

return `
<section class="comments-section">

    <h3>Comments</h3>

    <div id="commentsList"></div>

    <form id="commentForm" class="comment-form">
        <div class="comment-input-wrap">
            <textarea 
                id="commentContent"
                rows="1"
                placeholder="Write a comment..."
                required
            ></textarea>

            <button type="submit" class="comment-send-btn" aria-label="Post comment" title="Post comment">
                Post
            </button>
        </div>

        <div id="commentPolicyDialog" class="comment-policy-dialog" hidden>
            <div class="comment-policy-card" role="dialog" aria-modal="true" aria-labelledby="commentPolicyTitle">
                <h4 id="commentPolicyTitle">Confirm Publication</h4>
                <p>After publishing, this comment cannot be deleted directly and requires a delete request.</p>
                <div class="comment-policy-actions">
                    <button type="button" id="commentPolicyCancel" class="policy-link cancel">Cancel</button>
                    <button type="button" id="commentPolicyAccept" class="policy-link accept">Accept</button>
                </div>
            </div>
        </div>
    </form>

</section>
`;
}
// Φόρτωση των σχολίων για ένα post
async function loadComments(postId){

    const response = await fetch(
        "http://localhost/University-Web-Applications-System-B/backend/controllers/CommentController.php?action=list&post_id=" + postId
    );
    initCommentComposer();

    const comments = await response.json();

    const container = document.getElementById("commentsList");

    container.innerHTML = "";
    // Ταξινόμηση σχολίων από το πιο πρόσφατο προς το πιο παλιό
    comments.sort((a,b) => new Date(b.timestamp) - new Date(a.timestamp));
    comments.forEach(comment => {
        container.innerHTML += `
        <div class="comment">

            <div class="comment-text">
                ${comment.comment_content}
            </div>

            <div class="comment-header">
                <span class="comment-user">${comment.username}</span>
                <span class="comment-time">${timeAgo(comment.timestamp)}</span>
            </div>

        </div>
        `;

        });

}
// Toggle εμφάνισης συνημμένων αρχείων
document.addEventListener("click", function (event) {
    const dialog = document.getElementById("commentPolicyDialog");
    if (dialog && event.target === dialog) {
        commentPolicyAccepted = false;
        toggleCommentPolicyNotice(false);
        return;
    }

    const acceptBtn = event.target.closest("#commentPolicyAccept");
    if (acceptBtn) {
        commentPolicyAccepted = true;
        toggleCommentPolicyNotice(false);

        const form = document.getElementById("commentForm");
        if (form) {
            if (typeof form.requestSubmit === "function") {
                form.requestSubmit();
            } else {
                form.dispatchEvent(new Event("submit", { cancelable: true }));
            }
        }
        return;
    }

    const cancelBtn = event.target.closest("#commentPolicyCancel");
    if (cancelBtn) {
        commentPolicyAccepted = false;
        toggleCommentPolicyNotice(false);
        return;
    }

    const toggleButton = event.target.closest("#attachmentsToggle");
    if (!toggleButton) {
        return;
    }

    const list = document.getElementById("attachmentsList");
    if (!list) {
        return;
    }

    const shouldExpand = list.hidden;
    list.hidden = !shouldExpand;
    toggleButton.setAttribute("aria-expanded", String(shouldExpand));
});
// Αποστολή νέου σχολίου
document.addEventListener("submit", async function(e){

    if(e.target.id !== "commentForm"){
        return;
    }

    e.preventDefault();

    const contentEl = document.getElementById("commentContent");
    const content = contentEl.value.trim();

    if (content === "") {
        updateCommentSubmitState(contentEl);
        return;
    }

    if (!commentPolicyAccepted) {
        toggleCommentPolicyNotice(true);
        return;
    }

    commentPolicyAccepted = false;
    toggleCommentPolicyNotice(false);

    const postId = new URLSearchParams(window.location.search).get("id");
    const data = {
        post_id: postId,
        content: content
    };

    await fetch(
        "http://localhost/University-Web-Applications-System-B/backend/controllers/CommentController.php?action=create",
        {
            method:"POST",
            headers:{
                "Content-Type":"application/json"
            },
            
            body: JSON.stringify(data)
        }
    );

    contentEl.value = "";
    autoResizeCommentTextarea(contentEl);
    updateCommentSubmitState(contentEl);

    loadComments(postId);

});

document.addEventListener("input", function (event) {
    if (event.target.id !== "commentContent") {
        return;
    }

    autoResizeCommentTextarea(event.target);
    updateCommentSubmitState(event.target);
    commentPolicyAccepted = false;
    toggleCommentPolicyNotice(false);
});

document.addEventListener("keydown", function (event) {
    if (event.key !== "Escape") {
        return;
    }

    const dialog = document.getElementById("commentPolicyDialog");
    if (!dialog || dialog.hidden) {
        return;
    }

    commentPolicyAccepted = false;
    toggleCommentPolicyNotice(false);
});
