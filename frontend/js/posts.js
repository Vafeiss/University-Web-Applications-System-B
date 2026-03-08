// Run when page loads
document.addEventListener("DOMContentLoaded", loadPosts);

async function loadPosts(){

    try {

        // Request posts from backend
        const response = await fetch(
            "http://localhost/University-Web-Applications-System-B/backend/controllers/PostController.php?action=list"
        );

        const posts = await response.json();

        const container = document.getElementById("postsList");

        container.innerHTML = "";

        posts.forEach(post => {

            const postCard = document.createElement("div");

            postCard.classList.add("post-card");

            postCard.innerHTML = `
                <h3>${post.title}</h3>
                <p>${post.content}</p>
                <small>Posted by ${post.username}</small>
            `;

            container.appendChild(postCard);

        });

    } catch(error) {

        console.error("Error loading posts:", error);

    }

}