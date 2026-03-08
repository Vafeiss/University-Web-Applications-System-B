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

        <input 
            type="number"
            name="category_id"
            placeholder="Category ID"
        >

        <button type="submit">
            Publish
        </button>

    </form>

    <p id="response"></p>

</div>

<script src="js/createPost.js"></script>

</body>
</html>