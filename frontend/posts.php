<?php
session_start();

// Only logged-in users can see posts
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Posts Feed</title>

    <!-- Post styling -->
    <link rel="stylesheet" href="css/post.css">
</head>

<body>

<div class="post-container">

<h2>Posts Feed</h2>

<!-- Posts will appear here -->
<div id="postsList"></div>

</div>

<!-- Load posts script -->
<script src="js/posts.js"></script>

</body>
</html>