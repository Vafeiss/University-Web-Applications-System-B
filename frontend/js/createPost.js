const responseEl = document.getElementById("response");
const postForm = document.getElementById("postForm");
const attachmentsInput = document.getElementById("attachmentsInput");
const selectedFilesEl = document.getElementById("selectedFiles");
const MAX_ATTACHMENTS = 5;
let hideResponseTimer;
let selectedFiles = [];

function showResponseMessage(message, type) {
    responseEl.textContent = message;
    responseEl.className = `response-message ${type}`;

    clearTimeout(hideResponseTimer);
    hideResponseTimer = setTimeout(() => {
        responseEl.textContent = "";
        responseEl.className = "response-message";
    }, 3500);
}

function renderSelectedFiles() {
    if (selectedFiles.length === 0) {
        selectedFilesEl.textContent = "No files selected";
        return;
    }

    selectedFilesEl.textContent = `Selected ${selectedFiles.length}/${MAX_ATTACHMENTS}: ${selectedFiles.map((f) => f.name).join(", ")}`;
}

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

    // Allow selecting files again in a new picker action.
    this.value = "";
    renderSelectedFiles();
});

renderSelectedFiles();

// Listen for form submission
postForm.addEventListener("submit", async function(e){

    // Prevent page reload
    e.preventDefault();

    // Build FormData manually to guarantee all selected files are appended.
    const formData = new FormData();
    formData.append("title", postForm.elements["title"].value);
    formData.append("content", postForm.elements["content"].value);
    formData.append("category_id", postForm.elements["category_id"].value);

    selectedFiles.forEach((file) => {
        formData.append("attachments[]", file);
    });

    try {

        const response = await fetch(
            "/University-Web-Applications-System-B/backend/controllers/PostController.php?action=create",
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

        // Reset form μετά τη δημιουργία
        this.reset();
        selectedFiles = [];
        attachmentsInput.value = "";
        renderSelectedFiles();

    } catch(error) {

        console.error(error);
        showResponseMessage("Error creating post", "error");

    }

});