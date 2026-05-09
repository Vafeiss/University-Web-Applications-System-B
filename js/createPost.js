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

const responseEl = document.getElementById("response");
const postForm = document.getElementById("postForm");
const attachmentsInput = document.getElementById("attachmentsInput");
const selectedFilesEl = document.getElementById("selectedFiles");
const postPolicyDialog = document.getElementById("postPolicyDialog");
const MAX_ATTACHMENTS = 5;
let hideResponseTimer;
let selectedFiles = [];
let postPolicyAccepted = false;
let isSubmittingPost = false;

function setPublishingState(isPublishing) {
    if (!postForm) {
        return;
    }
    const submitBtn = postForm.querySelector('button[type="submit"]');
    if (submitBtn) {
        submitBtn.disabled = isPublishing;
        if (isPublishing) {
            if (!submitBtn.dataset.originalText) {
                submitBtn.dataset.originalText = submitBtn.textContent.trim();
            }
            submitBtn.textContent = "Publishing...";
            submitBtn.classList.add("is-loading");
        } else {
            if (submitBtn.dataset.originalText) {
                submitBtn.textContent = submitBtn.dataset.originalText;
            }
            submitBtn.classList.remove("is-loading");
        }
    }
    const acceptBtn = document.getElementById("postPolicyAccept");
    if (acceptBtn) {
        acceptBtn.disabled = isPublishing;
    }
}

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

    postForm.addEventListener("submit", function(e){

        e.preventDefault();

        // Αποτροπή πολλαπλών ταυτόχρονων submissions
        if (isSubmittingPost) {
            return;
        }

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

        // Lock κατά τη διάρκεια αποστολής - αποτρέπει double click
        isSubmittingPost = true;
        setPublishingState(true);

        const formData = new FormData();
        formData.append("title", postForm.elements["title"].value);
        formData.append("content", postForm.elements["content"].value);
        formData.append("category_id", postForm.elements["category_id"].value);
        formData.append("is_anonymous", postForm.elements["is_anonymous"].checked ? "1" : "0");

        selectedFiles.forEach((file) => {
            formData.append("attachments[]", file);
        });

        // Optimistic flash - εμφανίζεται στο pending page αμέσως
        try {
            sessionStorage.setItem(
                "pendingPostFlash",
                JSON.stringify({
                    message: "Post submitted for review",
                    type: "success",
                    timestamp: Date.now()
                })
            );
        } catch (storageError) {
            // sessionStorage μπορεί να αποτύχει σε private mode
        }

        // Ξεκινάμε το fetch (ΧΩΡΙΣ await) ώστε να συνεχίσει στο background
        const uploadPromise = fetch(
            "/student/backend/controllers/PostController.php?action=create",
            {
                method: "POST",
                body: formData
            }
        );

        const pendingButton = document.getElementById("pendingPostsBtn");

        if (pendingButton) {
            // INLINE MODE (μέσα στο posts.php):
            // Άμεσο tab switch - δεν περιμένουμε τον server.
            // Το fetch συνεχίζει στο background και ολοκληρώνεται κανονικά.
            postForm.reset();
            selectedFiles = [];
            renderSelectedFiles();
            pendingButton.click();

            // Unlock μετά από λίγο ώστε να ξαναποστάρει ο user
            window.setTimeout(() => {
                isSubmittingPost = false;
                setPublishingState(false);
            }, 500);

            // Σιωπηλή διαχείριση background errors
            uploadPromise.catch((error) => {
                console.error("Background upload error:", error);
            });
        } else {
            // STANDALONE MODE (create_post.php):
            // Πρέπει να περιμένουμε γιατί το full page navigation
            // ακυρώνει το fetch.
            uploadPromise.then(async (response) => {
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }
                postForm.reset();
                selectedFiles = [];
                renderSelectedFiles();
                window.location.replace("/student/posts.php?mode=pending&status=0");
            }).catch((error) => {
                console.error("Upload error:", error);
                showResponseMessage("Error creating post", "error");
                isSubmittingPost = false;
                setPublishingState(false);
            });
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
        // Αποτροπή πολλαπλών click στο Accept κατά τη διάρκεια submission
        if (isSubmittingPost || acceptBtn.disabled) {
            return;
        }

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
