// Φορτώνει τα posts όταν ανοίγει η σελίδα
document.addEventListener("DOMContentLoaded", loadPosts);
// Λειτουργία για φόρτωση των posts
async function loadPosts(){
    // Κάνουμε αίτημα στον backend για να πάρουμε τα εγκεκριμένα posts
    try{
        
        const response = await fetch(
        "http://localhost/University-Web-Applications-System-B/backend/controllers/PostController.php?action=list"
        );
        // Ελέγχουμε αν η απάντηση είναι επιτυχής
        const posts = await response.json();
        // Καθαρίζουμε το container για να εμφανίσουμε τα posts
        const container = document.getElementById("postsList");
        container.innerHTML = "";
        // Δημιουργούμε ένα card για κάθε post και το προσθέτουμε στο container
        posts.forEach(post => {

            const postCard = document.createElement("div");

            postCard.classList.add("post-card");
            // Προσθέτουμε το περιεχόμενο του post στο card
            postCard.innerHTML = `
                <a href="post.php?id=${post.post_id}" class="post-link">    
                    <h3 class="post-title">${post.title}</h3>   

                    <div class="post-meta">
                        <span class="category-badge">
                            ${post.category ?? "General"}
                        </span>
                            • ${post.username}  
                        </div>
                </a>
                `;
                // Toggle εμφάνιση περιεχομένου όταν πατηθεί το post
                postCard.addEventListener("click", () => {
                    
                const content = postCard.querySelector(".post-content");

                content.classList.toggle("hidden");

            });
                // Προσθέτουμε το περιεχόμενο του post στο card
                container.appendChild(postCard);

                });

            } catch(error){

            console.error("Error loading posts:", error);

        }

}