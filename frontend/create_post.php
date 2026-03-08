<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Create Post</title>
    <link rel="stylesheet" href="css/post.css">
</head>

<body>

<div class="post-container">

    <h2>Create New Post</h2>

    <form id="postForm">

        <input 
            type="text" 
            name="title" 
            placeholder="Post title"
            required
        >

        <textarea 
            name="content"
            placeholder="Write your content..."
            required
        ></textarea>
        
        <label>Category</label>
        <select name="category_id" required>

        <option value="">Select Category</option>
        <option value="1">Computer Science</option>
        <option value="2">Electrical Engineering</option>
        <option value="3">Business Administration</option>
        <option value="4">Mechanical Engineering</option>
        </select>

        <button type="submit">
            Publish
        </button>

    </form>

    <p id="response"></p>

</div>

<script src="js/createPost.js"></script>

</body>
</html>