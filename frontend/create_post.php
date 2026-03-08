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

        <!-- input για attachments -->
        <form id="postForm" enctype="multipart/form-data">

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

        <!-- Attachments -->
        <div class="attachments-upload">
            <div class="attachments-head">
                <span class="attachments-title">Attachments</span>
                <span class="attachments-hint">Up to 5 files (jpg, png, pdf, doc, docx, txt, zip)</span>
            </div>

            <input 
            type="file"
            id="attachmentsInput"
            name="attachments[]"
            multiple
            accept=".jpg,.jpeg,.png,.pdf,.doc,.docx,.txt,.zip"
            >

            <small id="selectedFiles" class="selected-files"></small>
        </div>

        <button type="submit">
        Publish
        </button>
        </form>

        <p id="response" class="response-message" aria-live="polite"></p>

        </div>

        <script src="js/createPost.js"></script>

</body>
</html>