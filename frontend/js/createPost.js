/**
 * File: createPost.js
 * Layer: Frontend Script
 * Module: Create Post
 * System: University Web Applications System B
 *
 * Description:
 * Frontend logic for the create-post page. Handles form submission,
 * attachment selection (up to 5 files), client-side validation and
 * feedback rendering after the post is submitted for review.
 *
 * Functions:
 * - renderSelectedFiles()
 * - showResponseMessage()
 * - togglePostPolicyDialog()
 *
 * Used By:
 * - frontend/create_post.php
 *
 * Author: Pelagia Koniotaki
 * Date: 2026
 */

const appConfig = window.APP_CONFIG || {};
const BACKEND_BASE = appConfig.backendBase || "/backend";
const FRONTEND_BASE = appConfig.frontendBase || "/frontend";

const responseEl = document.getElementById("response");
const postForm = document.getElementById("postForm");
const attachmentsInput = document.getElementById("attachmentsInput");
const selectedFilesEl = document.getElementById("selectedFiles");
const postPolicyDialog = document.getElementById("postPolicyDialog");
const MAX_ATTACHMENTS = 5;
let hideResponseTimer;
let selectedFiles = [];
let postPolicyAccepted = false;

function togglePostPolicyDialog(show) {
    if (!postPolicyDialog) {
        return;
    }

    postPolicyDialog.hidden = !show;
    document.body.classList.toggle("comment-dialog-open", show);
}

function showResponseMessage(message, type) {
    if (!responseEl) {
        return;
    }

    responseEl.textContent = message;
    responseEl.className = `response-message ${type}`;

    clearTimeout(hideResponseTimer);
    hideResponseTimer = setTimeout(() => {
        responseEl.textContent = "";
        responseEl.className = "response-message";
    }, 3500);
}

function renderSelectedFiles() {
    if (!selectedFilesEl) {
        return;
    }

    if (selectedFiles.length === 0) {
        selectedFilesEl.textContent = "";
        return;
    }

    selectedFilesEl.textContent = `Selected ${selectedFiles.length}/${MAX_ATTACHMENTS}: ${selectedFiles.map((f) => f.name).join(", ")}`;
}

if (postForm && attachmentsInput) {
    attachmentsInput.addEventListener("change", function() {
        const incomingFiles = Array.from(this.files);

        if (incomingFiles.length === 0) {
            return;
        }

        const merged = [...selectedFiles];

        incomingFiles.forEach((file) => {
            const exists = merged.some(
                (f) => f.name === file.name && f.size === file.size && f.lastModified === file.lastModified
            );

            if (!exists) {
                merged.push(file);
            }
        });

        if (merged.length > MAX_ATTACHMENTS) {
            showResponseMessage("Maximum 5 files allowed", "error");
            selectedFiles = merged.slice(0, MAX_ATTACHMENTS);
        } else {
            selectedFiles = merged;
        }

        this.value = "";
        renderSelectedFiles();
    });

    renderSelectedFiles();

    postForm.addEventListener("submit", async function(e){

        e.preventDefault();

        if (selectedFiles.length === 0) {
            showResponseMessage("No files selected. Please add at least one file before publishing.", "error");
            return;
        }

        if (!postPolicyAccepted) {
            togglePostPolicyDialog(true);
            return;
        }

        postPolicyAccepted = false;
        togglePostPolicyDialog(false);

        const formData = new FormData();
        formData.append("title", postForm.elements["title"].value);
        formData.append("content", postForm.elements["content"].value);
        formData.append("category_id", postForm.elements["category_id"].value);
        formData.append("is_anonymous", postForm.elements["is_anonymous"].checked ? "1" : "0");

        selectedFiles.forEach((file) => {
            formData.append("attachments[]", file);
        });

        try {

            const response = await fetch(
                `${BACKEND_BASE}/controllers/PostController.php?action=create`,
                {
                    method: "POST",
                    body: formData
                }
            );

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            const result = await response.json();

            showResponseMessage(result.message, "success");
            postForm.reset();
            selectedFiles = [];
            renderSelectedFiles();

            const pendingButton = document.getElementById("pendingPostsBtn");
            if (pendingButton) {
                window.setTimeout(() => {
                    pendingButton.click();
                }, 300);
                return;
            }

            const pendingUrl = `${FRONTEND_BASE}/posts.php?mode=pending&status=0`;
            window.location.replace(pendingUrl);

        } catch(error) {

            console.error(error);
            showResponseMessage("Error creating post", "error");

        }

    });
}

document.addEventListener("click", function(event) {
    if (!postPolicyDialog) {
        return;
    }

    if (event.target === postPolicyDialog) {
        postPolicyAccepted = false;
        togglePostPolicyDialog(false);
        return;
    }

    const acceptBtn = event.target.closest("#postPolicyAccept");
    if (acceptBtn) {
        postPolicyAccepted = true;
        togglePostPolicyDialog(false);

        if (typeof postForm.requestSubmit === "function") {
            postForm.requestSubmit();
        } else {
            postForm.dispatchEvent(new Event("submit", { cancelable: true }));
        }
        return;
    }

    const cancelBtn = event.target.closest("#postPolicyCancel");
    if (cancelBtn) {
        postPolicyAccepted = false;
        togglePostPolicyDialog(false);
    }
});

document.addEventListener("keydown", function(event) {
    if (event.key !== "Escape") {
        return;
    }

    if (!postPolicyDialog || postPolicyDialog.hidden) {
        return;
    }

    postPolicyAccepted = false;
    togglePostPolicyDialog(false);
});

/* custom category dropdown — max 2 visible, scroll για τα υπολοιπα */
(function initPostCategoryDropdown() {
    const dropdown = document.getElementById("categoryDropdown");
    if (!dropdown) return;

    const trigger = dropdown.querySelector(".post-category-trigger");
    const menu = dropdown.querySelector(".post-category-menu");
    const label = dropdown.querySelector(".post-category-label");
    const radios = Array.from(dropdown.querySelectorAll(".post-category-radio"));

    function setOpen(isOpen) {
        dropdown.classList.toggle("is-open", isOpen);
        trigger.setAttribute("aria-expanded", isOpen ? "true" : "false");
        if (menu) menu.hidden = !isOpen;
    }

    trigger.addEventListener("click", function() {
        setOpen(!dropdown.classList.contains("is-open"));
    });

    document.addEventListener("click", function(event) {
        if (!dropdown.contains(event.target)) {
            setOpen(false);
        }
    });

    radios.forEach(function(radio) {
        radio.addEventListener("change", function() {
            if (radio.checked) {
                const name = radio.parentElement.textContent.trim();
                label.textContent = name;
                label.classList.add("has-value");
                setOpen(false);
            }
        });
    });

    setOpen(false);
})();
