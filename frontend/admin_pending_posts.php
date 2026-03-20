<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: posts.php");
    exit();
}

$cssVersion = filemtime(__DIR__ . '/css/admin_pending_posts.css');
$jsVersion = filemtime(__DIR__ . '/js/admin_pending_posts.js');
?>

<!DOCTYPE html>
<html>

<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Pending Posts</title>
<link rel="stylesheet" href="css/admin_pending_posts.css?v=<?php echo $cssVersion; ?>">
</head>

<body>

<main class="pending-page">
    <header class="pending-page-header">
        <h1>Pending Posts</h1>
        <p>Review submitted posts and decide whether they should be published.</p>
    </header>

    <div id="pendingFeedback" class="pending-feedback" hidden></div>

    <!-- Εδώ θα εμφανιστούν τα pending posts(status=0) -->
    <section id="pendingPosts" class="pending-grid" aria-live="polite"></section>
</main>

<!-- js για φόρτωση των pending posts από controller & προσθηκη κουμπιων-->
<script src="js/admin_pending_posts.js?v=<?php echo $jsVersion; ?>"></script>

</body>

</html>