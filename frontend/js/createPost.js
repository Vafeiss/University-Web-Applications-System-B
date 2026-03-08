// Listen for form submission
document.getElementById("postForm").addEventListener("submit", async function(e){

    // Prevent page reload
    e.preventDefault();

    // Get form values
    const title = document.querySelector("input[name='title']").value;
    const content = document.querySelector("textarea[name='content']").value;
    const categoryValue = document.querySelector("select[name='category_id']").value;
    const category_id = categoryValue === "" ? null : Number(categoryValue);
    const data = {
        title: title,
        content: content,
        category_id: category_id
    };

    try {

        const response = await fetch(
            "/University-Web-Applications-System-B/backend/controllers/PostController.php?action=create",
            {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify(data)
            }
        );

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }

        const result = await response.json();

        document.getElementById("response").innerText = result.message;
        this.reset();

    } catch(error) {

        console.error(error);
        document.getElementById("response").innerText = "Error creating post";

    }

});