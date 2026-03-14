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

function autoResizeDeleteReasonTextarea(textarea){
    if (!textarea) {
        return;
    }

    textarea.style.height = "auto";
    const maxHeight = 180;
    const nextHeight = Math.min(textarea.scrollHeight, maxHeight);
    textarea.style.height = `${nextHeight}px`;
    textarea.style.overflowY = textarea.scrollHeight > maxHeight ? "auto" : "hidden";
}

function isAdminPreviewMode() {
    const params = new URLSearchParams(window.location.search);
    return params.get("admin_preview") === "1";
}

function getAdminSource() {
    const params = new URLSearchParams(window.location.search);
    return params.get("admin_source") || "";
}

function isCommentDeleteAdminSource() {
    const source = getAdminSource();
    return source === "comment_delete_requests" || source === "dashboard_comment_delete_requests";
}

function isDashboardPostsSource() {
    return window.isAdminSession === true && getAdminSource() === "dashboard_posts";
}

function showInlineNotice(message, type = "success"){
    const existing = document.getElementById("inlineNotice");
    if (existing) {
        existing.remove();
    }

    const notice = document.createElement("div");
    notice.id = "inlineNotice";
    notice.className = `inline-notice ${type}`;
    notice.textContent = message;
    document.body.appendChild(notice);

    requestAnimationFrame(() => {
        notice.classList.add("visible");
    });

    setTimeout(() => {
        notice.classList.remove("visible");
        setTimeout(() => notice.remove(), 220);
    }, 2500);
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
let deleteRequestCommentId = null;
let deleteRequestPostId = null;
let reportPostId = null;
let adminDeletePostId = null;
let adminDeleteCommentId = null;

function updateDialogBodyLock(){
    const commentDialog = document.getElementById("commentPolicyDialog");
    const deleteDialog = document.getElementById("deleteRequestDialog");
    const postDeleteDialog = document.getElementById("postDeleteRequestDialog");
    const postReportDialog = document.getElementById("postReportDialog");
    const adminPostDeleteDialog = document.getElementById("adminPostDeleteDialog");
    const adminCommentDeleteDialog = document.getElementById("adminCommentDeleteDialog");
    const hasOpenDialog = Boolean(
        (commentDialog && !commentDialog.hidden) ||
        (deleteDialog && !deleteDialog.hidden) ||
        (postDeleteDialog && !postDeleteDialog.hidden) ||
        (postReportDialog && !postReportDialog.hidden) ||
        (adminPostDeleteDialog && !adminPostDeleteDialog.hidden) ||
        (adminCommentDeleteDialog && !adminCommentDeleteDialog.hidden)
    );

    document.body.classList.toggle("comment-dialog-open", hasOpenDialog);
}

function toggleCommentPolicyNotice(show){
    const dialog = document.getElementById("commentPolicyDialog");
    if (!dialog) {
        return;
    }

    dialog.hidden = !show;
    updateDialogBodyLock();
}

function toggleDeleteRequestDialog(show){
    const dialog = document.getElementById("deleteRequestDialog");
    const reasonField = document.getElementById("deleteRequestReason");

    if (!dialog) {
        return;
    }

    dialog.hidden = !show;

    if (reasonField) {
        reasonField.value = "";
        autoResizeDeleteReasonTextarea(reasonField);
    }

    if (show && reasonField) {
        setTimeout(() => reasonField.focus(), 0);
    }

    if (!show) {
        deleteRequestCommentId = null;
    }

    updateDialogBodyLock();
}

function togglePostDeleteRequestDialog(show){
    const dialog = document.getElementById("postDeleteRequestDialog");
    const reasonField = document.getElementById("postDeleteRequestReason");

    if (!dialog) {
        return;
    }

    dialog.hidden = !show;

    if (reasonField) {
        reasonField.value = "";
        autoResizeDeleteReasonTextarea(reasonField);
    }

    if (show && reasonField) {
        setTimeout(() => reasonField.focus(), 0);
    }

    if (!show) {
        deleteRequestPostId = null;
    }

    updateDialogBodyLock();
}

function togglePostReportDialog(show){
    const dialog = document.getElementById("postReportDialog");
    const reasonField = document.getElementById("postReportReason");

    if (!dialog) {
        return;
    }

    dialog.hidden = !show;

    if (reasonField) {
        reasonField.value = "";
        autoResizeDeleteReasonTextarea(reasonField);
    }

    if (show && reasonField) {
        setTimeout(() => reasonField.focus(), 0);
    }

    if (!show) {
        reportPostId = null;
    }

    updateDialogBodyLock();
}

function toggleAdminPostDeleteDialog(show){
    const dialog = document.getElementById("adminPostDeleteDialog");

    if (!dialog) {
        return;
    }

    dialog.hidden = !show;

    if (!show) {
        adminDeletePostId = null;
        const confirmButton = document.getElementById("adminPostDeleteConfirm");
        if (confirmButton) {
            confirmButton.disabled = false;
        }
    }

    updateDialogBodyLock();
}

function toggleAdminCommentDeleteDialog(show){
    const dialog = document.getElementById("adminCommentDeleteDialog");

    if (!dialog) {
        return;
    }

    dialog.hidden = !show;

    if (!show) {
        adminDeleteCommentId = null;
        const confirmButton = document.getElementById("adminCommentDeleteConfirm");
        if (confirmButton) {
            confirmButton.disabled = false;
        }
    }

    updateDialogBodyLock();
}

function renderPostActionControls(post, adminPreviewMode, dashboardPostsMode) {
    if (adminPreviewMode) {
        return "";
    }

    if (dashboardPostsMode) {
        return `${renderAdminPostDeleteButton(post)}${renderAdminPostDeleteDialog()}`;
    }

    return `${renderPostDeleteButton(post)}${renderPostReportButton(post)}${renderPostDeleteDialog()}${renderPostReportDialog()}`;
}


/* Φόρτωση ενός post από το backend */

async function loadPost(postId){

    const response = await fetch(
        "http://localhost/University-Web-Applications-System-B/backend/controllers/PostController.php?action=get&id=" + postId
    );

    const post = await response.json();
    const adminPreviewMode = isAdminPreviewMode();
    const dashboardPostsMode = !adminPreviewMode && isDashboardPostsSource();
    const showComments = !adminPreviewMode || isCommentDeleteAdminSource();

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
        ${renderPostActionControls(post, adminPreviewMode, dashboardPostsMode)}
        ${renderAttachments(post.attachments)} 
        ${showComments ? renderCommentsSection(postId, { readOnly: adminPreviewMode, adminDeleteMode: dashboardPostsMode }) : ""} 
    </div>
    `;

    if (showComments) {
        loadComments(postId, { readOnly: adminPreviewMode, adminDeleteMode: dashboardPostsMode }); // Φόρτωση σχολίων μετά την απόδοση του post
    }
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
function renderCommentsSection(postId, options = {}){

const readOnly = options.readOnly === true;
const adminDeleteMode = options.adminDeleteMode === true;

return `
<section class="comments-section">

    <h3>Comments</h3>

    <div id="commentsList"></div>

    ${readOnly ? "" : `
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

    <div id="deleteRequestDialog" class="comment-policy-dialog" hidden>
        <form id="deleteRequestForm" class="comment-policy-card delete-request-form" role="dialog" aria-modal="true" aria-labelledby="deleteRequestTitle">
            <h4 id="deleteRequestTitle">Request Comment Deletion</h4>
            <p>Please explain why this comment should be removed.</p>
            <textarea id="deleteRequestReason" class="delete-request-reason" rows="1" placeholder="Write your reason..." required></textarea>
            <div class="comment-policy-actions">
                <button type="button" id="deleteRequestCancel" class="policy-link cancel">Cancel</button>
                <button type="submit" class="policy-link accept">Submit request</button>
            </div>
        </form>
    </div>
    ${adminDeleteMode ? `
    <div id="adminCommentDeleteDialog" class="comment-policy-dialog" hidden>
        <div class="comment-policy-card delete-request-form" role="dialog" aria-modal="true" aria-labelledby="adminCommentDeleteTitle">
            <h4 id="adminCommentDeleteTitle">Delete Comment</h4>
            <p>This will remove the comment immediately. Are you sure you want to continue?</p>
            <div class="comment-policy-actions">
                <button type="button" id="adminCommentDeleteCancel" class="policy-link cancel">Cancel</button>
                <button type="button" id="adminCommentDeleteConfirm" class="policy-link danger">Delete comment</button>
            </div>
        </div>
    </div>
    ` : ""}
    `}

</section>
`;
}
// Φόρτωση των σχολίων για ένα post
async function loadComments(postId, options = {}){

    const readOnly = options.readOnly === true;
    const adminDeleteMode = options.adminDeleteMode === true;

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
        const hasRequestedDelete = Number(comment.has_requested_delete) === 1 || comment.has_requested_delete === true;
        const deleteButton = readOnly
            ? ""
            : adminDeleteMode
            ? `
            <button class="admin-comment-delete-btn" data-id="${comment.comment_id}" aria-label="Delete comment" title="Delete comment">
                x
            </button>
            `
            : `
            <button class="delete-request-btn${hasRequestedDelete ? " is-disabled" : ""}" data-id="${comment.comment_id}" data-delete-requested="${hasRequestedDelete ? "1" : "0"}" aria-disabled="${hasRequestedDelete ? "true" : "false"}" aria-label="Request comment deletion" title="${hasRequestedDelete ? "Deletion request already submitted" : "Request deletion"}">
                x
            </button>
            `;

        container.innerHTML += `
        <div class="comment">

            ${deleteButton}

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
// Αν post ανήκει στον τρέχοντα χρήστη, εμφανίζει κουμπί διαγραφής
function renderPostDeleteButton(post){
    // Ελέγχουμε αν ο χρήστης είναι ο δημιουργός του post
    const currentUser = window.currentUserId;
    // Αν δεν είναι, δεν εμφανίζουμε το κουμπί διαγραφής
    if(post.user_id != currentUser){
        return "";
    }

    const hasRequestedDelete = Number(post.has_requested_delete) === 1 || post.has_requested_delete === true;

    return `
        <div class="post-delete-section">
            <button class="post-delete-request-btn${hasRequestedDelete ? " is-disabled" : ""}" data-id="${post.post_id}" data-delete-requested="${hasRequestedDelete ? "1" : "0"}" aria-disabled="${hasRequestedDelete ? "true" : "false"}" aria-label="Request post deletion" title="${hasRequestedDelete ? "Deletion request already submitted" : "Request post deletion"}">
                Request post deletion
            </button>
        </div>
    `;
}

function renderAdminPostDeleteButton(post){
    return `
        <div class="post-delete-section">
            <button class="admin-post-delete-btn" data-id="${post.post_id}" aria-label="Delete post" title="Delete post">
                Delete post
            </button>
        </div>
    `;
}

function renderPostReportButton(post){
    const currentUser = window.currentUserId;
    if(post.user_id == currentUser){
        return "";
    }

    const hasReported = Number(post.has_reported) === 1 || post.has_reported === true;

    return `
        <div class="post-report-section">
            <button class="post-report-btn${hasReported ? " is-disabled" : ""}" data-id="${post.post_id}" data-reported="${hasReported ? "1" : "0"}" aria-disabled="${hasReported ? "true" : "false"}" aria-label="Report post" title="${hasReported ? "Post already reported" : "Report post"}">
                Report post
            </button>
        </div>
    `;
}

function renderAdminPostDeleteDialog(){
    return `
    <div id="adminPostDeleteDialog" class="comment-policy-dialog" hidden>
        <div class="comment-policy-card delete-request-form" role="dialog" aria-modal="true" aria-labelledby="adminPostDeleteTitle">
            <h4 id="adminPostDeleteTitle">Delete Post</h4>
            <p>This will remove the post immediately from the published posts list. Are you sure you want to continue?</p>
            <div class="comment-policy-actions">
                <button type="button" id="adminPostDeleteCancel" class="policy-link cancel">Cancel</button>
                <button type="button" id="adminPostDeleteConfirm" class="policy-link danger">Delete post</button>
            </div>
        </div>
    </div>
    `;
}

function renderPostDeleteDialog(){
    return `
    <div id="postDeleteRequestDialog" class="comment-policy-dialog" hidden>
        <form id="postDeleteRequestForm" class="comment-policy-card delete-request-form" role="dialog" aria-modal="true" aria-labelledby="postDeleteRequestTitle">
            <h4 id="postDeleteRequestTitle">Request Post Deletion</h4>
            <p>Please explain why this post should be deleted.</p>
            <textarea id="postDeleteRequestReason" class="delete-request-reason" rows="1" placeholder="Write your reason..." required></textarea>
            <div class="comment-policy-actions">
                <button type="button" id="postDeleteRequestCancel" class="policy-link cancel">Cancel</button>
                <button type="submit" class="policy-link accept">Submit</button>
            </div>
        </form>
    </div>
    `;
}

function renderPostReportDialog(){
    return `
    <div id="postReportDialog" class="comment-policy-dialog" hidden>
        <form id="postReportForm" class="comment-policy-card delete-request-form" role="dialog" aria-modal="true" aria-labelledby="postReportTitle">
            <h4 id="postReportTitle">Report Post</h4>
            <p>Please explain why this post should be reported.</p>
            <textarea id="postReportReason" class="delete-request-reason" rows="1" placeholder="Write your reason..." required></textarea>
            <div class="comment-policy-actions">
                <button type="button" id="postReportCancel" class="policy-link cancel">Cancel</button>
                <button type="submit" class="policy-link accept">Submit</button>
            </div>
        </form>
    </div>
    `;
}


// Toggle εμφάνισης συνημμένων αρχείων
document.addEventListener("click", function (event) {
    const dialog = document.getElementById("commentPolicyDialog");
    if (dialog && event.target === dialog) {
        commentPolicyAccepted = false;
        toggleCommentPolicyNotice(false);
        return;
    }

    const deleteDialog = document.getElementById("deleteRequestDialog");
    if (deleteDialog && event.target === deleteDialog) {
        toggleDeleteRequestDialog(false);
        return;
    }

    const postDeleteDialog = document.getElementById("postDeleteRequestDialog");
    if (postDeleteDialog && event.target === postDeleteDialog) {
        togglePostDeleteRequestDialog(false);
        return;
    }

    const postReportDialog = document.getElementById("postReportDialog");
    if (postReportDialog && event.target === postReportDialog) {
        togglePostReportDialog(false);
        return;
    }

    const adminPostDeleteDialog = document.getElementById("adminPostDeleteDialog");
    if (adminPostDeleteDialog && event.target === adminPostDeleteDialog) {
        toggleAdminPostDeleteDialog(false);
        return;
    }

    const adminCommentDeleteDialog = document.getElementById("adminCommentDeleteDialog");
    if (adminCommentDeleteDialog && event.target === adminCommentDeleteDialog) {
        toggleAdminCommentDeleteDialog(false);
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

    const deleteCancelBtn = event.target.closest("#deleteRequestCancel");
    if (deleteCancelBtn) {
        toggleDeleteRequestDialog(false);
        return;
    }

    const postDeleteCancelBtn = event.target.closest("#postDeleteRequestCancel");
    if (postDeleteCancelBtn) {
        togglePostDeleteRequestDialog(false);
        return;
    }

    const postReportCancelBtn = event.target.closest("#postReportCancel");
    if (postReportCancelBtn) {
        togglePostReportDialog(false);
        return;
    }

    const adminPostDeleteCancelBtn = event.target.closest("#adminPostDeleteCancel");
    if (adminPostDeleteCancelBtn) {
        toggleAdminPostDeleteDialog(false);
        return;
    }

    const adminCommentDeleteCancelBtn = event.target.closest("#adminCommentDeleteCancel");
    if (adminCommentDeleteCancelBtn) {
        toggleAdminCommentDeleteDialog(false);
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
        if (event.target.id === "deleteRequestReason" || event.target.id === "postDeleteRequestReason" || event.target.id === "postReportReason") {
            autoResizeDeleteReasonTextarea(event.target);
        }
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

    const adminPostDeleteDialog = document.getElementById("adminPostDeleteDialog");
    if (adminPostDeleteDialog && !adminPostDeleteDialog.hidden) {
        toggleAdminPostDeleteDialog(false);
        return;
    }

    const adminCommentDeleteDialog = document.getElementById("adminCommentDeleteDialog");
    if (adminCommentDeleteDialog && !adminCommentDeleteDialog.hidden) {
        toggleAdminCommentDeleteDialog(false);
        return;
    }

    const postReportDialog = document.getElementById("postReportDialog");
    if (postReportDialog && !postReportDialog.hidden) {
        togglePostReportDialog(false);
        return;
    }

    const postDeleteDialog = document.getElementById("postDeleteRequestDialog");
    if (postDeleteDialog && !postDeleteDialog.hidden) {
        togglePostDeleteRequestDialog(false);
        return;
    }

    const deleteDialog = document.getElementById("deleteRequestDialog");
    if (deleteDialog && !deleteDialog.hidden) {
        toggleDeleteRequestDialog(false);
        return;
    }

    const dialog = document.getElementById("commentPolicyDialog");
    if (!dialog || dialog.hidden) {
        return;
    }

    commentPolicyAccepted = false;
    toggleCommentPolicyNotice(false);
});
// Comment deletion request
document.addEventListener("click", function (event) {
    const adminDeleteButton = event.target.closest(".admin-comment-delete-btn");
    if (adminDeleteButton) {
        adminDeleteCommentId = adminDeleteButton.dataset.id || null;
        if (!adminDeleteCommentId) {
            return;
        }

        toggleAdminCommentDeleteDialog(true);
        return;
    }

    // Εντοπισμός του κουμπιού διαγραφής που πατήθηκε
    const button = event.target.closest(".delete-request-btn");
    if (!button) {
        return;
    }

    if (button.dataset.deleteRequested === "1") {
        showInlineNotice("You have already submitted a delete request for this comment.", "error");
        return;
    }

    deleteRequestCommentId = button.dataset.id || null;
    if (!deleteRequestCommentId) {
        return;
    }

    toggleDeleteRequestDialog(true);
});

document.addEventListener("submit", async function (event) {
    if (event.target.id !== "deleteRequestForm") {
        return;
    }

    event.preventDefault();

    if (!deleteRequestCommentId) {
        toggleDeleteRequestDialog(false);
        return;
    }

    const submittedCommentId = deleteRequestCommentId;

    const reasonField = document.getElementById("deleteRequestReason");
    const reason = reasonField ? reasonField.value.trim() : "";
    if (reason === "") {
        if (reasonField) {
            reasonField.focus();
        }
        return;
    }

    // Αποστολή του αιτήματος διαγραφής στο backend
    try {

        const response = await fetch(
            "http://localhost/University-Web-Applications-System-B/backend/controllers/CommentController.php?action=requestDelete",
            {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({
                    comment_id: submittedCommentId,
                    reason: reason
                })
            }
        );
        const result = await response.json();

        // Έλεγχος της απόκρισης από το backend
        if (!response.ok) {
            throw new Error(result.message || "Request failed");
        }

        toggleDeleteRequestDialog(false);
        // Ενημέρωση χωρίς blocking browser alert
        showInlineNotice(result.message || "Your delete request was submitted for moderation.", "success");

        const activeButton = document.querySelector(`.delete-request-btn[data-id="${submittedCommentId}"]`);
        if (activeButton) {
            activeButton.dataset.deleteRequested = "1";
            activeButton.classList.add("is-disabled");
            activeButton.setAttribute("aria-disabled", "true");
            activeButton.setAttribute("title", "Deletion request already submitted");
        }
    } catch (error) {

        console.error("Delete request error:", error);
        showInlineNotice(error.message || "Failed to submit delete request.", "error");

    }

});

document.addEventListener("click", async function (event) {
    const button = event.target.closest("#adminCommentDeleteConfirm");
    if (!button) {
        return;
    }

    if (!adminDeleteCommentId) {
        toggleAdminCommentDeleteDialog(false);
        return;
    }

    button.disabled = true;

    try {
        const response = await fetch(
            "http://localhost/University-Web-Applications-System-B/backend/controllers/CommentController.php?action=adminDelete",
            {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                cache: "no-store",
                body: JSON.stringify({
                    comment_id: adminDeleteCommentId
                })
            }
        );

        const result = await response.json();
        if (!response.ok) {
            throw new Error(result.message || "Failed to delete comment.");
        }

        toggleAdminCommentDeleteDialog(false);
        showInlineNotice(result.message || "Comment deleted", "success");

        const postId = new URLSearchParams(window.location.search).get("id");
        if (postId) {
            loadComments(postId, { readOnly: false, adminDeleteMode: isDashboardPostsSource() });
        }
    } catch (error) {
        button.disabled = false;
        showInlineNotice(error.message || "Failed to delete comment.", "error");
    }
});
// Post deletion request
document.addEventListener("click", function(event){
    const button = event.target.closest(".admin-post-delete-btn");
    if (!button) {
        return;
    }

    adminDeletePostId = button.dataset.id || null;
    if (!adminDeletePostId) {
        return;
    }

    toggleAdminPostDeleteDialog(true);
});

document.addEventListener("click", async function(event){
    const button = event.target.closest("#adminPostDeleteConfirm");
    if (!button) {
        return;
    }

    if (!adminDeletePostId) {
        toggleAdminPostDeleteDialog(false);
        return;
    }

    button.disabled = true;

    try {
        const response = await fetch(
            "http://localhost/University-Web-Applications-System-B/backend/controllers/PostController.php?action=adminDelete",
            {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                cache: "no-store",
                body: JSON.stringify({
                    post_id: adminDeletePostId
                })
            }
        );

        const result = await response.json();
        if (!response.ok) {
            throw new Error(result.message || "Failed to delete post.");
        }

        toggleAdminPostDeleteDialog(false);
        showInlineNotice(result.message || "Post deleted", "success");

        setTimeout(() => {
            window.location.href = "admin_dashboard.php?section=posts";
        }, 650);
    } catch (error) {
        button.disabled = false;
        showInlineNotice(error.message || "Failed to delete post.", "error");
    }
});

document.addEventListener("click", function(event){
    // Εντοπισμός του κουμπιού διαγραφής που πατήθηκε
    const button = event.target.closest(".post-delete-request-btn");
    if(!button){
        return;
    }

    if (button.dataset.deleteRequested === "1") {
        showInlineNotice("You have already submitted a delete request for this post.", "error");
        return;
    }

    deleteRequestPostId = button.dataset.id || null;
    if(!deleteRequestPostId){
        return;
    }

    togglePostDeleteRequestDialog(true);
});

document.addEventListener("submit", async function(event){
    if (event.target.id !== "postDeleteRequestForm") {
        return;
    }

    event.preventDefault();

    if (!deleteRequestPostId) {
        togglePostDeleteRequestDialog(false);
        return;
    }

    const submittedPostDeleteId = deleteRequestPostId;

    const reasonField = document.getElementById("postDeleteRequestReason");
    const reason = reasonField ? reasonField.value.trim() : "";
    if (reason === "") {
        if (reasonField) {
            reasonField.focus();
        }
        return;
    }

    try {
        const response = await fetch(
            "http://localhost/University-Web-Applications-System-B/backend/controllers/PostController.php?action=requestDelete",
            {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({
                    post_id: submittedPostDeleteId,
                    reason: reason
                })
            }
        );

        const result = await response.json();
        if (!response.ok) {
            throw new Error(result.message || "Request failed");
        }

        togglePostDeleteRequestDialog(false);
        showInlineNotice(result.message || "Post delete request submitted", "success");

        const activeButton = document.querySelector(`.post-delete-request-btn[data-id="${submittedPostDeleteId}"]`);
        if (activeButton) {
            activeButton.dataset.deleteRequested = "1";
            activeButton.classList.add("is-disabled");
            activeButton.setAttribute("aria-disabled", "true");
            activeButton.setAttribute("title", "Deletion request already submitted");
        }
    } catch (error) {
        showInlineNotice(error.message || "Failed to submit post delete request.", "error");
    }
});

document.addEventListener("click", function(event){
    const button = event.target.closest(".post-report-btn");
    if (!button) {
        return;
    }

    if (button.dataset.reported === "1") {
        showInlineNotice("You have already reported this post.", "error");
        return;
    }

    reportPostId = button.dataset.id || null;
    if (!reportPostId) {
        return;
    }

    togglePostReportDialog(true);
});

document.addEventListener("submit", async function(event){
    if (event.target.id !== "postReportForm") {
        return;
    }

    event.preventDefault();

    if (!reportPostId) {
        togglePostReportDialog(false);
        return;
    }

    const submittedReportPostId = reportPostId;

    const reasonField = document.getElementById("postReportReason");
    const reason = reasonField ? reasonField.value.trim() : "";
    if (reason === "") {
        if (reasonField) {
            reasonField.focus();
        }
        return;
    }

    try {
        const response = await fetch(
            "http://localhost/University-Web-Applications-System-B/backend/controllers/PostController.php?action=requestReport",
            {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({
                    post_id: submittedReportPostId,
                    reason: reason
                })
            }
        );

        const result = await response.json();
        if (!response.ok) {
            throw new Error(result.message || "Request failed");
        }

        togglePostReportDialog(false);
        showInlineNotice(result.message || "Post report submitted", "success");

        const activeButton = document.querySelector(`.post-report-btn[data-id="${submittedReportPostId}"]`);
        if (activeButton) {
            activeButton.dataset.reported = "1";
            activeButton.classList.add("is-disabled");
            activeButton.setAttribute("aria-disabled", "true");
            activeButton.setAttribute("title", "Post already reported");
        }
    } catch (error) {
        showInlineNotice(error.message || "Failed to submit post report.", "error");
    }
});