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
$css_version = filemtime(__DIR__ . '/css/post.css');
$js_version = filemtime(__DIR__ . '/js/post.js');
?>

<!DOCTYPE html>
<html>

<head>

<title>View Post</title>
<link rel="stylesheet" href="css/post.css?v=<?php echo $css_version; ?>">

</head>

<body>

<div class="post-container">
<a href="posts.php" class="back-link">← Back to posts</a>

<!-- εδώ θα εμφανιστεί το post -->
<div id="post"></div>

</div>

<!-- Φόρτωση JS αρχείου -->
<script src="js/post.js?v=<?php echo $js_version; ?>"></script>

<script>
window.currentUserId = <?php echo (int) $_SESSION['user_id']; ?>;
loadPost(<?php echo $post_id; ?>);
</script>
</body>

</html>