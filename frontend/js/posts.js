const BASE_URL   = "http://localhost/University-Web-Applications-System-B/backend/controllers/PostController.php";
const CAT_URL    = "http://localhost/University-Web-Applications-System-B/backend/controllers/CategoryController.php";

// Λειτουργία για να πάρουμε το όνομα του συγγραφέα, λαμβάνοντας υπόψη την ανωνυμία
function getAuthorName(post) {
    // Αν το post είναι ανώνυμο και ο χρήστης δεν είναι admin, δείχνουμε απλά "Anonymous"
    if (post.is_anonymous == 1 && !isAdmin) {
        return "Anonymous";
    }
    // Αν ο χρήστης είναι admin, δείχνουμε το όνομα του συγγραφέα ακόμα και αν είναι ανώνυμος
    if (post.is_anonymous == 1 && isAdmin) {
        return `Anonymous (${escapeHtml(post.username)})`;
    }
    // Κανονική περίπτωση: δείχνουμε το όνομα του συγγραφέα
    return escapeHtml(post.username);
}
// Βοηθητική συνάρτηση για την αποφυγή XSS επιθέσεων με την κατάλληλη διαφυγή ειδικών χαρακτήρων
function escapeHtml(value) {
    return String(value ?? "")
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#39;");
}
// Βοηθητική συνάρτηση για την ασφαλή κωδικοποίηση τιμών σε URL παραμέτρους
// Fetch wrapper that returns parsed JSON
async function fetchJSON(url) {
    const res = await fetch(url, { cache: "no-store" });
    return { ok: res.ok, data: await res.json() };
}
// Ελέγχει αν ο χρήστης είναι admin 
// Φορτώνει τα interests του χρήστη και εμφανίζει το banner
async function loadInterests() {
    // Ελέγχουμε αν υπάρχει το banner πριν κάνουμε το αίτημα
    const banner = document.getElementById("interestsBanner");
    if (!banner) return;
    // Κάνουμε το αίτημα για τα interests του χρήστη
    try {
        const { ok, data } = await fetchJSON(`${CAT_URL}?action=userInterests`);
        // Αν δεν είναι επιτυχές ή δεν υπάρχουν interests, δείχνουμε το κατάλληλο μήνυμα
        if (!ok || !Array.isArray(data) || data.length === 0) {
            // Δεν έχει επιλέξει interests
            banner.innerHTML = `
                <div class="interests-banner no-interests">
                    <div class="no-interests-text">You have not selected any interests yet.</div>
                    <div class="no-interests-sub">Showing all posts.</div>
                </div>`;
            return;
        }
        // Αν υπάρχουν interests, δημιουργούμε τα chips για κάθε ενδιαφέρον και τα εμφανίζουμε στο banner
        const chips = data.map(c =>
            `<span class="pending-chip">${escapeHtml(c.name)}</span>`
        ).join("");

        banner.innerHTML = `
            <div class="interests-banner">
                <div class="interests-label">Your interests</div>
                <div class="interests-chips">${chips}</div>
            </div>`;

    } catch (err) {
        console.error("Could not load interests:", err);
    }
}

// Φορτώνει και εμφανίζει τα posts στο feed
async function loadPosts() {
    const container = document.getElementById("postsList");
    if (!container) return;
    // Εμφανίζουμε ένα μήνυμα φόρτωσης ενώ περιμένουμε την απάντηση από τον server
    container.innerHTML = '<div class="pending-state">Loading posts…</div>';
    // Κάνουμε το αίτημα για τα posts και τα εμφανίζουμε στο feed, λαμβάνοντας υπόψη την ανωνυμία και τα interests του χρήστη
    try {
        const { ok, data: posts } = await fetchJSON(`${BASE_URL}?action=list`);
        // Αν δεν είναι επιτυχές, δείχνουμε ένα μήνυμα σφάλματος
        if (!ok) {
            container.innerHTML = '<div class="pending-state">Could not load posts.</div>';
            return;
        }
        // Καθαρίζουμε το container και δημιουργούμε ένα card για κάθε post, λαμβάνοντας υπόψη την ανωνυμία και τα interests του χρήστη
        container.innerHTML = "";

        if (!Array.isArray(posts) || posts.length === 0) {
            container.innerHTML = '<div class="pending-state">No posts available yet.</div>';
            return;
        }
        // Για κάθε post, δημιουργούμε ένα card που δείχνει τον τίτλο, την κατηγορία, το όνομα του συγγραφέα (ή "Anonymous" αν είναι ανώνυμο και ο χρήστης δεν είναι admin), την ημερομηνία δημιουργίας και ένα απόσπασμα από το περιεχόμενο
        posts.forEach(post => {
            const card = document.createElement("article");
            card.className = "pending-card";

            const createdAt = post.timestamp
                ? new Date(post.timestamp).toLocaleString()
                : "Unknown date";

            const excerpt = String(post.content || "").trim().slice(0, 220);
            // Χρησιμοποιούμε την βοηθητική συνάρτηση getAuthorName για να πάρουμε το σωστό όνομα του συγγραφέα λαμβάνοντας υπόψη την ανωνυμία
            card.innerHTML = `
                <h3>
                    <a class="feed-title-link" href="post.php?id=${encodeURIComponent(post.post_id)}">
                        ${escapeHtml(post.title || "Untitled post")}
                    </a>
                </h3>
                <div class="pending-meta">
                    <span class="pending-chip">${escapeHtml(post.category || "General")}</span>
                    <span>${getAuthorName(post)}</span>
                    <span>${escapeHtml(createdAt)}</span>
                </div>
                ${excerpt ? `<div class="post-excerpt">${escapeHtml(excerpt)}</div>` : ""}
            `;

            container.appendChild(card);
        });
        // Αν ο χρήστης δεν έχει επιλέξει interests, δείχνουμε ένα μήνυμα στο banner που λέει ότι εμφανίζονται όλα τα posts
    } catch (err) {
        console.error("Error loading posts:", err);
        container.innerHTML = '<div class="pending-state">Failed to load posts.</div>';
    }
}

function setupFeedMenu() {
    const menu = document.getElementById("feedMenu");
    if (!menu) return;

    document.addEventListener("click", (event) => {
        if (!menu.open) return;
        if (!menu.contains(event.target)) {
            menu.open = false;
        }
    });

    menu.querySelectorAll("[data-coming-soon]").forEach((button) => {
        button.addEventListener("click", () => {
            const feature = button.getAttribute("data-coming-soon") || "Feature";
            alert(`${feature} page is coming soon.`);
            menu.open = false;
        });
    });
}

// Φορτώνει τα interests και τα posts παράλληλα
document.addEventListener("DOMContentLoaded", () => {
    setupFeedMenu();
    loadInterests();
    loadPosts();
});
