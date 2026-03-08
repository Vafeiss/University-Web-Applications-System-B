<?php
session_start();

/* Έλεγχος αν ο χρήστης είναι συνδεδεμένος */
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

/* Έλεγχος αν υπάρχει id */
if (!isset($_GET['id'])) {
    echo "Post not found";
    exit();
}

$post_id = $_GET['id'];
?>

<!DOCTYPE html>
<html>

<head>

<title>View Post</title>
<link rel="stylesheet" href="css/post.css">

</head>

<body>

<div class="post-container">
<a href="posts.php" class="back-link">← Back to posts</a>

<!-- εδώ θα εμφανιστεί το post -->
<div id="post"></div>

</div>

<!-- Φόρτωση JS αρχείου -->
<script src="js/post.js"></script>

<script>
loadPost(<?php echo $post_id; ?>);
</script>

</body>

</html>