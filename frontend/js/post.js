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

    </div>
    `;  
}