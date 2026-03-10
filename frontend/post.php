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
$is_admin_preview = isset($_GET['admin_preview']) && $_GET['admin_preview'] === '1';
$admin_source = $_GET['admin_source'] ?? '';

if ($is_admin_preview && $admin_source === 'reports') {
    $back_href = 'admin_reports.php';
    $back_label = 'Back to reported posts';
} elseif ($is_admin_preview) {
    $back_href = 'admin_pending_posts.php';
    $back_label = 'Back to pending posts';
} else {
    $back_href = 'posts.php';
    $back_label = 'Back to posts';
}
?>

<!DOCTYPE html>
<html>

<head>

<title>View Post</title>
<link rel="stylesheet" href="css/post.css?v=<?php echo $css_version; ?>">

</head>

<body>

<div class="post-container">
<a href="<?php echo $back_href; ?>" class="back-link">← <?php echo $back_label; ?></a>

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